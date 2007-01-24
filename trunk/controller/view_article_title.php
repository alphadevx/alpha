<?php

// $Id$

if(empty($sysRoot))
	require_once '../../config/config.conf';
require_once $sysRoot.'alpha/controller/view_article.php';

/**
* 
* Controller used to display a Markdown version of a page article where the title is provided in GET vars
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha CMS
* @copyright 2006 John Collins
*
*/
class view_article_title extends view_article
{								
	/**
	 * constructor that renders the page	
	 */
	function view_article_title() {
		global $sysTheme;
		
		// ensure that a title is provided
		if (isset($_GET["title"])) {
			$title = $_GET["title"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as a title was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		if(isset($_GET["no-forceframe"]))
			$this->force_frame = false;
		else
			$this->force_frame = true;
			
		$this->style_sheet = $sysTheme;
		
		$this->article = new article_object();
		$this->article->load_by_title($title);
		
		$this->set_title($this->article->get("title"));
		
		$this->display_page_head();
		
		$article_view = new article($this->article);
		$article_view->markdown_view();		
		
		$this->display_comments();
		$this->display_page_foot();
	}	
}

// now build the new controller
$controller = new view_article_title();

?>
