<?php

namespace Alpha\Controller;

use Alpha\Util\InputFilter;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Logging\KPI;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Extension\TCPDFFacade;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Util\File\FileUtils;
use Alpha\Model\Article;
use Alpha\Model\ArticleVote;
use Alpha\Model\ArticleComment;
use Alpha\View\View;
use Alpha\View\ViewState;
use Alpha\View\Widget\Button;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\LockingException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\FileNotFoundException;
use Alpha\Model\ActiveRecord;
use Alpha\Controller\Front\FrontController;

/**
 * Controller used handle Article objects.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 */
class ArticleController extends ActiveRecordController implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * constructor to set up the object.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ArticleController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Request
     *
     * @return Alpha\Util\Http\Response
     *
     * @throws Alpha\Exception\ResourceNotFoundException
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        // handle requests for PDFs
        if (isset($params['title']) && (isset($params['pdf']) || $request->getHeader('Accept') == 'application/pdf')) {
            try {
                $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                $record = new Article();
                $record->loadByAttribute('title', $title);
                $this->record = $record;

                ActiveRecord::disconnect();

                $pdf = new TCPDFFacade($record);
                $pdfData = $pdf->getPDFData();
                $pdfDownloadName = str_replace(' ', '-', $record->get('title').'.pdf');

                $headers = array(
                    'Pragma' => 'public',
                    'Expires' => 0,
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Content-Transfer-Encoding' => 'binary',
                    'Content-Type' => 'application/pdf',
                    'Content-Length' => strlen($pdfData),
                    'Content-Disposition' => 'attachment; filename="'.$pdfDownloadName.'";',
                );

                return new Response(200, $pdfData, $headers);
            } catch (IllegalArguementException $e) {
                self::$logger->error($e->getMessage());
                throw new ResourceNotFoundException($e->getMessage());
            } catch (RecordNotFoundException $e) {
                self::$logger->error($e->getMessage());
                throw new ResourceNotFoundException($e->getMessage());
            }
        }

        // view edit article requests
        if ((isset($params['view']) && $params['view'] == 'edit') && (isset($params['title']) || isset($params['ActiveRecordOID']))) {
            $record = new Article();

            try {
                if (isset($params['title'])) {
                    $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);
                    $record->loadByAttribute('title', $title);
                } else {
                    $record->load($params['ActiveRecordOID']);
                }
            } catch (RecordNotFoundException $e) {
                self::$logger->warn($e->getMessage());
                $body .= View::renderErrorPage(404, 'Failed to find the requested article!');

                return new Response(404, $body, array('Content-Type' => 'text/html'));
            }

            ActiveRecord::disconnect();

            $this->record = $record;
            $view = View::getInstance($record);

            // set up the title and meta details
            $this->setTitle($record->get('title').' (editing)');
            $this->setDescription('Page to edit '.$record->get('title').'.');
            $this->setKeywords('edit,article');

            $body .= View::displayPageHead($this);

            $body .= $view->editView(array('URI' => $request->getURI()));
            $body .= View::renderDeleteForm($request->getURI());

            $body .= View::displayPageFoot($this);
            self::$logger->debug('<<doGET');
            return new Response(200, $body, array('Content-Type' => 'text/html'));
        }

        // handle requests for viewing articles
        if (isset($params['title']) || isset($params['ActiveRecordOID'])) {
            $KDP = new KPI('viewarticle');
            $record = new Article();

            try {
                if (isset($params['title'])) {
                    $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                    $record->loadByAttribute('title', $title, false, array('OID', 'version_num', 'created_ts', 'updated_ts', 'title', 'author', 'published', 'content', 'headerContent'));
                } else {
                    $record->load($params['ActiveRecordOID']);
                }

                if (!$record->get('published')) {
                    throw new RecordNotFoundException('Attempted to load an article which is not published yet');
                }

                $record->set('tags', $record->getOID());
            } catch (IllegalArguementException $e) {
                self::$logger->warn($e->getMessage());
                throw new ResourceNotFoundException('The file that you have requested cannot be found!');
            } catch (RecordNotFoundException $e) {
                self::$logger->warn($e->getMessage());
                throw new ResourceNotFoundException('The article that you have requested cannot be found!');
            }

            $this->record = $record;
            $this->setTitle($record->get('title'));
            $this->setDescription($record->get('description'));

            $BOView = View::getInstance($record);

            $body .= View::displayPageHead($this);

            $message = $this->getStatusMessage();
            if (!empty($message)) {
                $body .= $message;
            }

            $body .= $BOView->markdownView();

            $body .= View::displayPageFoot($this);

            $KDP->log();

            return new Response(200, $body, array('Content-Type' => 'text/html'));
        }

        // handle requests to view an article stored in a file
        if (isset($params['file'])) {
            try {
                $record = new Article();

                // just checking to see if the file path is absolute or not
                if (mb_substr($params['file'], 0, 1) == '/') {
                    $record->loadContentFromFile($params['file']);
                } else {
                    $record->loadContentFromFile($config->get('app.root').'docs/'.$params['file']);
                }
            } catch (IllegalArguementException $e) {
                self::$logger->error($e->getMessage());
                throw new ResourceNotFoundException($e->getMessage());
            } catch (FileNotFoundException $e) {
                self::$logger->warn($e->getMessage().' File path is ['.$params['file'].']');
                throw new ResourceNotFoundException('Failed to load the requested article from the file system!');
            }

            $this->record = $record;
            $this->setTitle($record->get('title'));

            $BOView = View::getInstance($record);

            $body .= View::displayPageHead($this, false);

            $body .= $BOView->markdownView();

            $body .= View::displayPageFoot($this);

            return new Response(200, $body, array('Content-Type' => 'text/html'));
        }

        // handle requests to view a list of articles
        if (isset($params['start'])) {
            return parent::doGET($request);
        }

        // create a new article requests
        $record = new Article();
        $view = View::getInstance($record);

        // set up the title and meta details
        $this->setTitle('Creating article');
        $this->setDescription('Page to create a new article.');
        $this->setKeywords('create,article');

        $body .= View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $fields = array('formAction' => $this->request->getURI());
        $body .= $view->createView($fields);

        $body .= View::displayPageFoot($this);
        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Method to handle PUT requests.
     *
     * @param Alpha\Util\Http\Request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $body = '';

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
                self::$logger->debug('<<doPUT');
            }

            if (isset($params['markdownTextBoxRows']) && $params['markdownTextBoxRows'] != '') {
                $viewState = ViewState::getInstance();
                $viewState->set('markdownTextBoxRows', $params['markdownTextBoxRows']);
            }

            // save an existing comment
            // TODO: move to dedicated controller, or use generic Create::doPUT().
            if (isset($params['article_comment_id'])) {
                $comment = new ArticleComment();

                try {
                    $comment->load($params['article_comment_id']);

                    // re-populates the old object from post data
                    $comment->populateFromArray($params);

                    $success = $comment->save();

                    self::$logger->action('Updated the comment ['.$params['article_comment_id'].'] on the article ['.$record->getOID().']');

                    ActiveRecord::disconnect();

                    $this->setStatusMessage(View::displayUpdateMessage('Your comment has been updated.'));

                    return $this->doGET($request);
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                }
            }

            if (isset($params['title'])) {
                $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                $record = new Article();
                $record->loadByAttribute('title', $title);

                $View = View::getInstance($record);

                // set up the title and meta details
                $this->setTitle($record->get('title').' (editing)');
                $this->setDescription('Page to edit '.$record->get('title').'.');
                $this->setKeywords('edit,article');

                $body = View::displayPageHead($this);

                // saving an article
                if (isset($params['saveBut'])) {

                    // populate the transient object from post data
                    $record->populateFromArray($params);
                    $record->set('title', $title);

                    try {
                        $success = $record->save();

                        self::$logger->action('Article '.$record->getID().' saved');
                        $body .= View::displayUpdateMessage('Article '.$record->getID().' saved successfully.');
                    } catch (LockingException $e) {
                        $record->reload();
                        $body .= View::displayErrorMessage($e->getMessage());
                    }

                    ActiveRecord::disconnect();
                    $body .= $View->editView(array('URI' => $request->getURI()));
                }

                // uploading an article attachment
                if (isset($params['uploadBut'])) {
                    $source = $request->getFile('userfile')['tmp_name'];
                    $dest = $record->getAttachmentsLocation().'/'.$request->getFile('userfile')['name'];

                    // upload the file to the attachments directory
                    FileUtils::copy($source, $dest);

                    if (!file_exists($dest)) {
                        throw new AlphaException('Could not move the uploaded file ['.$request->getFile('userfile')['name'].']');
                    }

                    // set read/write permissions on the file
                    $success = chmod($dest, 0666);

                    if (!$success) {
                        throw new AlphaException('Unable to set read/write permissions on the uploaded file ['.$dest.'].');
                    }

                    if ($success) {
                        $body .= View::displayUpdateMessage('File uploaded successfully.');
                        self::$logger->action('File '.$source.' uploaded to '.$dest);
                    }

                    $view = View::getInstance($record);

                    $body .= $view->editView(array('URI' => $request->getURI()));
                }

                if (isset($params['deletefile'])) {
                    $success = unlink($record->getAttachmentsLocation().'/'.$params['deletefile']);

                    if (!$success) {
                        throw new AlphaException('Could not delete the file ['.$params['deletefile'].']');
                    }

                    if ($success) {
                        $body .= View::displayUpdateMessage($params['deletefile'].' deleted successfully.');
                        self::$logger->action('File '.$record->getAttachmentsLocation().'/'.$params['deletefile'].' deleted');
                    }

                    $view = View::getInstance($record);

                    $body .= $view->editView(array('URI' => $request->getURI()));
                }
            } else {
                throw new IllegalArguementException('No valid article ID provided!');
            }
        } catch (SecurityException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            $this->setStatusMessage(View::displayErrorMessage('Failed to load the requested article from the database!'));
        } catch (AlphaException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            self::$logger->error($e->getMessage());
        }

        $body .= View::renderDeleteForm($request->getURI());

        $body .= View::displayPageFoot($this);

        $response = new Response(200, $body, array('Content-Type' => 'text/html'));

        self::$logger->debug('<<doPUT');

        return $response;
    }

    /**
     * Method to handle PUT requests.
     *
     * @param Alpha\Util\Http\Request
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     *
     * @todo handle all of this functionality with ActiveRecordController
     */
    /*public function doDELETE($request)
    {
        self::$logger->debug('>>doDELETE($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        try {
            // check the hidden security fields before accepting the form DELETE data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
                self::$logger->debug('<<doPUT');
            }

            if (isset($params['title']) || isset($params['deleteOID'])) {
                if (isset($params['deleteOID'])) {
                    $record->load($params['deleteOID']);
                } else {
                    $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                    $record->loadbyAttribute('title', $title);
                }

                try {
                    $title = $record->get('title');
                    $record->delete();
                    $record = null;
                    self::$logger->action('Article '.$title.' deleted.');

                    // if we are deleting a record from a single request request, just render a message
                    if (isset($params['title'])) {
                        $body = View::displayPageHead($this);
                        $body .= View::displayUpdateMessage('Article '.$title.' deleted.');

                        $body .= '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.get_class($record))."'",
                            'Back to List', 'cancelBut');
                        $body .= $temp->render();

                        $body .= '</center>';

                        $body .= View::displayPageFoot($this);

                        self::$logger->debug('<<doDELETE');

                        return new Response(200, $body, array('Content-Type' => 'text/html'));
                    }

                    $this->setStatusMessage(View::displayUpdateMessage('Article '.$title.' deleted.'));
                    self::$logger->debug('<<doDELETE');

                    return $this->doGET($request);
                } catch (AlphaException $e) {
                    self::$logger->error($e->getTraceAsString());
                    $response = new Response(500, json_encode(array('message' => 'Error deleting the article, check the log!')), array('Content-Type' => 'application/json'));

                    self::$logger->debug('<<doDELETE');

                    return $response;
                }
            } else {
                $body .= View::renderErrorPage(404, 'Failed to find the requested article!');

                return new Response(404, $body, array('Content-Type' => 'text/html'));
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        }

        self::$logger->debug('<<doDELETE');
    }*/

    /**
     * Renders custom HTML header content.
     *
     * @return string
     *
     * @since 1.0
     */
    public function during_displayPageHead_callback()
    {
        $config = ConfigProvider::getInstance();

        $params = $this->request->getParams();

        if ((isset($params['view']) && $params['view'] == 'edit') || (isset($params['ActiveRecordType']) && !isset($params['ActiveRecordOID']))) {
            $config = ConfigProvider::getInstance();

            $fieldid = ($config->get('security.encrypt.http.fieldnames') ? 'text_field_'.base64_encode(SecurityUtils::encrypt('content')).'_0' : 'text_field_content_0');

            $html = '
                <script type="text/javascript">
                $(document).ready(function() {
                    $(\'[id="'.$fieldid.'"]\').pagedownBootstrap({
                        \'sanatize\': false
                    });
                });
                </script>';

            return $html;
        } elseif (isset($params['view']) && $params['view'] == 'print') {
            return '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'/css/print.css">';
        }
    }

    /**
     * Callback that inserts the CMS level header.
     *
     * @return string
     *
     * @since 1.0
     */
    public function insert_CMSDisplayStandardHeader_callback()
    {
        if ($this->request->getParam('token') != null) {
            return '';
        }

        if (!$this->record instanceof Alpha\Model\Article) {
            return '';
        }

        $config = ConfigProvider::getInstance();

        $html = '';

        if ($config->get('cms.display.standard.header')) {
            $html .= '<p><a href="'.$config->get('app.url').'">'.$config->get('app.title').'</a> &nbsp; &nbsp;';
            $html .= 'Date Added: <em>'.$this->record->getCreateTS()->getDate().'</em> &nbsp; &nbsp;';
            $html .= 'Last Updated: <em>'.$this->record->getUpdateTS()->getDate().'</em> &nbsp; &nbsp;';
            $html .= 'Revision: <em>'.$this->record->getVersion().'</em></p>';
        }

        $html .= $config->get('cms.header');

        return $html;
    }

    /**
     * Callback used to render footer content, including comments, votes and print/PDF buttons when
     * enabled to do so.
     *
     * @return string
     *
     * @since 1.0
     */
    public function before_displayPageFoot_callback()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $html = '';
        $params = $this->request->getParams();

        // this will ensure that direct requests to ActiveRecordController will be re-directed here.
        $this->setName($config->get('app.url').$this->request->getURI());
        $this->setUnitOfWork(array($config->get('app.url').$this->request->getURI(), $config->get('app.url').$this->request->getURI()));

        if ($this->record != null) {
            if (isset($params['view']) && $params['view'] == 'detailed') {
                if ($config->get('cms.display.comments')) {
                    $html .= $this->renderComments();
                }

                if ($config->get('cms.display.tags')) {
                    $tags = $this->record->getPropObject('tags')->getRelatedObjects();

                    if (count($tags) > 0) {
                        $html .= '<p>Tags:';

                        foreach ($tags as $tag) {
                            $html .= ' <a href="'.$config->get('app.url').'/search/'.$tag->get('content').'">'.$tag->get('content').'</a>';
                        }
                        $html .= '</p>';
                    }
                }

                if ($config->get('cms.display.votes')) {
                    $rating = $this->record->getArticleScore();
                    $votes = $this->record->getArticleVotes();
                    $html .= '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
                }

                if (!$this->record->checkUserVoted() && $config->get('cms.voting.allowed')) {
                    $URL = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType=Alpha\Model\ArticleVote');
                    $html .= '<form action="'.$URL.'" method="post" accept-charset="UTF-8">';
                    $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('score')) : 'score');
                    $html .= '<p>Please rate this article from 1-10 (10 being the best):'.
                            '<select name="'.$fieldname.'">'.
                            '<option value="1">1'.
                            '<option value="2">2'.
                            '<option value="3">3'.
                            '<option value="4">4'.
                            '<option value="5">5'.
                            '<option value="6">6'.
                            '<option value="7">7'.
                            '<option value="8">8'.
                            '<option value="9">9'.
                            '<option value="10">10'.
                            '</select></p>&nbsp;&nbsp;';

                    $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('articleOID')) : 'articleOID');
                    $html .= '<input type="hidden" name="'.$fieldname.'" value="'.$this->record->getOID().'"/>';

                    $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('personOID')) : 'personOID');
                    $html .= '<input type="hidden" name="'.$fieldname.'" value="'.$session->get('currentUser')->getID().'"/>';

                    $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('statusMessage')) : 'statusMessage');
                    $html .= '<input type="hidden" name="'.$fieldname.'" value="Thank you for rating this article!"/>';

                    $temp = new Button('submit', 'Vote!', 'voteBut');
                    $html .= $temp->render();

                    $html .= View::renderSecurityFields();
                    $html .= '<form>';
                }

                ActiveRecord::disconnect();

                if ($config->get('cms.allow.print.versions')) {
                    $html .= '&nbsp;&nbsp;';
                    $temp = new Button("window.open('".$this->record->get('printURL')."')", 'Open Printer Version', 'printBut');
                    $html .= $temp->render();
                }

                $html .= '&nbsp;&nbsp;';
                if ($config->get('cms.allow.pdf.versions')) {
                    $html .= '&nbsp;&nbsp;';
                    $temp = new Button("document.location = '".FrontController::generateSecureURL("act=Alpha\Controller\ArticleController&mode=pdf&title=".$this->record->get('title'))."';", 'Open PDF Version', 'pdfBut');
                    $html .= $temp->render();
                }

                // render edit button for admins only
                if ($session->get('currentUser') instanceof Alpha\Model\Person && $session->get('currentUser')->inGroup('Admin')) {
                    $html .= '&nbsp;&nbsp;';
                    $button = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ArticleController&mode=edit&ActiveRecordOID='.$this->record->getID())."'", 'Edit', 'editBut');
                    $html .= $button->render();
                }
            }

            if ($config->get('cms.display.standard.footer')) {
                $html .= '<p>Article URL: <a href="'.$this->record->get('URL').'">'.$this->record->get('URL').'</a><br>';
                $html .= 'Title: '.$this->record->get('title').'<br>';
                $html .= 'Author: '.$this->record->get('author').'</p>';
            }
        }

        $html .= $config->get('cms.footer');

        return $html;
    }

    /**
     * Method for displaying the user comments for the article.
     *
     * @return string
     *
     * @since 1.0
     */
    private function renderComments()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $html = '';

        $comments = $this->record->getArticleComments();
        $commentsCount = count($comments);

        $URL = FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType=Alpha\Model\ArticleComment');

        $fields = array('formAction' => $URL);

        if ($config->get('cms.display.comments') && $commentsCount > 0) {
            $html .= '<h2>There are ['.$commentsCount.'] user comments for this article</h2>';

            for ($i = 0; $i < $commentsCount; ++$i) {
                $view = View::getInstance($comments[$i]);
                $html .= $view->markdownView($fields);
            }
        }

        if ($session->get('currentUser') != null && $config->get('cms.comments.allowed')) {
            $comment = new ArticleComment();
            $comment->set('articleOID', $this->record->getID());

            $view = View::getInstance($comment);
            $html .= $view->createView($fields);
        }

        return $html;
    }
}
