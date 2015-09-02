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
use Alpha\Controller\Front\FrontController;

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

        try {
            // load the business object (BO) definition
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
                $this->activeRecordType = $ActiveRecordType;

                if (class_exists($ActiveRecordType))
                    $this->BO = new $ActiveRecordType();
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
                $fields = array('formAction' => $request->getURI());
                $body .= $this->BOView->editView($fields);
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
     * Handle DELETE requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doDELETE($request)
    {
        self::$logger->debug('>>doDELETE(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        try {
            // check the hidden security fields before accepting the form data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept data from remote servers!');
                self::$logger->debug('<<doDELETE');
            }

            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
                $this->activeRecordType = $ActiveRecordType;

                if (class_exists($ActiveRecordType))
                    $this->BO = new $ActiveRecordType();
                else
                    throw new IllegalArguementException('No ActiveRecord available to edit!');

                $this->BO->load($params['ActiveRecordOID']);

                $this->BOView = View::getInstance($this->BO);

                // set up the title and meta details
                $this->setTitle('Editing a '.$ActiveRecordType);
                $this->setDescription('Page to edit a '.$ActiveRecordType.'.');
                $this->setKeywords('edit,'.$ActiveRecordType);

                $body .= View::displayPageHead($this);

                if (isset($params['deleteOID'])) {
                    $temp = new $ActiveRecordType();
                    $temp->load($params['deleteOID']);

                    try {
                        $temp->delete();

                        self::$logger->action('Deleted '.$ActiveRecordType.' instance with OID '.$params['deleteOID']);

                        ActiveRecord::disconnect();

                        $body .= View::displayUpdateMessage($this->activeRecordType.' '.$params['deleteOID'].' deleted successfully.');

                        $body .= '<center>';

                        $temp = new Button("document.location = '".FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.get_class($this->BO))."'",
                            'Back to List','cancelBut');
                        $body .= $temp->render();

                        $body .= '</center>';
                    } catch (AlphaException $e) {
                        self::$logger->error($e->getMessage());
                        $body .= View::displayErrorMessage('Error deleting the OID ['.$params['deleteOID'].'], check the log!');
                    }
                }
            } else {
                throw new IllegalArguementException('No active record type available to edit!');
            }
        } catch (SecurityException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            $body .= View::displayErrorMessage('Failed to load the requested item from the database!');
            self::$logger->warn($e->getMessage());
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doDELETE');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle PUT requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        try {
            // check the hidden security fields before accepting the form data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept put data from remote servers!');
                self::$logger->debug('<<doPUT');
            }

            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                $ActiveRecordType = $params['ActiveRecordType'];
                $this->activeRecordType = $ActiveRecordType;

                if (class_exists($ActiveRecordType))
                    $this->BO = new $ActiveRecordType();
                else
                    throw new IllegalArguementException('No ActiveRecord available to edit!');

                $this->BO->load($params['ActiveRecordOID']);

                $this->BOView = View::getInstance($this->BO);

                // set up the title and meta details
                $this->setTitle('Editing a '.$ActiveRecordType);
                $this->setDescription('Page to edit a '.$ActiveRecordType.'.');
                $this->setKeywords('edit,'.$ActiveRecordType);

                $body .= View::displayPageHead($this);

                if (isset($params['saveBut'])) {

                    // populate the transient object from the request
                    $this->BO->populateFromArray($params);

                    try {
                        $this->BO->save();

                        self::$logger->action('Saved '.$ActiveRecordType.' instance with OID '.$this->BO->getOID());

                        $body .= View::displayUpdateMessage(get_class($this->BO).' '.$this->BO->getID().' saved successfully.');

                    } catch (LockingException $e) {
                        $this->BO->reload();
                        $body .= View::displayErrorMessage($e->getMessage());
                    }

                    ActiveRecord::disconnect();

                    $fields = array('formAction' => $request->getURI());
                    $body .= $this->BOView->editView($fields);
                }
            } else {
                throw new IllegalArguementException('No active record type available to edit!');
            }
        } catch (SecurityException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        } catch (RecordNotFoundException $e) {
            $body .= View::displayErrorMessage('Failed to load the requested item from the database!');
            self::$logger->warn($e->getMessage());
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doPUT');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }
}

?>