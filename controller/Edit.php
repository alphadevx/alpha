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
 * Controller used to edit BO, which must be supplied in GET vars
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class Edit extends AlphaController implements AlphaControllerInterface {
	/**
	 * The business object to be edited
	 * 
	 * @var Object
	 */
	protected $BO;
	
	/**
	 * The name of the BO
	 * 
	 * @var string
	 */
	protected $BOName;
	
	/**
	 * The OID of the BO to be edited
	 * 
	 * @var int
	 */
	private $BOoid;
	
	/**
	 * The AlphaView object used for rendering the object to edit
	 * 
	 * @var AlphaView BOView
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
		self::$logger = new Logger('Edit');
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
			if (isset($params['bo']) && isset($params['oid'])) {
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				/*
				 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
				 */
				if($this->getCustomControllerName($BOName, 'edit') != null)
					$this->loadCustomController($BOName, 'edit');
				
				$this->BO = new $BOName();
				$this->BO->load($params['oid']);
				
				$this->BOName = $BOName;
				
				$this->BOView = AlphaView::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle('Editing a '.$BOName);
				$this->setDescription('Page to edit a '.$BOName.'.');
				$this->setKeywords('edit,'.$BOName);
				
				echo AlphaView::displayPageHead($this);
		
				echo AlphaView::renderDeleteForm();
		
				echo $this->BOView->editView();		
			}else{
				throw new IllegalArguementException('No BO available to edit!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested item from the database!</p>';
		}
		
		echo AlphaView::displayPageFoot($this);
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			// load the business object (BO) definition
			if (isset($params['bo']) && isset($params['oid'])) {
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				$this->BO = new $BOName();
				$this->BO->load($params['oid']);
				
				$this->BOView = AlphaView::getInstance($this->BO);
					
				// set up the title and meta details
				$this->setTitle('Editing a '.$BOName);
				$this->setDescription('Page to edit a '.$BOName.'.');
				$this->setKeywords('edit,'.$BOName);
					
				echo AlphaView::displayPageHead($this);
		
				if (isset($params['saveBut'])) {			
					
					// populate the transient object from post data
					$this->BO->populateFromPost();
					
					try {
						$this->BO->save();			
						echo AlphaView::displayUpdateMessage(get_class($this->BO).' '.$this->BO->getID().' saved successfully.');
					}catch (LockingException $e) {
						$this->BO->reload();
						echo AlphaView::displayErrorMessage($e->getMessage());
					}
					
					echo $this->BOView->editView();
				}
				
				if (!empty($params['delete_oid'])) {
					$temp = new $BOName();
					$temp->load($params['delete_oid']);
					
					try {
						$temp->delete();
								
						echo '<p class="success">'.$this->BOName.' '.$params['delete_oid'].' deleted successfully.</p>';
										
						echo '<center>';
						
						$temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error deleting the OID ['.$params['delete_oid'].'], check the log!</p>';
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to edit!');
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
		
		echo AlphaView::displayPageFoot($this);
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Edit.php') {
	$controller = new Edit();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>