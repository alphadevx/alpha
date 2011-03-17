<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used install the database
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class Install extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * the constructor
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('Install');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		parent::__construct('Public');
		
		// if there is nobody logged in, we will send them off to the Login controller to do so before coming back here
		if(!isset($_SESSION['currentUser'])) {
			self::$logger->info('Nobody logged in, invoking Login controller...');
			
			require_once $config->get('sysRoot').'alpha/controller/Login.php';

			$controller = new Login();
			$controller->setName('Login');
			$controller->setUnitOfWork(array('Login', 'Install'));
			$controller->doGET(array());
			
			self::$logger->debug('<<__construct');
			exit;
		}else{
			
			// ensure that the super class constructor is called, indicating the rights group
			parent::__construct('Admin');
			
			// set up the title and meta details
			$this->setTitle('Installing '.$config->get('sysTitle'));
			
			self::$logger->debug('<<__construct');
		}
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		global $config;
		
		echo AlphaView::displayPageHead($this);
		
		$this->createAppDirectories();
		
		// start a new database transaction
		AlphaDAO::begin();
		
		/*
		 * Create DEnum tables
		 */
		$DEnum = new DEnum();
		$DEnumItem = new DEnumItem();
		try{
			echo '<p>Attempting to create the DEnum tables...';
			if(!$DEnum->checkTableExists())
				$DEnum->makeTable();
			self::$logger->info('Created the ['.$DEnum->getTableName().'] table successfully');
			
			if(!$DEnumItem->checkTableExists())
				$DEnumItem->makeTable();
			self::$logger->info('Created the ['.$DEnumItem->getTableName().'] table successfully');
			
			
			// create a default article DEnum category
			$DEnum = new DEnum('ArticleObject::section');
			$DEnumItem = new DEnumItem();
			$DEnumItem->set('value', 'Main');
			$DEnumItem->set('DEnumID', $DEnum->getID());
			$DEnumItem->save();
			
			echo AlphaView::displayUpdateMessage('DEnums set up successfully.');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			self::$logger->error($e->getMessage());
			AlphaDAO::rollback();
			exit;
		}
		
		/*
		 * Loop over each business object in the system, and create a table for it
		 */
		$classNames = AlphaDAO::getBOClassNames();
		$loadedClasses = array();
		
		foreach($classNames as $classname) {
			AlphaDAO::loadClassDef($classname);
			array_push($loadedClasses, $classname);
		}
		
		foreach($loadedClasses as $classname) {
			try {
				echo '<p>Attempting to create the table for the class ['.$classname.']...';
							
				try {echo '1<br>';
					$BO = new $classname();
					
					if(!$BO->checkTableExists()) {
						$BO->makeTable();
					}else{
						if($BO->checkTableNeedsUpdate()) {		
							$missingFields = $BO->findMissingFields();
							
							$count = count($missingFields);
		    	
							for($i = 0; $i < $count; $i++)
								$BO->addProperty($missingFields[$i]);
						}
					}
				}catch (FailedIndexCreateException $eice) {
					// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
					self::$logger->warn($eice->getMessage());
				}catch (FailedLookupCreateException $elce) {
					// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
					self::$logger->warn($elce->getMessage());
				}
				
				self::$logger->info('Created the ['.$BO->getTableName().'] table successfully');
				echo AlphaView::displayUpdateMessage('Created the ['.$BO->getTableName().'] table successfully');
			}catch (Exception $e) {
				echo AlphaView::displayErrorMessage($e->getMessage());
				echo AlphaView::displayErrorMessage('Aborting.');
				self::$logger->error($e->getMessage());
				AlphaDAO::rollback();
				exit;
			}
		}
		
		echo AlphaView::displayUpdateMessage('All business object tables created successfully!');
		
		/*
		 * Create the Admin and Standard groups
		 */
		$adminGroup = new RightsObject();
		$adminGroup->set('name', 'Admin');
		$standardGroup = new RightsObject();
		$standardGroup->set('name', 'Standard');
		try{
			try{
				echo '<p>Attempting to create the Admin and Standard groups...';
				$adminGroup->save();
				$standardGroup->save();
				
				self::$logger->info('Created the Admin and Standard rights groups successfully');
				echo AlphaView::displayUpdateMessage('Created the Admin and Standard rights groups successfully');
			}catch (FailedIndexCreateException $eice) {
				// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
				self::$logger->warn($eice->getMessage());
			}catch (FailedLookupCreateException $elce) {
				// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
				self::$logger->warn($elce->getMessage());
			}
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			self::$logger->error($e->getMessage());
			AlphaDAO::rollback();
			exit;
		}
		
		/*
		 * Save the admin user to the database in the right group
		 */
		try{
			try {
				echo '<p>Attempting to save the Admin account...';
				$admin = new PersonObject();
				$admin->set('displayName', 'Admin');
				$admin->set('email', $_SESSION['currentUser']->get('email'));
				$admin->set('password', $_SESSION['currentUser']->get('password'));
				$admin->save();
				self::$logger->info('Created the admin user account ['.$_SESSION['currentUser']->get('email').'] successfully');
				
				$adminGroup->loadByAttribute('name', 'Admin');
						
				$lookup = $adminGroup->getMembers()->getLookup();
				$lookup->setValue(array($admin->getID(), $adminGroup->getID()));
				$lookup->save();
				
				self::$logger->info('Added the admin account to the Admin group successfully');
				echo AlphaView::displayUpdateMessage('Added the admin account to the Admin group successfully');
			}catch (FailedIndexCreateException $eice) {
				// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
				self::$logger->warn($eice->getMessage());
			}catch (FailedLookupCreateException $elce) {
				// this are safe to ignore for now as they will be auto-created later once all of the tables are in place
				self::$logger->warn($elce->getMessage());
			}
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			self::$logger->error($e->getMessage());
			AlphaDAO::rollback();
			exit;
		}		
		
		echo '<br><p align="center"><a href="'.FrontController::generateSecureURL('act=ListBusinessObjects').'">Administration Home Page</a></p><br>';
		echo AlphaView::displayPageFoot($this);
		
		// commit
		AlphaDAO::commit();
		
		self::$logger->info('Finished installation!');
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Copies a .htaccess file that restricts public access to the target directory
	 * 
	 * @param string $dir
	 * @since 1.0
	 */
	private function copyRestrictedAccessFileToDirectory($dir) {
		global $config;
		
		copy($config->get('sysRoot').'alpha/.htaccess', $dir.'/.htaccess');
	}
	
	/**
	 * Creates the standard application directories
	 * 
	 * @since 1.0
	 */
	private function createAppDirectories() {
		global $config;
		
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
				
			$this->copyRestrictedAccessFileToDirectory($logsDir);
			
			self::$logger = new Logger('Install');
			self::$logger->info('Started installation process!');
			self::$logger->info('Logs directory ['.$logsDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Logs directory ['.$logsDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
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
				
			$this->copyRestrictedAccessFileToDirectory($logsDir);
			
			self::$logger->info('Tasks directory ['.$tasksDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Tasks directory ['.$tasksDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			exit;
		}
		
		/*
		 * Create the controller directory
		 */
		try {
			$controllerDir = $config->get('sysRoot').'controller';
			
			echo '<p>Attempting to create the controller directory <em>'.$controllerDir.'</em>...';
			
			if(!file_exists($controllerDir))
				mkdir($controllerDir, 0766);
			
			self::$logger->info('Controller directory ['.$controllerDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Controllers directory ['.$controllerDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			exit;
		}
		
		/*
		 * Create the model directory
		 */
		try {
			$modelDir = $config->get('sysRoot').'model';
			
			echo '<p>Attempting to create the model directory <em>'.$modelDir.'</em>...';
			
			if(!file_exists($modelDir))
				mkdir($modelDir, 0766);
				
			$this->copyRestrictedAccessFileToDirectory($modelDir);
			
			self::$logger->info('Model directory ['.$modelDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Model directory ['.$modelDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			exit;
		}
		
		/*
		 * Create the view directory
		 */
		try {
			$viewDir = $config->get('sysRoot').'view';
			
			echo '<p>Attempting to create the view directory <em>'.$viewDir.'</em>...';
			
			if(!file_exists($viewDir))
				mkdir($viewDir, 0766);
				
			$this->copyRestrictedAccessFileToDirectory($viewDir);
			
			self::$logger->info('View directory ['.$viewDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('View directory ['.$viewDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
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
				
			$this->copyRestrictedAccessFileToDirectory($attachmentsDir);
			
			self::$logger->info('Attachments directory ['.$attachmentsDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Attachments directory ['.$attachmentsDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
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
				
			$this->copyRestrictedAccessFileToDirectory($cacheDir);
			
			self::$logger->info('Cache directory ['.$cacheDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Cache directory ['.$cacheDir.'] successfully created');
			
			// cache/html
			echo '<p>Attempting to create the HTML cache directory <em>'.$htmlDir.'</em>...';
			if(!file_exists($htmlDir))			
				mkdir($htmlDir, 0766);
				
			$this->copyRestrictedAccessFileToDirectory($htmlDir);
			
			self::$logger->info('Cache directory ['.$htmlDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Cache directory ['.$htmlDir.'] successfully created');
			
			// cache/images
			echo '<p>Attempting to create the cache directory <em>'.$imagesDir.'</em>...';
			if(!file_exists($imagesDir))			
				mkdir($imagesDir, 0766);
			
			$this->copyRestrictedAccessFileToDirectory($imagesDir);
			
			self::$logger->info('Cache directory ['.$imagesDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Cache directory ['.$imagesDir.'] successfully created');
			
			// cache/pdf
			echo '<p>Attempting to create the cache directory <em>'.$pdfDir.'</em>...';
			if(!file_exists($pdfDir))			
				mkdir($pdfDir, 0766);
				
			$this->copyRestrictedAccessFileToDirectory($pdfDir);
			
			self::$logger->info('Cache directory ['.$pdfDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Cache directory ['.$pdfDir.'] successfully created');
			
			// cache/xls
			echo '<p>Attempting to create the cache directory <em>'.$xlsDir.'</em>...';
			if(!file_exists($xlsDir))			
				mkdir($xlsDir, 0766);
				
			$this->copyRestrictedAccessFileToDirectory($xlsDir);
			
			self::$logger->info('Cache directory ['.$xlsDir.'] successfully created');
			echo AlphaView::displayUpdateMessage('Cache directory ['.$xlsDir.'] successfully created');
		}catch (Exception $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			echo AlphaView::displayErrorMessage('Aborting.');
			exit;
		}
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Custom version of the check rights method that only checks for a session for the config admin username/password,
	 * when the system database is not set-up
	 * 
	 * @return boolean
	 * @since 1.0
	 */
	public function checkRights() {
		self::$logger->debug('>>checkRights()');
		
		global $config;
		
		if ($this->getVisibility() == 'Public') {
			self::$logger->debug('<<checkRights [true]');
			return true;
		}
		
		if(AlphaDAO::isInstalled()) {
			self::$logger->debug('<<checkRights [false]');
			return false;
		}
		
		// the person is logged in?
		if (isset($_SESSION['currentUser'])) {
			if ($_SESSION['currentUser']->get('email') == $config->get('sysInstallUsername')) {
				self::$logger->debug('<<checkRights [true]');
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