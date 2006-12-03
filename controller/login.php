<?php

require_once '../../config/config.conf';
require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/person.inc';
require_once $sysRoot.'config/db_connect.inc';
require_once $sysRoot.'alpha/controller/Controller.inc';

/**
 *
 * Login controller that adds the current user object to the session
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * @todo logging of user login times
 * 
 */
class login extends Controller
{
	/**
	 * the person to be logged in
	 * @var person_object
	 */
	var $person_object;
	
	/**
	 * the person view object
	 * @var person
	 */
	var $person_view;
	
	/**
	 * constructor to set up the object
	 */
	function login() {
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->person_object = new person_object();
		$this->person_view = new person($this->person_object);
		$this->set_BO($this->person_object);
		
		// set up the title and meta details
		$this->set_title("Login to the Site");
		$this->set_description("Login page.");
		$this->set_keywords("login,logon");		
	}	
		
	/**
	 * method to initialise (display) the controller
	 */
	function init() {
		$this->display_page_head();
		
		if (isset($_GET["reset"]))
			$this->person_view->display_reset_form();
		else
			$this->person_view->display_login_form();	
		
		$this->display_page_foot();
	}	
	
	/**
	 * method to handle the post (adds $current_user person object to the session)
	 */
	function handle_post() {		
		global $sysURL;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["loginBut"])) {
			// here we are attempting to load the person from the email address
			$success = $this->person_object->load_from_email($_POST["email"]);
			
			// check the password
			if ($success) {
				if (crypt($_POST["password"], $this->person_object->get_password()) == $this->person_object->get_password()) {				
					$_SESSION["current_user"] = $this->person_object;
					if ($this->get_next_job() != '')
						header('Location: '.$sysURL.'/alpha/controller/'.$this->get_next_job());
					//else
					//	header('Location: '.$sysURL.'/controller/whats_new.php');
				}else{								
					$error = new handle_error($_SERVER["PHP_SELF"],'Failed to login user '.$_POST["email"].', the password is incorrect!' ,'handle_post()','validation');
				}
			}
			
			$this->display_page_head();
			
			$this->person_view->display_login_form();		
					
			$this->display_page_foot();
		}
		if (isset($_POST["resetBut"])) {
			$this->display_page_head();
			
			// here we are attempting to load the person from the email address			
			$success = $this->person_object->load_from_email($_POST["email"]);	
			
			if ($success) {				
				// generate a new random password
				$new_password = $this->person_object->generate_password();
								
				// now encrypt and save the new password, then e-mail the user
				$this->person_object->set_password(crypt($new_password));				
				$this->person_object->save_object();
				
				$message = 'The password for your account has been reset to '.$new_password.' as you requested.  You can now login to the site using your e-mail address and this new password as before.';
				$subject = 'Password change request';
				
				$success = $this->person_object->send_mail($message, $subject);				
				
				if ($success) {
					echo '<p class="success">The password for the user <strong>'.$_POST["email"].'</strong> has been reset, and the new password has been sent to that e-mail address.</p>';
					echo '<a href="'.$sysURL.'">Home Page</a>';
				}else{
					$error = new handle_error($_SERVER["PHP_SELF"],'Server error: unable to send new password, e-mail server may be down!' ,'handle_post()', 'warning');
					echo '<a href="'.$sysURL.'">Home Page</a>';
				}
				
				$this->display_page_foot();
			}else{				
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to find user '.$_POST["email"].', the email address is incorrect!' ,'handle_post()','validation');
		
				$this->person_view->display_reset_form();
				
				$this->display_page_foot();
			}
		}		
	}
}

// now build the new controller if this file is called directly
if ('login.php' == basename($_SERVER["PHP_SELF"])) {
	$controller = new login();
	
	if(!empty($_POST)) {			
		$controller->handle_post();		
	}else{
		$controller->init();
	}
}

?>