<?php

require_once $config->get('sysRoot').'alpha/util/feeds/AlphaFeed.inc';

/**
 * 
 * RSS 1.0 class for synication
 * 
 * @package alpha::util::feeds
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class RSS extends AlphaFeed {
	/**
	 * The XML namespace
	 * 
	 * @var string
	 */
	protected $ns = 'http://purl.org/rss/1.0/';
	
	/**
	 * The RDF namespace
	 * 
	 * @var string
	 */
	private $rdfns = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	
	/**
	 * The main feed tag
	 * 
	 * @var string
	 */
	protected $shell = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" />';
	
	/**
	 * Add a URL to feed item
	 * 
	 * @param $url
	 */
	private function addToItems($url) {
		if(is_null($this->items)) {
			$container = $this->createFeedElement($this->ns, 'items');
			$this->root->appendChild($container);
			$this->items = $this->rssDoc->createElementNS($this->rdfns, 'Seq');
			$container->appendChild($this->items);
		}
		
		$item = $this->rssDoc->createElementNS($this->rdfns, 'li');
		$this->items->appendChild($item);
		$item->setAttribute('resource', $url);
	}
	
	/**
	 * Method for adding an item to a feed
	 * 
	 * (non-PHPdoc)
	 * @see alpha/util/feeds/AlphaFeed#addItem()
	 */
	protected function addItem($title, $link, $description = null, $pubDate = null, $id = null) {
		if(parent::addItem($title, $link, $description, $pubDate, $id)) {
			$this->addToItems($link);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Method for creating an RSS node with a title, link and description
	 * 
	 * (non-PHPdoc)
	 * @see alpha/util/feeds/AlphaFeed#createRSSNode()
	 */
	protected function createRSSNode($type, $parent, $title, $url, $description, $pubDate = null) {
		$parent->setAttributeNS($this->rdfns, 'rdf:about', $url);
		parent::createRSSNode($type, $parent, $title, $url, $description, $pubDate);
	}	
}

?>