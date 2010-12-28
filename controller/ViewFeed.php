<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/RSS2.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/RSS.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/Atom.inc';
require_once $config->get('sysRoot').'alpha/util/LogFile.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Controller for viewing news feeds
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ViewFeed extends AlphaController implements AlphaControllerInterface {
	/**
	 * The name of the BO to render as a feed
	 * 
	 * @var string
	 */
	private $BOName;
	
	/**
	 * The type of feed to render (RSS, RSS2 or Atom)
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * The title of the feed
	 * 
	 * @var string
	 */
	protected $title;
	
	/**
	 * The description of the feed
	 * 
	 * @var string
	 */
	protected $description;
	
	/**
	 * The BO to feed field mappings
	 * 
	 * @var array
	 */
	protected $fieldMappings;
	
	/**
	 * The BO field name to sort the feed by (descending), default is OID
	 * 
	 * @var string
	 */
	private $sortBy = 'OID';
	
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
		self::$logger = new Logger('ViewFeed');
			
		global $config;		
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');		
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		try {
			if (isset($params['bo'])) {
				$BOName = $params['bo'];	
			}else{
				throw new IllegalArguementException('BO not specified to generate feed!');
			}
			
			if (isset($params['type'])) {
				$type = $params['type'];	
			}else{
				throw new IllegalArguementException('No feed type specified to generate feed!');
			}
		
			$this->BOName = $BOName;
			$this->type = $type;		
			
			$this->setup();
			
			switch($type) {
				case 'RSS2':
					$feed = new RSS2($BOName, $this->title, str_replace('&', '&amp;', $_SERVER["REQUEST_URI"]), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
				break;
				case 'RSS':
					$feed = new RSS($BOName, $this->title, str_replace('&', '&amp;', $_SERVER["REQUEST_URI"]), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
				break;
				case 'Atom':
					$feed = new Atom($BOName, $this->title, str_replace('&', '&amp;', $_SERVER["REQUEST_URI"]), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3], $this->fieldMappings[4]);
					// TODO this should come from param or config
					$feed->addAuthor('John Collins');
				break;
			}
			
			// now add the twenty last items (from newest to oldest) to the feed, and render
			$feed->addItems(20, $this->sortBy);
			echo $feed->dump();
			
			// log the request for this news feed
			$feedLog = new LogFile($config->get('sysRoot').'logs/feeds.log');		
			$feedLog->writeLine(array($this->BOName, $this->type, date("Y-m-d H:i:s"), $_SERVER["HTTP_USER_AGENT"], $_SERVER["REMOTE_ADDR"]));
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
	}
	
	/**
	 * setup the feed title, field mappings and description based on common BO types 
	 */
	protected function setup() {
		global $config;		
		
		// set up some BO to feed fields mappings based on common BO types
		switch($this->BOName) {
			case 'article_object':
				$this->title = 'Latest articles from '.$config->get('sysTitle');
				$this->description = 'News feed containing all of the details on the latest articles published on '.$config->get('sysTitle').'.';
				$this->fieldMappings = array('title', 'URL', 'description', 'created_ts', 'OID');
				$this->sortBy = 'created_ts';
			break;
			case 'news_object':
				$this->title = 'Latest news from '.$config->get('sysTitle');
				$this->description = 'News feed containing all of the latest news items from '.$config->get('sysTitle').'.';
				$this->fieldMappings = array('title', 'URL', 'content', 'created_ts', 'OID');
			break;
		}
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewFeed.php') {
	$controller = new ViewFeed();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>