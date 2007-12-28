<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'model/news_object.inc';

/**
* 
* Controller used to generate a site map of all of the articles in the database
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha CMS
* @copyright 2007 John Collins
*
*/
class site_map extends Controller
{								
	/**
	 * constructor that renders the page	
	 */
	function site_map() {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$article = new article_object();
		$article_objects = $article->load_all(0, $article->get_count(), "date_added");
				
		$sections_enum = $article->get_prop_object("section");
		$sections = $sections_enum->get_options();
		
		$this->set_title("Site Map");
		
		$this->display_page_head();
		
		foreach ($sections as $section) {
			echo "<h2>$section</h2>";
			echo '<ul>';
			foreach($article_objects as $article) {
				if($article->section->get_value() == $section && $article->published->get_value() == 1)
					echo '<li><a href="'.$article->URL.'">'.$article->get("title").'</a></li>';
			}
			echo '</ul>';
		}
		
		// now list out the news items
		$news = new news_object();
		$news_objects = $news->load_all(0, $news->get_count(), "OID", "DESC");
				
		echo "<h2>News Items</h2>";
		echo '<ul>';
		foreach($news_objects as $news) {			
			echo '<li><a href="'.$news->URL.'">'.$news->get("title").'</a></li>';
		}
		echo '</ul>';		
		
		$this->display_page_foot();
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "site_map.php")
	$controller = new site_map();

?>
