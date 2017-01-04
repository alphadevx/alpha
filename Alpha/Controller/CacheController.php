<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\File\FileUtils;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\View\View;
use Alpha\View\Widget\Button;

/**
 * Controller used to clear out the CMS cache when required.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class CacheController extends Controller implements ControllerInterface
{
    /**
     * The root of the cache directory.
     *
     * @var string
     *
     * @since 1.0
     */
    private $dataDir;

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
        self::$logger = new Logger('CacheManager');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        $this->setTitle('Cache Manager');
        $this->dataDir = $config->get('app.file.store.dir').'cache/';

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Response $request
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        if (!is_array($params)) {
            throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');
        }

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $body .= '<h3>Listing contents of cache directory: '.$this->dataDir.'</h3>';

        $fileList = '';
        $fileCount = FileUtils::listDirectoryContents($this->dataDir, $fileList, 0, array('.htaccess'));
        $body .= $fileList;

        $body .= '<h3>Total of '.$fileCount.' files in the cache.</h3>';

        $body .= '<form action="'.$request->getURI().'" method="post" name="clearForm" id="clearForm">';
        $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(SecurityUtils::encrypt('clearCache')) : 'clearCache');
        $body .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value="false"/>';
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
        $button = new Button($js, 'Clear cache', 'clearBut');
        $body .= $button->render();

        $body .= View::renderSecurityFields();
        $body .= '</form>';

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests.
     *
     * @param Alpha\Util\Http\Response $request
     *
     * @throws Alpha\Exception\SecurityException
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            if (!is_array($params)) {
                throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');
            }

            if (isset($params['clearCache']) && $params['clearCache'] == 'true') {
                try {
                    FileUtils::deleteDirectoryContents($this->dataDir, array('.htaccess','html','images','pdf','xls'));

                    $this->setStatusMessage(View::displayUpdateMessage('Cache contents deleted successfully.'));

                    $config = ConfigProvider::getInstance();
                    $sessionProvider = $config->get('session.provider.name');
                    $session = SessionProviderFactory::getInstance($sessionProvider);

                    self::$logger->info('Cache contents deleted successfully by user ['.$session->get('currentUser')->get('displayName').'].');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
                }
            }

            return $this->doGET($request);
        } catch (SecurityException $e) {
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));

            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
        }

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $body .= View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}
