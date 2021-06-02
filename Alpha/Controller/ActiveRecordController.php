<?php

namespace Alpha\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Helper\Validator;
use Alpha\View\View;
use Alpha\View\ViewState;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ValidationException;
use Alpha\Model\ActiveRecord;

/**
 * The main active record CRUD controller for the framework.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class ActiveRecordController extends Controller implements ControllerInterface
{
    /**
     * The start number for list pageination.
     *
     * @var int
     *
     * @since 2.0
     */
    protected $start = 0;

    /**
     * The amount of records to return during pageination.
     *
     * @var int
     *
     * @since 2.0
     */
    protected $limit;

    /**
     * The count of the records of this type in the database (used during pagination).
     *
     * @var int
     *
     * @since 2.0
     */
    protected $recordCount = 0;

    /**
     * The field name to sort the list by (optional, default is ID).
     *
     * @var string
     *
     * @since 2.0
     */
    protected $sort;

    /**
     * The order to sort the list by (optional, should be ASC or DESC, default is ASC).
     *
     * @var string
     *
     * @since 2.0
     */
    protected $order;

    /**
     * The name of the Record field to filter the list by (optional).
     *
     * @var string
     *
     * @since 2.0
     */
    protected $filterField;

    /**
     * The value of the filterField to filter by (optional).
     *
     * @var string
     *
     * @since 2.0
     */
    protected $filterValue;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 2.0
     */
    private static $logger = null;

    /**
     * Constructor to set up the object.
     *
     * @param string $visibility The name of the rights group that can access this controller.
     *
     * @since 1.0
     */
    public function __construct(string $visibility = 'Admin')
    {
        self::$logger = new Logger('ActiveRecordController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 2.0
     */
    public function doGET(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doGET(request=['.var_export($request, true).'])');

        $params = $request->getParams();
        $accept = $request->getAccept();

        $body = '';

        try {
            // get a single record
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordID'])) {
                $body .= $this->renderRecord($params, $accept);
            } elseif (isset($params['ActiveRecordType']) && isset($params['start'])) {
                // list all records of this type
                $body .= $this->renderRecords($params, $accept);
            } elseif (isset($params['ActiveRecordType'])) {
                // create a new record of this type
                $ActiveRecordType = urldecode($params['ActiveRecordType']);

                if (class_exists($ActiveRecordType)) {
                    $record = new $ActiveRecordType();
                } else {
                    throw new IllegalArguementException('No ActiveRecord available to create!');
                }

                // set up the title and meta details
                if (!isset($this->title)) {
                    $this->setTitle('Create a new '.$record->getFriendlyClassName());
                }
                if (!isset($this->description)) {
                    $this->setDescription('Create a new '.$record->getFriendlyClassName().'.');
                }
                if (!isset($this->keywords)) {
                    $this->setKeywords('create,new,'.$record->getFriendlyClassName());
                }

                $view = View::getInstance($record, false, $accept);

                $body .= View::displayPageHead($this);
                $fields = array('formAction' => $this->request->getURI());
                $body .= $view->createView($fields);
            } else {
                throw new IllegalArguementException('No ActiveRecord available to display!');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');

        return new Response(200, $body, array('Content-Type' => ($accept == 'application/json' ? 'application/json' : 'text/html')));
    }

    /**
     * Method to handle POST requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\SecurityException
     *
     * @since 2.0
     */
    public function doPOST(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doDPOST(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();
        $accept = $request->getAccept();

        try {
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
            } else {
                throw new IllegalArguementException('No ActiveRecord available to create!');
            }

            if (class_exists($ActiveRecordType)) {
                $record = new $ActiveRecordType();
            } else {
                throw new IllegalArguementException('No ActiveRecord ['.$ActiveRecordType.'] available to create!');
            }

            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            $record->populateFromArray($params);

            try {
                $record->save();
            } catch (ValidationException $e) {
                self::$logger->warn($e->getMessage());
                $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            }

            self::$logger->action('Created new '.$ActiveRecordType.' instance with ID '.$record->getID());

            if (isset($params['statusMessage'])) {
                $this->setStatusMessage(View::displayUpdateMessage($params['statusMessage']));
            } else {
                $this->setStatusMessage(View::displayUpdateMessage('Created'));
            }

            ActiveRecord::disconnect();

            if ($accept == 'application/json') {
                $view = View::getInstance($record, false, $accept);
                $body = $view->detailedView();
                $response = new Response(201);
                $response->setHeader('Content-Type', 'application/json');
                $response->setHeader('Location', $config->get('app.url').'/record/'.$params['ActiveRecordType'].'/'.$record->getID());
                $response->setBody($body);

                self::$logger->debug('<<doPOST');

                return $response;
            } else {
                $response = new Response(301);

                if ($this->getNextJob() != '') {
                    $response->redirect($this->getNextJob());
                } else {
                    if ($this->request->isSecureURI()) {
                        $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.$ActiveRecordType.'&ActiveRecordID='.$record->getID()));
                    } else {
                        $response->redirect($config->get('app.url').'/record/'.$params['ActiveRecordType'].'/'.$record->getID());
                    }
                }

                self::$logger->debug('<<doPOST');

                return $response;
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        }
    }

    /**
     * Method to handle PUT requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\SecurityException
     *
     * @since 2.0
     */
    public function doPUT(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doPUT(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();
        $accept = $request->getAccept();

        try {
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
            } else {
                throw new IllegalArguementException('No ActiveRecord available to edit!');
            }

            if (class_exists($ActiveRecordType)) {
                $record = new $ActiveRecordType();
            } else {
                throw new IllegalArguementException('No ActiveRecord ['.$ActiveRecordType.'] available to edit!');
            }

            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            $record->load($params['ActiveRecordID']);
            $record->populateFromArray($params);

            try {
                $record->save();
                $this->record = $record;
            } catch (ValidationException $e) {
                self::$logger->warn($e->getMessage());
                $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
            }

            self::$logger->action('Saved '.$ActiveRecordType.' instance with ID '.$record->getID());

            if (isset($params['statusMessage'])) {
                $this->setStatusMessage(View::displayUpdateMessage($params['statusMessage']));
            } else {
                $this->setStatusMessage(View::displayUpdateMessage('Saved'));
            }

            ActiveRecord::disconnect();

            if ($accept == 'application/json') {
                $view = View::getInstance($record, false, $accept);
                $body = $view->detailedView();
                $response = new Response(200);
                $response->setHeader('Content-Type', 'application/json');
                $response->setHeader('Location', $config->get('app.url').'/record/'.$params['ActiveRecordType'].'/'.$record->getID());
                $response->setBody($body);
            } else {
                $response = new Response(301);

                if ($this->getNextJob() != '') {
                    $response->redirect($this->getNextJob());
                } else {
                    if ($this->request->isSecureURI()) {
                        $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.urldecode($params['ActiveRecordType']).'&ActiveRecordID='.$record->getID().'&view=edit'));
                    } else {
                        $response->redirect($config->get('app.url').'/record/'.$params['ActiveRecordType'].'/'.$record->getID().'/edit');
                    }
                }
            }

            self::$logger->debug('<<doPUT');

            return $response;
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        }
    }

    /**
     * Method to handle DELETE requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\SecurityException
     * @throws \Alpha\Exception\ResourceNotAllowedException
     *
     * @since 2.0
     */
    public function doDELETE(\Alpha\Util\Http\Request $request): \Alpha\Util\Http\Response
    {
        self::$logger->debug('>>doDELETE(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();
        $accept = $request->getAccept();

        try {
            // check the hidden security fields before accepting the form data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept data from remote servers!');
            }

            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
            } else {
                throw new IllegalArguementException('No ActiveRecord available to edit!');
            }

            if (class_exists($ActiveRecordType)) {
                $record = new $ActiveRecordType();
            } else {
                throw new IllegalArguementException('No ActiveRecord ['.$ActiveRecordType.'] available to edit!');
            }

            // check the hidden security fields before accepting the form POST data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
            }

            $record->load($params['ActiveRecordID']);

            ActiveRecord::begin();
            $record->delete();
            ActiveRecord::commit();
            ActiveRecord::disconnect();

            self::$logger->action('Deleted '.$ActiveRecordType.' instance with ID '.$params['ActiveRecordID']);

            if ($accept == 'application/json') {
                $response = new Response(200);
                $response->setHeader('Content-Type', 'application/json');
                $response->setBody(json_encode(array('message' => 'deleted')));
            } else {
                $response = new Response(301);

                if (isset($params['statusMessage'])) {
                    $this->setStatusMessage(View::displayUpdateMessage($params['statusMessage']));
                } else {
                    $this->setStatusMessage(View::displayUpdateMessage('Deleted'));
                }

                if ($this->getNextJob() != '') {
                    $response->redirect($this->getNextJob());
                } else {
                    if ($this->request->isSecureURI()) {
                        $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.$ActiveRecordType.'&start=0&limit='.$config->get('app.list.page.amount')));
                    } else {
                        $response->redirect($config->get('app.url').'/records/'.$params['ActiveRecordType']);
                    }
                }
            }

            self::$logger->debug('<<doDELETE');

            return $response;
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        } catch (AlphaException $e) {
            self::$logger->error($e->getMessage());
            ActiveRecord::rollback();
            throw new ResourceNotAllowedException($e->getMessage());
        }
    }

    /**
     * Sets up the pagination start point and limit.
     *
     * @since 2.0
     */
    public function afterDisplayPageHead(): string
    {
        $body = parent::afterDisplayPageHead();

        // set the start point for the list pagination
        if ($this->request->getParam('start') != null) {
            $this->start = $this->request->getParam('start');

            $viewState = ViewState::getInstance();
            $viewState->set('selectedStart', $this->start);

            if ($this->request->getParam('limit') != null) {
                $this->limit = $this->request->getParam('limit');
            } else {
                $config = ConfigProvider::getInstance();
                $this->limit = $config->get('app.list.page.amount');
            }

            $accept = $this->request->getAccept();

            if ($accept == 'application/json') {
                $body .= '[';
            }
        }

        return $body;
    }

    /**
     * Method to display the page footer with pageination links.
     *
     * @since 2.0
     */
    public function beforeDisplayPageFoot(): string
    {
        $body = '';

        if ($this->request->getParam('start') != null) {
            $accept = $this->request->getAccept();

            if ($accept == 'application/json') {
                $body .= ']';
            } else {
                $body .= View::displayPageLinks($this);
                $body .= '<br>';
            }
        }

        return $body;
    }

    /**
     * Load the requested record and render the HTML or JSON for it.
     *
     * @param array $params The request params
     * @param string|null $accept The HTTP accept heard value
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 3.0
     */
    private function renderRecord(array $params, string|null $accept): string
    {
        if (!Validator::isInteger($params['ActiveRecordID'])) {
            throw new IllegalArguementException('Invalid oid ['.$params['ActiveRecordID'].'] provided on the request!');
        }

        $ActiveRecordType = urldecode($params['ActiveRecordType']);

        if (class_exists($ActiveRecordType)) {
            $record = new $ActiveRecordType();
        } else {
            throw new IllegalArguementException('No ActiveRecord available to view!');
        }

        // set up the title and meta details
        if (isset($params['view']) && $params['view'] == 'edit') {
            if (!isset($this->title)) {
                $this->setTitle('Editing a '.$record->getFriendlyClassName());
            }
            if (!isset($this->description)) {
                $this->setDescription('Page to edit a '.$record->getFriendlyClassName().'.');
            }
            if (!isset($this->keywords)) {
                $this->setKeywords('edit,'.$record->getFriendlyClassName());
            }
        } else {
            if (!isset($this->title)) {
                $this->setTitle('Viewing a '.$record->getFriendlyClassName());
            }
            if (!isset($this->description)) {
                $this->setDescription('Page to view a '.$record->getFriendlyClassName().'.');
            }
            if (!isset($this->keywords)) {
                $this->setKeywords('view,'.$record->getFriendlyClassName());
            }
        }

        $record->load($params['ActiveRecordID']);
        ActiveRecord::disconnect();

        $view = View::getInstance($record, false, $accept);

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $body .= View::renderDeleteForm($this->request->getURI());

        if (isset($params['view']) && $params['view'] == 'edit') {
            $fields = array('formAction' => $this->request->getURI());
            $body .= $view->editView($fields);
        } else {
            $body .= $view->detailedView();
        }

        return $body;
    }

    /**
     * Load all records of the type requested and render the HTML or JSON for them.
     *
     * @param array $params The request params
     * @param string|null $accept The HTTP accept heard value
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 3.0
     */
    private function renderRecords(array $params, string|null $accept): string
    {
        $ActiveRecordType = urldecode($params['ActiveRecordType']);

        if (class_exists($ActiveRecordType)) {
            $record = new $ActiveRecordType();
        } else {
            throw new IllegalArguementException('No ActiveRecord available to view!');
        }

        // set up the title and meta details
        if (!isset($this->title)) {
            $this->setTitle('Listing all '.$record->getFriendlyClassName());
        }
        if (!isset($this->description)) {
            $this->setDescription('Listing all '.$record->getFriendlyClassName());
        }
        if (!isset($this->keywords)) {
            $this->setKeywords('list,all,'.$record->getFriendlyClassName());
        }

        if (isset($this->filterField) && isset($this->filterValue)) {
            if (isset($this->sort) && isset($this->order)) {
                $records = $record->loadAllByAttribute(
                    $this->filterField,
                    $this->filterValue,
                    $params['start'],
                    $params['limit'],
                    $this->sort,
                    $this->order
                );
            } else {
                $records = $record->loadAllByAttribute($this->filterField, $this->filterValue, $params['start'], $params['limit']);
            }

            $this->recordCount = $record->getCount(array($this->filterField), array($this->filterValue));
        } else {
            if (isset($this->sort) && isset($this->order)) {
                $records = $record->loadAll($params['start'], $params['limit'], $this->sort, $this->order);
            } else {
                $records = $record->loadAll($params['start'], $params['limit']);
            }

            $this->recordCount = $record->getCount();
        }

        ActiveRecord::disconnect();

        $view = View::getInstance($record, false, $accept);

        $body = View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message)) {
            $body .= $message;
        }

        $body .= View::renderDeleteForm($this->request->getURI());

        foreach ($records as $record) {
            $view = View::getInstance($record, false, $accept);
            $fields = array('formAction' => $this->request->getURI());
            $body .= $view->listView($fields);
        }

        if ($accept == 'application/json') {
            $body = rtrim($body, ',');
        }

        return $body;
    }

    /**
     * Get the pagination start point
     *
     * @since 3.0
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Get the pagination record count
     *
     * @since 3.0
     */
    public function getRecordCount(): int
    {
        return $this->recordCount;
    }

    /**
     * Get the pagination limit
     *
     * @since 3.0
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
