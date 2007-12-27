<?php

// $Id$

require_once $config->get('sysRoot').'config/db_connect.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/log_file.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/feed.inc';

/**
* 
* Atom class for synication
* 
* @package Alpha Feeds
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class Atom extends feed {
	var $NS = "http://www.w3.org/2005/Atom";	
	var $SHELL = '<feed xmlns="http://www.w3.org/2005/Atom" />';
	var $has_channel = false;
	var $tag_map = array('item'=>'entry','feeddesc'=>'subtitle','itemdesc'=>'summary');
	
	function create_link($parent, $url) {
		$link = $this->rss_doc->createElementNS($this->NS, 'link');
		$parent->appendChild($link);
		$link->setAttribute('href', $url);
	}
	
	function Atom($title, $url, $description, $pub_date = null, $id = null) {
		if(empty($id))
			$id = $url;
		if(empty($pub_date))
			$pub_date = date("Y-m-d");
		parent::feed($title, $url, $description, $pub_date, $id);
	}
	
	function add_author($name) {
		$author = $this->rss_doc->createElementNS($this->NS, 'author');
		
		if($this->doc_element->appendChild($author)) {
			$namenode = $this->rss_doc->createElementNS($this->NS, 'name', $name);
			if($author->appendChild($namenode)) {
				return true;
			}
		}
		
		return false;
	}
	
	function add_item($title, $link, $description=null, $pub_date = null, $id = null) {
		if(empty($id))
			$id = $link;
		if(empty($pub_date))
			$pub_date = date("Y-m-d");
		return parent::add_item($title, $link, $description, $pub_date, $id);
	}
}

?>