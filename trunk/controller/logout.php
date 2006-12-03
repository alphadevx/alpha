<?php

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/person.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';

/**
 *
 * Logout controller that removes the current user object to the session
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * @todo logging of user logout times
 * 
 */
class logout extends Controller
{
	/**
	 * constructor to set up the object
	 */
	function logout() {
		global $sysURL;
		
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->person_object = new person_object();
		$this->person_view = new person($this->person_object);
		$this->set_BO($this->person_object);
		
		// set up the title and meta details
		$this->set_title("Logged out successfully.");
		$this->set_description("logout page.");
		$this->set_keywords("logout,logon");
		
		$_SESSION = array();
		
		session_destroy();
		
		$this->display_page_head();
		
		echo '<center><p class="success">You have successfully logged out of the system.</p><br>';
		
		echo '<a href="'.$sysURL.'">Home Page</a></center>';
		
		$this->display_page_foot();
	}	
}

$controller = new logout();

?>