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
    private $BOName;

    /**
     * The default AlphaView object used for rendering the business object
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
        self::$logger = new Logger('Detail');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.0
     */
    public function doGET($params)
    {
        self::$logger->debug('>>doGET(params=['.var_export($params, true).'])');

        try{
            // load the business object (BO) definition
            if (isset($params['bo']) && isset($params['oid'])) {
                if(!AlphaValidator::isInteger($params['oid']))
                    throw new IllegalArguementException('Invalid oid ['.$params['oid'].'] provided on the request!');

                $BOName = $params['bo'];
                ActiveRecord::loadClassDef($BOName);

                /*
                *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
                */
                if ($this->getCustomControllerName($BOName, 'view') != null)
                    $this->loadCustomController($BOName, 'view');

                $this->BO = new $BOName();
                $this->BO->load($params['oid']);
                ActiveRecord::disconnect();

                $this->BOName = $BOName;
                $this->BOView = View::getInstance($this->BO);

                echo View::displayPageHead($this);
                echo View::renderDeleteForm();
                echo $this->BOView->detailedView();
            } else {
                throw new IllegalArguementException('No BO available to display!');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        } catch (BONotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        }

        echo View::displayPageFoot($this);
        self::$logger->debug('<<doGET');
    }

    /**
     * Method to handle POST requests
     *
     * @param array $params
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST(params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        echo View::displayPageHead($this);

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields())
                throw new SecurityException('This page cannot accept post data from remote servers!');

            // load the business object (BO) definition
            if (isset($params['bo'])) {
                $BOName = $params['bo'];
                ActiveRecord::loadClassDef($BOName);

                $this->BO = new $BOName();
                $this->BOname = $BOName;
                $this->BOView = View::getInstance($this->BO);

                if (!empty($params['deleteOID'])) {
                    if (!Validator::isInteger($params['deleteOID']))
                        throw new IllegalArguementException('Invalid deleteOID ['.$params['deleteOID'].'] provided on the request!');

                    $temp = new $BOName();
                    $temp->load($params['deleteOID']);

                    try {
                        ActiveRecord::begin();
                        $temp->delete();
                        self::$logger->action('Deleted '.$BOName.' instance with OID '.$params['deleteOID']);
                        ActiveRecord::commit();

                        echo View::displayUpdateMessage($BOName.' '.$params['deleteOID'].' deleted successfully.');

                        echo '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
                            'Back to List','cancelBut');
                        echo $temp->render();

                        echo '</center>';
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getMessage());
                        echo View::displayErrorMessage('Error deleting the BO of OID ['.$params['deleteOID'].'], check the log!');
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

        echo View::displayPageFoot($this);
        self::$logger->debug('<<doPOST');
    }

    /**
     * Sets up the title etc.
     *
     * @since 1.0
     */
    public function before_displayPageHead_callback()
    {
        if ($this->title == '' && isset($this->BO))
            $this->setTitle('Displaying '.$this->BOName.' number '.$this->BO->getID());
        if ($this->description == '' && isset($this->BO))
            $this->setDescription('Page to display '.$this->BOName.' number '.$this->BO->getID());
        if ($this->keywords == '')
            $this->setKeywords('display,details,'.$this->BOName);
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
            $menu .= AlphaView::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }
}

?>