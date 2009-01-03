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
 * Controller used to display the details of a BO, which must be supplied in GET vars
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class Detail extends Controller implements AlphaControllerInterface {
	/**
	 * The BO to be displayed
	 * 
	 * @var Object
	 */
	protected $BO;
	
	/**
	 * The OID of the BO to be displayed
	 * 
	 * @var int
	 */
	private $BOoid;
	
	/**
	 * The name of the BO
	 * 
	 * @var string
	 */
	private $BOName;
	
	/**
	 * The default View object used for rendering the business object
	 * 
	 * @var View
	 */
	private $BOView;
	
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
			self::$logger = new Logger('Detail');
		self::$logger->debug('>>__construct()');
		
		global $config;
				
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Standard');
		
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
			if (isset($params['bo']) && isset($params['oid'])) {
				$BOName = $params['bo'];
				DAO::loadClassDef($BOName);
				
				$this->BO = new $BOName();						
				$this->BOName = $BOName;		
				$this->BOView = View::getInstance($this->BO);
				
				echo View::displayPageHead($this);
				
				echo View::renderDeleteForm();
		
				$this->BO->load($params['oid']);
				
				echo $this->BOView->detailedView();
			}else{
				throw new IllegalArguementException('No BO available to display!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested item from the database!</p>';
		}
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		echo View::displayPageHead($this);
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOName = $params['bo'];
				DAO::loadClassDef($BOName);
				
				$this->BO = new $BOName();
				$this->BOname = $BOName;		
				$this->BOView = View::getInstance($this->BO);
		
				if (!empty($params['delete_oid'])) {
					$temp = new $BOName();
					$temp->load($params['delete_oid']);
					
					try {
						$temp->delete();
								
						echo '<p class="success">'.$this->BOName.' '.$params['delete_oid'].' deleted successfully.</p>';
										
						echo '<center>';
						
						$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error deleting the OID ['.$params['delete_oid'].'], check the log!</p>';
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to display!');
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested item from the database!</p>';
		}
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Sets up the title etc.
	 */
	public function before_displayPageHead_callback() {
		$this->setTitle('Displaying '.$this->BOName.' number '.$this->BOoid);
		$this->setDescription('Page to display '.$this->BOName.' number '.$this->BOoid);
		$this->setKeywords('display,details,'.$this->BOName);
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Detail.php') {
	$controller = new Detail();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>