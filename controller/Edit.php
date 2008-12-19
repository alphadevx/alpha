<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
* 
* Controller used to edit BO, which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
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
	 */
	function Edit() {
		global $config;
		
		// load the business object (BO) definition
		if (isset($_GET["bo"])) {
			$BO_name = $_GET["bo"];
			if (file_exists($config->get('sysRoot').'alpha/model/'.$BO_name.'.inc')) {
				require_once $config->get('sysRoot').'alpha/model/'.$BO_name.'.inc';
			}elseif (file_exists($config->get('sysRoot').'model/'.$BO_name.'.inc')) {
				require_once $config->get('sysRoot').'model/'.$BO_name.'.inc';
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
		if (file_exists($config->get('sysRoot').'controller/edit_'.$BO_name.'.php')) {
			// handle secure URLs
			if(isset($_GET['tk']))
				header('Location: '.Front_Controller::generate_secure_URL('act=edit_'.$BO_name.'&'.Front_Controller::decode_query_params($_SERVER['QUERY_STRING'])));
			else
				header('Location: '.$config->get('sysURL').'/controller/edit_'.$BO_name.'.php?'.$_SERVER['QUERY_STRING']);
		}
		if (file_exists($config->get('sysRoot').'alpha/controller/edit_'.$BO_name.'.php')) {
			// handle secure URLs
			if(isset($_GET['tk']))
				header('Location: '.Front_Controller::generate_secure_URL('act=edit_'.$BO_name.'&'.Front_Controller::decode_query_params($_SERVER['QUERY_STRING'])));
			else
				header('Location: '.$config->get('sysURL').'/alpha/controller/edit_'.$BO_name.'.php?'.$_SERVER['QUERY_STRING']);	
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new $BO_name();
		$this->BO->load($BO_oid);
		
		$this->BO_name = $BO_name;
		
		$this->BO_View = View::getInstance($this->BO);
		
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
		
		$this->BO_View->editView();		
		
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
			$this->BO->populateFromPost();
			
			try {
				$success = $this->BO->save();			
				echo '<p class="success">'.get_class($this->BO).' '.$this->BO->getID().' saved successfully.</p>';
			}catch (LockingException $e) {
				$this->BO->reload();
			}
			
			$this->BO_View->setBO($this->BO);
			
			$this->BO_View->editView();		
		
			$this->display_page_foot();
		}
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new $this->BO_name();
			
			$temp->load($_POST["delete_oid"]);			
					
			try {
				$success = $temp->delete();			
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
				
				echo '<center>';
				$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/ListAll.php?bo=".get_class($this->BO)."'","Back to List","cancelBut");
				echo $temp->render();
				echo '</center>';
			}catch (FailedDeleteException $e) {
				echo '<p class="error">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted failed, database rolled back.</p>';
			}
			exit;
		}
	}	
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "Edit.php")
	$controller = new Edit();

?>