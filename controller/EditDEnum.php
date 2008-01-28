<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Edit.php';
require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/DEnumView.inc';

/**
* 
* Controller used to edit DEnums and associated DEnumItems
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
*
*/
class EditDEnum extends Edit
{
	/**
	 * constructor that renders the page	
	 */
	function EditDEnum() {
		global $config;
		
		// ensure that a OID is provided
		if (isset($_GET["oid"])) {
			$BO_oid = $_GET["oid"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the DEnum object as an oid was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new DEnum();
		$this->BO->load_object($BO_oid);
		
		$this->BO_name = "DEnum";
		
		$this->BO_View = new DEnumView($this->BO);
		
		// set up the title and meta details
		$this->set_title("Editing a DEnum");
		$this->set_description("Page to edit a DEnum.");
		$this->set_keywords("edit,DEnum");
		
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
			
			$success = $this->BO->save_object();			
			
			$this->BO->load_object($this->BO->get_ID());
			
			// now save the DEnumItems			
			$tmp = new DEnumItem();
			$denumItems = $tmp->load_items($this->BO->get_ID());						
			
			foreach ($denumItems as $item) {
				$item->set("value", $_POST["value_".$item->get_ID()]);
				$this->mark_dirty($item);
			}
			
			// handle new DEnumItem if posted
			if(isset($_POST["new_value"]) && trim($_POST["new_value"]) != "") {
				$newItem = new DEnumItem();
				$newItem->set("value", $_POST["new_value"]);
				$newItem->set("DEnumID", $this->BO->get_ID());
				$this->mark_new($newItem);
			}			
					
			$this->commit();
			
			if($success) {
				echo '<p class="success">'.get_class($this->BO).' '.$this->BO->get_ID().' saved successfully.</p>';
			}
			
			$this->BO_View->set_BO($this->BO);
			
			$this->BO_View->edit_view();		
		
			$this->display_page_foot();
		}		
	}
	
	/**
	 * Using this callback to blank the new_value field when the page loads, regardless of anything being posted
	 */
	function during_display_page_head_callback() {
		echo '<script language="javascript">';
		echo 'function clearNewField() {';
		echo '	document.getElementById("new_value").value = "";';
		echo '}';
		echo 'addOnloadEvent(clearNewField);';
		echo '</script>';	
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "EditDEnum.php")
	$controller = new EditDEnum();

?>