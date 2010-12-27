<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/helpers/Validator.inc';

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
class Detail extends AlphaController implements AlphaControllerInterface {
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
	 * The default AlphaView object used for rendering the business object
	 * 
	 * @var AlphaView
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
		self::$logger->debug('>>doGET(params=['.print_r($params, true).'])');
		
		try{
			// load the business object (BO) definition
			if (isset($params['bo']) && isset($params['oid'])) {
				if(!Validator::isInteger($params['oid']))
					throw new IllegalArguementException('Invalid oid ['.$params['oid'].'] provided on the request!');
				
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				/*
			 	*  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
			 	*/
				if($this->getCustomControllerName($BOName, 'list') != null)
					$this->loadCustomController($BOName, 'view');
				
				$this->BO = new $BOName();						
				$this->BOName = $BOName;		
				$this->BOView = AlphaView::getInstance($this->BO);
				
				echo AlphaView::displayPageHead($this);
				
				echo AlphaView::renderDeleteForm();
		
				$this->BO->load($params['oid']);
				
				echo $this->BOView->detailedView();
			}else{
				throw new IllegalArguementException('No BO available to display!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The item that you have requested cannot be found!');
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST(params=['.print_r($params, true).'])');
		
		global $config;
		
		echo AlphaView::displayPageHead($this);
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				$this->BO = new $BOName();
				$this->BOname = $BOName;		
				$this->BOView = AlphaView::getInstance($this->BO);
		
				if (!empty($params['delete_oid'])) {
					if(!Validator::isInteger($params['delete_oid']))
						throw new IllegalArguementException('Invalid delete_oid ['.$params['delete_oid'].'] provided on the request!');
					
					$temp = new $BOName();
					$temp->load($params['delete_oid']);
					
					try {
						AlphaDAO::begin();
						$temp->delete();
						AlphaDAO::commit();

						echo AlphaView::displayUpdateMessage($BOName.' '.$params['delete_oid'].' deleted successfully.');
										
						echo '<center>';
						
						$temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getMessage());
						echo AlphaView::displayErrorMessage('Error deleting the BO of OID ['.$params['delete_oid'].'], check the log!');
						AlphaDAO::rollback();
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to display!');
			}
		}catch(SecurityException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotAllowedException($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The item that you have requested cannot be found!');
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
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