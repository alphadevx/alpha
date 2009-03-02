<?php

// $Id$

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/LogFile.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/feed.inc';

/**
* 
* RSS 1.0 class for synication
* 
* @package Alpha Feeds
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class RSS extends feed {
	var $NS = 'http://purl.org/rss/1.0/';
	var $RDFNS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	var $SHELL = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" />';
	
	function addToItems($url) {
		if(is_null($this->items)) {
			$container = $this->createFeedElement($this->NS, 'items');
			$this->root->appendChild($container);
			$this->items = $this->rssDoc->createElementNS(self::RDFNS, 'Seq');
			$container->appendChild($this->items);
		}
		
		$item = $this->rssDoc->createElementNS(self::RDFNS, 'li');
		$this->items->appednChild($item);
		$item->setAttribute("resource", $url);
	}
	
	function addItem($title, $link, $description = null, $pub_date = null, $id = null) {
		if(parent::addItem($title, $link, $description, $pub_date, $id)) {
			$this->addToItems($link);
			return true;
		}
		
		return false;
	}
	
	function createRSSNode($type, $parent, $title, $url, $description, $pub_date = null) {
		$parent->setAttributeNS(self::RDFNS, 'rdf:about', $url);
		parent::createRSSNode($type, $parent, $title, $url, $description, $pub_date);
	}	
}

?>