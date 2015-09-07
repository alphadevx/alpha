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
use Alpha\Model\ActiveRecord;

/**
 * The main active record CRUD controller for the framework.
 *
 * @since 2.0
 *
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
     * The field name to sort the list by (optional, default is OID).
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
     * The name of the BO field to filter the list by (optional).
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
     * @var Alpha\Util\Logging\Logger
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
    public function __construct($visibility = 'Admin')
    {
        self::$logger = new Logger('ActiveRecordController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();
        $accept = $request->getAccept();

        $body = '';

        try {
            // get a single record
            if (isset($params['ActiveRecordType']) && isset($params['ActiveRecordOID'])) {
                if (!Validator::isInteger($params['ActiveRecordOID'])) {
                    throw new IllegalArguementException('Invalid oid ['.$params['ActiveRecordOID'].'] provided on the request!');
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

                $record->load($params['ActiveRecordOID']);
                ActiveRecord::disconnect();

                $view = View::getInstance($record, false, $accept);

                $body .= View::displayPageHead($this);

                $message = $this->getStatusMessage();
                if (!empty($message)) {
                    $body .= $message;
                }

                $body .= View::renderDeleteForm($request->getURI());

                if (isset($params['view']) && $params['view'] == 'edit') {
                    $fields = array('formAction' => $this->request->getURI());
                    $body .= $view->editView($fields);
                } else {
                    $body .= $view->detailedView();
                }
            } elseif (isset($params['ActiveRecordType']) && isset($params['start'])) {
                // list all records of this type
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
                        $records = $record->loadAllByAttribute($this->filterField, $this->filterValue, $params['start'], $params['limit'],
                            $this->sort, $this->order);
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

                $body .= View::displayPageHead($this);
                $body .= View::renderDeleteForm($this->request->getURI());

                foreach ($records as $record) {
                    $view = View::getInstance($record, false, $accept);
                    $fields = array('formAction' => $this->request->getURI());
                    $body .= $view->listView($fields);
                }

                if ($accept == 'application/json') {
                    $body = rtrim($body, ',');
                }
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
     * @param Alpha\Util\Http\Request $request
     *
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     *
     * @todo implement
     */
    public function doPOST($request)
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
            $record->save();

            self::$logger->action('Created new '.$ActiveRecordType.' instance with OID '.$record->getOID());

            ActiveRecord::disconnect();
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        } catch (ValidationException $e) {
            self::$logger->warn($e->getMessage().', query ['.$record->getLastQuery().']');
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
        }

        if ($accept == 'application/json') {
            $view = View::getInstance($record, false, $accept);
            $body = $view->detailedView();
            $response = new Response(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Location', $config->get('app.url').'record/'.$params['ActiveRecordType'].'/'.$record->getOID());
            $response->setBody($body);
        } else {
            $response = new Response(301);

            if ($this->getNextJob() != '') {
                $response->redirect($this->getNextJob());
            } else {
                if ($this->request->isSecureURI()) {
                    $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.$ActiveRecordType.'&ActiveRecordOID='.$record->getOID()));
                } else {
                    $response->redirect($config->get('app.url').'record/'.$params['ActiveRecordType'].'/'.$record->getOID());
                }
            }
        }

        self::$logger->debug('<<doPOST');

        return $response;
    }

    /**
     * Method to handle PUT requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     *
     * @todo implement
     */
    public function doPUT($request)
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

            $record->load($params['ActiveRecordOID']);
            $record->populateFromArray($params);
            $record->save();

            self::$logger->action('Saved '.$ActiveRecordType.' instance with OID '.$record->getOID());

            $this->setStatusMessage(View::displayUpdateMessage('Saved '.$ActiveRecordType.' instance with OID '.$record->getOID()));

            ActiveRecord::disconnect();
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The record that you have requested cannot be found!');
        } catch (ValidationException $e) {
            self::$logger->warn($e->getMessage().', query ['.$record->getLastQuery().']');
            $this->setStatusMessage(View::displayErrorMessage($e->getMessage()));
        }

        if ($accept == 'application/json') {
            $view = View::getInstance($record, false, $accept);
            $body = $view->detailedView();
            $response = new Response(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Location', $config->get('app.url').'record/'.$params['ActiveRecordType'].'/'.$record->getOID());
            $response->setBody($body);
        } else {
            $response = new Response(301);

            if ($this->getNextJob() != '') {
                $response->redirect($this->getNextJob());
            } else {
                if ($this->request->isSecureURI()) {
                    $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.$ActiveRecordType.'&ActiveRecordOID='.$record->getOID().'&view=edit'));
                } else {
                    $response->redirect($config->get('app.url').'record/'.$params['ActiveRecordType'].'/'.$record->getOID().'/edit');
                }
            }
        }

        self::$logger->debug('<<doPUT');

        return $response;
    }

    /**
     * Method to handle DELETE requests.
     *
     * @param Alpha\Util\Http\Request $request
     *
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     *
     * @return Alpha\Util\Http\Response
     *
     * @since 2.0
     *
     * @todo implement
     */
    public function doDELETE($request)
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

            $record->load($params['ActiveRecordOID']);

            ActiveRecord::begin();
            $record->delete();
            ActiveRecord::commit();
            ActiveRecord::disconnect();

            self::$logger->action('Deleted '.$ActiveRecordType.' instance with OID '.$params['ActiveRecordOID']);

            if ($accept == 'application/json') {
                $response = new Response(200);
                $response->setHeader('Content-Type', 'application/json');
                $response->setBody(json_encode(array('message' => 'deleted')));
            } else {
                $response = new Response(301);

                $this->setStatusMessage(View::displayUpdateMessage('Deleted '.$ActiveRecordType.' instance with OID '.$params['ActiveRecordOID']));

                if ($this->getNextJob() != '') {
                    $response->redirect($this->getNextJob());
                } else {
                    if ($this->request->isSecureURI()) {
                        $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType='.$ActiveRecordType.'&start=0&limit='.$config->get('app.list.page.amount')));
                    } else {
                        $response->redirect($config->get('app.url').'records/'.$params['ActiveRecordType']);
                    }
                }
            }
        } catch (SecurityException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotAllowedException($e->getMessage());
        } catch (RecordNotFoundException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('The item that you have requested cannot be found!');
        } catch (AlphaException $e) {
            self::$logger->error($e->getMessage());
            $body .= View::displayErrorMessage('Error deleting the BO of OID ['.$params['ActiveRecordOID'].'], check the log!');
            ActiveRecord::rollback();
        }

        self::$logger->debug('<<doDELETE');

        return $response;
    }

    /**
     * Sets up the pagination start point and limit.
     *
     * @since 2.0
     */
    public function after_displayPageHead_callback()
    {
        $body = parent::after_displayPageHead_callback();

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
     * @return string
     *
     * @since 2.0
     */
    public function before_displayPageFoot_callback()
    {
        $body = '';

        if ($this->request->getParam('start') != null) {
            $accept = $this->request->getAccept();

            if ($accept == 'application/json') {
                $body .= ']';
            } else {
                $body .= $this->renderPageLinks();
                $body .= '<br>';
            }
        }

        return $body;
    }

    /**
     * Method for rendering the pagination links.
     *
     * @return string
     *
     * @since 2.0
     *
     * @todo review how the links are generated
     */
    protected function renderPageLinks()
    {
        $config = ConfigProvider::getInstance();

        $body = '';

        // the index of the last record displayed on this page
        $last = $this->start + $config->get('app.list.page.amount');

        // ensure that the last index never overruns the total record count
        if ($last > $this->recordCount) {
            $last = $this->recordCount;
        }

        // render a message for an empty list
        if ($this->recordCount > 0) {
            $body .= '<ul class="pagination">';
        } else {
            $body .= '<p align="center">The list is empty.&nbsp;&nbsp;</p>';

            return $body;
        }

        // render "Previous" link
        if ($this->start > 0) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null) {
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$this->request->getParam('ActiveRecordType').'&start='.($this->start - $this->limit).'&limit='.$this->limit).'">&lt;&lt;-Previous</a></li>';
            } else {
                $body .= '<li><a href="/records/'.urlencode($this->request->getParam('ActiveRecordType')).'/'.($this->start - $this->limit).'/'.$this->limit.'">&lt;&lt;-Previous</a></li>';
            }
        } elseif ($this->recordCount > $this->limit) {
            $body .= '<li class="disabled"><a href="#">&lt;&lt;-Previous</a></li>';
        }

        // render the page index links
        if ($this->recordCount > $this->limit) {
            $page = 1;

            for ($i = 0; $i < $this->recordCount; $i += $this->limit) {
                if ($i != $this->start) {
                    // handle secure URLs
                    if ($this->request->getParam('token', null) != null) {
                        $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$this->request->getParam('ActiveRecordType').'&start='.$i.'&limit='.$this->limit).'">'.$page.'</a></li>';
                    } else {
                        $body .= '<li><a href="/records/'.urlencode($this->request->getParam('ActiveRecordType')).'/'.$i.'/'.$this->limit.'">'.$page.'</a></li>';
                    }
                } elseif ($this->recordCount > $this->limit) { // render an anchor for the current page
                    $body .= '<li class="active"><a href="#">'.$page.'</a></li>';
                }

                ++$page;
            }
        }

        // render "Next" link
        if ($this->recordCount > $last) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null) {
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ActiveRecordController&ActiveRecordType='.$this->request->getParam('ActiveRecordType').'&start='.($this->start + $this->limit).'&limit='.$this->limit).'">Next-&gt;&gt;</a></li>';
            } else {
                $body .= '<li><a href="/records/'.urlencode($this->request->getParam('ActiveRecordType')).'/'.($this->start + $this->limit.'/'.$this->limit).
                    '">Next-&gt;&gt;</a></li>';
            }
        } elseif ($this->recordCount > $this->limit) {
            $body .= '<li class="disabled"><a href="#">Next-&gt;&gt;</a></li>';
        }

        $body .= '</ul>';

        return $body;
    }
}
