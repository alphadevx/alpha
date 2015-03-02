<?php

namespace Alpha\Controller;

use Alpha\Util\InputFilter;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Logging\KPI;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Extension\MarkdownFacade;
use Alpha\Util\Extension\TCPDFFacade;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
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
 *
 * Controller used handle Article objects
 *
 * @since 1.0
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
 *
 */
class ArticleController extends Controller implements ControllerInterface
{
    /**
     * The current article object
     *
     * @var Alpha\Model\Article
     * @since 1.0
     */
    protected $BO;

    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Used to track the mode param passed on the request (can be /create or /edit, if not provided then default to read-only).
     *
     * @var string
     * @since 2.0
     */
    private $mode = 'read';

    /**
     * constructor to set up the object
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ArticleController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Standard');

        $this->BO = new Article();

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\ResourceNotFoundException
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        // TODO: this will never be create, read is default, so we are only using edit.  Do we even need this?
        $this->mode = (isset($params['mode']) && in_array($params['mode'], array('create','edit')) ? $params['mode'] : 'read');

        $body = '';

        // handle requests for PDFs
        if ($this->mode == 'read' && isset($params['title']) && $request->getHeader('Accept') == 'application/pdf') {
            try {
                $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                $this->BO = new Article();
                $this->BO->loadByAttribute('title', $title);

                ActiveRecord::disconnect();

                $pdf = new TCPDFFacade($this->BO);
                $pdfData = $pdf->getPDFData();
                $pdfDownloadName = str_replace(' ', '-', $this->BO->get('title').'.pdf');

                $headers = array(
                    'Pragma' => 'public',
                    'Expires' => 0,
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Content-Transfer-Encoding' => 'binary',
                    'Content-Type' => 'application/pdf',
                    'Content-Length' => strlen($pdfData),
                    'Content-Disposition' => 'attachment; filename="'.$pdfDownloadName.'";'
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

        // handle requests for viewing articles
        if ($this->mode == 'read' && isset($params['title'])) {

            $KDP = new KPI('viewarticle');

            try {
                // it may have already been loaded by a doPOST call
                if ($this->BO->isTransient()) {
                    $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);

                    $this->BO->loadByAttribute('title', $title, false, array('OID', 'version_num', 'created_ts', 'updated_ts', 'author', 'published', 'content', 'headerContent'));

                    if (!$this->BO->get('published'))
                        throw new RecordNotFoundException('Attempted to load an article which is not published yet');

                    $this->BO->set('tags', $this->BO->getOID());
                }

            } catch (IllegalArguementException $e) {
                self::$logger->warn($e->getMessage());
                throw new ResourceNotFoundException('The file that you have requested cannot be found!');
            } catch (RecordNotFoundException $e) {
                self::$logger->warn($e->getMessage());
                throw new ResourceNotFoundException('The article that you have requested cannot be found!');
            }

            $this->setTitle($this->BO->get('title'));
            $this->setDescription($this->BO->get('description'));

            $BOView = View::getInstance($this->BO);

            $body .= View::displayPageHead($this);

            $body .= $BOView->markdownView();

            $body .= View::displayPageFoot($this);

            $KDP->log();

            return new Response(200, $body, array('Content-Type' => 'text/html'));
        }

        // handle requests to view an article stored in a file
        if ($this->mode == 'read' && isset($params['file'])) {
            try {

                $this->BO = new Article();

                // just checking to see if the file path is absolute or not
                if (mb_substr($params['file'], 0, 1) == '/')
                    $this->BO->loadContentFromFile($params['file']);
                else
                    $this->BO->loadContentFromFile($config->get('app.root').'docs/'.$params['file']);

            } catch (IllegalArguementException $e) {
                self::$logger->error($e->getMessage());
                throw new ResourceNotFoundException($e->getMessage());
            } catch (FileNotFoundException $e) {
                self::$logger->warn($e->getMessage().' File path is ['.$params['file'].']');
                throw new ResourceNotFoundException('Failed to load the requested article from the file system!');
            }

            $this->setTitle($this->BO->get('title'));

            $BOView = View::getInstance($this->BO);

            $body .= View::displayPageHead($this, false);

            $body .= $BOView->markdownView();

            $body .= View::displayPageFoot($this);

            return new Response(200, $body, array('Content-Type' => 'text/html'));
        }

        // view edit artile requests
        if ($this->mode == 'edit' && isset($params['title'])) {

            try {
                $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);
                $this->BO->loadByAttribute('title', $title);
            } catch (RecordNotFoundException $e) {
                self::$logger->warn($e->getMessage());
                $body .= View::renderErrorPage(404, 'Failed to find the requested article!');
                return new Response(404, $body, array('Content-Type' => 'text/html'));
            }

            ActiveRecord::disconnect();

            $view = View::getInstance($this->BO);

            // set up the title and meta details
            $this->setTitle($this->BO->get('title').' (editing)');
            $this->setDescription('Page to edit '.$this->BO->get('title').'.');
            $this->setKeywords('edit,article');

            $body .= View::displayPageHead($this);

            $body .= $view->editView();
            $body .= View::renderDeleteForm();
        }

        // create a new article requests
        if ($request->getParam('title') === '') {
            $view = View::getInstance($this->BO);

            // set up the title and meta details
            $this->setTitle('Creating article');
            $this->setDescription('Page to create a new article.');
            $this->setKeywords('create,article');

            $body .= View::displayPageHead($this);

            $body .= $view->createView();
        }

        $body .= View::displayPageFoot($this);

        return new Response(200, $body, array('Content-Type' => 'text/html'));

        self::$logger->debug('<<doGET');
    }

    /**
     * Method to handle POST requests
     *
     * @param Alpha\Util\Http\Request
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\SecurityException
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        // TODO: see comment in doGET, we should get rid of this
        $this->mode = (isset($params['mode']) && in_array($params['mode'], array('create','edit')) ? $params['mode'] : 'read');

        if ($this->mode == 'read') {
            try {
                // check the hidden security fields before accepting the form POST data
                if (!$this->checkSecurityFields())
                    throw new SecurityException('This page cannot accept post data from remote servers!');

                // save an article up-vote
                // TODO: move to dedicated controller, or use generic Create::doPOST().
                if (isset($params['voteBut']) && !$this->BO->checkUserVoted()) {
                    $vote = new ArticleVote();

                    if (isset($params['oid'])) {
                        $vote->set('articleOID', $params['oid']);
                    } else {
                        // load article by title?
                        if (isset($params['title'])) {
                            $title = str_replace($config->get('cms.url.title.separator'), ' ', $params['title']);
                        } else {
                            throw new IllegalArguementException('Could not load the article as a title or OID was not supplied!');
                        }

                        $this->BO = new Article();
                        $this->BO->loadByAttribute('title', $title);
                        $vote->set('articleOID', $this->BO->getOID());
                    }

                    $vote->set('personOID', $session->get('currentUser')->getID());
                    $vote->set('score', $params['userVote']);

                    try {
                        $vote->save();

                        self::$logger->action('Voted on the article ['.$this->BO->getOID().']');

                        ActiveRecord::disconnect();

                        $this->setStatusMessage(View::displayUpdateMessage('Thank you for rating this article!'));

                        return $this->doGET($request);
                    } catch (FailedSaveException $e) {
                        self::$logger->error($e->getMessage());
                    }
                }

                // save an article comment
                // TODO: move to dedicated controller, or use generic Create::doPOST().
                if (isset($params['createCommentBut'])) {
                    $comment = new ArticleComment();

                    // populate the transient object from post data
                    $comment->populateFromArray($params);

                    // filter the comment before saving
                    $comment->set('content', InputFilter::encode($comment->get('content')));

                    try {
                        $success = $comment->save();

                        self::$logger->action('Commented on the article ['.$this->BO->getOID().']');

                        ActiveRecord::disconnect();

                        $this->setStatusMessage(View::displayUpdateMessage('Thank you for your comment!'));

                        return $this->doGET($request);
                    } catch (FailedSaveException $e) {
                        self::$logger->error($e->getMessage());
                    }
                }

                // save an existing comment
                // TODO: move to dedicated controller, or use generic Create::doPUT().
                if (isset($params['saveBut'])) {
                    $comment = new ArticleComment();

                    try {
                        $comment->load($params['article_comment_id']);

                        // re-populates the old object from post data
                        $comment->populateFromArray($params);

                        $success = $comment->save();

                        self::$logger->action('Updated the comment ['.$params['article_comment_id'].'] on the article ['.$this->BO->getOID().']');

                        ActiveRecord::disconnect();

                        $this->setStatusMessage(View::displayUpdateMessage('Your comment has been updated.'));

                        return $this->doGET($request);
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getMessage());
                    }
                }
            } catch (SecurityException $e) {
                self::$logger->warn($e->getMessage());
                throw new ResourceNotAllowedException($e->getMessage());
            }
        }

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            $this->BO = new Article();

            // saving a new article
            if (isset($params['createBut'])) {
                // populate the transient object from post data
                $this->BO->populateFromArray($params);
                $this->BO->save();

                self::$logger->action('Created new Article instance with OID '.$this->BO->getOID());

                ActiveRecord::disconnect();

                try {
                    $response = new Response(301);
                    if ($this->getNextJob() != '')
                        $response->redirect($this->getNextJob());
                    else
                        $response->redirect(FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));

                    return $response;

                } catch (AlphaException $e) {
                        self::$logger->error($e->getTraceAsString());
                        $this->setStatusMessage(View::displayErrorMessage('Error creating the new article, check the log!'));
                }
            }

            // previewing an article
            if (isset($params['data'])) {
                // allow the consumer to optionally indicate another BO than Article
                if (isset($params['bo']) && class_exists($params['bo']))
                    $temp = new $params['bo'];
                else
                    $temp = new Article();

                $temp->set('content', $params['data']);

                if (isset($params['oid']))
                    $temp->set('OID', $params['oid']);

                $parser = new MarkdownFacade($temp, false);

                // render a simple HTML header
                $body = '<html>';
                $body .= '<head>';
                $body .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/lib/jquery/ui/themes/'.$config->get('app.css.theme').'/jquery.ui.all.css">';
                $body .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/css/alpha.css">';
                $body .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'config/css/overrides.css">';
                $body .= '</head>';
                $body .= '<body>';

                // transform text using parser.
                $body .= $parser->getContent();

                $body .= '</body>';
                $body .= '</html>';

                $response = new Response(200, $body, array('Content-Type' => 'text/html'));
                return $response;
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        }

        self::$logger->debug('<<doPOST');
    }

    /**
     * Method to handle PUT requests
     *
     * @param Alpha\Util\Http\Request
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT(params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

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

            if (isset($params['oid'])) {
                if (!Validator::isInteger($params['oid']))
                    throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');

                $this->BO->load($params['oid']);

                $View = View::getInstance($this->BO);

                // set up the title and meta details
                $this->setTitle($this->BO->get('title').' (editing)');
                $this->setDescription('Page to edit '.$this->BO->get('title').'.');
                $this->setKeywords('edit,article');

                echo View::displayPageHead($this);

                if (isset($params['saveBut'])) {

                    // populate the transient object from post data
                    $this->BO->populateFromPost();

                    try {
                        $success = $this->BO->save();
                        self::$logger->action('Article '.$this->BO->getID().' saved');
                        echo View::displayUpdateMessage('Article '.$this->BO->getID().' saved successfully.');
                    } catch (LockingException $e) {
                        $this->BO->reload();
                        echo View::displayErrorMessage($e->getMessage());
                    }

                    ActiveRecord::disconnect();
                    echo $View->editView();
                }

                if (!empty($params['deleteOID'])) {

                    $this->BO->load($params['deleteOID']);

                    try {
                        $this->BO->delete();
                        self::$logger->action('Article '.$params['deleteOID'].' deleted.');
                        ActiveRecord::disconnect();

                        echo View::displayUpdateMessage('Article '.$params['deleteOID'].' deleted successfully.');

                        echo '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
                            'Back to List','cancelBut');
                        echo $temp->render();

                        echo '</center>';
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getTraceAsString());
                        echo View::displayErrorMessage('Error deleting the article, check the log!');
                    }
                }

                if (isset($params['uploadBut'])) {

                    // upload the file to the attachments directory
                    $success = move_uploaded_file($_FILES['userfile']['tmp_name'], $this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name']);

                    if (!$success)
                        throw new AlphaException('Could not move the uploaded file ['.$_FILES['userfile']['name'].']');

                    // set read/write permissions on the file
                    $success = chmod($this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'], 0666);

                    if (!$success)
                        throw new AlphaException('Unable to set read/write permissions on the uploaded file ['.$this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'].'].');

                    if ($success) {
                        echo View::displayUpdateMessage('File uploaded successfully.');
                        self::$logger->action('File '.$_FILES['userfile']['name'].' uploaded to '.$this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name']);
                    }

                    $view = View::getInstance($this->BO);

                    echo $view->editView();
                }

                if (!empty($params['file_to_delete'])) {

                    $success = unlink($this->BO->getAttachmentsLocation().'/'.$params['file_to_delete']);

                    if (!$success)
                        throw new AlphaException('Could not delete the file ['.$params['file_to_delete'].']');

                    if ($success) {
                        echo View::displayUpdateMessage($params['file_to_delete'].' deleted successfully.');
                        self::$logger->action('File '.$this->BO->getAttachmentsLocation().'/'.$params['file_to_delete'].' deleted');
                    }

                    $view = View::getInstance($this->BO);

                    echo $view->editView();
                }
            } else {
                throw new IllegalArguementException('No valid article ID provided!');
            }
        } catch (SecurityException $e) {
            echo View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            echo View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            echo View::displayErrorMessage('Failed to load the requested article from the database!');
        } catch (AlphaException $e) {
            echo View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        }

        echo View::renderDeleteForm();

        echo View::displayPageFoot($this);

        self::$logger->debug('<<doPOST');
    }

    /**
     * Renders custom HTML header content
     *
     * @return string
     * @since 1.0
     */
    public function during_displayPageHead_callback()
    {
        if ($this->mode == 'read') {
            return $this->BO->get('headerContent');
        } else {
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
        }
    }

    /**
     * Use this callback to inject in the admin menu template fragment for admin users of
     * the backend only.
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $menu = '';

        if (isset($_SESSION['currentUser']) && ActiveRecord::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $menu .= View::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }

    /**
     * Callback that inserts the CMS level header
     *
     * @return string
     * @since 1.0
     */
    public function insert_CMSDisplayStandardHeader_callback()
    {
        $config = ConfigProvider::getInstance();

        $html = '';

        if ($config->get('cms.display.standard.header')) {
            $html.= '<p><a href="'.$config->get('app.url').'">'.$config->get('app.title').'</a> &nbsp; &nbsp;';
            $html.= 'Date Added: <em>'.$this->BO->getCreateTS()->getDate().'</em> &nbsp; &nbsp;';
            $html.= 'Last Updated: <em>'.$this->BO->getUpdateTS()->getDate().'</em> &nbsp; &nbsp;';
            $html.= 'Revision: <em>'.$this->BO->getVersion().'</em></p>';
        }

        $html.= $config->get('cms.header');

        return $html;
    }

    /**
     * Callback used to render footer content, including comments, votes and print/PDF buttons when
     * enabled to do so.
     *
     * @return string
     * @since 1.0
     */
    public function before_displayPageFoot_callback()
    {
        if ($this->mode != 'read')
            return '';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $html = '';

        if ($config->get('cms.display.comments'))
            $html .= $this->renderComments();

        if ($config->get('cms.display.tags')) {
            $tags = $this->BO->getPropObject('tags')->getRelatedObjects();

            if (count($tags) > 0) {
                $html .= '<p>Tags:';

                foreach($tags as $tag)
                    $html .= ' <a href="'.$config->get('app.url').'search/q/'.$tag->get('content').'">'.$tag->get('content').'</a>';
                $html .= '</p>';
            }
        }

        if ($config->get('cms.display.votes')) {
            $rating = $this->BO->getArticleScore();
            $votes = $this->BO->getArticleVotes();
            $html .= '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
        }

        if (!$this->BO->checkUserVoted() && $config->get('cms.voting.allowed')) {
            $html .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" accept-charset="UTF-8">';
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('userVote')) : 'userVote');
            $html .= '<p>Please rate this article from 1-10 (10 being the best):' .
                    '<select name="'.$fieldname.'">' .
                    '<option value="1">1' .
                    '<option value="2">2' .
                    '<option value="3">3' .
                    '<option value="4">4' .
                    '<option value="5">5' .
                    '<option value="6">6' .
                    '<option value="7">7' .
                    '<option value="8">8' .
                    '<option value="9">9' .
                    '<option value="10">10' .
                    '</select></p>&nbsp;&nbsp;';
            $temp = new Button('submit','Vote!','voteBut');
            $html .= $temp->render();

            $html .= View::renderSecurityFields();
            $html .= '<form>';
        }

        ActiveRecord::disconnect();

        if ($config->get('cms.allow.print.versions')) {
            $html .= '&nbsp;&nbsp;';
            $temp = new Button("window.open('".$this->BO->get('printURL')."')",'Open Printer Version','printBut');
            $html .= $temp->render();
        }

        $html .= '&nbsp;&nbsp;';
        if ($config->get('cms.allow.pdf.versions')) {
            $html .= '&nbsp;&nbsp;';
            $temp = new Button("document.location = '".FrontController::generateSecureURL("act=ViewArticlePDF&title=".$this->BO->get("title"))."';",'Open PDF Version','pdfBut');
            $html .= $temp->render();
        }

        // render edit button for admins only
        if ($session->get('currentUser') !== false && $session->get('currentUser')->inGroup('Admin')) {
            $html .= '&nbsp;&nbsp;';
            $button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'",'Edit','editBut');
            $html .= $button->render();
        }

        if ($config->get('cms.display.standard.footer')) {
            $html .= '<p>Article URL: <a href="'.$this->request->getURL().'">'.$this->request->getURL().'</a><br>';
            $html .= 'Title: '.$this->BO->get('title').'<br>';
            $html .= 'Author: '.$this->BO->get('author').'</p>';
        }

        $html .= $config->get('cms.footer');

        return $html;
    }

    /**
     * Method for displaying the user comments for the article.
     *
     * @return string
     * @since 1.0
     */
    private function renderComments()
    {
        $config = ConfigProvider::getInstance();

        $html = '';

        $comments = $this->BO->getArticleComments();
        $comment_count = count($comments);

        if ($config->get('cms.display.comments') && $comment_count > 0) {
            $html .= '<h2>There are ['.$comment_count.'] user comments for this article</h2>';

            ob_start();
            for($i = 0; $i < $comment_count; $i++) {
                $view = View::getInstance($comments[$i]);
                $view->markdownView();
            }
            $html.= ob_get_clean();
        }

        if (isset($_SESSION['currentUser']) && $config->get('cms.comments.allowed')) {
            $comment = new ArticleComment();
            $comment->set('articleOID', $this->BO->getID());

            ob_start();
            $view = View::getInstance($comment);
            $view->createView();
            $html.= ob_get_clean();
        }

        return $html;
    }
}

?>