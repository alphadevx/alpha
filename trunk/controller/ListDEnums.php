<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/ListAll.php';
require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/DEnumView.inc';

/**
* 
* Controller used to list all DEnums
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
*
*/
class ListDEnums extends ListAll
{
	/**
	 * constructor that renders the page	
	 */
	function ListDEnums() {
		global $config;
				
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new DEnum();
		
		// make sure that the DEnum tables exist
		if(!$this->BO->check_table_exists()) {
			echo '<p class="warning">Warning! The DEnum tables do not exist, attempting to create them now...</p>';
			$this->create_DEnum_tables();
		}
		
		$this->BO_name = "DEnum";
		
		$this->BO_View = new View($this->BO);		
		
		// set up the title and meta details
		$this->set_title("Listing all DEnums");
		$this->set_description("Page to list all DEnums.");
		$this->set_keywords("list,all,DEnums");
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		if(!empty($_POST)) {
			$this->handle_post();
			return;
		}
		
		// get all of the BOs and invoke the list_view on each one
		$temp = new DEnum();
		// set the start point for the list pagination
		if (isset($_GET["start"]) ? $this->start_point = $_GET["start"]: $this->start_point = 0);
			
		$objects = $temp->load_all($this->start_point);
			
		$this->BO_count = $this->BO->get_count();
			
		$this->display_page_head();
		
		$this->render_delete_form();
		
		foreach($objects as $object) {
			$temp = new DEnumView($object);
			$temp->list_view();
		}
		
		$this->display_page_foot();
	}
	
	/**
	 * Method to create the DEnum tables if they don't exist
	 */
	function create_DEnum_tables() {
		$tmpDEnum = new DEnum();

		echo '<p>Attempting to build table '.DEnum::TABLE_NAME.' for class DEnum : </p>';
		
		$result = $tmpDEnum->make_table();
		
		if($result)
			echo '<p class="success">Successfully re-created the database table '.DEnum::TABLE_NAME.'</p>';
				
		$tmpDEnumItem = new DEnumItem();
		
		echo '<p>Attempting to build table '.DEnumItem::TABLE_NAME.' for class DEnumItem : </p>';
		
		$result = $tmpDEnumItem->make_table();
		
		if($result)
			echo '<p class="success">Successfully re-created the database table '.DEnumItem::TABLE_NAME.'</p>';		
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "ListDEnums.php")
	$controller = new ListDEnums();

?>
