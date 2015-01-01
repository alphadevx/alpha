<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Helper\Validator;
use Alpha\Model\Article;
use Alpha\View\View;
use Alpha\View\ViewState;
use Alpha\View\Widget\Button;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\LockingException;
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
     * constructor to set up the object
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ArticleController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Standard');

        $this->BO = new Article();

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @since 1.0
     */
    public function doGET($params)
    {
        self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

        // editing
        if (isset($params['oid'])) {
            if (!Validator::isInteger($params['oid']))
                throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');

            try {
                $this->BO->load($params['oid']);
            } catch (RecordNotFoundException $e) {
                self::$logger->warn($e->getMessage());
                echo View::renderErrorPage(404, 'Failed to find the requested article!');
                return;
            }

            ActiveRecord::disconnect();

            $view = View::getInstance($this->BO);

            // set up the title and meta details
            $this->setTitle($this->BO->get('title').' (editing)');
            $this->setDescription('Page to edit '.$this->BO->get('title').'.');
            $this->setKeywords('edit,article');

            echo View::displayPageHead($this);

            echo $view->editView();
            echo View::renderDeleteForm();
        } else { // creating
            $view = View::getInstance($this->BO);

            // set up the title and meta details
            $this->setTitle('Creating article');
            $this->setDescription('Page to create a new article.');
            $this->setKeywords('create,article');

            echo View::displayPageHead($this);

            echo $view->createView();
        }

        echo View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
    }

    /**
     * Method to handle POST requests
     *
     * @param array $params
     * @throws Alpha\Exception\SecurityException
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            $this->BO = new Article();

            if (isset($params['createBut'])) {
                // populate the transient object from post data
                $this->BO->populateFromPost();

                $this->BO->save();

                self::$logger->action('Created new ArticleObject instance with OID '.$this->BO->getOID());

                ActiveRecord::disconnect();

                try {
                    if ($this->getNextJob() != '')
                        header('Location: '.$this->getNextJob());
                    else
                        header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
                } catch (AlphaException $e) {
                        self::$logger->error($e->getTraceAsString());
                        echo '<p class="error"><br>Error creating the new article, check the log!</p>';
                }
            }

            if (isset($params['cancelBut'])) {
                header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
            }
        } catch (SecurityException $e) {
            echo View::displayPageHead($this);
            echo '<p class="error"><br>'.$e->getMessage().'</p>';
            self::$logger->warn($e->getMessage());
        }

        self::$logger->debug('<<doPOST');
    }

    /**
     * Method to handle PUT requests
     *
     * @param array $params
     * @since 1.0
     */
    public function doPUT($params)
    {
        self::$logger->debug('>>doPUT(params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

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
     * Renders the Javascript required in the header by markItUp!
     *
     * @return string
     * @since 1.0
     */
    public function during_displayPageHead_callback()
    {
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

    /**
     * Use this callback to inject in the admin menu template fragment for admin users of
     * the backend only.
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback() {
        $menu = '';

        if (isset($_SESSION['currentUser']) && ActiveRecord::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $menu .= View::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }
}

?>