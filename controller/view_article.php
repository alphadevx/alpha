<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/view/article.inc';
require_once $sysRoot.'alpha/model/article_object.inc';

// ensure that a OID is provided
if (isset($_GET["oid"])) {
	$article_oid = $_GET["oid"];
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as an oid was not supplied!','GET');
	exit;
}

/**
* 
* Controller used to display a Markdown version of an article
* 
* @author John Collins <john@design-ireland.net>
* @package Design-Ireland
* @copyright 2006 John Collins
*
*/
class view_article extends Controller
{
	/**
	 * the article to be rendered
	 * @var article_object
	 */
	var $article;	
								
	/**
	 * constructor that renders the page
	 * @param int $article_oid the ID of the article to display
	 */
	function view_article($article_oid) {		
		
		// ensure that the super class constructor is called
		$this->Controller();		
		
		$this->article = new article_object();
		$this->article->load_object($article_oid);
		
		$this->set_title($this->article->get("title"));
		
		$this->display_page_head();
		
		$article_view = new article($this->article);
		$article_view->markdown_view();		
		
		$this->display_page_foot();
	}
	
	/**
	 * method to render the header mark-up
	 */
	function display_page_head() {
		global $sysURL;
		global $sysTheme;
		global $sysUseWidgets;
		global $sysRoot;
		global $sysForceFrame;
		global $sysTitle;
		global $sysCMSHeader;
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>'.$this->get_title().'</title>';
		echo '<meta name="Keywords" content="'.$this->get_keywords().'">';
		echo '<meta name="Description" content="'.$this->get_description().'">';
		echo '<meta name="Author" content="john collins">';
		echo '<meta name="copyright" content="copyright ">';
		echo '<meta name="identifier" content="http://'.$sysURL.'/">';
		echo '<meta name="revisit-after" content="7 days">';
		echo '<meta name="expires" content="never">';
		echo '<meta name="language" content="en">';
		echo '<meta name="distribution" content="global">';
		echo '<meta name="title" content="'.$this->get_title().'">';
		echo '<meta name="robots" content="index,follow">';
		echo '<meta http-equiv="imagetoolbar" content="no">';			
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$sysURL.'/config/css/'.$sysTheme.'.css.php">';
		if(!isset($_GET["no-forceframe"]) && $sysForceFrame)
			echo '<script language="JavaScript" src="'.$sysURL.'/alpha/scripts/force-frame.js"></script>';
		
		if ($sysUseWidgets) {
			echo '<script language="JavaScript" src="'.$sysURL.'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $sysRoot.'alpha/view/widgets/button.js.php';			
		}
		
		if (!empty($this->article->header_content))
			echo $this->article->header_content->get_value();
		
		echo '</head>';
		echo '<body'.(!empty($this->article->body_onload) ? ' onload="'.$this->article->body_onload->get_value().'"' : '').'>';
		echo '<p><a href="'.$sysURL.'">'.$sysTitle.'</a> &nbsp; &nbsp;';
		$prop_obj = $this->article->get_prop_object("section");
		echo 'Site Section: <em>'.$prop_obj->get_value().'</em> &nbsp; &nbsp;';
		$prop_obj = $this->article->get_prop_object("date_added");
		echo 'Date Added: <em>'.$prop_obj->get_date().'</em> &nbsp; &nbsp;';
		$prop_obj = $this->article->get_prop_object("date_updated");
		echo 'Last Updated: <em>'.$prop_obj->get_date().'</em> &nbsp; &nbsp;';
		echo 'Revision: <em>'.$this->article->get_version().'</em></p>';
		echo $sysCMSHeader;
	}
	
	/**
	 * method to display the page footer
	 */
	function display_page_foot() {
		global $sysURL;
		global $sysCMSFooter;
		
		$rating = $this->article->get_score();
		$votes = $this->article->get_votes();
		
		echo '<p>Article User Rating: <strong>'.$rating.'</strong> (based on <strong>'.count($votes).'</strong> votes)</p>';
		
		echo '<p>Article URL: <a href="'.$sysURL.'/alpha/controller/view_article_title.php?title='.$this->article->get("title").'">'.$sysURL.'/alpha/controller/view_article_title.php?title='.$this->article->get("title").'</a><br>';
		echo 'Title: '.$this->article->get("title").'<br>';
		echo 'Author: '.$this->article->get("author").'<br>';
		echo $sysCMSFooter.'</p>';
		echo '</body>';
		echo '</html>';
	}
}

// now build the new controller
$controller = new view_article($article_oid);

?>
