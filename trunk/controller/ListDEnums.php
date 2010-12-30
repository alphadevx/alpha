<?php

// include the config file
if(!isset($config)) {
	require_once '../util/configLoader.inc';
	$config = configLoader::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ListAll.php';
require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/DEnumView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to list all DEnums
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ListDEnums extends ListAll implements AlphaControllerInterface {
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
		self::$logger = new Logger('ListDEnums');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->BO = new DEnum();
		
		// make sure that the DEnum tables exist
		if(!$this->BO->checkTableExists()) {
			echo '<p class="warning">Warning! The DEnum tables do not exist, attempting to create them now...</p>';
			$this->createDEnumTables();
		}
		
		$this->BOname = 'DEnum';
		
		$this->BOView = AlphaView::getInstance($this->BO);
		
		// set up the title and meta details
		$this->setTitle('Listing all DEnums');
		$this->setDescription('Page to list all DEnums.');
		$this->setKeywords('list,all,DEnums');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		echo AlphaView::displayPageHead($this);
		
		// get all of the BOs and invoke the list_view on each one
		$temp = new DEnum();
		// set the start point for the list pagination
		if (isset($params['start']) ? $this->startPoint = $params['start']: $this->startPoint = 0);
			
		$objects = $temp->loadAll($this->startPoint);
			
		$this->BOCount = $this->BO->getCount();
		
		echo AlphaView::renderDeleteForm();
		
		foreach($objects as $object) {
			$temp = AlphaView::getInstance($object);
			echo $temp->listView();
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');		
	}
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');		
	}
	
	/**
	 * Method to create the DEnum tables if they don't exist
	 */
	private function createDEnumTables() {
		$tmpDEnum = new DEnum();

		echo '<p>Attempting to build table '.DEnum::TABLE_NAME.' for class DEnum : </p>';
		
		try {
			$tmpDEnum->makeTable();
			echo '<p class="success">Successfully re-created the database table '.DEnum::TABLE_NAME.'</p>';
		}catch(AlphaException $e) {
			echo '<p class="warning">Failed re-created the database table '.DEnum::TABLE_NAME.', check the log</p>';
			self::$logger->error($e->getMessage());
		}
		
		$tmpDEnumItem = new DEnumItem();
		
		echo '<p>Attempting to build table '.DEnumItem::TABLE_NAME.' for class DEnumItem : </p>';
		
		try {
			$tmpDEnumItem->makeTable();
			echo '<p class="success">Successfully re-created the database table '.DEnumItem::TABLE_NAME.'</p>';
		}catch(AlphaException $e) {
			echo '<p class="warning">Failed re-created the database table '.DEnumItem::TABLE_NAME.', check the log</p>';
			self::$logger->error($e->getMessage());
		}			
	}
}

// now build the new controller if this file is called directly
if ('ListDEnums.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ListDEnums();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>