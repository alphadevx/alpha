<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\ValidationException;
use Alpha\Model\ActiveRecord;
use Alpha\Controller\Front\FrontController;

/**
 * Controller used to create a new BO, whose classname must be supplied in GET vars
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
class CreateController extends Controller implements ControllerInterface
{
    /**
     * The name of the ActiveRecord type that we will be creating
     *
     * @var string
     * @since 1.0
     */
    protected $activeRecordType;

    /**
     * The new BO to be created
     *
     * @var Alpha\Model\ActiveRecord
     * @since 1.0
     */
    protected $BO;

    /**
     * The View object used for rendering the objects to create
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
     * Constructor to set up the object
     *
     * @param string $visibility
     * @since 1.0
     */
    public function __construct($visibility='Admin')
    {
        self::$logger = new Logger('CreateController');
        self::$logger->debug('>>__construct(visibility=['.$visibility.'])');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\ResourceNotFoundException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            // load the business object (BO) definition
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = $params['ActiveRecordType'];
                $this->activeRecordType = $ActiveRecordType;
            } elseif (isset($this->BOname)) {
                $ActiveRecordType = $this->BOname;
            } else {
                throw new IllegalArguementException('No ActiveRecord available to create!');
            }

            /*
             *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
             *
             * TODO: do we still want to do this?
             */
            if ($this->getCustomControllerName($ActiveRecordType, 'create') != null)
                $this->loadCustomController($ActiveRecordType, 'create');

            $className = "Alpha\\Model\\$ActiveRecordType";
            if (class_exists($className))
                $this->BO = new $className();
            else
                throw new IllegalArguementException('No ActiveRecord available to create!');

            $this->BOView = View::getInstance($this->BO);

            // set up the title and meta details
            if (!isset($this->title))
                $this->setTitle('Create a new '.$ActiveRecordType);
            if (!isset($this->description))
                $this->setDescription('Page to create a new '.$ActiveRecordType.'.');
            if (!isset($this->keywords))
                $this->setKeywords('create,new,'.$ActiveRecordType);

            $body = View::displayPageHead($this);

            $body .= $this->BOView->createView();
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Method to handle POST requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\ResourceNotAllowedException
     * @return Alpha\Util\Http\Response
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

            // load the business object (BO) definition
            if (isset($params['bo'])) {
                $ActiveRecordType = $params['bo'];
                $this->BOname = $ActiveRecordType;
            } elseif (isset($this->BOname)) {
                $ActiveRecordType = $this->BOname;
            } else {
                throw new IllegalArguementException('No BO available to create!');
            }

            $this->BO = new $ActiveRecordType();

            if (isset($params['createBut'])) {
                // populate the transient object from post data
                $this->BO->populateFromPost();

                $this->BO->save();

                self::$logger->action('Created new '.$ActiveRecordType.' instance with OID '.$this->BO->getOID());

                ActiveRecord::disconnect();

                try {
                    if ($this->getNextJob() != '')
                        header('Location: '.$this->getNextJob());
                    else
                        header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getOID()));
                } catch (AlphaException $e) {
                    echo View::displayPageHead($this);
                    self::$logger->error($e->getTraceAsString());
                    echo View::displayErrorMessage('Error creating the new ['.$ActiveRecordType.'], check the log!');
                }
            }

            if (isset($params['cancelBut'])) {
                header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            echo View::displayPageHead($this);
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            echo View::displayPageHead($this);
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        } catch (ValidationException $e) {
            self::$logger->warn($e->getMessage().', query ['.$this->BO->getLastQuery().']');
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            $this->doGET($params);
        }

        self::$logger->debug('<<doPOST');
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

        if (isset($_SESSION['currentUser']) && AlphaDAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $menu .= View::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }
}

?>