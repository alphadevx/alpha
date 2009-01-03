<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
* 
* Controller used to list a BO, which must be supplied in GET vars
* 
* @package alpha::controller
* @author John Collins <john@design-ireland.net>
* @copyright 2009 John Collins
* @version $Id$
*
*/
class ListAll extends Controller implements AlphaControllerInterface {
	/**
	 * The name of the BO
	 * 
	 * @var string
	 */
	private $BOname;
	
	/**
	 * The new default View object used for rendering the onjects to list
	 * 
	 * @var View BOView
	 */
	private $BOView;
	
	/**
	 * The start number for list pageination
	 * 
	 * @var integer 
	 */
	private $startPoint;
	
	/**
	 * The count of the BOs of this type in the database
	 * 
	 * @var integer
	 */
	private $BOCount = 0;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
								
	/**
	 * constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('ListAll');
		self::$logger->debug('>>__construct()');
		
		global $config;
				
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {		
		try{
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				DAO::loadClassDef($BOname);
				
				$this->BO = new $BOname();		
				$this->BOname = $BOname;		
				$this->BOView = View::getInstance($this->BO);
				
				echo View::displayPageHead($this);
			}else{
				throw new IllegalArguementException('No BO available to list!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}
		
		$this->displayBodyContent();
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		echo View::displayPageHead($this);
		
		try{
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				DAO::loadClassDef($BOname);
				
				$this->BO = new $BOname();		
				$this->BOname = $BOname;		
				$this->BOView = View::getInstance($this->BO);
				
				if (!empty($params['delete_oid'])) {
					$temp = new $BOname();
					$temp->load($params['delete_oid']);
		
					try {
						$temp->delete();
						
						echo '<p class="success">'.$this->BOname.' '.$params['delete_oid'].' deleted successfully.</p>';
						
						$this->displayBodyContent();
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error deleting the OID ['.$params['delete_oid'].'], check the log!</p>';
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to list!');
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Sets up the title etc. and pagination start point
	 */
	public function before_displayPageHead_callback() {
		// set up the title and meta details
		$this->setTitle('Listing all '.$this->BOname);
		$this->setDescription('Page listing all '.$this->BOname.'.');
		$this->setKeywords('list,all,'.$this->BOname);
		// set the start point for the list pagination
		if (isset($_GET['start']) ? $this->startPoint = $_GET['start']: $this->startPoint = 0);
	}
	
	/**
	 * Renders an administration home page link after the page header is rendered
	 * 
	 * @return string
	 */
	public function after_displayPageHead_callback() {
		global $config;
		
		$html = '<p align="center"><a href="'.Front_Controller::generate_secure_URL('act=ListBusinessObjects').'">Administration Home Page</a></p>';
		
		return $html;
	}
	
	/**
	 * Method to display the page footer with pageination links
	 * 
	 * @return string
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
	 */
	private function renderPageLinks() {
		global $config;
		
		$html = '';
		
		$end = ($this->startPoint+$config->get('sysListPageAmount'));
		
		if($end > $this->BOCount)
			$end = $this->BOCount;
		
		if ($this->startPoint > 9)
			$html .= '<p align="center">Displaying '.($this->startPoint+1).' to '.$end.' of <strong>'.$this->BOCount.'</strong>.&nbsp;&nbsp;';		
		else
			$html .= '<p align="center">Displaying &nbsp;'.($this->startPoint+1).' to '.$end.' of <strong>'.$this->BOCount.'</strong>.&nbsp;&nbsp;';		
				
		if ($this->startPoint > 0) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '<a href="'.Front_Controller::generate_secure_URL('act=ListAll&bo='.$this->BOname.'&start='.($this->startPoint-$config->get('sysListPageAmount'))).'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
			else
				$html .= '<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".($this->startPoint-$config->get('sysListPageAmount')).'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
		}elseif($this->BOCount > $config->get('sysListPageAmount')){
			$html .= '&lt;&lt;-Previous&nbsp;&nbsp;';
		}
		$page = 1;
		for ($i = 0; $i < $this->BOCount; $i+=$config->get('sysListPageAmount')) {
			if($i != $this->startPoint) {
				// handle secure URLs
				if(isset($_GET['tk']))
					$html .= '&nbsp;<a href="'.Front_Controller::generate_secure_URL('act=ListAll&bo='.$this->BOname.'&start='.$i).'">'.$page.'</a>&nbsp;';
				else
					$html .= '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".$i.'">'.$page.'</a>&nbsp;';
			}elseif($this->BOCount > $config->get('sysListPageAmount')){
				$html .= '&nbsp;'.$page.'&nbsp;';
			}
			$page++;
		}
		if ($this->BOCount > $end) {
			// handle secure URLs
			if(isset($_GET['tk']))
				$html .= '&nbsp;&nbsp;<a href="'.Front_Controller::generate_secure_URL('act=ListAll&bo='.$this->BOname.'&start='.($this->startPoint+$config->get('sysListPageAmount'))).'">Next-&gt;&gt;</a>';
			else
				$html .= '&nbsp;&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BOname."&start=".($this->startPoint+$config->get('sysListPageAmount')).'">Next-&gt;&gt;</a>';
		}elseif($this->BOCount > $config->get('sysListPageAmount')){
			$html .= '&nbsp;&nbsp;Next-&gt;&gt;';
		}
		$html .= '</p>';
		
		return $html;
	}
	
	/**
	 * Private method to display the main body HTML for this page
	 */
	private function displayBodyContent() {
		global $config;
		
		// get all of the BOs and invoke the listView on each one
		$temp = new $this->BOname;
			
		$objects = $temp->loadAll($this->startPoint, $config->get('sysListPageAmount'));
			
		$this->BOCount = $this->BO->getCount();
		
		echo View::renderDeleteForm();
		
		foreach($objects as $object) {
			$temp = View::getInstance($object);
			$temp->listView();
		}
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