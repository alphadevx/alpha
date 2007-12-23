<?php

// $Id$

if(empty($sysRoot))
	require_once '../../config/config.conf';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/controller/front/Front_Controller.inc';

/**
* 
* Controller used to generate secure URLs from the query strings provided
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha Admin
* @copyright 2007 John Collins
*
*/
class gen_secure_query_strings extends Controller
{
	
	function gen_secure_query_strings() {
		global $sysURL;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_title("Generate Secure Query Strings");
		
		$this->display_page_head();
		
		echo '<p>Use this form to generate secure (encrypted) URLs which make use of the Front Controller.  Always be sure to specify an action controller (act) at a minimum.</p>';
		echo '<p>Example 1: to generate a secure URL for viewing article object 00000000001, enter 
<em>act=view_article&oid=00000000001</em></p>';
		echo '<p>Example 2: to generate a secure URL for viewing an Atom news feed of the articles, enter 
<em>act=view_feed&bo=article_object&type=Atom</em</p>';
		echo '<p align="center"><a href="'.$sysURL.'/alpha/controller/ListBusinessObjects.php">Administration Home Page</a></p>';
				
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		$this->render_form();
		
		if(!empty($_POST)) {			
			$this->handle_post();
			return;
		}
		
		$this->display_page_foot();
	}
	
	function handle_post() {
		global $sysURL;		
		
		echo '<p style="width:90%; overflow:scroll;">';
		if(isset($_POST["QS"]))
			echo $sysURL."/FC.php?tk=".Front_Controller::encode_query($_POST["QS"]);
		echo '</p>';
	}
	
	function render_form() {
		echo '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
		echo '<input type="text" name="QS" size="100"/>';
		echo '<input type="submit" value="Generate"/>';
		echo '</form>';
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "gen_secure_query_strings.php")
	$controller = new gen_secure_query_strings();

?>
