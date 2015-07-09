<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\View\View;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\AlphaException;
use Alpha\Controller\Front\FrontController;
use Alpha\Model\ActiveRecord;

/**
 *
 * Controller used to list an active record, the classname for which must be supplied in GET vars
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
class ListController extends Controller implements ControllerInterface
{
    /**
     * The name of the ActiveRecord type that we will be creating
     *
     * @var string
     * @since 2.0
     */
    protected $activeRecordType;

    /**
     * The new default View object used for rendering the onjects to list
     *
     * @var Alpha\View\View
     * @since 1.0
     */
    protected $BOView;

    /**
     * The start number for list pageination
     *
     * @var integer
     * @since 1.0
     */
    protected $startPoint = 1;

    /**
     * The count of the BOs of this type in the database
     *
     * @var integer
     * @since 1.0
     */
    protected $BOCount = 0;

    /**
     * The field name to sort the list by (optional, default is OID)
     *
     * @var string
     * @since 1.0
     */
    protected $sort;

    /**
     * The order to sort the list by (optional, should be ASC or DESC, default is ASC)
     *
     * @var string
     * @since 1.0
     */
    protected $order;

    /**
     * The name of the BO field to filter the list by (optional)
     *
     * @var string
     * @since 1.0
     */
    protected $filterField;

    /**
     * The value of the filterField to filter by (optional)
     *
     * @var string
     * @since 1.0
     */
    protected $filterValue;

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
        self::$logger = new Logger('ListController');
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
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        try {
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
                $this->activeRecordType = $ActiveRecordType;
            } else {
                throw new IllegalArguementException('No ActiveRecord available to create!');
            }

            if (class_exists($ActiveRecordType))
                $this->BO = new $ActiveRecordType();
            else
                throw new IllegalArguementException('No ActiveRecord available to create!');

            $this->BOView = View::getInstance($this->BO);

            // TODO: order and sort not defined in the route in FrontController
            if (isset($params['order'])) {
                if($params['order'] == 'ASC' || $params['order'] == 'DESC')
                    $this->order = $params['order'];
                else
                    throw new IllegalArguementException('Order value ['.$params['order'].'] provided is invalid!');
            }

            if (isset($params['sort']))
                $this->sort = $params['sort'];

        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
        }

        $this->BOView = View::getInstance($this->BO);

        $body .= View::displayPageHead($this);

        $message = $this->getStatusMessage();
        if (!empty($message))
            $body .= $message;

        $body .= $this->renderBodyContent();

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doGET');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Handle DELETE requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @since 1.0
     */
    public function doDELETE($request)
    {
        self::$logger->debug('>>doDELETE($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $body = '';

        try {
            // check the hidden security fields before accepting the form data
            if (!$this->checkSecurityFields()) {
                throw new SecurityException('This page cannot accept post data from remote servers!');
                self::$logger->debug('<<doDELETE');
            }

            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = urldecode($params['ActiveRecordType']);
                $this->activeRecordType = $ActiveRecordType;
            } else {
                throw new IllegalArguementException('No ActiveRecord available to create!');
            }

            if (isset($params['order'])) {
                if ($params['order'] == 'ASC' || $params['order'] == 'DESC')
                    $this->order = $params['order'];
                else
                    throw new IllegalArguementException('Order value ['.$params['order'].'] provided is invalid!');
            }

            if (isset($params['sort']))
                $this->sort = $params['sort'];

            if (!empty($params['deleteOID'])) {
                if (!Validator::isInteger($params['deleteOID']))
                    throw new IllegalArguementException('Invalid deleteOID ['.$params['deleteOID'].'] provided on the request!');

                try {
                    $temp = new $ActiveRecordType();
                    $temp->load($params['deleteOID']);

                    ActiveRecord::begin();
                    $temp->delete();
                    self::$logger->action('Deleted an instance of '.$this->activeRecordType.' with id '.$params['deleteOID']);
                    ActiveRecord::commit();

                    $body .= View::displayPageHead($this);

                    $body .= View::displayUpdateMessage($this->activeRecordType.' '.$params['deleteOID'].' deleted successfully.');

                    $body .= $this->renderBodyContent();
                } catch (AlphaException $e) {
                    self::$logger->error($e->getMessage());
                    $body .= View::displayErrorMessage('Error deleting the BO of OID ['.$params['deleteOID'].'], check the log!');
                    ActiveRecord::rollback();
                }

                ActiveRecord::disconnect();
            }
        } catch (SecurityException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->warn($e->getMessage());
        } catch (IllegalArguementException $e) {
            $body .= View::displayErrorMessage($e->getMessage());
            self::$logger->error($e->getMessage());
        }

        $body .= View::displayPageFoot($this);

        self::$logger->debug('<<doDELETE');
        return new Response(200, $body, array('Content-Type' => 'text/html'));
    }

    /**
     * Sets up the title etc. and pagination start point
     *
     * @since 1.0
     */
    public function before_displayPageHead_callback()
    {
        // set up the title and meta details
        if (!isset($this->title))
            $this->setTitle('Listing all '.$this->activeRecordType);
        if (!isset($this->description))
            $this->setDescription('Page listing all '.$this->activeRecordType.'.');
        if (!isset($this->keywords))
            $this->setKeywords('list,all,'.$this->activeRecordType);
        // set the start point for the list pagination
        if ($this->request->getParam('start') != null)
            $this->startPoint = $this->request->getParam('start');
    }

    /**
     * Method to display the page footer with pageination links
     *
     * @return string
     * @since 1.0
     */
    public function before_displayPageFoot_callback()
    {
        $html = $this->renderPageLinks();

        $html .= '<br>';

        return $html;
    }

    /**
     * Method for rendering the pagination links
     *
     * @return string
     * @since 1.0
     * @todo review how the links are generated
     */
    protected function renderPageLinks()
    {
        $config = ConfigProvider::getInstance();

        $html = '';

        $end = (($this->startPoint-1)+$config->get('app.list.page.amount'));

        if ($end > $this->BOCount)
            $end = $this->BOCount;

        if ($this->BOCount > 0) {
            $html .= '<ul class="pagination">';
        } else {
            $html .= '<p align="center">The list is empty.&nbsp;&nbsp;</p>';

            return $html;
        }

        if ($this->startPoint > 1) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null)
                $html .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($this->startPoint-$config->get('app.list.page.amount'))).'">&lt;&lt;-Previous</a></li>';
            else
                $html .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($this->startPoint-$config->get('app.list.page.amount')).'">&lt;&lt;-Previous</a></li>';
        } elseif ($this->BOCount > $config->get('app.list.page.amount')){
            $html .= '<li class="disabled"><a href="#">&lt;&lt;-Previous</a></li>';
        }

        $page = 1;

        for ($i = 0; $i < $this->BOCount; $i+=$config->get('app.list.page.amount')) {
            if ($i != ($this->startPoint-1)) {
                // handle secure URLs
                if ($this->request->getParam('token', null) != null)
                    $html .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($i+1)).'">'.$page.'</a></li>';
                else
                    $html .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($i+1).'">'.$page.'</a></li>';
            } elseif ($this->BOCount > $config->get('app.list.page.amount')) {
                $html .= '<li class="active"><a href="#">'.$page.'</a></li>';
            }

            $page++;
        }

        if ($this->BOCount > $end) {
            // handle secure URLs
            if ($this->request->getParam('token', null) != null)
                $html .= '<li><a href="'.FrontController::generateSecureURL('act=Alpha\Controller\ListController&ActiveRecordType='.$this->activeRecordType.'&start='.($this->startPoint+$config->get('app.list.page.amount'))).'">Next-&gt;&gt;</a></li>';
            else
                $html .= '<li><a href="/listall/'.urlencode($this->activeRecordType)."/".($this->startPoint+$config->get('app.list.page.amount')).
                    '">Next-&gt;&gt;</a></li>';
        } elseif ($this->BOCount > $config->get('app.list.page.amount')) {
            $html .= '<li class="disabled"><a href="#">Next-&gt;&gt;</a></li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Method to display the main body HTML for this page
     *
     * @return string
     * @since 1.0
     */
    protected function renderBodyContent()
    {
        $config = ConfigProvider::getInstance();

        $body = '';

        // get all of the BOs and invoke the listView on each one
        $className = $this->activeRecordType;
        $temp = new $className;

        if (isset($this->filterField) && isset($this->filterValue)) {
            if (isset($this->sort) && isset($this->order)) {
                $objects = $temp->loadAllByAttribute($this->filterField, $this->filterValue, $this->startPoint-1, $config->get('app.list.page.amount'),
                    $this->sort, $this->order);
            } else {
                $objects = $temp->loadAllByAttribute($this->filterField, $this->filterValue, $this->startPoint-1, $config->get('app.list.page.amount'));
            }

            $this->BOCount = $temp->getCount(array($this->filterField), array($this->filterValue));
        } else {
            if (isset($this->sort) && isset($this->order))
                $objects = $temp->loadAll($this->startPoint-1, $config->get('app.list.page.amount'), $this->sort, $this->order);
            else
                $objects = $temp->loadAll($this->startPoint-1, $config->get('app.list.page.amount'));

            $this->BOCount = $temp->getCount();
        }

        ActiveRecord::disconnect();

        $body .= View::renderDeleteForm($this->request->getURI());

        foreach ($objects as $object) {
            $temp = View::getInstance($object);
            $fields = array('formAction' => $this->request->getURI());
            $body .= $temp->listView($fields);
        }

        return $body;
    }

    /**
     * Use this callback to inject in the admin menu template fragment
     *
     * @since 1.2
     */
    public function after_displayPageHead_callback()
    {
        $menu = View::loadTemplateFragment('html', 'adminmenu.phtml', array());

        return $menu;
    }
}

?>