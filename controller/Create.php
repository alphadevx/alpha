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
class Create extends AlphaController implements AlphaControllerInterface {
	/**
	 * The name of the BO
	 * 
	 * @var string
	 */
	protected $BOname;
	
	/**
	 * The new BO to be created
	 * 
	 * @var Object
	 */
	protected $BO;
	
	/**
	 * The AlphaView object used for rendering the objects to create
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
	 * Constructor to set up the object
	 * 
	 * @param string $visibility
	 */
	public function __construct($visibility='Admin') {
		if(self::$logger == null)
			self::$logger = new Logger('Create');
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
	 */
	public function doGET($params) {
		try{
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to create!');
			}
			
			AlphaDAO::loadClassDef($BOname);
		
			/*
			 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
			 */
			if($this->getCustomControllerName($BOname, 'list') != null)
				$this->loadCustomController($BOname, 'create');
		
			$this->BO = new $BOname();
				
			$this->BOView = AlphaView::getInstance($this->BO);
				
			// set up the title and meta details
			if(!isset($this->title))
				$this->setTitle('Create a new '.$BOname);
			if(!isset($this->description))
				$this->setDescription('Page to create a new '.$BOname.'.');
			if(!isset($this->keywords))
				$this->setKeywords('create,new,'.$BOname);				
						
			echo AlphaView::displayPageHead($this);
				
			echo $this->BOView->createView();
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}
		echo AlphaView::displayPageFoot($this);
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to create!');
			}
			
			AlphaDAO::loadClassDef($BOname);
				
			$this->BO = new $BOname();
		
			if (isset($params['createBut'])) {			
				// populate the transient object from post data
				$this->BO->populateFromPost();
							
				$this->BO->save();			
	
				try {
					if ($this->getNextJob() != '')					
						header('Location: '.$this->getNextJob());
					else					
						header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
				}catch(AlphaException $e) {
					echo AlphaView::displayPageHead($this);
					self::$logger->error($e->getTraceAsString());
					echo AlphaView::displayErrorMessage('Error creating the new ['.$BOname.'], check the log!');
				}
			}
			
			if (isset($params['cancelBut'])) {
				header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
			}
		}catch(SecurityException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayPageHead($this);
			throw new ResourceNotAllowedException($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayPageHead($this);
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}catch(ValidationException $e) {
			self::$logger->warn($e->getMessage().', query ['.$this->BO->getLastQuery().']');
			$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
			$this->doGET($params);
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