<?php

require_once '../config/config.conf';
require_once '../view/View.inc';
require_once '../config/db_connect.inc';
require_once '../controller/Controller.inc';
require_once '../model/article_object.inc';

/**
* 
* Controller used to preview a new article before commiting it to the database
* 
* @author John Collins <john@design-ireland.net>
* @package Design-Ireland
*
*/
class preview_article_object extends Controller
{
	/**
	 * the new article to be created
	 * @var article_object
	 */
	var $BO;
				
	/**
	 * constructor that renders the page and intercepts POST messages
	 */
	function preview_article_object() {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		$this->BO = $this->new_objects[0];
		
		if(!empty($_POST))
			$this->handle_post();		
		
		$this->set_name('create_article_object.php');
		$this->set_unit_of_work(array('create_article_object.php','preview_article_object.php'));
		
		// set up the title and meta details
		$this->set_title($this->BO->get("title")." (preview)");
		$this->set_description("Page to create a new article.");
		$this->set_keywords("create,new,article");
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}	
		
		$this->display_page_head();
		
		$view = View::get_instance($this->BO);
		
		$view->markdown_view();
		
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
		
		if(isset($_POST["saveBut"])) {
			// save the new photo and load it back from the DB
			$this->commit();
			$this->BO->load_object($this->BO->get_MAX());
			
			$this->BO->create_attachments_folder();
				
			// redirect to the detailed display page			
			header('Location: Detail.php?bo='.get_class($this->BO).'&oid='.$this->BO->get_ID());
		}elseif (isset($_POST["cancelBut"])) {
			$this->abort();			
			header('Location: '.$sysURL);
		}
	}
	
	/**
	 * method to display the page footer with save/cancel buttons
	 */
	function display_page_foot() {
		global $sysURL;
		
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		if (class_exists("button")) {
			$temp = new button("submit", "Save New Article", "saveBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("submit", "Cancel", "cancelBut");
		} else {
			echo '<input type="submit" name="saveBut" value="Save New Article"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="submit" name="cancelBut" value="Cancel"/>';
		}
		View::render_security_fields();
		echo '</form>';
		echo '</body>';
		echo '</html>';
	}
}

// now build the new controller
$controller = new preview_article_object();

?>
