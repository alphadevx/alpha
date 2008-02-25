<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/article.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'alpha/util/input_filter.inc';

/**
* 
* Controller used to display a Markdown version of an article
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha CMS
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
	 * the force-frame status for the article
	 * @var boolean 
	 */
	var $force_frame;
	/**
	 * the style-sheet to use for the article
	 * @var string
	 */
	var $style_sheet;
								
	/**
	 * constructor that renders the page	
	 */
	function view_article() {
		global $config;
		
		// ensure that a OID is provided
		if (isset($_GET["oid"])) {
			$article_oid = $_GET["oid"];
			if(!is_numeric($article_oid)) {
				$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as the oid ['.$article_oid.'] supplied is not valid!','GET');
				exit;
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as an oid was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		if(isset($_GET["no-forceframe"]))
			$this->force_frame = false;
		else
			$this->force_frame = true;
			
		$this->style_sheet = $config->get('sysTheme');
		
		$this->article = new article_object();
		$this->article->load_object($article_oid);
		
		// check to see if we need to force a re-direct to the article URL
		if($config->get('sysForceModRewriteURLs') && (basename($_SERVER["PHP_SELF"]) == "view_article.php" || basename($_SERVER["PHP_SELF"]) == "FC.php")) {
			// set the correct HTTP header for the response
    		header('HTTP/1.1 301 Moved Permanently');
    		
    		header('Location: '.$this->article->URL);
 
		    // we're done here
    		exit();
		}
		
		$this->set_title($this->article->get("title"));
		
		$this->display_page_head();
		
		$article_view = new article($this->article);
		$article_view->markdown_view();		
		
		$this->display_comments();
		$this->display_page_foot();
	}
	
	/**
	 * method to render the header mark-up
	 */
	function display_page_head() {
		global $config;
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>'.$this->get_title().'</title>';
		echo '<meta name="Keywords" content="'.$this->get_keywords().'">';
		echo '<meta name="Description" content="'.$this->get_description().'">';
		echo '<meta name="Author" content="john collins">';
		echo '<meta name="copyright" content="copyright ">';
		echo '<meta name="identifier" content="http://'.$config->get('sysURL').'/">';
		echo '<meta name="revisit-after" content="7 days">';
		echo '<meta name="expires" content="never">';
		echo '<meta name="language" content="en">';
		echo '<meta name="distribution" content="global">';
		echo '<meta name="title" content="'.$this->get_title().'">';
		echo '<meta name="robots" content="index,follow">';
		echo '<meta http-equiv="imagetoolbar" content="no">';			
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$this->style_sheet.'.css.php">';
		if($this->force_frame && $config->get('sysForceFrame'))
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/force-frame.js"></script>';
		
		if ($config->get('sysUseWidgets')) {
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/string_box.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/text_box.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/image.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';
		
			echo '<script type="text/javascript">';
			$validator = new form_validator(new article_comment_object());
			echo '</script>';
		}
		
		if (!empty($this->article->header_content))
			echo $this->article->header_content->get_value();
		
		echo '</head>';
		echo '<body'.(!empty($this->article->body_onload) ? ' onload="'.$this->article->body_onload->get_value().'"' : '').'>';
		
		if($config->get('sysCMSDisplayStandardHeader')) {
			echo '<p><a href="'.$config->get('sysURL').'">'.$config->get('sysTitle').'</a> &nbsp; &nbsp;';
			$prop_obj = $this->article->get_prop_object("section");
			echo 'Site Section: <em>'.$prop_obj->get_value().'</em> &nbsp; &nbsp;';
			$prop_obj = $this->article->get_prop_object("date_added");
			echo 'Date Added: <em>'.$prop_obj->get_value().'</em> &nbsp; &nbsp;';
			$prop_obj = $this->article->get_prop_object("date_updated");
			echo 'Last Updated: <em>'.$prop_obj->get_value().'</em> &nbsp; &nbsp;';
			echo 'Revision: <em>'.$this->article->get_version().'</em></p>';
		}
		
		echo $config->get('sysCMSHeader');
		
		if(!empty($_POST))
			$this->handle_post();
	}
	
	/**
	 * method to display the page footer
	 */
	function display_page_foot() {
		global $config;
		
		$rating = $this->article->get_score();
		$votes = $this->article->get_votes();
		
		if($config->get('sysCMSDisplayVotes'))
			echo '<p>Average Article User Rating: <strong>'.$rating.'</strong> out of 10 (based on <strong>'.count($votes).'</strong> votes)</p>';
		
		if(!$this->article->check_user_voted() && $config->get('sysCMSVotingAllowed')) {
			echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="post">';
			echo '<p>Please rate this article from 1-10 (10 being the best):' .
					'<select name="user_vote">' .
					'<option value="1">1' .
					'<option value="2">2' .
					'<option value="3">3' .
					'<option value="4">4' .
					'<option value="5">5' .
					'<option value="6">6' .
					'<option value="7">7' .
					'<option value="8">8' .
					'<option value="9">9' .
					'<option value="10">10' .
					'</select></p>&nbsp;&nbsp;';
			$temp = new button("submit","Vote!","voteBut");
			View::render_security_fields();
			echo "<form>";
		}
		
		echo "&nbsp;&nbsp;";
		$temp = new button("window.open('".$this->article->printURL."')","Open Printer Version","printBut");
		
		echo "&nbsp;&nbsp;";
		if($config->get('sysAllowPDFVersions'))
			$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/view_article_pdf.php?title=".$this->article->get("title")."';","Open PDF Version","pdfBut");
		
		if($config->get('sysCMSDisplayStandardFooter')) {
			echo '<p>Article URL: <a href="'.$this->article->URL.'">'.$this->article->URL.'</a><br>';
			echo 'Title: '.$this->article->get("title").'<br>';
			echo 'Author: '.$this->article->get("author").'<br>';
		}
		echo $config->get('sysCMSFooter').'</p>';
		echo '</body>';
		echo '</html>';
	}
	
	/**
	 * handles the user posting article ratings or comments
	 */
	function handle_post() {
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
			
		if(isset($_POST["voteBut"]) && !$this->article->check_user_voted()) {
			$vote = new article_vote_object();
			$vote->set("article_oid", $this->article->get_ID());
			$vote->set("person_oid", $_SESSION["current_user"]->get_ID());
			$vote->set("score", $_POST["user_vote"]);
			$success = $vote->save_object();
			if($success)
				echo '<p class="success">Thank you for rating this article!</p>';
		}
		
		if(isset($_POST["createBut"])) {
			$comment = new article_comment_object();
			
			// populate the transient object from post data
			$comment->populate_from_post();
			
			// filter the comment before saving
			$filter = new input_filter($comment->get_prop_object("content"));
			$comment->set("content", $filter->encode());
			
			$success = $comment->save_object();			
			
			if($success) {
				echo '<p class="success">Thank you for your comment!</p>';
			}
		}
		
		if(isset($_POST["saveBut"])) {			
			$comment = new article_comment_object();
			$comment->load_object($_POST["OID"]);
			
			// re-populates the old object from post data
			$comment->populate_from_post();			
			
			// filter the comment before saving
			$filter = new input_filter($comment->get_prop_object("content"));
			$comment->set("content", $filter->encode());
			
			$success = $comment->save_object();			
			
			if($success) {
				echo '<p class="success">Your comment has been updated.</p>';
			}
		}
	}
	
	/**
	 * method for displaying the user comments for the article
	 */
	function display_comments() {
		global $config;
		
		$comments = $this->article->get_comments();
		$comment_count = count($comments);
		
		if($config->get('sysCMSDisplayComments') && $comment_count > 0) {
			echo "<h2>There are [".$comment_count."] user comments for this article</h2>";
			
			for($i = 0; $i < $comment_count; $i++) {
				$view = View::get_instance($comments[$i]);
				$view->markdown_view();
			}
		}
		
		if(isset($_SESSION["current_user"]) && $config->get('sysCMSCommentsAllowed')) {
			$comment = new article_comment_object();
			$comment->set("article_oid", $this->article->get_ID());
			
			$view = View::get_instance($comment);
			$view->create_view();
		}
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "view_article.php")
	$controller = new view_article();

?>
