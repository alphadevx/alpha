<?php

//require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
//require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
//require_once $config->get('sysRoot').'alpha/view/View.inc';
//require_once $config->get('sysRoot').'alpha/util/LogFile.inc';
require_once $config->get('sysRoot').'alpha/util/feeds/AlphaFeed.inc';

/**
 * 
 * RSS 2.0 class for syndication
 * 
 * @package alpha::util::feeds
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class RSS2 extends AlphaFeed {
	/**
	 * The XML namespace
	 * 
	 * @var string
	 */
	protected $ns = null;

	/**
	 * The main feed tag
	 * 
	 * @var string
	 */
	protected $shell = '<rss version="2.0" />';	
	
	/**
	 * Constructor
	 * 
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param string $pubDate
	 * @param integer $id
	 */
	public function __construct($title, $url, $description, $pubDate=null, $id=null) {
		parent::__construct($title, $url, $description, $pubDate, $id);
		$this->docElement = $this->root;
	}
}

?>