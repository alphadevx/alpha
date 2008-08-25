<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/mysqlDAO.inc';
require_once $config->get('sysRoot').'alpha/model/article_comment_object.inc';

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
class person_object extends mysqlDAO
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
	var $dataLabels = array(
		"OID"=>"Member ID#",		
		"displayname"=>"Forum Name",		
		"email"=>"E-mail Address",
		"password"=>"Password",		
		"access_level"=>"Page Access Privileage Level",
		"state"=>"Account state",
		"URL"=>"Your site address"
	);
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	const TABLE_NAME = 'person';
	
	/**
	 * the state of the person
	 * @var Enum
	 */
	var $state;
	/**
	 * the site URL of the person
	 * @var String
	 */
	var $URL;
	
	/**
	 * constructor for the class that populates all of the complex types with default values
	 */
	function person_object() {
		// ensure to call the parent constructor
		parent::__construct();
		$this->displayname = new String();
		$this->displayname->setRule(RULE_USERNAME);
		$this->displayname->setSize(70);
		$this->displayname->setHelper("Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).");
		$this->email = new String();
		$this->email->setRule(RULE_EMAIL);
		$this->email->setSize(70);
		$this->email->setHelper("Please provide a valid e-mail address as your username.");
		$this->password = new String();
		$this->password->setSize(70);
		$this->password->setHelper("Please provide a password for logging in.");
		$this->password->setRule("/\w+/");		
		$this->access_level = new Enum(array(
											"Standard",
											"Moderator",
											"Administrator"));
		$this->access_level->setValue("Standard");
		$this->state = new Enum(array(
										"Active",
										"Disabled"));
		$this->state->setValue("Active");
		$this->URL = new String();
		$this->URL->setRule(RULE_URL_BLANK);
		$this->URL->setHelper("URLs must be in the format http://some_domain/ or left blank!");
	}
	
	/**
	 * setter for displayname, runs a check on the DB to ensure that the displayname is not already in use
	 * @param string $displayname
	 * @return boolean true on success, false otherwise
	 */
	function set_displayname($displayname)
	{
		$this->displayname->setValue($displayname);
		
		$sql_query = "SELECT oid FROM ".self::TABLE_NAME." WHERE displayname = '$displayname';";		

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
		return $this->displayname->getValue();
	}	
	
	/**
	 * setter for email, runs a check on the DB to ensure that the email is not already in use
	 * @param string $email
	 * @return boolean true on success, false otherwise
	 */
	function set_email($email)
	{
		$this->email->setValue($email);
		
		$sql_query = "SELECT oid FROM ".self::TABLE_NAME." WHERE email = '$email';";		

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
		return $this->email->getValue();
	}
	
	/**
	 * setter for password, encrypts provided value using crypt()
	 * @param string $password
	 */
	function set_password($password)
	{
		$this->password->setValue(crypt($password, 'xy'));
	}

	/**
	 * getter for password
	 * @return string password
	 */
	function get_password() {
		return $this->password->getValue();
	}	
	 
	/**
	 * setter for access_level
	 * @param string $access_level
	 */
	function set_access_level($access_level)
	{
		$this->access_level->setValue($access_level);
	}

	/**
	 * getter for access_level
	 * @return string access_level
	 */
	function get_access_level()
	{
		return $this->access_level->getValue();
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
		$sql_query = "SELECT * FROM ".self::TABLE_NAME." WHERE email = '$email';";		

		$this->lastQuery = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load user '.$email.', not found in database.' ,'load_from_email($email)','validation');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		$properties = get_class_vars(get_class($this));

		foreach(array_keys($properties) as $prop) {
			if (get_class($this->$prop) != false) {	
				if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
					$this->$prop->setValue($row[$prop]);
				}else{
					$this->$prop->populateFromString($row[$prop]);
				}
			}			
			if($prop == "OID"){
				$this->OID = $row[$prop];
			}
		}
		
		if(mysql_error() == '') {
			$this->setEnumOptions();				
			return true;
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load user, MySql error is: '.mysql_error().', query: '.$this->lastQuery,'load_from_email($email)','framework');
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
		global $config;
		
		$body = "Dear ".$this->get_displayname()."\n\n";
		
		$body .= $message;
		
		$body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('sysURL');
		
		return mail($this->get_email(), $subject, $body, "From: ".$config->get('sysReplyTo')."\r\nReply-To: ".$config->get('sysReplyTo')."\r\nX-Mailer: PHP/" . phpversion());
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
		
		return $new_password;
	}
	
	/**
	 * method for getting a count of the amount of article comments posted by the user
	 * @return int the comment count
	 */
	function get_comment_count() {
		$temp = new article_comment_object();
		
		$sql_query = "SELECT COUNT(OID) AS post_count FROM ".self::TABLE_NAME." WHERE created_by='".$this->OID."';";

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {			
			return $row["post_count"];
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get the count of the comments posted for the person, query: '.$this->last_query,'get_comment_count()','framework');
			return 0;
		}
	}
}

?>