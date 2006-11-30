<?php

// $Id$

require_once '../config/config.conf';
require_once '../controller/Controller.inc';
require_once '../util/LOC/metrics.inc';

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
		global $sysURL;
		global $sysTheme;
		global $sysUseWidgets;
		global $sysRoot;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>'.$this->get_title().'</title>';
		echo '<meta name="Keywords" content="'.$this->get_keywords().'">';
		echo '<meta name="Description" content="'.$this->get_description().'">';
		echo '<meta name="Author" content="john collins">';
		echo '<meta name="copyright" content="copyright ">';
		echo '<meta name="identifier" content="http://'.$sysURL.'/">';
		echo '<meta name="revisit-after" content="7 days">';
		echo '<meta name="expires" content="never">';
		echo '<meta name="language" content="en">';
		echo '<meta name="distribution" content="global">';
		echo '<meta name="title" content="'.$this->get_title().'">';
		echo '<meta name="robots" content="index,follow">';
		echo '<meta http-equiv="imagetoolbar" content="no">';			
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$sysURL.'/config/css/'.$sysTheme.'.css.php">';
		
		if ($sysUseWidgets) {
			echo '<script language="JavaScript" src="'.$sysURL.'/scripts/addOnloadEvent.js"></script>';
			require_once $sysRoot.'view/widgets/button.js.php';
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$sysURL.'/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
		
		echo '<p align="center"><a href="'.$sysURL.'/controller/ListBusinessObjects.php">Administration Home Page</a></p><br>';
	}
}

// now build the new controller
$controller = new view_metrics($sysRoot);

?>