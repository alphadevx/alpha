<?php

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/view/View.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/model/article_object.inc';

// load the business object (BO) definition
if (isset($_GET["bo"])) {
	$BO_name = $_GET["bo"];
	if (file_exists($sysRoot.'alpha/model/'.$BO_name.'.inc')) {
		require_once $sysRoot.'alpha/model/'.$BO_name.'.inc';
	}elseif (file_exists($sysRoot.'model/'.$BO_name.'.inc')) {
		require_once $sysRoot.'model/'.$BO_name.'.inc';
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the defination for the BO class '.$BO_name,'GET');
		exit;
	}
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No article available to edit!','GET');
	exit;
}

// ensure that a OID is also provided
if (isset($_GET["oid"])) {
	$BO_oid = $_GET["oid"];
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the article as an oid was not supplied!','GET');
	exit;
}

/**
* 
* Controller used to edit an existing article
* 
* @author John Collins <john@design-ireland.net>
* @package Design-Ireland
*
*/
class edit_article_object extends Controller
{
	/**
	 * the new article to be edited
	 * @var article_object
	 */
	var $BO;
				
	/**
	 * constructor that renders the page and intercepts POST messages
	 * @param string $BO_oid the id of the article that we editing
	 */
	function edit_article_object($BO_oid) {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		$this->BO = new article_object();
		$this->BO->load_object($BO_oid);
		
		if(!empty($_POST)) {			
			$this->handle_post();
			exit;
		}	
		
		// set up the title and meta details
		$this->set_title($this->BO->get("title")." (editing)");		
		
		$this->display_page_head();
		
		$view = View::get_instance($this->BO);
		
		$view->edit_view();
		
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
		
		if (isset($_POST["saveBut"])) {			
			
			// populate the transient object from post data
			$this->BO->populate_from_post();
			
			$success = $this->BO->save_object();
			
			$this->BO->load_object($this->BO->get_ID());
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();		
			
			if($success) {
				echo '<p class="success">Article '.$this->BO->get_ID().' saved successfully.</p>';
			}
			
			$view = View::get_instance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if(isset($_POST["uploadBut"])) {
						
			// upload the file to the attachments directory
			$success = move_uploaded_file($_FILES['userfile']['tmp_name'], $this->BO->get_attachments_location().'/'.$_FILES['userfile']['name']);
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();
			
			if(!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Error :- could not move file: '.$success,'handle_post()','framework');
			
			// set read/write permissions on the file
			$success = chmod($this->BO->get_attachments_location().'/'.$_FILES['userfile']['name'], 0666);
			
			if (!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Unable to set read/write permissions on the file '.$this->BO->get_attachments_location().'/'.$_FILES['userfile']['name'].'.','handle_post()','framework');
			
			if($success) {
				echo '<p class="success">File uploaded successfully.</p>';
			}
			
			$view = View::get_instance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if (!empty($_POST["file_to_delete"])) {			
					
			$success = unlink($this->BO->get_attachments_location().'/'.$_POST["file_to_delete"]);
			
			// set up the title and meta details
			$this->set_title($this->BO->get("title")." (editing)");		
		
			$this->display_page_head();
			
			if(!$success)
				$error = new handle_error($_SERVER["PHP_SELF"],'Error :- could not delete the file: '.$_POST["file_to_delete"],'handle_post()','framework');
			
			if($success) {
				echo '<p class="success">'.$_POST["file_to_delete"].' deleted successfully.</p>';
			}
			
			$view = View::get_instance($this->BO);
		
			$view->edit_view();
		
			$this->display_page_foot();
		}
		
		if (isset($_POST["cancelBut"])) {
			$this->abort();
			//unlink($sysRoot.'photos/'.$this->new_photo->get_title().'_temp.jpg');
			header('Location: '.$sysURL);
		}
	}	
}

// now build the new controller
$controller = new edit_article_object($BO_oid);

?>
