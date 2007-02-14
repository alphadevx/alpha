<?php

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/util/feeds/RSS2.inc';

if (isset($_GET["bo"])) {
	$BO_name = $_GET["bo"];	
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to generate feed!','GET');
	exit;
}

if (isset($_GET["type"])) {
	$type = $_GET["type"];	
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No feed type specified to generate feed!','GET');
	exit;
}

/**
 *
 * Controller for viewing news feeds
 * 
 * @package Alpha Feeds
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins
 * 
 */
class view_feed extends Controller
{
	/**
	 * the name of the BO to render as a feed
	 * @var string
	 */
	var $BO_name;
	
	/**
	 * the type of feed to render (RSS, RSS2 or Atom)
	 * @var string
	 */
	var $type;
	
	/**
	 * the title of the feed
	 * @var string
	 */
	var $title;
	
	/**
	 * the description of the feed
	 * @var string
	 */
	var $description;
	/**
	 * the BO to feed field mappings
	 * @var array
	 */
	var $field_mappings;
	
	/**
	 * constructor to set up the object
	 * @param string $BO_name the name of the BO to render as a feed
	 * @param string $type the type of feed to render (RSS, RSS2 or Atom)	 
	 * 
	 */
	function view_feed($BO_name, $type) {
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO_name = $BO_name;
		$this->type = $type;		
		
		$this->setup();
		
		switch($type) {
			case 'RSS2':
				$feed = new RSS2($BO_name, $this->title, str_replace('&', '&amp;', $_SERVER["REQUEST_URI"]), $this->description);
				$feed->set_field_mappings($this->field_mappings[0], $this->field_mappings[1], $this->field_mappings[2]);
			break;
		}
		
		// now add the twenty last items (from newest to oldest) to the feed, and render
		$feed->add_items(20);
		echo $feed->dump();
	}
	
	/**
	 * setup the feed title, field mappings and description based on common BO types 
	 */
	function setup() {
		global $sysTitle;
		
		// set up some BO to feed fields mappings based on common BO types
		switch($this->BO_name) {
			case 'article_object':
				$this->title = "Latest articles from ".$sysTitle;
				$this->description = "News feed containing all of the details on the latest articles published on ".$sysTitle.".";
				$this->field_mappings = array("title", "URL", "description");
			break;
			case 'news_object':
				$this->title = "Latest news from ".$sysTitle;
				$this->description = "News feed containing all of the latest news items from ".$sysTitle.".";
				$this->field_mappings = array("title", "URL", "content");
			break;
		}
	}
}

// now build the new controller
$controller = new view_feed($BO_name, $type);

?>