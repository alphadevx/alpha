<?php

// include the config file
if(!isset($config))
	require_once '../util/AlphaConfig.inc';
$config = AlphaConfig::getInstance();

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/util/AlphaFileUtil.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * 
 * Controller used to allow an admin to manage tags in the database
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2010 John Collins
 * @version $Id$
 */
class TagManager extends AlphaController implements AlphaControllerInterface {	
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
			self::$logger = new Logger('TagManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Tag Manager');		
		
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
		
		echo '<h2>Listing business objects which are tagged</h2>';
		
		$BOs = AlphaDAO::getBOClassNames();
		
		foreach ($BOs as $BO) {
			AlphaDAO::loadClassDef($BO);
			$temp = new $BO;
			if($temp->isTagged()) {
				$tag = new tag_object();
				$count = count($tag->loadAllByAttribute('taggedClass', $BO));
				echo '<h3>'.$temp->getFriendlyClassName().' object is tagged ('.$count.' tags found)</h3>';
				$button = new button("if (confirm('Are you sure you want to delete all tags attached to the ".$temp->getFriendlyClassName()." class, and have them re-created?')) {document.forms['clearForm']['clearTaggedClass'].value = '".$BO."'; document.forms['clearForm'].submit();}", "Re-create tags", "clearBut");
   				echo $button->render();
			}
		}		
		
   		echo '<form action="'.$_SERVER['PHP_SELF'].(empty($_SERVER['QUERY_STRING'])? '':'?'.$_SERVER['QUERY_STRING']).'" method="POST" name="clearForm">';
   		echo '<input type="hidden" name="clearTaggedClass"/>';
   		echo AlphaView::renderSecurityFields();
   		echo '</form>';
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
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
			
			if (isset($params['clearTaggedClass']) && $params['clearTaggedClass'] != '') {
				try {
					self::$logger->info('About to start rebuilding the tags for the class ['.$params['clearTaggedClass'].']');
					$startTime = microtime(true);
					
					AlphaDAO::loadClassDef($params['clearTaggedClass']);
					$temp = new $params['clearTaggedClass'];
					$BOs = $temp->loadAll();
					
					self::$logger->info('Loaded all of the BOs (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					AlphaDAO::begin();
					
					$tag = new tag_object();
					$tag->deleteAllByAttribute('taggedClass', $params['clearTaggedClass']);
					
					self::$logger->info('Deleted all of the old tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					foreach ($BOs as $BO) {
						foreach($BO->get('taggedAttributes') as $tagged) {
							$tags = tag_object::tokenize($BO->get($tagged), get_class($BO), $BO->getOID());
							foreach($tags as $tag) {
								try {
									$tag->save();
								}catch(ValidationException $e){
									/*
									 * The unique key has most-likely been violated because this BO is already tagged with this
									 * value, so we can ignore in this case.
									 */
								}
							}
						}
					}

					self::$logger->info('Saved all of the new tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					AlphaDAO::commit();
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Tags recreated on the '.$temp->getFriendlyClassName().' class.'));
					
					self::$logger->info('Tags recreated on the ['.$params['clearTaggedClass'].'] class (time taken ['.round(microtime(true)-$startTime, 5).'] seconds).');
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
					AlphaDAO::rollback();
				}				
			}
			
			$this->doGET($params);
		}catch(SecurityException $e) {
			throw new ResourceNotAllowedException($e->getMessage());
			
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
}

// now build the new controller if this file is called directly
if ('TagManager.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new TagManager();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>