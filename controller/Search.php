<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/model/tag_object.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/LogFile.inc';

/**
 * 
 * Generic tag-based search engine controller
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class Search extends Controller implements AlphaControllerInterface {
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
			self::$logger = new Logger('Search');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->setTitle('Search results');
		
		//$this->BO = new article_object();
		
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
		
		echo View::displayPageHead($this);
		
		if(isset($params['q'])) {
			echo '<h2>Display results for &quot;'.$params['q'].'&quot;</h2>';
			
			// if a BO name is provided, only search tags on that class, otherwise search all BOs
			if(isset($params['bo']))
				$BOs = array($params['bo']);
			else			
				$BOs = DAO::getBOClassNames();
			
			try {
				foreach($BOs as $BO) {
					DAO::loadClassDef($BO);
					$temp = new $BO;
					
					if($temp->isTagged()) {
						// log the user's search query in a log file
						$log = new LogFile($config->get('sysRoot').'logs/search.log');		
						$log->writeLine(array($params['q'], date('Y-m-d H:i:s'), $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']));
					
						// explode the user's query into a set of tokenized transient tag_objects
						$queryTags = tag_object::tokenize($params['q']);			
						$matchingTags = array();
						
						// load tag_objects from the DB where content equals the content of one of our transient tag_objects
						foreach($queryTags as $queryTag) {
							$tags = $queryTag->loadAllByAttribute('content', $queryTag->get('content'));
							$matchingTags = array_merge($matchingTags, $tags);
						}
						
						/*
						 * Build an array of BOs for the matching tags from the DB:
						 * array key = BO ID
						 * array value = weight (the amount of tags matching the BO)
						 */
						$BOIDs = array();
						foreach($matchingTags as $tag) {							
							if($tag->get('taggedClass') == $BO) {
								if(isset($BOIDs[$tag->get('taggedOID')])) {
									// increment the weight if the same BO is tagged more than once
									$BOIDs[$tag->get('taggedOID')] = intval($BOIDs[$tag->get('taggedOID')]) + 1;									
								}else{
									$BOIDs[$tag->get('taggedOID')] = 1;									
								}								
							}
						}
						
						// sort the BO IDs based on tag frequency weight
						krsort($BOIDs);
						
						// render the list view for each BO
						foreach(array_keys($BOIDs) as $oid) {
							try {
								$temp = new $BO;
								$temp->load($oid);
								
								$view = View::getInstance($temp);
								echo $view->listView();
							}catch(BONotFoundException $e) {
								// TODO orphaned tag!
							}
						}
					}
				}
			}catch(IllegalArguementException $e) {
				self::$logger->fatal($e->getMessage());
				echo '<p class="error"><br>Illegal search query provided!</p>';				
			}
		}else{
			echo '<p class="error"><br>No search query provided!</p>';
		}		
		
		echo View::displayPageFoot($this);
		
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

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Search.php') {
	$controller = new Search();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>