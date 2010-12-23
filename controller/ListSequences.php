<?php

// include the config file
if(!isset($config)) {
	require_once '../util/configLoader.inc';
	$config = configLoader::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ListAll.php';
require_once $config->get('sysRoot').'alpha/model/types/Sequence.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to list all Sequences
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id: ListSequences.php 960 2009-09-26 18:46:49Z johnc $
 *
 */
class ListSequences extends ListAll implements AlphaControllerInterface {
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
			self::$logger = new Logger('ListSequences');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$BO = new Sequence();
		
		// make sure that the Sequence tables exist
		if(!$BO->checkTableExists()) {
			echo '<p class="warning">Warning! The Sequence table do not exist, attempting to create it now...</p>';
			$BO->makeTable();
		}
		
		// set up the title and meta details
		$this->setTitle('Listing all Sequences');
		$this->setDescription('Page to list all Sequences.');
		$this->setKeywords('list,all,Sequences');
		
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
		$temp = new Sequence();
		// set the start point for the list pagination
		if (isset($params['start']) ? $this->startPoint = $params['start']: $this->startPoint = 0);
			
		$objects = $temp->loadAll($this->startPoint);
		
		$BO = new Sequence();
		$this->BOCount = $BO->getCount();
		
		echo AlphaView::renderDeleteForm();
		
		foreach($objects as $object) {
			$temp = new AlphaView($object);
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
}

// now build the new controller if this file is called directly
if ('ListSequences.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ListSequences();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>