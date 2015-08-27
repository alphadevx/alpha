<?php

namespace Alpha\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Helper\Validator;
use Alpha\View\View;
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
class ActiveRecordController extends Controller implements ControllerInterface
{
    /**
     * The start number for list pageination
     *
     * @var integer
     * @since 2.0
     */
    protected $startPoint = 1;

    /**
     * The count of the records of this type in the database (used during pagination)
     *
     * @var integer
     * @since 2.0
     */
    protected $recordCount = 0;

    /**
     * The field name to sort the list by (optional, default is OID)
     *
     * @var string
     * @since 2.0
     */
    protected $sort;

    /**
     * The order to sort the list by (optional, should be ASC or DESC, default is ASC)
     *
     * @var string
     * @since 2.0
     */
    protected $order;

    /**
     * The name of the BO field to filter the list by (optional)
     *
     * @var string
     * @since 2.0
     */
    protected $filterField;

    /**
     * The value of the filterField to filter by (optional)
     *
     * @var string
     * @since 2.0
     */
    protected $filterValue;

    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 2.0
     */
    private static $logger = null;

    /**
     * Constructor to set up the object
     *
     * @param string $visibility The name of the rights group that can access this controller.
     * @since 1.0
     */
    public function __construct($visibility='Admin')
    {
        self::$logger = new Logger('ActiveRecordController');
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

                $record->load($params['ActiveRecordOID']);
                ActiveRecord::disconnect();

                $view = View::getInstance($record, false, $accept);

                $body .= View::displayPageHead($this);
                $body .= View::renderDeleteForm($request->getURI());
                $body .= $view->detailedView();
            } elseif (isset($params['ActiveRecordType'])) {
                // list all records of this type
                $ActiveRecordType = urldecode($params['ActiveRecordType']);

                if (class_exists($ActiveRecordType)) {
                    $record = new $ActiveRecordType();
                } else {
                    throw new IllegalArguementException('No ActiveRecord available to view!');
                }

                if (isset($this->filterField) && isset($this->filterValue)) {
                    if (isset($this->sort) && isset($this->order)) {
                        $records = $record->loadAllByAttribute($this->filterField, $this->filterValue, $params['start'], $params['limit'],
                            $this->sort, $this->order);
                    } else {
                        $records = $record->loadAllByAttribute($this->filterField, $this->filterValue, $params['start'], $params['limit']);
                    }

                    $this->BOCount = $record->getCount(array($this->filterField), array($this->filterValue));
                } else {
                    if (isset($this->sort) && isset($this->order)) {
                        $records = $record->loadAll($params['start'], $params['limit'], $this->sort, $this->order);
                    } else {
                        $records = $record->loadAll($params['start'], $params['limit']);
                    }

                    $this->BOCount = $record->getCount();
                }

                ActiveRecord::disconnect();

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
        return new Response(200, $body, array('Content-Type' => ($accept == 'application/json' ? 'application/json' : 'text/html')));
    }

    /**
     * Method to handle POST requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doPOST($request)
    {
        self::$logger->debug('>>doDPOST(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doPOST');
        return new Response(201, $body, array('Content-Type' => 'application/json'));
    }

    /**
     * Method to handle PUT requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doPUT');
        return new Response(200, $body, array('Content-Type' => 'application/json'));
    }

    /**
     * Method to handle DELETE requests
     *
     * @param Alpha\Util\Http\Request $request
     * @throws Alpha\Exception\IllegalArguementException
     * @throws Alpha\Exception\SecurityException
     * @return Alpha\Util\Http\Response
     * @since 2.0
     * @todo implement
     */
    public function doDELETE($request)
    {
        self::$logger->debug('>>doDELETE(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $body = '';

        self::$logger->debug('<<doDELETE');
        return new Response(200, $body, array('Content-Type' => 'application/json'));
    }

    /**
     * Sets up the pagination start point
     *
     * @since 2.0
     */
    public function after_displayPageHead_callback()
    {
        // set the start point for the list pagination
        if ($this->request->getParam('start') != null) {
            $this->startPoint = $this->request->getParam('start');

            $accept = $this->request->getAccept();

            if ($accept == 'application/json') {
                return '[';
            }
        }
    }

    /**
     * Method to display the page footer with pageination links
     *
     * @return string
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
     * Method for rendering the pagination links
     *
     * @return string
     * @since 2.0
     * @todo review how the links are generated
     */
    protected function renderPageLinks()
    {
        $config = ConfigProvider::getInstance();

        $body = '';

        $end = (($this->startPoint-1)+$config->get('app.list.page.amount'));

        if ($end > $this->recordCount) {
            $end = $this->recordCount;
        }

        if ($this->recordCount > 0) {
            $body .= '<ul class="pagination">';
        } else {
            $body .= '<p align="center">The list is empty.&nbsp;&nbsp;</p>';

            return $body;
        }

        if ($this->startPoint > 1) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null)
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($this->startPoint-$config->get('app.list.page.amount'))).'">&lt;&lt;-Previous</a></li>';
            else
                $body .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($this->startPoint-$config->get('app.list.page.amount')).'">&lt;&lt;-Previous</a></li>';
        } elseif ($this->recordCount > $config->get('app.list.page.amount')){
            $body .= '<li class="disabled"><a href="#">&lt;&lt;-Previous</a></li>';
        }

        $page = 1;

        for ($i = 0; $i < $this->recordCount; $i+=$config->get('app.list.page.amount')) {
            if ($i != ($this->startPoint-1)) {
                // handle secure URLs
                if ($this->request->getParam('token', null) != null)
                    $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($i+1)).'">'.$page.'</a></li>';
                else
                    $body .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($i+1).'">'.$page.'</a></li>';
            } elseif ($this->recordCount > $config->get('app.list.page.amount')) {
                $body .= '<li class="active"><a href="#">'.$page.'</a></li>';
            }

            $page++;
        }

        if ($this->recordCount > $end) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null)
                $body .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($this->startPoint+$config->get('app.list.page.amount'))).'">Next-&gt;&gt;</a></li>';
            else
                $body .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($this->startPoint+$config->get('app.list.page.amount')).
                    '">Next-&gt;&gt;</a></li>';
        } elseif ($this->recordCount > $config->get('app.list.page.amount')) {
            $body .= '<li class="disabled"><a href="#">Next-&gt;&gt;</a></li>';
        }

        $body .= '</ul>';

        return $body;
    }
}

?>