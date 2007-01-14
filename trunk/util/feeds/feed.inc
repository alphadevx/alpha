<?php

// $Id$

require_once '../../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/util/handle_error.inc';
require_once $sysRoot.'alpha/view/View.inc';
require_once $sysRoot.'alpha/util/log_file.inc';

/**
* 
* Base feed class for synication (should not be instantiated directly)
* 
* @package Alpha Feeds
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class feed {
	/**
	 * the DOMDocument object used to create the feed.
	 * @var DOMDocument
	 */
	var $rss_doc;
	
	/**
	 * the DOMElement object used to hold the item or entry elements.
	 * @var DOMElement
	 */
	var $doc_element;
	
	/**
	 * holds the DOMElement to which metadata is added for the feed
	 * @var DOMElement
	 */
	var $root;
	
	/**
	 * 
	 */
	var $items;
	
	/**
	 * 
	 */
	var $has_channel;
	
	/**
	 * 
	 */
	var $tag_map = array('item'=>'item','feeddesc'=>'description','itemdesc'=>'description');
	
	var $ITEM = 0;
	var $FEED = 1;
	
	/**
	 * the constructor method
	 */
	function feed($title, $url, $description, $publish_date=null, $id=null) {
		$this->rss_doc = new DOMDocument();
		$this->rss_doc->loadXML($this->SHELL);
		$this->doc_element = $this->rss_doc->documentElement;
		
		if ($this->has_channel) {
			$root = $this->create_feed_element($this->NS, 'channel');
			$this->root = $this->doc_element->appendChild($root);
		}else{
			$this->root = $this->doc_element;
		}
		
		$this->create_RSS_node($this->FEED, $this->root, $title, $url, $description, $publish_date, $id);
		
		return;
	}
	
	/**
	 * method for creating namespace for feeds
	 */
	function create_feed_element($namespace, $name, $value=null) {
		if(is_null($namespace)) {
			return $this->rss_doc->createElement($name, $value);
		}else{
			return $this->rss_doc->createElementNS($namespace, $name, $value);
		}
	}
	
	/**
	 * method for creating link elements (note that Atom has a different format)
	 */
	function create_link($parent, $url) {
		$link = $this->create_feed_element($this->NS, 'link', $url);
		$parent->appendChild($link);
	}
	
	/**
	 * method for creating an RSS node with a title, link and description
	 */
	function create_RSS_node($type, $parent, $title, $url, $description, $publish_date=null, $id = null) {
		$this->create_link($parent, $url);
		$title = $this->create_feed_element($this->NS, 'title', $title);
		$parent->appendChild($title);
		
		if ($type == $this->ITEM) {
			$titletag = $this->tag_map['itemdesc'];
		}else{
			$titletag = $this->tag_map['feeddesc'];
		}
		
		$description = $this->create_feed_element($this->NS, $titletag, $description);
		$parent->appendChild($description);
		
		// id elements and updated elements are just for Atom!
		if (!is_null($id)) {
			$idnode = $this->create_feed_element($this->NS, 'id', $id);
			$parent->appendChild($idnode);
		}
		
		if (!is_null($publish_date)) {
			$datenode = $this->create_feed_element($this->NS, 'updated', $publish_date);
			$parent->appendChild($datenode);
		}
	}
	
	/**
	 * method for adding an item to a feed
	 */
	function add_item($title, $link, $description=null, $publish_date=null, $id=null) {
		$item = $this->create_feed_element($this->NS, $this->tag_map['item']);
		
		if ($this->doc_element->appendChild($item)) {
			$this->create_RSS_node($this->ITEM, $item, $title, $link, $description, $publish_date, $id);
			return true;
		}
		return false;
	}
	
	/**
	 * returns the formatted XML for the feed as a string
	 */
	function dump() {
		if ($this->rss_doc) {
			$this->rss_doc->formatOutput = true;
			return $this->rss_doc->saveXML();
		}else{
			return "";
		}
	}
}

?>
