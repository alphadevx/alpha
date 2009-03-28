<?php

require_once $config->get('sysRoot').'alpha/util/feeds/AlphaFeed.inc';

/**
 * 
 * Atom class for syndication
 * 
 * @package alpha::util::feeds
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class Atom extends AlphaFeed {
	/**
	 * The XML namespace
	 * 
	 * @var string
	 */
	protected $ns = 'http://www.w3.org/2005/Atom';

	/**
	 * The main feed tag
	 * 
	 * @var string
	 */
	protected $shell = '<feed xmlns="http://www.w3.org/2005/Atom" />';
	
	/**
	 * Maps the tags to the feed-specific tags
	 * 
	 * @var array
	 */
	protected $tagMap = array('item'=>'entry','feeddesc'=>'subtitle','itemdesc'=>'summary');
		
	/**
	 * Used to create a link in the feed
	 * 
	 * (non-PHPdoc)
	 * @see alpha/util/feeds/AlphaFeed#createLink($parent, $url)
	 */
	protected function createLink($parent, $url) {
		$link = $this->rssDoc->createElementNS($this->ns, 'link');
		$parent->appendChild($link);
		$link->setAttribute('href', $url);
	}
	
	/**
	 * Constructor to create a new Atom feed
	 * 
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 */
	public function __construct($title, $url, $description, $pubDate = null, $id = null) {
		if(empty($id))
			$id = $url;
		if(empty($pubDate))
			$pubDate = date("Y-m-d");
		parent::__construct($title, $url, $description, $pubDate, $id);
	}
	
	/**
	 * Adds an auther to a feed
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function addAuthor($name) {
		$author = $this->rssDoc->createElementNS($this->ns, 'author');
		
		if($this->docElement->appendChild($author)) {
			$namenode = $this->rssDoc->createElementNS($this->ns, 'name', $name);
			if($author->appendChild($namenode)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Adds a new item to the Atom feed
	 * 
	 * (non-PHPdoc)
	 * @see alpha/util/feeds/AlphaFeed#addItem($title, $link, $description, $publish_date, $id)
	 */
	protected function addItem($title, $link, $description=null, $pubDate = null, $id = null) {
		if(empty($id))
			$id = $link;
		if(empty($pubDate))
			$pubDate = date("Y-m-d");
		return parent::addItem($title, $link, $description, $pubDate, $id);
	}
}

?>