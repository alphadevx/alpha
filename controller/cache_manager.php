<?php

// $Id: view_metrics.php 369 2007-12-27 14:53:32Z johnc $

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/file_util.inc';

/**
* 
* Controller used to clear out the CMS cache when required
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha Util
* @copyright 2008 John Collins
*
*/
class cache_manager extends Controller
{
	/**
	 * The root of the cache directory
	 * @var string
	 */
	var $dataDir;
	
	/**
	 * constructor that renders the page
	 */
	function cache_manager() {
		global $config;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility("Administrator");
		if(!$this->check_rights()){			
			exit;
		}
		
		$this->set_title("Manage Cache");
		
		$this->display_page_head();
		
		$this->dataDir  = $config->get('sysRoot').'cache/';
		
		echo '<h1>Listing contents of cache directory: '.$this->dataDir.'</h1>';
		
		if(!empty($_POST))
			$this->handle_post();
   
   		$fileCount = file_util::list_directory_contents($this->dataDir);
   		
   		echo '<h2>Total of '.$fileCount.' files in the cache.</h2>';
   		
   		echo '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="clearForm">';
   		echo '<input type="hidden" name="clearCache" value="false"/>';
   		$temp = new button("if (confirm('Are you sure you want to delete all files in the cache?')) {document.forms['clearForm']['clearCache'].value = 'true'; document.forms['clearForm'].submit();}", "Clear cache", "clearBut");
   		View::render_security_fields();
   		echo '</form>';			
		
		$this->display_page_foot();
	}
	
	/**
	 * method to display the page head with pageination links
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
		
		echo '<p align="center"><a href="'.$config->get('sysURL').'/alpha/controller/ListBusinessObjects.php">Administration Home Page</a></p><br>';
	}
	
	function handle_post() {
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}		
		
		if (isset($_POST["clearCache"]) && $_POST["clearCache"] == "true") {
			$success = file_util::delete_directory_contents($this->dataDir);
					
			if($success) {
				echo '<p class="success">Cache contents deleted successfully.</p>';
			}
		}
	}
}

// now build the new controller
$controller = new cache_manager();

?>