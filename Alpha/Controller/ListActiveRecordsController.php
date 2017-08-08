<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\View\View;
use Alpha\View\ViewState;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Model\ActiveRecord;

/**
 * Controller used to list all of the active record types in the system.
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
class ListActiveRecordsController extends Controller implements ControllerInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * the constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ListActiveRecordsController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Admin');

        // set up the title and meta details
        $this->setTitle('Listing all active records in the system');
        $this->setDescription('Page to list all active records.');
        $this->setKeywords('list,all,active,records');

        $viewState = ViewState::getInstance();
        $viewState->set('renderAdminMenu', true);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param alpha\Util\Http\Request $request
     *
     * @return alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $body = View::displayPageHead($this);

        $body .= $this->displayBodyContent();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle POST requests.
     *
     * @param alpha\Util\Http\Request $request
     *
     * @return alpha\Util\Http\Response
     *
     * @since 1.0
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doPOST($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        $body = View::displayPageHead($this);

        try {
            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            if (isset($params['createTableBut'])) {
                try {
                    $classname = $params['createTableClass'];

                    $Record = new $classname();
                    $Record->makeTable();

                    self::$logger->action('Created the table for class '.$classname);

                    $body .= View::displayUpdateMessage('The table for the class '.$classname.' has been successfully created.');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $body .= View::displayErrorMessage('Error creating the table for the class '.$classname.', check the log!');
                }
            }

            if (isset($params['createHistoryTableBut'])) {
                try {
                    $classname = $params['createTableClass'];

                    $Record = new $classname();
                    $Record->makeHistoryTable();

                    self::$logger->action('Created the history table for class '.$classname);

                    $body .= View::displayUpdateMessage('The history table for the class '.$classname.' has been successfully created.');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $body .= View::displayErrorMessage('Error creating the history table for the class '.$classname.', check the log!');
                }
            }

            if (isset($params['recreateTableClass']) && $params['admin_'.stripslashes($params['recreateTableClass']).'_button_pressed'] == 'recreateTableBut') {
                try {
                    $classname = $params['recreateTableClass'];
                    $Record = new $classname();
                    $Record->rebuildTable();

                    self::$logger->action('Recreated the table for class '.$classname);

                    $body .= View::displayUpdateMessage('The table for the class '.$classname.' has been successfully recreated.');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $body .= View::displayErrorMessage('Error recreating the table for the class '.$classname.', check the log!');
                }
            }

            if (isset($params['updateTableClass']) && $params['admin_'.stripslashes($params['updateTableClass']).'_button_pressed'] == 'updateTableBut') {
                try {
                    $classname = $params['updateTableClass'];

                    $Record = new $classname();
                    $missingFields = $Record->findMissingFields();

                    $count = count($missingFields);

                    for ($i = 0; $i < $count; ++$i) {
                        $Record->addProperty($missingFields[$i]);
                    }

                    self::$logger->action('Updated the table for class '.$classname);

                    $body .= View::displayUpdateMessage('The table for the class '.$classname.' has been successfully updated.');
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $body .= View::displayErrorMessage('Error updating the table for the class '.$classname.', check the log!');
                }
            }
        } catch (SecurityException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        }

        $body .= $this->displayBodyContent();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doPOST');

        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Private method to generate the main body HTML for this page.
     *
     * @since 1.0
     *
     * @return string
     */
    private function displayBodyContent()
    {
        $classNames = ActiveRecord::getRecordClassNames();

        $body = '';

        $fields = array('formAction' => $this->request->getURI());

        foreach ($classNames as $className) {
            try {
                $activeRecord = new $className();
                $view = View::getInstance($activeRecord);
                $body .= $view->adminView($fields);
            } catch (AlphaException $e) {
                self::$logger->error("[$classname]:".$e->getMessage());
                // its possible that the exception occured due to the table schema being out of date
                if ($activeRecord->checkTableExists() && $activeRecord->checkTableNeedsUpdate()) {
                    $missingFields = $activeRecord->findMissingFields();

                    $count = count($missingFields);

                    for ($i = 0; $i < $count; ++$i) {
                        $activeRecord->addProperty($missingFields[$i]);
                    }

                    // now try again...
                    $activeRecord = new $className();
                    $view = View::getInstance($activeRecord);
                    $body .= $view->adminView($fields);
                }
            } catch (\Exception $e) {
                self::$logger->error($e->getMessage());
                $body .= View::displayErrorMessage('Error accessing the class ['.$classname.'], check the log!');
            }
        }

        return $body;
    }
}
