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
* Controller used to list all of the business objects for the system
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class ListBusinessObjects extends Controller
{
	/**
	 * the constructor
	 */
	function ListBusinessObjects() {
		global $config;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		// set up the title and meta details
		$this->set_title("Listing all business objects in the system");
		$this->set_description("Page to list all business objects.");
		$this->set_keywords("list,all,business,objects");
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}		
		
		$this->display_page_head();
		
		if(!empty($_POST))
			$this->handle_post();

		$classNames = mysqlDAO::getBOClassNames();
		$loadedClasses = array();
		
		foreach($classNames as $classname) {
			$foundFile = true;
			
			if(file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'model/'.$classname.'.inc';
			elseif(file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
			else
				$foundFile = false;
	    	
			if($foundFile) {
				array_push($loadedClasses, $classname);
			}
		}
		
		foreach($loadedClasses as $classname) {
			$BO = new $classname();				
			
			$BO_View = new View($BO);
			$BO_View->adminView();
		}
		
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
		
		if(isset($_POST["createTableBut"])) {
				
			$classname = $_POST["createTableClass"];
			if (file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'model/'.$classname.'.inc';
			if (file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
	    		
	    	$BO = new $classname();	
			$success = $BO->makeTable();
			
			if ($success)
				echo '<p class="success">The table for the class '.$classname.' has been successfully created.</p>';
		}
		
		if(isset($_POST["recreateTableClass"]) && $_POST['admin_'.$_POST["recreateTableClass"].'_button_pressed'] == "recreateTableBut") {
				
			$classname = $_POST["recreateTableClass"];
			if (file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'model/'.$classname.'.inc';
			if (file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
	    		
	    	$BO = new $classname();	
			$success = $BO->rebuildTable();
			
			if ($success)
				echo '<p class="success">The table for the class '.$classname.' has been successfully recreated.</p>';
		}
		
		if(isset($_POST["updateTableClass"]) && $_POST['admin_'.$_POST["updateTableClass"].'_button_pressed'] == "updateTableBut") {
			
			$classname = $_POST["updateTableClass"];
			if (file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'model/'.$classname.'.inc';
			if (file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
				require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
	    		
	    	$BO = new $classname();
	    	$missing_fields = $BO->findMissingFields();
	    	$success = false;
	    	
	    	for($i = 0; $i < count($missing_fields); $i++)
				$success = $BO->addProperty($missing_fields[$i]);
			
			if ($success)
				echo '<p class="success">The table for the class '.$classname.' has been successfully updated.</p>';
		}
	}
	
	/**
	 * method to display the page head
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
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$config->get('sysTheme').'.css.php">';
		
		if ($config->get('sysUseWidgets')) {
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';			
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->getDisplayName().'.  <a href="'.$config->get('sysURL').'/alpha/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}		
				
		echo '<p align="center"><a href="'.$config->get('sysURL').'">Home Page</a>&nbsp;-';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=view_log&log_path='.$config->get('sysRoot').'alpha/util/logs/error_log.log').'">Error Log</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=view_log&log_path='.$config->get('sysRoot').'alpha/util/logs/search_log.log').'">Search Log</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=view_log&log_path='.$config->get('sysRoot').'alpha/util/logs/feed_log.log').'">Feed Log</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=gen_secure_query_strings').'">Generate Secure URL</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=view_metrics').'">Software Metrics</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=cache_manager').'">Manage Cache</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=ListDEnums').'">Manage DEnums</a>&nbsp;-&nbsp;';
		echo '<a href="'.Front_Controller::generate_secure_URL('act=view_test_results').'">Application Unit Tests</a></p>';
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "ListBusinessObjects.php")
	$controller = new ListBusinessObjects();

?>
