<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\ActiveRecord;
use Alpha\View\View;
use Alpha\View\Widget\Button;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\LockingException;

/**
 *
 * Controller used to edit BO
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
class EditController extends Controller implements ControllerInterface
{
    /**
     * The business object to be edited
     *
     * @var Alpha\Model\ActiveRecord
     * @since 1.0
     */
    protected $BO;

    /**
     * The name of the ActiveRecord type that we will be creating
     *
     * @var string
     * @since 2.0
     */
    protected $activeRecordType;

    /**
     * The OID of the BO to be edited
     *
     * @var integer
     * @since 1.0
     */
    private $BOoid;

    /**
     * The View object used for rendering the object to edit
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
    public function __construct($visibility='Admin')
    {
        self::$logger = new Logger('EditController');
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
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\ResourceNotFoundException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET(request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        try{
            // load the business object (BO) definition
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                $ActiveRecordType = $params['ActiveRecordType'];
                $this->activeRecordType = $ActiveRecordType;

                /*
                 * check and see if a custom edit controller exists for this BO, and if it does use it otherwise continue
                 *
                 * TODO: do we still want to do this?
                 */
                if ($this->getCustomControllerName($ActiveRecordType, 'edit') != null)
                    $this->loadCustomController($ActiveRecordType, 'edit');

                $className = "Alpha\\Model\\$ActiveRecordType";
                if (class_exists($className))
                    $this->BO = new $className();
                else
                    throw new IllegalArguementException('No ActiveRecord available to edit!');

                $this->BO->load($params['ActiveRecordOID']);

                ActiveRecord::disconnect();

                $this->BOView = View::getInstance($this->BO);

                // set up the title and meta details
                if ($this->title == '')
                    $this->setTitle('Editing a '.$ActiveRecordType);
                if ($this->description == '')
                    $this->setDescription('Page to edit a '.$ActiveRecordType.'.');
                if ($this->keywords == '')
                    $this->setKeywords('edit,'.$ActiveRecordType);

                $body .= View::displayPageHead($this);
                $body .= View::renderDeleteForm($request->getURI());
                $body .= $this->BOView->editView();
            } else {
                throw new IllegalArguementException('No ActiveRecord available to edit!');
            }
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());

            $body .= View::displayPageHead($this);
            $body .= '<p class="error"><br>Failed to load the requested record from the database!</p>';
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @param string $saveMessage Optional status message to display on successful save of the BO, otherwise default will be used
     * @since 1.0
     */
    public function doPOST($params, $saveMessage='')
    {
        self::$logger->debug('>>doPOST(params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
                self::$logger->debug('<<doPOST');
            }

            // load the business object (BO) definition
            if (isset($params['bo']) && isset($params['oid'])) {
                $BOName = $params['bo'];
                ActiveRecord::loadClassDef($BOName);

                $this->BO = new $BOName();
                $this->BO->load($params['oid']);

                $this->BOView = View::getInstance($this->BO);

                // set up the title and meta details
                $this->setTitle('Editing a '.$BOName);
                $this->setDescription('Page to edit a '.$BOName.'.');
                $this->setKeywords('edit,'.$BOName);

                echo View::displayPageHead($this);

                if (isset($params['saveBut'])) {

                    // populate the transient object from post data
                    $this->BO->populateFromPost();

                    try {
                        $this->BO->save();

                        self::$logger->action('Saved '.$BOName.' instance with OID '.$this->BO->getOID());

                        if($saveMessage == '')
                            echo View::displayUpdateMessage(get_class($this->BO).' '.$this->BO->getID().' saved successfully.');
                        else
                            echo View::displayUpdateMessage($saveMessage);
                    } catch (LockingException $e) {
                        $this->BO->reload();
                        echo View::displayErrorMessage($e->getMessage());
                    }

                    ActiveRecord::disconnect();

                    echo $this->BOView->editView();
                }

                if (!empty($params['deleteOID'])) {
                    $temp = new $BOName();
                    $temp->load($params['deleteOID']);

                    try {
                        $temp->delete();

                        self::$logger->action('Deleted '.$BOName.' instance with OID '.$params['deleteOID']);

                        ActiveRecord::disconnect();

                        echo View::displayUpdateMessage($this->BOName.' '.$params['deleteOID'].' deleted successfully.');

                        echo '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
                            'Back to List','cancelBut');
                        echo $temp->render();

                        echo '</center>';
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getMessage());
                        echo View::displayErrorMessage('Error deleting the OID ['.$params['deleteOID'].'], check the log!');
                    }
                }
            } else {
                throw new IllegalArguementException('No BO available to edit!');
            }
        } catch (SecurityException $e) {
            echo View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            echo View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        } catch (BONotFoundException $e) {
            self::$logger->warn($e->getMessage());
            echo View::displayErrorMessage('Failed to load the requested item from the database!');
        }

        echo View::displayPageFoot($this);

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

        if (isset($_SESSION['currentUser']) && ActiveRecord::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
            $menu .= View::loadTemplateFragment('html', 'adminmenu.phtml', array());
        }

        return $menu;
    }
}

?>