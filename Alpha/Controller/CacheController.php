<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\File\FileUtils;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\View\View;
use Alpha\View\Widget\Button;

/**
 *
 * Controller used to clear out the CMS cache when required
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
class CacheController extends Controller implements ControllerInterface
{
    /**
     * The root of the cache directory
     *
     * @var string
     * @since 1.0
     */
    private $dataDir;

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
        self::$logger = new Logger('CacheManager');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        $this->setTitle('Cache Manager');
        $this->dataDir  = $config->get('app.file.store.dir').'cache/';

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doGET($params)
    {
        self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        if (!is_array($params))
            throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');

        echo View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            echo $message;

        echo '<h3>Listing contents of cache directory: '.$this->dataDir.'</h3>';

        $fileCount = FileUtils::listDirectoryContents($this->dataDir, 0, array('.htaccess'));

        echo '<h3>Total of '.$fileCount.' files in the cache.</h3>';

        echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="clearForm" id="clearForm">';
        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('clearCache')) : 'clearCache');
        echo '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value="false"/>';
        $js = "if(window.jQuery) {
                    BootstrapDialog.show({
                        title: 'Confirmation',
                        message: 'Are you sure you want to delete all files in the cache?',
                        buttons: [
                            {
                                icon: 'glyphicon glyphicon-remove',
                                label: 'Cancel',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself){
                                    dialogItself.close();
                                }
                            },
                            {
                                icon: 'glyphicon glyphicon-ok',
                                label: 'Okay',
                                cssClass: 'btn btn-default btn-xs',
                                action: function(dialogItself) {
                                    $('[id=\"".$fieldname."\"]').attr('value', 'true');
                                    $('#clearForm').submit();
                                    dialogItself.close();
                                }
                            }
                        ]
                    });
                }";
        $button = new Button($js, "Clear cache", "clearBut");
        echo $button->render();

        echo View::renderSecurityFields();
        echo '</form>';

        echo View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @throws Alpha\Exception\SecurityException
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            if (!is_array($params))
                throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');

            if (isset($params['clearCache']) && $params['clearCache'] == 'true') {
                try {
                    FileUtils::deleteDirectoryContents($this->dataDir, array('.htaccess'));

                    $this->setStatusMessage(View::displayUpdateMessage('Cache contents deleted successfully.'));

                    self::$logger->info('Cache contents deleted successfully by user ['.$_SESSION['currentUser']->get('displayName').'].');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
                }
            }

            return $this->doGET($params);
        } catch (SecurityException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));

            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
        }

        echo View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            echo $message;

        echo View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');
    }

    /**
     * Use this callback to inject in the admin menu template fragment
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array());

        return $menu;
    }
}

?>