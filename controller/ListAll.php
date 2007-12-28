<?php

// $Id$

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';


// load the business object (BO) definition
if (isset($_GET["bo"])) {
	$BO_name = $_GET["bo"];
	if (file_exists($config->get('sysRoot').'alpha/model/'.$BO_name.'.inc')) {
		require_once $config->get('sysRoot').'alpha/model/'.$BO_name.'.inc';
	}elseif (file_exists($config->get('sysRoot').'model/'.$BO_name.'.inc')) {
		require_once $config->get('sysRoot').'model/'.$BO_name.'.inc';
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the defination for the BO class '.$BO_name,'GET');
		exit;
	}
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to list!','GET');
	exit;
}

/**
* 
* Controller used to list a BO, which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class ListAll extends Controller
{
	/**
	 * the new BO to be listed
	 * @var object
	 */
	var $BO;
	
	/**
	 * the name of the BO
	 * @var string
	 */
	var $BO_name;
	
	/**
	 * the new default View object used for rendering the onjects to list
	 * @var View BO_view
	 */
	var $BO_View;
	
	/**
	 * the start number for list pageination
	 * @var integer 
	 */
	var $start_point;
	
	/**
	 * the count of the BOs of this type in the database
	 * @var integer
	 */
	var $BO_count = 0;
								
	/**
	 * constructor that renders the page
	 * @param string $BO_name the name of the BO that we are listing
	 */
	function ListAll($BO_name) {		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new $BO_name();
		
		$this->BO_name = $BO_name;
		
		$this->BO_View = new View($this->BO);		
		
		// set up the title and meta details
		$this->set_title("Listing all ".$BO_name);
		$this->set_description("Page to list all ".$BO_name.".");
		$this->set_keywords("list,all,".$BO_name);
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}
		
		if(!empty($_POST)) {
			$this->handle_post();
			return;
		}
		
		// get all of the BOs and invoke the list_view on each one
		$temp = new $BO_name();
		// set the start point for the list pagination
		if (isset($_GET["start"]) ? $this->start_point = $_GET["start"]: $this->start_point = 0);
			
		$objects = $temp->load_all($this->start_point);
			
		$this->BO_count = $this->BO->get_count();
			
		$this->display_page_head();
		
		$this->render_delete_form();
		
		foreach($objects as $object) {
			$temp = View::get_instance($object);
			$temp->list_view();
		}
		
		$this->display_page_foot();
	}	
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (!empty($_POST["delete_oid"])) {
			
			$temp = new $this->BO_name();
			
			$temp->load_object($_POST["delete_oid"]);			
					
			$success = $temp->delete_object();
			
			// get all of the BOs and invoke the list_view on each one
			$temp = new $this->BO_name();
			// set the start point for the list pagination
			if (isset($_GET["start"]) ? $this->start_point = $_GET["start"]: $this->start_point = 0);
				
			$objects = $temp->load_all($this->start_point);
				
			$this->BO_count = $this->BO->get_count();
				
			$this->display_page_head();
			
			if($success) {
				echo '<p class="success">'.$this->BO_name.' '.$_POST["delete_oid"].' deleted successfully.</p>';
			}
			
			$this->render_delete_form();
			
			foreach($objects as $object) {
				$temp = View::get_instance($object);
				$temp->list_view();
			}
			
			$this->display_page_foot();					
		}
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
		
		echo '<p align="center"><a href="'.$config->get('sysURL').'/alpha/controller/ListBusinessObjects.php">Administration Home Page</a></p>';
		
		$this->render_page_links();
	}
	
	/**
	 * method to display the page footer with pageination links
	 */
	function display_page_foot() {
				
		$this->render_page_links();
		
		echo '<br></body>';
		echo '</html>';
	}
	
	/**
	 * method for rendering the pagination links 
	 */
	function render_page_links() {
		global $config;
		
		$end = ($this->start_point+$config->get('sysListPageAmount'));
		
		if($end > $this->BO_count)
			$end = $this->BO_count;
		
		if ($this->start_point > 9)
			echo '<p align="center">Displaying '.($this->start_point+1).' to '.$end.' of <strong>'.$this->BO_count.'</strong>.&nbsp;&nbsp;';		
		else
			echo '<p align="center">Displaying &nbsp;'.($this->start_point+1).' to '.$end.' of <strong>'.$this->BO_count.'</strong>.&nbsp;&nbsp;';		
				
		if ($this->start_point > 0) {
			echo '<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name."&start=".($this->start_point-$config->get('sysListPageAmount')).'">&lt;&lt;-Previous</a>&nbsp;&nbsp;';
		}else{
			echo '&lt;&lt;-Previous&nbsp;&nbsp;';
		}
		$page = 1;
		for ($i = 0; $i < $this->BO_count; $i+=$config->get('sysListPageAmount')) {
			if($i != $this->start_point)
				echo '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name."&start=".$i.'">'.$page.'</a>&nbsp;';
			else
				echo '&nbsp;'.$page.'&nbsp;';
			$page++;
		}
		if ($this->BO_count > $end) {
			echo '&nbsp;&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?bo='.$this->BO_name."&start=".($this->start_point+$config->get('sysListPageAmount')).'">Next-&gt;&gt;</a>';
		}else{
			echo '&nbsp;&nbsp;Next-&gt;&gt;';
		}
		echo '</p>';
	}
}

// now build the new controller
$controller = new ListAll($BO_name);

?>
