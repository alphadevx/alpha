<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Helper\Validator;
use Alpha\View\View;
use Alpha\View\Widget\Button;
use Alpha\Model\ActiveRecord;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;

/**
 * Controller used to display the details of a BO
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
class ViewController extends Controller implements ControllerInterface
{
    /**
     * The BO to be displayed
     *
     * @var Alpha\Model\ActiveRecord
     * @since 1.0
     */
    protected $BO;

    /**
     * The name of the BO
     *
     * @var string
     * @since 1.0
     */
    private $activeRecordType;

    /**
     * The default View object used for rendering the business object
     *
     * @var Alpha\View\View
     * @since 1.0
     */
    private $BOView;

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
     * @param string $visibility The name of the rights group that can access this controller.
     * @since 1.0
     */
    public function __construct($visibility='Standard')
    {
        self::$logger = new Logger('ViewController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\IllegalArguementException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        try {
            // load the business object (BO) definition
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                if (!AlphaValidator::isInteger($params['ActiveRecordOID']))
                    throw new IllegalArguementException('Invalid oid ['.$params['ActiveRecordOID'].'] provided on the request!');

                $ActiveRecordType = $params['ActiveRecordType'];

                /*
                *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
                */
                if ($this->getCustomControllerName($activeRecordType, 'view') != null)
                    $this->loadCustomController($activeRecordType, 'view');

                $this->BO = new $ActiveRecordType();
                $this->BO->load($params['ActiveRecordOID']);
                ActiveRecord::disconnect();

                $this->activeRecordType = $ActiveRecordType;
                $this->BOView = View::getInstance($this->BO);

                $body .= View::displayPageHead($this);
                $body .= View::renderDeleteForm();
                $body .= $this->BOView->detailedView();
            } else {
                throw new IllegalArguementException('No ActiveRecord available to display!');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        } catch (BONotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        }

        $body .= View::displayPageFoot($this);
        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Method to handle POST requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        $body = View::displayPageHead($this);

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            // load the business object (BO) definition
            if (isset($params['ActiveRecordType'])) {
                $activeRecordType = $params['ActiveRecordType'];

                $this->BO = new $ActiveRecordType();
                $this->activeRecordType = $ActiveRecordType;
                $this->BOView = View::getInstance($this->BO);

                if (!empty($params['deleteOID'])) {
                    if (!Validator::isInteger($params['deleteOID']))
                        throw new IllegalArguementException('Invalid deleteOID ['.$params['deleteOID'].'] provided on the request!');

                    $temp = new $ActiveRecordType();
                    $temp->load($params['deleteOID']);

                    try {
                        ActiveRecord::begin();
                        $temp->delete();
                        self::$logger->action('Deleted '.$ActiveRecordType.' instance with OID '.$params['deleteOID']);
                        ActiveRecord::commit();

                        $body .= View::displayUpdateMessage($ActiveRecordType.' '.$params['deleteOID'].' deleted successfully.');

                        $body .= '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
                            'Back to List','cancelBut');
                        $body .= $temp->render();

                        $body .= '</center>';
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getMessage());
                        $body .= View::displayErrorMessage('Error deleting the BO of OID ['.$params['deleteOID'].'], check the log!');
                        ActiveRecord::rollback();
                    }

                    ActiveRecord::disconnect();
                }
            } else {
                throw new IllegalArguementException('No BO available to display!');
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        } catch (BONotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        }

        $body .= View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Sets up the title etc.
     *
     * @since 1.0
     */
    public function before_displayPageHead_callback()
    {
        if ($this->title == '' && isset($this->BO))
            $this->setTitle('Displaying '.$this->activeRecordType.' number '.$this->BO->getID());
        if ($this->description == '' && isset($this->BO))
            $this->setDescription('Page to display '.$this->activeRecordType.' number '.$this->BO->getID());
        if ($this->keywords == '')
            $this->setKeywords('display,details,'.$this->activeRecordType);
    }

    /**
     * Use this callback to inject in the admin menu template fragment for admin users of
     * the backend only.
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $menu = '';

        if ($session->get('currentUser') !== false && ActiveRecord::isInstalled() && $session->get('currentUser')->inGroup('Admin') && mb_strpos($this->request->getURI()) !== false) {
            $menu .= View::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }
}

?>