<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/controller/view_article.php';

/**
* 
* Controller used to display a printer-friendly version of an article where the title is provided in GET vars
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha CMS
* @copyright 2006 John Collins
*
*/
class view_article_print extends view_article
{								
	/**
	 * constructor that renders the page	
	 */
	function view_article_print() {
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
		
		$this->force_frame = false;		
			
		$this->style_sheet = "print";
		
		$this->article = new article_object();
		$this->article->load_by_title($title);
		
		$this->set_title($this->article->get("title"));
		
		$this->display_page_head();
		
		$article_view = new article($this->article);
		$article_view->markdown_view();		
		
		$this->display_page_foot();
	}
	
	/**
	 * method to display the page footer
	 */
	function display_page_foot() {
		global $sysURL;
		global $sysCMSFooter;		
		
		$rating = $this->article->get_score();
		$votes = $this->article->get_votes();
		
		echo '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
		
		echo '<p>Article URL: <a href="'.$sysURL.'/alpha/controller/view_article_title.php?title='.$this->article->get("title").'">'.$sysURL.'/alpha/controller/view_article_title.php?title='.$this->article->get("title").'</a><br>';
		echo 'Title: '.$this->article->get("title").'<br>';
		echo 'Author: '.$this->article->get("author").'<br>';
		echo $sysCMSFooter.'</p>';
		echo '</body>';
		echo '</html>';
	}
}

// now build the new controller
$controller = new view_article_print();

?>
