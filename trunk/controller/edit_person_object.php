<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
// load the business object (BO) definition
require_once $config->get('sysRoot').'alpha/model/person_object.inc';

/**
* 
* Controller used to edit a person object
* 
* @package Alpha Admin
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class edit_person_object extends Controller
{
	/**
	 * the new to be edited
	 * @var object BO
	 */
	var $BO;
	
	/**
	 * the OID of the BO to be loaded
	 * @var int
	 */
	var $BO_oid;
	
	/**
	 * the new default View object used for rendering the object to edit
	 * @var View BO_view
	 */
	var $BO_View;
								
	/**
	 * constructor that renders the page	 
	 */
	function edit_person_object() {
		global $config;
		
		// ensure that a OID is also provided
		if (isset($_GET["oid"])) {
			$BO_oid = $_GET["oid"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the person object as an oid was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new person_object();
		$this->BO->load_object($BO_oid);
				
		$this->BO_View = View::get_instance($this->BO);
		
		// set up the title and meta details
		$this->set_title("Editing the profile for ".$this->BO->get_displayname());
		$this->set_description("Page to edit a person.");
		$this->set_keywords("edit,person");
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}	
		
		$this->display_page_head();
		
		$this->render_delete_form();
		
		if(!empty($_POST)) {
			$this->handle_post();
			return;
		}
		
		$this->BO_View->edit_view();		
		
		$this->display_page_foot();
	}	
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {
		global $config;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["saveBut"])) {			
			
			// populate the transient object from post data
			$this->BO->populate_from_post();
			
			// check to see if the password was reset
			if (!empty($_POST["new_password"]))
				$this->BO->set_password($_POST["new_password"]);
			$success = $this->BO->save_object();			
			
			$this->BO->load_object($this->BO->get_ID());			
			
			if($success) {
				echo '<p class="success">User profile updated successfully.</p>';
			}
			
			$this->BO_View->set_BO($this->BO);
			
			$this->BO_View->edit_view();		
		
			$this->display_page_foot();
		}
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new person_object();
			
			$temp->load_object($_POST["delete_oid"]);			
					
			$success = $temp->delete_object();
					
			if($success) {
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
			}
			
			echo '<center>';
			if (class_exists("button")) {
				$temp = new button("document.location = '".$config->get('sysURL')."/controller/ListAll.php?bo=".get_class($this->BO)."'","Back to List","cancelBut");
			}else{
				echo '<input type="button" name="cancelBut" value="Back to List" onclick="document.location = \''.$config->get('sysURL').'/controller/ListAll.php?bo='.get_class($this->BO).'\'"/>';
			}
			echo '</center>';
			exit;
		}
	}	
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "edit_person_object.php")
	$controller = new edit_person_object();

?>
