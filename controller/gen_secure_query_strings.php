<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/controller/front/Front_Controller.inc';

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
		global $config;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->set_title("Generate Secure Query Strings");
		
		$this->display_page_head();
		
		echo '<p align="center"><a href="'.Front_Controller::generate_secure_URL('act=ListBusinessObjects').'">Administration Home Page</a></p>';
		
		echo '<p>Use this form to generate secure (encrypted) URLs which make use of the Front Controller.  Always be sure to specify an action controller (act) at a minimum.</p>';
		echo '<p>Example 1: to generate a secure URL for viewing article object 00000000001, enter <em>act=view_article&oid=00000000001</em></p>';
		echo '<p>Example 2: to generate a secure URL for viewing an Atom news feed of the articles, enter <em>act=view_feed&bo=article_object&type=Atom</em</p>';
				
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
		global $config;		
		
		echo '<p style="width:90%; overflow:scroll;">';
		if(isset($_POST["QS"]))
			echo $config->get('sysURL')."/FC.php?tk=".Front_Controller::encode_query($_POST["QS"]);
		echo '</p>';
	}
	
	function render_form() {
		echo '<form action="'.$_SERVER["PHP_SELF"].(empty($_SERVER["QUERY_STRING"])? '':'?'.$_SERVER["QUERY_STRING"]).'" method="post">';
		echo '<input type="text" name="QS" size="100"/>';
		echo '<input type="submit" value="Generate"/>';
		echo '</form>';
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "gen_secure_query_strings.php")
	$controller = new gen_secure_query_strings();

?>
