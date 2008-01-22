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
		
		$handle = opendir($config->get('sysRoot').'model');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/_object.inc/", $file)) {
	    		$classname = substr($file, 0, -4);	    		
	    		
	    		require_once $config->get('sysRoot').'model/'.$classname.'.inc';
	    		
	    		$BO = new $classname();				
		
				$BO_View = new View($BO);
				$BO_View->admin_view();	
	    	}
	    }
	    
	    // now loop over the core BOs provided with Alpha
	    
	    $handle = opendir($config->get('sysRoot').'alpha/model');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/_object.inc/", $file)) {
	    		$classname = substr($file, 0, -4);	    		
	    		
	    		require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
	    		
	    		$BO = new $classname();				
		
				$BO_View = new View($BO);
				$BO_View->admin_view();	
	    	}
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
			$success = $BO->make_table();
			
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
			$success = $BO->rebuild_table();
			
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
	    	$missing_fields = $BO->find_missing_fields();
	    	$success = false;
	    	
	    	for($i = 0; $i < count($missing_fields); $i++)
				$success = $BO->add_property($missing_fields[$i]);
			
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
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$config->get('sysURL').'/alpha/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
		
		echo '<p align="center"><a href="'.$config->get('sysURL').'">Application Home Page</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/view_log.php?log_path='.$config->get('sysRoot').'alpha/util/logs/error_log.log'.'">View Error Log</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/view_log.php?log_path='.$config->get('sysRoot').'alpha/util/logs/search_log.log'.'">View Search Log</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/view_log.php?log_path='.$config->get('sysRoot').'alpha/util/logs/feed_log.log'.'">View Feed Log</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/gen_secure_query_strings.php">Generate Secure URL</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/view_metrics.php">Application Software Metrics</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/cache_manager.php">Manage Cache</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/controller/ListDEnums.php">Manage DEnums</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$config->get('sysURL').'/alpha/tests/view_test_results.php">Application Unit Tests</a></p>';
	}
}

// now build the new controller
$controller = new ListBusinessObjects();

?>
