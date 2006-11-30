<?php

// $Id$

require_once $sysRoot.'model/person_object.inc';
require_once $sysRoot.'view/View.inc';

/**
 *
 * The rendering class for the person_object class
 * 
 * @package Photo-Site
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class person extends View
{
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * constructor for the person	
	 * @param person_object $person_object the person business object for rendering
	 */
	function person($person_object)
	{
		$this->View($person_object);
	}	
	
	/**
	 * method to render the login HTML form
	 */
	function display_login_form() {
		global $sysURL;
		
		echo '<table cols="2">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';
		echo '<tr>';
		echo '	<td>Password</td> <td><input type="password" name="password" size="50"/></td>';
		echo '</tr>';
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Login","loginBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$sysURL."')","Cancel","cancelBut");
		}else{
			echo '	<input type="submit" name="loginBut" value="Login"/>';
			$temp = new button("document.location.replace('".$sysURL."')","Cancel","cancelBut");
		}
		echo '</td></tr>';
		
		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';
		echo '<a href="'.$_SERVER["PHP_SELF"].'?reset=true">Forgotten your password?</a>';
	}
	
	/**
	 * method to render the reset password HTML form
	 */
	function display_reset_form() {
		global $sysURL;
		
		echo '<p>If you have forgotten your password, you can use this form to have a new password automatically generated and sent to your e-mail address.</p>';
		echo '<table cols="2">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?reset=true" method="POST">';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';		
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Reset Password","resetBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$sysURL."')","Cancel","cancelBut");
		}else{
			echo '	<input type="submit" name="resetBut" value="Reset Password"/>';
			echo '	<input type="button" name="cancel" value="Cancel" onclick="document.location.replace(\''.$sysURL.'\')"/>';
		}
		echo '</td></tr>';
		
		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';	
	}
	
	/**
	 * method to render the user registration form
	 */
	function register_view() {
		global $sysURL;
		
		echo '<p>In order to make comments on the articles on this web site, and to post comments on the forum, you will need to create a user account.  In order to do so, please provide a valid email address below and a password will be sent to your inbox shortly.</p>';
		echo '<p class="warning">Please note: the user comments and forum are currently not available, but will be made available in the coming weeks!</p>';
		echo '<table cols="2">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?reset=true" method="POST">';
		echo '<tr>';
		echo '	<td>Forum name</td> <td><input type="text" name="displayname" size="50" value="'.(isset($_POST["displayname"])? $_POST["displayname"] : '').'"/></td>';
		echo '</tr>';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';		
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Register","registerBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$sysURL."')","Cancel","cancelBut");
		}else{
			echo '	<input type="submit" name="registerBut" value="Register"/>';
			echo '	<input type="button" name="cancel" value="Cancel" onclick="document.location.replace(\''.$sysURL.'\')"/>';
		}
		echo '</td></tr>';
		
		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';	
	}
}

?>
