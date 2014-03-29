<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 *
 * Controller used to list a BO, which must be supplied in GET vars
 *
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2014, John Collins (founder of Alpha Framework).
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
class ListAll extends AlphaController implements AlphaControllerInterface {
	/**
	 * The name of the BO
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $BOname;

	/**
	 * The new default AlphaView object used for rendering the onjects to list
	 *
	 * @var AlphaView
	 * @since 1.0
	 */
	protected $BOView;

	/**
	 * The start number for list pageination
	 *
	 * @var integer
	 * @since 1.0
	 */
	protected $startPoint;

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
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Constructor to set up the object
	 *
	 * @param string $visibility
	 * @since 1.0
	 */
	public function __construct($visibility='Admin') {
		self::$logger = new Logger('ListAll');
		self::$logger->debug('>>__construct()');

		global $config;

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct($visibility);

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

		try{
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to list!');
			}

			if (isset($params['order'])) {
				if($params['order'] == 'ASC' || $params['order'] == 'DESC')
					$this->order = $params['order'];
				else
					throw new IllegalArguementException('Order value ['.$params['order'].'] provided is invalid!');
			}

			if (isset($params['sort']))
				$this->sort = $params['sort'];

			AlphaDAO::loadClassDef($BOname);

			/*
			 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
			 */
			if($this->getCustomControllerName($BOname, 'list') != null)
				$this->loadCustomController($BOname, 'list');

			$this->BO = new $BOname();
			$this->BOView = AlphaView::getInstance($this->BO);

			echo AlphaView::displayPageHead($this);
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}

		$this->displayBodyContent();

		echo AlphaView::displayPageFoot($this);

		self::$logger->debug('<<doGET');
	}

	/**
	 * Handle POST requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

		try{
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}

			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to list!');
			}

			if (isset($params['order'])) {
				if($params['order'] == 'ASC' || $params['order'] == 'DESC')
					$this->order = $params['order'];
				else
					throw new IllegalArguementException('Order value ['.$params['order'].'] provided is invalid!');
			}

			if (isset($params['sort']))
				$this->sort = $params['sort'];

			AlphaDAO::loadClassDef($BOname);

			$this->BO = new $BOname();
			$this->BOname = $BOname;
			$this->BOView = AlphaView::getInstance($this->BO);

			echo AlphaView::displayPageHead($this);

			if (!empty($params['deleteOID'])) {
				if(!AlphaValidator::isInteger($params['deleteOID']))
						throw new IllegalArguementException('Invalid deleteOID ['.$params['deleteOID'].'] provided on the request!');

				try {
					$temp = new $BOname();
					$temp->load($params['deleteOID']);

					AlphaDAO::begin();
					$temp->delete();
					self::$logger->action('Deleted an instance of '.$BOname.' with id '.$params['deleteOID']);
					AlphaDAO::commit();

					echo AlphaView::displayUpdateMessage($BOname.' '.$params['deleteOID'].' deleted successfully.');

					$this->displayBodyContent();
				}catch(AlphaException $e) {
					self::$logger->error($e->getMessage());
					echo AlphaView::displayErrorMessage('Error deleting the BO of OID ['.$params['deleteOID'].'], check the log!');
					AlphaDAO::rollback();
				}

				AlphaDAO::disconnect();
			}
		}catch(SecurityException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->error($e->getMessage());
		}

		echo AlphaView::displayPageFoot($this);

		self::$logger->debug('<<doPOST');
	}

	/**
	 * Sets up the title etc. and pagination start point
	 *
	 * @since 1.0
	 */
	public function before_displayPageHead_callback() {
		// set up the title and meta details
		if(!isset($this->title))
			$this->setTitle('Listing all '.$this->BOname);
		if(!isset($this->description))
			$this->setDescription('Page listing all '.$this->BOname.'.');
		if(!isset($this->keywords))
			$this->setKeywords('list,all,'.$this->BOname);
		// set the start point for the list pagination
		if (isset($_GET['start']) ? $this->startPoint = $_GET['start']: $this->startPoint = 1);
	}

	/**
	 * Method to display the page footer with pageination links
	 *
	 * @return string
	 * @since 1.0
	 */
	public function before_displayPageFoot_callback() {
		$html = $this->renderPageLinks();

		$html .= '<br>';

		return $html;
	}

	/**
	 * Method for rendering the pagination links
	 *
	 * @return string
	 * @since 1.0
	 */
	protected function renderPageLinks() {
		global $config;

		$html = '';

		$end = (($this->startPoint-1)+$config->get('app.list.page.amount'));

		if($end > $this->BOCount)
			$end = $this->BOCount;

		if($this->BOCount > 0) {
			$html .= '<ul class="pagination">';
		}else{
			$html .= '<p align="center">The list is empty.&nbsp;&nbsp;</p>';

			return $html;
		}

		if ($this->startPoint > 1) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<li><a href="'.FrontController::generateSecureURL('act=ListAll&bo='.$this->BOname.'&start='.($this->startPoint-$config->get('app.list.page.amount'))).'">&lt;&lt;-Previous</a></li>';
			else
				$html .= '<li><a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".($this->startPoint-$config->get('app.list.page.amount')).'">&lt;&lt;-Previous</a></li>';
		}elseif($this->BOCount > $config->get('app.list.page.amount')){
			$html .= '<li class="disabled"><a href="#">&lt;&lt;-Previous</a></li>';
		}
		$page = 1;
		for ($i = 0; $i < $this->BOCount; $i+=$config->get('app.list.page.amount')) {
			if($i != ($this->startPoint-1)) {
				// handle secure URLs
				if(isset($_GET['tk']))
					$html .= '<li><a href="'.FrontController::generateSecureURL('act=ListAll&bo='.$this->BOname.'&start='.($i+1)).'">'.$page.'</a></li>';
				else
					$html .= '<li><a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".($i+1).'">'.$page.'</a></li>';
			}elseif($this->BOCount > $config->get('app.list.page.amount')){
				$html .= '<li class="active"><a href="#">'.$page.'</a></li>';
			}
			$page++;
		}
		if ($this->BOCount > $end) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<li><a href="'.FrontController::generateSecureURL('act=ListAll&bo='.$this->BOname.'&start='.($this->startPoint+$config->get('app.list.page.amount'))).'">Next-&gt;&gt;</a></li>';
			else
				$html .= '<li><a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".($this->startPoint+$config->get('app.list.page.amount')).
					'">Next-&gt;&gt;</a></li>';
		}elseif($this->BOCount > $config->get('app.list.page.amount')){
			$html .= '<li class="disabled"><a href="#">Next-&gt;&gt;</a></li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Method to display the main body HTML for this page
	 *
	 * @since 1.0
	 */
	protected function displayBodyContent() {
		global $config;

		// get all of the BOs and invoke the listView on each one
		$temp = new $this->BOname;

		if(isset($this->filterField) && isset($this->filterValue)) {
			if(isset($this->sort) && isset($this->order)) {
				$objects = $temp->loadAllByAttribute($this->filterField, $this->filterValue, $this->startPoint-1, $config->get('app.list.page.amount'),
					$this->sort, $this->order);
			}else{
				$objects = $temp->loadAllByAttribute($this->filterField, $this->filterValue, $this->startPoint-1, $config->get('app.list.page.amount'));
			}

			$this->BOCount = $temp->getCount(array($this->filterField), array($this->filterValue));
		}else{
			if(isset($this->sort) && isset($this->order))
				$objects = $temp->loadAll($this->startPoint-1, $config->get('app.list.page.amount'), $this->sort, $this->order);
			else
				$objects = $temp->loadAll($this->startPoint-1, $config->get('app.list.page.amount'));

			$this->BOCount = $temp->getCount();
		}

		AlphaDAO::disconnect();

		echo AlphaView::renderDeleteForm();

		foreach($objects as $object) {
			$temp = AlphaView::getInstance($object);
			$temp->listView();
		}
	}

	/**
	 * Use this callback to inject in the admin menu template fragment for admin users of
	 * the backend only.
	 *
	 * @since 1.2
	 */
	public function after_displayPageHead_callback() {
		$menu = '';

		if (isset($_SESSION['currentUser']) && AlphaDAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$menu .= AlphaView::loadTemplateFragment('html', 'adminmenu.phtml', array());
		}

		return $menu;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ListAll.php') {
	$controller = new ListAll();

	if(!empty($_POST)) {
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>