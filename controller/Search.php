<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

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
			// replace any %20 on the URL with spaces
			$params['q'] = str_replace('%20', ' ', $params['q']);
			
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
						
						self::$logger->debug('There are ['.count($matchingTags).'] tag_objects matching the query ['.$params['q'].']');
						
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
									$weight = intval($BOIDs[$tag->get('taggedOID')]) + 1;
									$BOIDs[$tag->get('taggedOID')] = $weight;									
								}else{
									$BOIDs[$tag->get('taggedOID')] = 1;									
								}
								self::$logger->debug('Found BO ['.$tag->get('taggedOID').'] has weight ['.$BOIDs[$tag->get('taggedOID')].']');								
							}
						}
						
						// sort the BO IDs based on tag frequency weight						
						arsort($BOIDs);						
						
						// render the list view for each BO
						foreach(array_keys($BOIDs) as $oid) {
							try {
								$temp = new $BO;
								$temp->load($oid);
								
								$view = View::getInstance($temp);
								echo $view->listView();
								
								$tags = $temp->getPropObject('tags')->getRelatedObjects();
			
								if(count($tags) > 0) {
									echo '<p>Tags: ';
									
									$queryTerms = explode(' ', strtolower($params['q']));
									
									foreach($tags as $tag) {
										echo (in_array($tag->get('content'), $queryTerms) ? '<strong>'.$tag->get('content').' </strong>' : $tag->get('content').' ');
									}
									
									echo '</p>';
								}
							}catch(BONotFoundException $e) {
								self::$logger->warn('Orpaned tag_object detected pointing to a non-existant BO of OID ['.$oid.'] and type ['.$BO.'].');
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
	
	/**
	 * Displays a search form on the top of the page
	 * 
	 * @return string
	 */
	public function after_displayPageHead_callback() {
		global $config;
		
		$html = '<div align="center"><form method="GET" id="search_form">';
		$html .= 'Search for: <input type="text" size="80" name="q" id="q"/>&nbsp;';		
		$button = new button('document.location = \''.$config->get('sysURL').'search/q/\'+document.getElementById(\'q\').value;', 'Search', 'searchButton');
		$html .= $button->render();
		$html .= '</form></div>';
		
		return $html;
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