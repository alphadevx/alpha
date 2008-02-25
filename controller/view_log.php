<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/log_file.inc';

/**
* 
* Controller used to display a log file, the path for which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class view_log extends Controller
{	
	/**
	 * the path to the log that we are displaying
	 * @var string
	 */
	var $log_path;
	
	/**
	 * constructor that renders the page
	 */
	function view_log() {
				
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		$this->set_title("Displaying the requested log");
		
		$this->display_page_head();

		// load the business object (BO) definition
		if (isset($_GET["log_path"])) {
			$log_path = $_GET["log_path"];	
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'No log file path available to view!','GET');
			exit;
		}
		
		$this->log_path = $log_path;
		
		$log = new log_file($this->log_path);
		if(preg_match("/error_log.*/", basename($this->log_path)))
			$log->render_log(array("Date of error","Error file","Error method","Error message","Error type","Client IP","Client Server","Client Application"));
		if(preg_match("/search_log.*/", basename($this->log_path)))
			$log->render_log(array("Search query","Search date","Client Application","Client IP"));
		if(preg_match("/feed_log.*/", basename($this->log_path)))
			$log->render_log(array("Business object","Feed type","Request date","Client Application","Client IP"));		
		
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
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "view_log.php")
	$controller = new view_log();

?>
