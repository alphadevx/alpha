<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/LOC/metrics.inc';

/**
* 
* Controller used to display the software metrics for the application
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha Util
* @copyright 2006 John Collins
*
*/
class view_metrics extends Controller
{								
	/**
	 * constructor that renders the page
	 * @param string $dir the root directory of the application to base the metrics from
	 */
	function view_metrics($dir) {		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_visibility("Administrator");
		if(!$this->check_rights()){			
			exit;
		}
		
		$this->set_title("Application Metrics");
		
		$this->display_page_head();
		
		$metrics = new metrics($dir);
		$metrics->calculate_LOC();
		$metrics->results_to_HTML();
		
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
$controller = new view_metrics($sysRoot);

?>