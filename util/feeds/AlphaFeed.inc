<?php

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/util/MarkdownFacade.inc';

/**
 * Base feed class for generating syndication feeds
 * 
 * @package alpha::util::feeds
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
abstract class AlphaFeed {
	/**
	 * The DOMDocument object used to create the feed
	 * 
	 * @var DOMDocument
	 */
	protected $rssDoc;
	
	/**
	 * The DOMElement object used to hold the item or entry elements
	 * 
	 * @var DOMElement
	 */
	protected $docElement;
	
	/**
	 * Holds the DOMElement to which metadata is added for the feed
	 * 
	 * @var DOMElement
	 */
	protected $root;
	
	/**
	 * An array of feed items
	 * 
	 * @var array
	 */
	protected $items;
	
	/**
	 * If the feed format has a channel or not
	 * 
	 * @var boolean
	 */
	protected $hasChannel = true;
	
	/**
	 * Maps the tags to the feed-specific tags
	 * 
	 * @var array
	 */
	protected $tagMap = array('item'=>'item','feeddesc'=>'description','itemdesc'=>'description');
	
	/**
	 * The BO which we will serve up in this feed
	 * 
	 * @var BO
	 */
	private $BO;
	
	/**
	 * An array containing BO field names -> RSS field name mappings
	 * 
	 * @var array
	 */
	protected $fieldNameMappings;
	
	/**
	 * The position in the $tagMap array of the item type
	 * 
	 * @var integer
	 */
	const ITEM = 0;
	
	/**
	 * The position in the $tagMap array of the feed type
	 * 
	 * @var integer
	 */
	const FEED = 1;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;		
	
	/**
	 * The constructor
	 * 
	 * @param string $BOName
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 * @param integer $limit
	 */
	public function __construct($BOName, $title, $url, $description, $pubDate=null, $id=null, $limit = 10) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaFeed');
		
		$this->rssDoc = new DOMDocument();
		$this->rssDoc->loadXML($this->shell);
		$this->docElement = $this->rssDoc->documentElement;
		$this->BO = $this->loadBOClass($BOName);		
		
		if ($this->hasChannel) {
			$root = $this->createFeedElement($this->ns, 'channel');
			$this->root = $this->docElement->appendChild($root);
		}else{
			$this->root = $this->docElement;
		}
		
		$this->createRSSNode(self::FEED, $this->root, $title, $url, $description, $pubDate, $id);
	}
	
	/**
	 * Method to add all of the BO items to the feed, from the newest to the $limit provided 
	 * 
	 * @param integer $limit
	 * @param string $sortBy
	 */
	public function addItems($limit, $sortBy) {		
		
		$BOs = $this->BO->loadAll(0, $limit, $sortBy, 'DESC');
		
		foreach($BOs as $BO) {
			$title = $BO->get($this->fieldNameMappings['title']);			
			$link = $BO->get($this->fieldNameMappings['link']);
			
			if(isset($this->fieldNameMappings['description'])) {
				// if its a news item, we want the raw HTML rather than the Markdown content in the feed
				if(get_class($BO) == 'news_object') {
					$markdown = new MarkdownFacade($BO);
					$description = $markdown->getContent();
				}else{
					$description = $BO->get($this->fieldNameMappings['description']);
				}
			}else{
				$description = '';
			}
			
			if(isset($this->fieldNameMappings['pubDate'])) {
				$dateTS = strtotime($BO->get($this->fieldNameMappings['pubDate']));
				$pubDate = date(DATE_ATOM, $dateTS);
			}else{
				$pubDate = '';
			}
			
			if(isset($this->fieldNameMappings['id']))
				$id = $BO->get($this->fieldNameMappings['id']);
			else
				$id = '';
			
			$this->addItem($title, $link, $description, $pubDate, $id);
		}
	}
	
	/**
	 * Method for mapping BO fieldnames to feed field names
	 * 
	 * @param string $title
	 * @param string $link
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 */
	public function setFieldMappings($title, $link, $description=null, $pubDate=null, $id=null) {
		$this->fieldNameMappings = array(
			'title' => $title,
			'link' => $link			
		);
		
		if(isset($description))
			$this->fieldNameMappings['description'] = $description;
			
		if(isset($pubDate))
			$this->fieldNameMappings['pubDate'] = $pubDate;
			
		if(isset($id))
			$this->fieldNameMappings['id'] = $id;
	}
	
	/**
	 * Method to return a transient instance of a class provided by name
	 * 
	 * @param $BOName
	 * @return BO
	 */
	private function loadBOClass($BOName) {
		global $config;
		
		try {
		
			AlphaDAO::loadClassDef($BOName);
			
			$BO = new $BOName();
			return $BO;
		}catch(IllegalArguementException $e) {
			self::$logger->fatal('Failed to find the definition for the class ['.$BO_name.'] while trying to render a feed!');
			exit;
		}
	}
	
	/**
	 * Method for creating a new feed element
	 * 
	 * @param string $namespace
	 * @param string $name
	 * @param string $value
	 * @return DOMElement
	 */
	protected function createFeedElement($namespace, $name, $value=null) {
		$value = str_replace('&', '&amp;', $value); 
		if(is_null($namespace)) {			
			return $this->rssDoc->createElement($name, $value);
		}else{
			return $this->rssDoc->createElementNS($namespace, $name, $value);
		}
	}
	
	/**
	 * Method for creating link elements (note that Atom has a different format)
	 * 
	 * @param DOMElement $parent
	 * @param string $url
	 */
	protected function createLink($parent, $url) {
		$link = $this->createFeedElement($this->ns, 'link', $url);
		$parent->appendChild($link);
	}
	
	/**
	 * Method for creating an RSS node with a title, link and description
	 * 
	 * @param integer $type
	 * @param DOMElement $parent
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 */
	protected function createRSSNode($type, $parent, $title, $url, $description, $pubDate=null, $id = null) {
		$this->createLink($parent, $url);		
		$title = $this->createFeedElement($this->ns, 'title', $title);		
		$parent->appendChild($title);
		
		if ($type == self::ITEM) {
			$titletag = $this->tagMap['itemdesc'];
		}else{
			$titletag = $this->tagMap['feeddesc'];
		}
		
		$description = $this->createFeedElement($this->ns, $titletag, $description);
		$parent->appendChild($description);
		
		// id elements and updated elements are just for Atom!
		if (!is_null($id)) {
			$idnode = $this->createFeedElement($this->ns, 'id', $id);
			$parent->appendChild($idnode);
		}
		
		if (!is_null($pubDate)) {
			$datenode = $this->createFeedElement($this->ns, 'updated', $pubDate);
			$parent->appendChild($datenode);
		}
	}
	
	/**
	 * Method for adding an item to a feed
	 * 
	 * @param string $title
	 * @param string $link
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 * @return boolean
	 */
	protected function addItem($title, $link, $description=null, $pubDate=null, $id=null) {		
		$item = $this->createFeedElement($this->ns, $this->tagMap['item']);
		
		if ($this->docElement->appendChild($item)) {			
			$this->createRSSNode(self::ITEM, $item, $title, $link, $description, $pubDate, $id);
			return true;
		}
		return false;
	}
	
	/**
	 * Returns the formatted XML for the feed as a string
	 * @return string
	 */
	public function dump() {
		if ($this->rssDoc) {
			$this->rssDoc->formatOutput = true;
			return $this->rssDoc->saveXML();
		}else{
			return '';
		}
	}
}

?>