<?php

// $Id$

if(empty($sysRoot))
	require_once '../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/model/article_object.inc';

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
		global $sysURL;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$article = new article_object();
		$article_objects = $article->load_all(0, $article->get_count());
				
		$sections_enum = $article->get_prop_object("section");
		$sections = $sections_enum->get_options();
		
		$this->set_title("Site Map");
		
		$this->display_page_head();
		
		foreach ($sections as $section) {
			echo "<h2>$section</h2>";
			echo '<ul>';
			foreach($article_objects as $article) {
				if($article->section->get_value() == $section && $article->published->get_value() == 1)
					echo '<li><a href="'.$sysURL.'/alpha/controller/view_article_title.php?title='.$article->get("title").'">'.$article->get("title").'</a></li>';
			}
			echo '</ul>';			
		}
		
		$this->display_page_foot();
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "site_map.php")
	$controller = new site_map();

?>
