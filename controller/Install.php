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
 * Controller used install the database
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class Install extends Controller implements AlphaControllerInterface {
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
		//if(self::$logger == null)
			//self::$logger = new Logger('Install');
		//self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Installing '.$config->get('sysTitle'));		
		
		//self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		global $config;
		
		echo View::displayPageHead($this);
		
		// set the umask first before attempt mkdir
		umask(0);
		
		/*
		 * Create the logs directory, then instantiate a new logger
		 */
		try {
			$logsDir = $config->get('sysRoot').'logs';
			
			echo '<p>Attempting to create the logs directory <em>'.$logsDir.'</em>...';
			
			if(!file_exists($logsDir))
				mkdir($logsDir, 0766);
			
			self::$logger = new Logger('Install');
			self::$logger->info('Started installation process!');
			self::$logger->info('Logs directory ['.$logsDir.'] successfully created');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';			
			echo '<p>Aborting.</p>';
			exit;
		}
		
		/*
		 * Create the cron tasks directory
		 */
		try {
			$tasksDir = $config->get('sysRoot').'tasks';
			
			echo '<p>Attempting to create the tasks directory <em>'.$tasksDir.'</em>...';
			
			if(!file_exists($tasksDir))
				mkdir($tasksDir, 0766);			
			
			self::$logger->info('Tasks directory ['.$tasksDir.'] successfully created');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';			
			echo '<p>Aborting.</p>';
			exit;
		}
		
		/*
		 * Create the attachments directory
		 */
		try {
			$attachmentsDir = $config->get('sysRoot').'attachments';
			
			echo '<p>Attempting to create the attachments directory <em>'.$attachmentsDir.'</em>...';
			
			if(!file_exists($attachmentsDir))
				mkdir($attachmentsDir, 0766);			
			
			self::$logger->info('Attachments directory ['.$attachmentsDir.'] successfully created');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';			
			echo '<p>Aborting.</p>';
			exit;
		}
		
		/*
		 * Create the cache directory and sub-directories
		 */
		try {
			$cacheDir = $config->get('sysRoot').'cache';
			$htmlDir = $config->get('sysRoot').'cache/html';
			$imagesDir = $config->get('sysRoot').'cache/images';
			$pdfDir = $config->get('sysRoot').'cache/pdf';
			$xlsDir = $config->get('sysRoot').'cache/xls';
			
			// cache
			echo '<p>Attempting to create the cache directory <em>'.$cacheDir.'</em>...';
			if(!file_exists($cacheDir))			
				mkdir($cacheDir, 0766);			
			self::$logger->info('Cache directory ['.$cacheDir.'] successfully created');
			
			// cache/html
			echo '<p>Attempting to create the HTML cache directory <em>'.$htmlDir.'</em>...';
			if(!file_exists($htmlDir))			
				mkdir($htmlDir, 0766);			
			self::$logger->info('Cache directory ['.$htmlDir.'] successfully created');
			
			// cache/images
			echo '<p>Attempting to create the cache directory <em>'.$imagesDir.'</em>...';
			if(!file_exists($imagesDir))			
				mkdir($imagesDir, 0766);			
			self::$logger->info('Cache directory ['.$imagesDir.'] successfully created');
			
			// cache/pdf
			echo '<p>Attempting to create the cache directory <em>'.$pdfDir.'</em>...';
			if(!file_exists($pdfDir))			
				mkdir($pdfDir, 0766);			
			self::$logger->info('Cache directory ['.$pdfDir.'] successfully created');
			
			// cache/xls
			echo '<p>Attempting to create the cache directory <em>'.$xlsDir.'</em>...';
			if(!file_exists($xlsDir))			
				mkdir($xlsDir, 0766);			
			self::$logger->info('Cache directory ['.$xlsDir.'] successfully created');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';			
			echo '<p>Aborting.</p>';
			exit;
		}
		
		// start a new database transaction
		DAO::begin();
		
		/*
		 * Create DEnum tables
		 */
		$DEnum = new DEnum();
		$DEnumItem = new DEnumItem();
		try{
			echo '<p>Attempting to create the DEnum tables...';
			if(!$DEnum->checkTableExists())
				$DEnum->makeTable();
			if(!$DEnumItem->checkTableExists())
				$DEnumItem->makeTable();
			echo '<p class="success">Done!</p>';
			self::$logger->info('Created the ['.$DEnum->getTableName().'] table successfully');
			self::$logger->info('Created the ['.$DEnumItem->getTableName().'] table successfully');
			
			// create a default article DEnum category
			$DEnum = new DEnum('article_object::section');
			$DEnumItem = new DEnumItem();
			$DEnumItem->set('value', 'Main');
			$DEnumItem->set('DEnumID', $DEnum->getID());
			$DEnumItem->save();			
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';											
			self::$logger->error($e->getMessage());
			echo '<p>Aborting.</p>';
			exit;
		}
		
		/*
		 * Loop over each business object in the system, and create a table for it
		 */
		$classNames = DAO::getBOClassNames();
		$loadedClasses = array();
		
		foreach($classNames as $classname) {
			DAO::loadClassDef($classname);
			array_push($loadedClasses, $classname);
		}
		
		foreach($loadedClasses as $classname) {
			try {				
				$BO = new $classname();
				echo '<p>Attempting to create the table for the class ['.$classname.']...';
				if(!$BO->checkTableExists()) {
					$BO->makeTable();
				}else{
					if($BO->checkTableNeedsUpdate()) {				
						$missingFields = $BO->findMissingFields();
	    	
						for($i = 0; $i < count($missingFields); $i++)
							$BO->addProperty($missingFields[$i]);
					}
				}
				echo '<p class="success">Done!</p>';
				self::$logger->info('Created the ['.$BO->getTableName().'] table successfully');
			}catch (Exception $e) {
				echo '<p class="error"><br><pre>'.$e->getTraceAsString().'</pre></p>';											
				self::$logger->error($e->getTraceAsString());
				echo '<p>Aborting.</p>';
				exit;				
			}
		}
		
		/*
		 * Create the Admin and Standard groups
		 */
		$adminGroup = new rights_object();
		$adminGroup->set('name', 'Admin');
		$standardGroup = new rights_object();
		$standardGroup->set('name', 'Standard');
		try{
			echo '<p>Attempting to create the Admin and Standard groups...';
			$adminGroup->save();
			$standardGroup->save();
			echo '<p class="success">Done!</p>';
			self::$logger->info('Created the Admin and Standard rights groups successfully');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';											
			self::$logger->error($e->getMessage());
			echo '<p>Aborting.</p>';
			exit;				
		}
		
		/*
		 * Save the admin user to the database in the right group
		 */
		try{
			echo '<p>Attempting to save the Admin account...';
			$admin = new person_object();
			$admin->set('displayName', 'Admin');
			$admin->set('email', $_SESSION['currentUser']->get('email'));
			$admin->set('password', $_SESSION['currentUser']->get('password'));
			$admin->save();
			self::$logger->info('Created the admin user account ['.$_SESSION['currentUser']->get('email').'] successfully');
			
			$adminGroup->loadByAttribute('name', 'Admin');
					
			$lookup = $adminGroup->getMembers()->getLookup();
			$lookup->setValue(array($admin->getID(), $adminGroup->getID()));
			$lookup->save();
			echo '<p class="success">Done!</p>';
			self::$logger->info('Added the admin account to the Admin group successfully');
		}catch (Exception $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';											
			self::$logger->error($e->getMessage());
			echo '<p>Aborting.</p>';			
			exit;				
		}		
		
		echo '<p align="center"><a href="'.FrontController::generateSecureURL('act=ListBusinessObjects').'">Administration Home Page</a></p>';
		echo View::displayPageFoot($this);
		
		// commit
		DAO::commit();
		
		self::$logger->info('Finished installation!');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Custom version of the check rights method that only checks for a session for the config admin username/password,
	 * when the system database is not set-up
	 * 
	 * @return boolean
	 */
	public function checkRights() {
		//self::$logger->debug('>>checkRights()');
		
		global $config;

		if(DAO::isInstalled()) {
			//self::$logger->debug('<<checkRights [false]');
			return false;
		}
		
		// the person is logged in?
		if (isset($_SESSION['currentUser'])) {
			if ($_SESSION['currentUser']->get('email') == $config->get('sysInstallUsername')) {
				//self::$logger->debug('<<checkRights [true]');
				return true;
			}
		}
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Install.php') {
	$controller = new Install();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>