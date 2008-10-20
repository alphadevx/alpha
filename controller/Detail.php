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
* Controller used to display the details of a BO, which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
*
*/
class Detail extends Controller
{
	/**
	 * the new BO to be displayed
	 * @var object
	 */
	var $BO;
	
	/**
	 * the OID of the BO to be loaded
	 * @var int
	 */
	var $BO_oid;
	
	/**
	 * the name of the BO
	 * @var string
	 */
	var $BO_name;
	
	/**
	 * the new default View object used for rendering the onject
	 * @var View BO_view
	 */
	var $BO_View;
	
	/**
	 * constructor that renders the page	 
	 */
	function Detail($BO_name=null) {
		global $config;
			
		// load the business object (BO) definition
		if (isset($_GET["bo"])) {
			$BO_name = $_GET["bo"];			
		}elseif($BO_name==null) {
			$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to list!','GET');
			exit;
		}
		
		if (file_exists($config->get('sysRoot').'model/'.$BO_name.'.inc')) {
			require_once $config->get('sysRoot').'model/'.$BO_name.'.inc';
		} elseif (file_exists($config->get('sysRoot').'alpha/model/'.$BO_name.'.inc')) {
			require_once $config->get('sysRoot').'alpha/model/'.$BO_name.'.inc';
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the defination for the BO class '.$BO_name,'GET');
			exit;
		}
		
		// ensure that a OID is also provided
		if (isset($_GET["oid"])) {
			$BO_oid = $_GET["oid"];
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the BO object '.$BO_name.' as an oid was not supplied!','GET');
			exit;
		}
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new $BO_name();
		$this->BO->load($BO_oid);		
		
		$this->BO_name = $BO_name;
		
		$this->BO_View = View::getInstance($this->BO);
		
		// set up the title and meta details
		$this->set_title("Displaying ".$BO_name." number ".$BO_oid);
		$this->set_description("Page to display ".$BO_name." number ".$BO_oid);
		$this->set_keywords("display,details,".$BO_name);		
		
		$this->display_page_head();
		
		if(!empty($_POST))
			$this->handle_post();
		
		$this->render_delete_form();
		
		$this->BO_View->detailedView();
		
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
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new $this->BO_name();
			
			$temp->load_object($_POST["delete_oid"]);			
					
			$success = $temp->delete_object();
					
			if($success) {
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
			}
			
			echo '<center>';
			
			$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=ListAll&bo='.get_class($this->BO))."'","Back to List","cancelBut");
			
			echo '</center>';
			exit;
		}
		
		if (isset($_POST["homeBut"])) {
			header('Location: '.$config->get('sysURL'));
		}
	}
}

// now build the new controller
if(basename($_SERVER["PHP_SELF"]) == "Detail.php")
	$controller = new Detail();

?>
