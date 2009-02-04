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
* Controller used to create a new BO, which must be supplied in GET vars
* 
* @package alpha::controller
* @author John Collins <john@design-ireland.net>
* @copyright 2009 John Collins
* @version $Id$
*
*/
class Create extends Controller implements AlphaControllerInterface {
	/**
	 * The new BO to be created
	 * 
	 * @var Object
	 */
	protected $BO;
	
	/**
	 * The View object used for rendering the objects to create
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
			self::$logger = new Logger('Create');
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
				$BOName = $params['bo'];
				DAO::loadClassDef($BOName);
		
				/*
				 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
				 */
				$this->loadCustomController($BOName, 'create');
		
				$this->BO = new $BOName();
				
				$this->BOView = View::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle('Create a new '.$BOName);
				$this->setDescription('Page to create a new '.$BOName.'.');
				$this->setKeywords('create,new,'.$BOName);				
						
				echo View::displayPageHead($this);
				
				echo $this->BOView->createView();
			}else{
				throw new IllegalArguementException('No BO available to display!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
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
		
				if (isset($params['createBut'])) {			
					// populate the transient object from post data
					$this->BO->populateFromPost();
					
					// check to see if a person is being created, then encrypt the password
					if (get_class($this->BO) == 'person_object' && isset($params['password']))
						$this->BO->set('password', crypt($params['password']));
							
					$this->BO->save();			
	
					try {
						if ($this->getNextJob() != '')					
							header('Location: '.$this->getNextJob());
						else					
							header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error creating the new ['.$BOName.'], check the log!</p>';
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to create!');
			}
			
			if (isset($params['cancelBut'])) {
				header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Create.php') {
	$controller = new Create();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>