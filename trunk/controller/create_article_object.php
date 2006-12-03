<?php

require_once '../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/view/View.inc';
require_once $sysRoot.'alpha/model/article_object.inc';

/**
* 
* Controller used to create a new article to the database
* 
* @author John Collins <john@design-ireland.net>
* @package Design-Ireland
* @todo Validation must include checking the size of the uploaded file
*
*/
class create_article_object extends Controller
{
	/**
	 * the new article to be created
	 * @var article_object
	 */
	var $new_article;
								
	/**
	 * constructor that renders the page
	 */
	function create_article_object() {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->new_article = new article_object();
		
		$this->set_name('create_article_object.php');
		$this->set_unit_of_work(array('create_article_object.php','preview_article_object.php'));
		
		// set up the title and meta details
		$this->set_title("Create a new Article");
		$this->set_description("Page to create a new article.");
		$this->set_keywords("create,new,article");
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		if(!empty($_POST))
			$this->handle_post();
		
		$this->display_page_head();
		
		$view = View::get_instance($this->new_article);
		
		$view->create_view();		
		
		$this->display_page_foot();
	}	
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {
		global $sysRoot;
		global $sysURL;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["createBut"])) {
			// populate the transient object from post data
			$this->new_article->populate_from_post();
			
			// redirect to the next job after saving the article to the dirty list
			$this->mark_new($this->new_article);
			header('Location: '.$this->get_next_job());
			exit;
		}
		if (isset($_POST["cancelBut"])) {			
			$this->abort();			
			header('Location: '.$sysURL.'/alpha/controller/ListBusinessObjects.php');
		}
	}
	
	/**
	 * method to display the page head
	 */
	function display_page_head() {
		global $sysURL;
		global $sysTheme;
		global $sysUseWidgets;
		global $sysRoot;
		
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
		
		if ($sysUseWidgets) {
			echo '<script language="JavaScript" src="'.$sysURL.'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $sysRoot.'alpha/view/widgets/button.js.php';
			require_once $sysRoot.'alpha/view/widgets/string_box.js.php';
			require_once $sysRoot.'alpha/view/widgets/text_box.js.php';
		
			require_once $sysRoot.'alpha/view/widgets/form_validator.js.php';
		
			echo '<script type="text/javascript">';
			$validator = new form_validator($this->new_article);
			echo '</script>';
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$sysURL.'/logout/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
	}
}

// now build the new controller
$controller = new create_article_object();

?>
