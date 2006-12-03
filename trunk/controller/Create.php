<?php

// $Id$

require_once '../../config/config.conf';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';
require_once $sysRoot.'alpha/view/View.inc';


// load the business object (BO) definition
if (isset($_GET["bo"])) {
	$BO_name = $_GET["bo"];
	if (file_exists($sysRoot.'model/'.$BO_name.'.inc')) {
		require_once $sysRoot.'model/'.$BO_name.'.inc';
	} elseif (file_exists($sysRoot.'alpha/model/'.$BO_name.'.inc')) {
		require_once $sysRoot.'alpha/model/'.$BO_name.'.inc';
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'Could not load the defination for the BO class '.$BO_name,'GET');
		exit;
	}
}else{
	$error = new handle_error($_SERVER["PHP_SELF"],'No BO available to create!','GET');
	exit;
}

// check and see if a custom create_*.php controller exists for this BO, and if it does use it otherwise continue
if (file_exists('../controller/create_'.$BO_name.'.php')) {
	header('Location: ../controller/create_'.$BO_name.'.php');	
}

/**
* 
* Controller used to create a new BO, which must be supplied in GET vars
* 
* @package Alpha Core Scaffolding
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*
*/
class Create extends Controller
{
	/**
	 * the new BO to be created
	 * @var object BO
	 */
	var $BO;
	
	/**
	 * the new default View object used for rendering the objects to create
	 * @var View BO_view
	 */
	var $BO_View;
								
	/**
	 * constructor that renders the page
	 * @param string $BO_name the name of the BO that we are creating
	 */
	function Create($BO_name) {
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->BO = new $BO_name();
		
		$this->BO_View = View::get_instance($this->BO);
		
		// set up the title and meta details
		$this->set_title("Create a New ".$BO_name);
		$this->set_description("Page to create a new ".$BO_name.".");
		$this->set_keywords("create,new,".$BO_name);
		
		$this->set_visibility('Administrator');
		if(!$this->check_rights()) {
			exit;
		}		
		
		if(!empty($_POST))
			$this->handle_post();
		
		$this->display_page_head();
		
		$this->BO_View->create_view();		
		
		$this->display_page_foot();
	}	
	
	/**
	 * method to handle POST requests
	 */
	function handle_post() {
		global $sysRoot;
		global $sysURL;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["createBut"])) {			
			// populate the transient object from post data
			$this->BO->populate_from_post();
					
			$success = $this->BO->save_object();
			$this->BO->load_object($this->BO->get_MAX());
					
			if($success) {
				if ($this->get_next_job() != "")					
					header('Location: '.$this->get_next_job());
				else
					header('Location: Detail.php?bo='.get_class($this->BO).'&oid='.$this->BO->get_ID());
			}	
		}
		
		if (isset($_POST["cancelBut"])) {
			header('Location: '.$sysURL.'/controller/ListBusinessObjects.php');
		}	
	}
	
	/**
	 * method to display the page head
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
			require_once $sysRoot.'alpha/view/widgets/button.js.php';
			require_once $sysRoot.'alpha/view/widgets/string_box.js.php';
		
			require_once $sysRoot.'alpha/view/widgets/form_validator.js.php';
		
			echo '<script type="text/javascript">';
			$validator = new form_validator($this->BO);
			echo '</script>';
		}
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->get_displayname().'.  <a href="'.$sysURL.'/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
	}
}

// now build the new controller
$controller = new Create($BO_name);

?>