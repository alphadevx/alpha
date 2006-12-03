<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/view/View.inc';


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
	$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to edit!','GET');
	exit;
}

// ensure that a OID is also provided
if (isset($_GET["oid"])) {
	$BO_oid = $_GET["oid"];
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the BO object '.$BO_name.' as an oid was not supplied!','GET');
	exit;
}

// check and see if a custom edit_*.php controller exists for this BO, and if it does use it otherwise continue
if (file_exists($sysRoot.'controller/edit_'.$BO_name.'.php')) {
	header('Location: '.$sysURL.'/controller/edit_'.$BO_name.'.php?'.$_SERVER['QUERY_STRING']);	
}
if (file_exists($sysRoot.'alpha/controller/edit_'.$BO_name.'.php')) {
	header('Location: '.$sysURL.'/alpha/controller/edit_'.$BO_name.'.php?'.$_SERVER['QUERY_STRING']);	
}

/**
* 
* Controller used to edit BO, which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class Edit extends Controller
{
	/**
	 * the new to be edited
	 * @var object BO
	 */
	var $BO;
	
	/**
	 * the name of the BO
	 * @var string
	 */
	var $BO_name;
	
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
	 * @param string $BO_name the name of the BO that we are editing
	 * @param string $BO_oid the id of the object that we editing
	 */
	function Edit($BO_name, $BO_oid) {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new $BO_name();
		$this->BO->load_object($BO_oid);
		
		$this->BO_name = $BO_name;
		
		$this->BO_View = View::get_instance($this->BO);
		
		// set up the title and meta details
		$this->set_title("Editing a ".$BO_name);
		$this->set_description("Page to edit a ".$BO_name.".");
		$this->set_keywords("edit,".$BO_name);
		
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
			
			if($success) {
				echo '<p class="success">'.get_class($this->BO).' '.$this->BO->get_ID().' saved successfully.</p>';
			}
			
			$this->BO_View->set_BO($this->BO);
			
			$this->BO_View->edit_view();		
		
			$this->display_page_foot();
		}
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new $this->BO_name();
			
			$temp->load_object($_POST["delete_oid"]);			
					
			$success = $temp->delete_object();
					
			if($success) {
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
			}
			
			echo '<center>';
			if (class_exists("button")) {
				$temp = new button("document.location = '".$sysURL."/controller/ListAll.php?bo=".get_class($this->BO)."'","Back to List","cancelBut");
			}else{
				echo '<input type="button" name="cancelBut" value="Back to List" onclick="document.location = \''.$sysURL.'/controller/ListAll.php?bo='.get_class($this->BO).'\'"/>';
			}
			echo '</center>';
			exit;
		}
	}	
}

// now build the new controller
$controller = new Edit($BO_name, $BO_oid);

?>
