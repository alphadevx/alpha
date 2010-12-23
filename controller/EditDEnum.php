<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/Edit.php';
require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/DEnumView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedSaveException.inc';

/**
 * 
 * Controller used to edit DEnums and associated DEnumItems
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class EditDEnum extends Edit implements AlphaControllerInterface {
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
			self::$logger = new Logger('EditDEnum');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Editing a DEnum');
		$this->setDescription('Page to edit a DEnum.');
		$this->setKeywords('edit,DEnum');
		
		$this->BO = new DEnum();
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		echo AlphaView::displayPageHead($this);
		
		// ensure that a OID is provided
		if (isset($params['oid'])) {
			$BOoid = $params['oid'];
		}else{
			throw new IllegalArguementException('Could not load the DEnum object as an oid was not supplied!');
			return;
		}
		
		try {
			$this->BO->load($BOoid);
			
			$this->BOName = 'DEnum';
			
			$this->BOView = new DEnumView($this->BO);			
			
			echo AlphaView::renderDeleteForm();			
			
			echo $this->BOView->editView();
		}catch(BONotFoundException $e) {
			self::$logger->error('Unable to load the DEnum of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
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
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
		
			// ensure that a OID is provided
			if (isset($params['oid'])) {
				$BOoid = $params['oid'];
			}else{
				throw new IllegalArguementException('Could not load the DEnum object as an oid was not supplied!');
			}
			
			if (isset($params['saveBut'])) {
				try {
					$this->BO->load($BOoid);
					// update the object from post data
					$this->BO->populateFromPost();
					
					AlphaDAO::begin();
					
					$this->BO->save();
					
					// now save the DEnumItems			
					$tmp = new DEnumItem();
					$denumItems = $tmp->loadItems($this->BO->getID());						
					
					foreach ($denumItems as $item) {
						$item->set('value', $params['value_'.$item->getID()]);
						$item->save();
					}
					
					// handle new DEnumItem if posted
					if(isset($params['new_value']) && trim($params['new_value']) != '') {
						$newItem = new DEnumItem();
						$newItem->set('value', $params['new_value']);
						$newItem->set('DEnumID', $this->BO->getID());
						$newItem->save();
					}			
							
					AlphaDAO::commit();					
					
					$this->setStatusMessage('<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> '.get_class($this->BO).' '.$this->BO->getID().' saved successfully.</p>
						</div>');
					
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error('Unable to save the DEnum of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
					AlphaDAO::rollback();
				}
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
				
		self::$logger->debug('<<doPOST');		
	}
	
	/**
	 * Using this callback to blank the new_value field when the page loads, regardless of anything being posted
	 * 
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		$html = '';
		$html .= '<script language="javascript">';
		$html .= 'function clearNewField() {';
		$html .= '	document.getElementById("new_value").value = "";';
		$html .= '}';
		$html .= 'addOnloadEvent(clearNewField);';
		$html .= '</script>';
		return $html;
	}
}

// now build the new controller if this file is called directly
if ('EditDEnum.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new EditDEnum();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>