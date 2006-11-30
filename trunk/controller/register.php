<?php

require_once '../config/config.conf';
require_once '../model/person_object.inc';
require_once '../view/person.inc';
require_once '../config/db_connect.inc';
require_once '../controller/Controller.inc';

/**
 *
 * Rehister controller to allow new users to register their details
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class register extends Controller
{
	/**
	 * the person to be created
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
	function register() {
		// ensure that the super class constructor is called
		$this->Controller();
		
		$this->person_object = new person_object();
		$this->person_view = new person($this->person_object);
		$this->set_BO($this->person_object);
		
		// set up the title and meta details
		$this->set_title("Register Your Details");
		$this->set_description("Registration page.");
		$this->set_keywords("registeration,register");
		
		$this->display_page_head();
		
		if(!empty($_POST))	
			$this->handle_post();
		else
			$this->person_view->register_view();
		
		$this->display_page_foot();
	}	
		
	
	/**
	 * method to handle the post
	 */
	function handle_post() {		
		global $sysURL;
		
		// check the hidden security fields before accepting the form POST data
		if(!$this->check_security_fields()) {
			$error = new handle_error($_SERVER["PHP_SELF"],'This page cannot accept post data from remote servers!','handle_post()','validation');
			exit;
		}
		
		if (isset($_POST["registerBut"])) {
						
			$username_not_in_use = $this->person_object->set_displayname($_POST["displayname"]);
			$email_not_in_use = $this->person_object->set_email($_POST["email"]);
						
			if($username_not_in_use && $email_not_in_use) {			
				$new_password = $this->person_object->generate_password();
				
				// now encrypt and save the new password, then e-mail the user
				$this->person_object->set_password(crypt($new_password));				
				$this->person_object->save_object();
				
				$message = "Welcome to Design-Ireland.net!  You can now log into the web site using the following details:\n\n";//" '.$new_password.' as you requested.  You can now login to the site using your e-mail address and this new password as before.';
				$message .= "Username: ".$_POST["email"]."\nPassword: $new_password\n\n";
				$subject = 'Your new Design-Ireland.net account details';
					
				$success = $this->person_object->send_mail($message, $subject);				
					
				if ($success) {
					echo '<p class="success">Your new account has been created successfully, and the login details have been sent to the email address provided.</p>';
					echo '<a href="'.$sysURL.'">Home Page</a>';
				}else{
					$error = new handle_error($_SERVER["PHP_SELF"],'Server error: unable to send new account details, e-mail server may be down!' ,'handle_post()', 'warning');
					echo '<a href="'.$sysURL.'">Home Page</a>';
				}
			}else{
				$this->person_view->register_view();
			}
		}
	}
}

// now build the new controller
$controller = new register();

?>