<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to list all of the business objects for the system
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class ListBusinessObjects extends Controller implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * the constructor
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('ListBusinessObjects');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Listing all business objects in the system');
		$this->setDescription('Page to list all business objects.');
		$this->setKeywords('list,all,business,objects');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		echo View::displayPageHead($this);
		
		$this->displayBodyContent();
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Handle POST requests
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
		
			if(isset($params['createTableBut'])) {
				try {					
					$classname = $params['createTableClass'];
					DAO::loadClassDef($classname);
			    		
			    	$BO = new $classname();	
					$BO->makeTable();
				
					echo '<p class="success">The table for the class '.$classname.' has been successfully created.</p>';
				}catch(AlphaException $e) {
					self::$logger->error($e->getTraceAsString());
					echo '<p class="error"><br>Error creating the table for the class '.$classname.', check the log!</p>';
				}
			}
			
			if(isset($params['recreateTableClass']) && $params['admin_'.$params['recreateTableClass'].'_button_pressed'] == 'recreateTableBut') {
				try {					
					$classname = $params['recreateTableClass'];
					DAO::loadClassDef($classname);		    		
			    	$BO = new $classname();	
					$BO->rebuildTable();
					
					echo '<p class="success">The table for the class '.$classname.' has been successfully recreated.</p>';
				}catch(AlphaException $e) {
					self::$logger->error($e->getTraceAsString());
					echo '<p class="error"><br>Error recreating the table for the class '.$classname.', check the log!</p>';
				}
			}
			
			if(isset($params['updateTableClass']) && $params['admin_'.$params['updateTableClass'].'_button_pressed'] == 'updateTableBut') {
				try {
					$classname = $params['updateTableClass'];
					DAO::loadClassDef($classname);
			    		
			    	$BO = new $classname();
			    	$missing_fields = $BO->findMissingFields();
			    	
			    	for($i = 0; $i < count($missing_fields); $i++)
						$BO->addProperty($missing_fields[$i]);
					
					echo '<p class="success">The table for the class '.$classname.' has been successfully updated.</p>';
				}catch(AlphaException $e) {
					self::$logger->error($e->getTraceAsString());
					echo '<p class="error"><br>Error updating the table for the class '.$classname.', check the log!</p>';
				}
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}
		
		$this->displayBodyContent();
				
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Private method to display the main body HTML for this page
	 */
	private function displayBodyContent() {
		$classNames = DAO::getBOClassNames();
		$loadedClasses = array();
		
		foreach($classNames as $classname) {
			DAO::loadClassDef($classname);
			array_push($loadedClasses, $classname);
		}
		
		foreach($loadedClasses as $classname) {
			try {
				
				$BO = new $classname();
				$BO_View = View::getInstance($BO);				
				$BO_View->adminView();				
			}catch (AlphaException $e) {
				self::$logger->error("[$classname]:".$e->getMessage());
				// its possible that the exception occured due to the table schema being out of date
				if($BO->checkTableNeedsUpdate()) {				
					$missingFields = $BO->findMissingFields();
		    	
					for($i = 0; $i < count($missingFields); $i++)
						$BO->addProperty($missingFields[$i]);
						
					// now try again...
					$BO = new $classname();
					$BO_View = new View($BO);
					$BO_View->adminView();
				}
			}catch (Exception $e) {
				self::$logger->error($e->getTraceAsString());
				echo '<p class="error"><br>Error accessing the class ['.$classname.'], check the log!</p>';
			}
		}
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ListBusinessObjects.php') {
	$controller = new ListBusinessObjects();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>