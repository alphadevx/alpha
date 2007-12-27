<?php

// $Id$

require_once $config->get('sysRoot').'config/db_connect.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/log_file.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/feed.inc';

/**
* 
* RSS 2.0 class for synication
* 
* @package Alpha Feeds
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class RSS2 extends feed {
	var $NS = null;
	var $SHELL = '<rss version="2.0" />';
	
	/**
	 * constructor
	 */
	function RSS2($title, $url, $description, $publish_date=null, $id=null) {
		parent::feed($title, $url, $description, $publish_date, $id);
		$this->doc_element = $this->root;
	}
}

?>