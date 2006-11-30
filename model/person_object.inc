<?php

// $Id$

require_once $sysRoot.'model/mysql_DAO.inc';

/**
 *
 * the main person class for the site
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */ 
class person_object extends mysql_DAO
{	
	/**
	 * the forum display name of the person
	 * @var String
	 */
	var $displayname;	
	/**
	 * the email address for the person
	 * @var String
	 */
	var $email;
	/**
	 * the password for the person
	 * @var String
	 */
	var $password;	
	/**
	 * the access privileage level of the person
	 * @var Enum
	 */
	var $access_level;
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $data_labels = array(
		"OID"=>"Member ID#",		
		"displayname"=>"Forum Name",		
		"email"=>"E-mail Address",
		"password"=>"Password",		
		"access_level"=>"Page Access Privileage Level"	
	);
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	var $TABLE_NAME = "person";
	
	/**
	 * constructor for the class that populates all of the complex types with default values
	 */
	function person_object() {
		$this->displayname = new String();
		$this->displayname->set_rule(RULE_USERNAME);
		$this->displayname->set_helper("Please provide a name for display on the website (only letters & numbers are allowed!).");
		$this->displayname->set_size(70);		
		$this->email = new String();
		$this->email->set_rule(RULE_EMAIL);
		$this->email->set_helper("Please provide a valid e-mail address as your username.");
		$this->email->set_size(70);
		$this->password = new String();
		$this->password->set_helper("Please provide a password for logging in.");
		$this->password->set_rule("/\w+/");
		$this->password->set_size(70);
		$this->access_level = new Enum(array(
											"Standard",
											"Moderator",
											"Administrator"));
		$this->access_level->set_value('Standard');
	}
	
	/**
	 * setter for displayname, runs a check on the DB to ensure that the displayname is not already in use
	 * @param string $displayname
	 * @return boolean true on success, false otherwise
	 */
	function set_displayname($displayname)
	{
		$this->displayname->set_value($displayname);
		
		$sql_query = "SELECT oid FROM ".$this->TABLE_NAME." WHERE displayname = '$displayname';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to check the submited forum display name, MySql error is: '.mysql_error().', query: '.$sql_query ,'set_displayname()','framework');
			return false;
		}
		
		if(mysql_num_rows($result) == 0) {
			return true;
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'The forum username '.$displayname.', is already in use!' ,'set_displayname','validation');
			return false;
		}		
	}

	/**
	 * getter for displayname
	 * @return string displayname
	 */
	function get_displayname() {
		return $this->displayname->get_value();
	}	
	
	/**
	 * setter for email, runs a check on the DB to ensure that the email is not already in use
	 * @param string $email
	 * @return boolean true on success, false otherwise
	 */
	function set_email($email)
	{
		$this->email->set_value($email);
		
		$sql_query = "SELECT oid FROM ".$this->TABLE_NAME." WHERE email = '$email';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to check the submited email address, MySql error is: '.mysql_error().', query: '.$sql_query ,'set_email()','framework');
			return false;
		}
		
		if(mysql_num_rows($result) == 0) {
			return true;
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'The email address '.$email.', is already in use!' ,'set_email','validation');
			return false;
		}		
	}

	/**
	 * getter for email
	 * @return string email
	 */
	function get_email() {
		return $this->email->get_value();
	}
	
	/**
	 * setter for password
	 * @param string $password
	 */
	function set_password($password)
	{
		$this->password->set_value($password);
	}

	/**
	 * getter for password
	 * @return string password
	 */
	function get_password() {
		return $this->password->get_value();
	}	
	 
	/**
	 * setter for access_level
	 * @param string $access_level
	 */
	function set_access_level($access_level)
	{
		$this->access_level->set_value($access_level);
	}

	/**
	 * getter for access_level
	 * @return string access_level
	 */
	function get_access_level()
	{
		return $this->access_level->get_value();
	}	
	
	/**
	 * getter for access_level options
	 * @return array $options
	 */
	function get_access_level_options()
	{
		return $this->access_level->get_options();
	}
	
	/**
	 * as emails are forced to me unique, this method allows for loading a person from an email address
	 * @param string $email The email of the person to load
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function load_from_email($email)
	{		
		$sql_query = "SELECT * FROM ".$this->TABLE_NAME." WHERE email = '$email';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load user '.$email.', not found in database.' ,'load_from_email($email)','validation');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		$properties = get_class_vars(get_class($this));

		foreach(array_keys($properties) as $prop) {
			if($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {						
				if (get_class($this->$prop) != 'Date') {
					$this->$prop->set_value($row[$prop]);
				}else{
					$this->$prop->populate_from_string($row[$prop]);
				}
			}
			if($prop == "version_num"){
				$this->version_num = $row[$prop];
			}
			if($prop == "OID"){
				$this->OID = $row[$prop];
			}
		}
		
		if(mysql_error() == '' && $this->set_enum_options()) {	
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load user, MySql error is: '.mysql_error().', query: '.$this->last_query,'load_from_email($email)','framework');
			return false;
		}		
	}
	
	/**
	 * a generic method for mailing a customer
	 * @param string $message the e-mail body message
	 * @param string $subject the subject of the e-mail
	 * @return boolean true if succeeded in sending the e-mail, false otherwise
	 */
	function send_mail($message, $subject) {
		global $sysURL;
		
		$body = 'Dear '.$this->get_displayname().'\n\n';
		
		$body .= $message;
		
		$body .= '\n\nKind regards,\n\nDesign-Ireland.net Administrator\n--\n'.$sysURL;
		
		//echo 'To: '.$this->get_email();
		//echo 'Subject: '.$subject;
		//echo 'Message: '.$body;		
		
		return mail($this->get_email(), $subject, $body);
		//return true;
	}
	
	/**
	 * generates a random password for the user
	 * @return string the new random password
	 */
	function generate_password() {
		$alphabet = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		// the password will be 7 random characters and 2 numbers
		$new_password = '';
		for ($i = 0; $i < 7; $i++) {
			$new_password.= $alphabet[rand(0,25)];
		}
		$new_password.= rand(0,100);
		$new_password.= rand(0,100);
		
		echo $new_password;
		return $new_password;
	}
}

?>