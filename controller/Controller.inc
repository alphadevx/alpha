<?php

// $Id$

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';

require_once $config->get('sysRoot').'alpha/view/person.inc';
require_once $config->get('sysRoot').'alpha/controller/front/Front_Controller.inc';
require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';

/**
 *
 * The master controller class for the framework
 * 
 * @package Alpha Core
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class Controller
{
	/**
	 * the name of the controller
	 * @var string
	 */
	var $name = '';
	
	/**
	 * used to set access privileages to the controller
	 * @var string
	 */ 
	var $visibility = 'public';
	
	/**
	 * Optionally, a BO may be set for the default validation form handling code to load in the display_page_head
	 * method.  The defination of this BO class will need to be included in the child controller.
	 * @var BO
	 */
	var $BO = null;
	
	/**
	 * used to determine if the controller is part of a unit of work sequence (empty or the name of the unit)
	 * @var string
	 */
	var $unit_of_work = '';
	
	/**
	 * stores the start time of a unit of work transaction
	 * @var Timestamp
	 */
	var $unit_start_time;
	
	/**
	 * stores the end time of a unit of work transaction
	 * @var Timestamp
	 */
	var $unit_end_time;
	
	/**
	 * stores the maximum allowed time duration (in second) of the unit of work
	 * @var integer
	 */
	var $unit_MAX_duration;
	
	/**
	 * the file name of the first controller that is used in this unit of work
	 * @var string
	 */
	var $first_job = '';
	
	/**
	 * the file name of the next controller that is used in this unit of work
	 * @var string
	 */
	var $next_job = '';
	
	/**
	 * the file name of the previous controller that is used in this unit of work
	 * @var string
	 */
	var $previous_job = '';
	
	/**
	 * the file name of the last controller that is used in this unit of work
	 * @var string
	 */
	var $last_job = '';
	
	/**
	 * an array for storing dirty objects in a session
	 * @var array
	 */
	var $dirty_objects = array();
	
	/**
	 * an array for storing new objects in a session
	 * @var array
	 */
	var $new_objects = array();
	
	/**
	 * the title to be displayed for the controller page
	 * @var string
	 */
	var $title;
	
	/**
	 * meta keywords for the controller page
	 * @var string
	 */
	var $keywords;
	
	/**
	 * meta description for the controller page
	 * @avr string
	 */
	var $description;
	
	/**
	 * constructor for the Controller that sets up the unit of work start time
	 * and gets any business objects stored in the session
	 */
	 function Controller() {
	 	global $config;
	 	
	 	// if configured to do so, force redirect to the front controller
	 	if($config->get('sysForceFC') && basename($_SERVER['PHP_SELF']) != 'FC.php' && basename($_SERVER['PHP_SELF']) != 'index.php') {
	 		// set the correct HTTP header for the response
    		header('HTTP/1.1 301 Moved Permanently');
    		
    		if(empty($_SERVER["QUERY_STRING"]))
    			header('Location: '.$config->get('sysURL').'/FC.php?act='.get_class($this));
    		else
    			header('Location: '.$config->get('sysURL').'/FC.php?act='.get_class($this).'&'.$_SERVER["QUERY_STRING"]);
 
		    // we're done here
    		exit();
	 	}
	 	
	 	$this->unit_start_time = new Timestamp();
	 	
	 	if(!isset($_SESSION))
	 		session_start();	 	
	 	
	 	if(isset($_SESSION["unit_of_work"])) {	 		
	 		$this->set_unit_of_work($_SESSION["unit_of_work"]);
	 	}
	 	if(isset($_SESSION["dirty_objects"]))
	 		$this->dirty_objects = $_SESSION["dirty_objects"];
	 	
	 	if(isset($_SESSION["new_objects"]))	 		
	 		$this->new_objects = $_SESSION["new_objects"];	 	
	 }
	 
	 /**
	  * get the BO for this controller (if any)
	  * @return null if BO not set, BO object otherwise
	  */
	 function get_BO() {
	 	return $this->BO;
	 }
	 
	 /**
	  * setter for the BO for this controller
	  * @param BO $BO
	  */ 
	 function set_BO($BO) {
	 	$this->BO = $BO;
	 }
	 
	 /**
	  * get the name of the unit of work
	  * @return string $name
	  */
	 function get_name() {
	 	return $this->name;
	 }
	 
	 /**
	  * setter for the unit of work name
	  * @param string $name
	  */ 
	 function set_name($name) {
	 	$this->name = $name;
	 }
	 
	 /**
	  * get the visibility privileage string for the controller
	  * @return string $visibility
	  */
	 function get_visibility() {
	 	return $this->visibility;
	 }
	 
	 /**
	  * setter for the controller visibility privileage level
	  * @param string $visibility
	  */
	 function set_visibility($visibility) {
	 	$this->visibility = $visibility;	
	 }
	 
	 /**
	  * gets the name of the first job in this unit of work
	  * @return string $first_job
	  */
	 function get_first_job() {
	 	return $this->first_job;
	 }
	 
	 /**
	  * gets the name of the next job in this unit of work
	  * @return string $next_job
	  */
	 function get_next_job() {
	 	return $this->next_job;
	 }
	 
	 /**
	  * gets the name of the previous job in this unit of work
	  * @return string $previous_job
	  */
	 function get_previous_job() {
	 	return $this->previous_job;
	 }
	 
	 /**
	  * gets the name of the last job in this unit of work
	  * @return string $last_job
	  */
	 function get_last_job() {
	 	return $this->last_job;
	 }
	 
	 /**
	  * sets the name of the controller job sequence to the values in the supplied array (and stores array in the session)
	  * @param array $jobs An array of file names that make up the unit of work
	  */
	 function set_unit_of_work($jobs) {
	 	if(method_exists($this, 'before_set_unit_of_work_callback'))
			$this->before_set_unit_of_work_callback();
			
		// clear out any previous unit of work from the session
		$_SESSION["unit_of_work"] = null;		
		$this->dirty_objects = array();
		$this->new_objects = array();
		
	 	$num_of_jobs = count($jobs);
	 	
	 	for($i=0; $i<$num_of_jobs; $i++) {
	 		// the first job in the sequence
	 		if($i==0) {
	 			$this->first_job = $jobs[$i];
	 		}
	 		// found the current job
	 		if($this->name == $jobs[$i]) {
	 			if(isset($jobs[$i-1])) {
	 				// set the previous job if it exists
	 				$this->previous_job = $jobs[$i-1];
	 			}
	 			if(isset($jobs[$i+1])) {
	 				// set the next job if it exists
	 				$this->next_job = $jobs[$i+1];
	 			}
	 		}
	 		// the last job in the sequence
	 		if($i==($num_of_jobs-1)) {
	 			$this->last_job = $jobs[$i];
	 		}	
	 	}
	 	$_SESSION["unit_of_work"] = $jobs;
	 	
	 	if(method_exists($this, 'after_set_unit_of_work_callback'))
			$this->after_set_unit_of_work_callback();
	 }
	 
	 /**
	  * getter for the unit start time
	  * @return Timestamp $unit_start_time
	  */
	 function get_start_time() {
	 	return $this->unit_start_time->getValue();
	 }
	 
	 /**
	  * setter for the unit start time
	  * @param int $year
	  * @param int $month
	  * @param int $day
	  * @param int $hour
	  * @param int $minute
	  * @param int $second
	  */
	 function set_unit_start_time($year, $month, $day, $hour, $minute, $second) {
	 	$this->unit_start_time = setValue($year, $month, $day, $hour, $minute, $second);
	 }
	 
	 /**
	  * getter for the unit end time
	  * @return Timestamp $unit_end_time
	  */
	 function get_end_time() {
	 	return $this->unit_end_time->getValue();
	 }
	 
	 /**
	  * setter for the unit end time
	  * @param int $year
	  * @param int $month
	  * @param int $day
	  * @param int $hour
	  * @param int $minute
	  * @param int $second
	  */
	 function set_unit_end_time($year, $month, $day, $hour, $minute, $second) {
	 	$this->unit_end_time = new Timestamp();
	 	$this->unit_end_time->setValue($year, $month, $day, $hour, $minute, $second);
	 }
	 
	 /**
	  * getter for the unit MAX duration
	  * @return integer $unit_MAX_duration
	  */
	 function get_MAX_duration() {
	 	return $this->unit_MAX_duration->getValue();
	 }
	 
	 /**
	  * setter for the unit MAX duration
	  * @param integer $unit_MAX_duration
	  */
	 function set_unit_MAX_duration($duration) {
	 	$this->unit_MAX_duration = $duration;
	 }
	 
	 /**
	  * calculates and returns the unit of work duration in seconds
	  * @return integer $duration
	  */
	 function get_unit_duration() {
	 	$int_start_time = mktime(
	 		$this->unit_start_time->get_hour(),
	 		$this->unit_start_time->get_minute(),
	 		$this->unit_start_time->get_second(),
	 		$this->unit_start_time->get_month(),
	 		$this->unit_start_time->get_date(),
	 		$this->unit_start_time->get_year()
	 		);
	 	$int_end_time = mktime(
	 		$this->unit_end_time->get_hour(),
	 		$this->unit_end_time->get_minute(),
	 		$this->unit_end_time->get_second(),
	 		$this->unit_end_time->get_month(),
	 		$this->unit_end_time->get_date(),
	 		$this->unit_end_time->get_year()
	 		);
	 	return $int_end_time-$int_start_time;
	 }
	 
	 /**
	  * adds a loaded object to the dirty_objects array
	  * @param business_object $object
	  */
	 function mark_dirty($object) {
	 	if(method_exists($this, 'before_mark_dirty_callback'))
			$this->before_mark_dirty_callback();
		
	 	$this->dirty_objects[count($this->dirty_objects)] = $object;
	 	
	 	$_SESSION["dirty_objects"] = $this->dirty_objects;
	 	
	 	if(method_exists($this, 'after_mark_dirty_callback'))
			$this->after_mark_dirty_callback();
	 }
	 
	 /**
	  * getter for the dirty objects array
	  * @return array $dirty_objects
	  */
	 function get_dirty_objects() {
	 	return $this->dirty_objects;
	 }
	 
	 /**
	  * adds a newly created object to the new_objects array
	  * @param business_object $object
	  */
	 function mark_new($object) {
	 	if(method_exists($this, 'before_mark_new_callback'))
			$this->before_mark_new_callback();
		
	 	$this->new_objects[count($this->new_objects)] = $object;
	 	
	 	$_SESSION["new_objects"] = $this->new_objects;
	 	
	 	if(method_exists($this, 'after_mark_new_callback'))
			$this->after_mark_new_callback();
	 }
	 
	 /**
	  * getter for the new objects array
	  * @return array $new_objects
	  */
	 function get_new_objects() {
	 	return $this->new_objects;
	 }
	 
	 /**
	 * commits (saves) all of the new amd modified (dirty) objects in the unit of work to the database
	 * @return bool True on success, false otherwise
	 */
	function commit() {
		if(method_exists($this, 'before_commit_callback'))
			$this->before_commit_callback();
		
		$sql_query = 'BEGIN;';		

		$result = mysql_query($sql_query);		

		$new_objects = $this->get_new_objects();

		for ($i=0; $i<count($new_objects); $i++) {
			$success = true;
			try {
				$dirty_objects[$i]->save();
			}catch (FailedSaveException $e) {
				$success = false;
			}catch (LockingException $e) {
				$success = false;
			}
			
			if (!$success) {
				$this->last_query .= " Error: ".mysql_error();
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to commit the transaction','commit()','framework');
				$this->abort();
				return false;
			}
		}
		
		$dirty_objects = $this->get_dirty_objects();

		for ($i=0; $i<count($dirty_objects); $i++) {
			$success = true;
			try {
				$dirty_objects[$i]->save();
			}catch (FailedSaveException $e) {
				$success = false;
			}catch (LockingException $e) {
				$success = false;
			}
			
			if (!$success) {
				$this->last_query .= " Error: ".mysql_error();
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to commit the transaction','commit()','framework');
				$this->abort();
				return false;
			}
		}
		
		$sql_query = 'COMMIT;';		

		$result = mysql_query($sql_query);
		
		$_SESSION["unit_of_work"] = null;
		$_SESSION["dirty_objects"] = null;
		$_SESSION["new_objects"] = null;
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_commit_callback'))
				$this->after_commit_callback();
			
			return true;
		}else{					
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to commit the transaction','commit()','framework');
			return false;
		}		
	}
	
	/**
	 * method to clearup a cancelled unit of work
	 * @return bool True on success, false otherwise
	 */
	function abort() {
		if(method_exists($this, 'before_abort_callback'))
			$this->before_abort_callback();
		
		$_SESSION["unit_of_work"] = null;
		$_SESSION["dirty_objects"] = null;
		$_SESSION["new_objects"] = null;
		
		$sql_query = 'ROLLBACK;';		

		$result = mysql_query($sql_query);
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_abort_callback'))
				$this->after_abort_callback();
			
			return true;
		}else{					
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to rollback the transaction','abort()','framework');
			return false;
		}		
	}
	
	/**
	 * method to display the page head
	 */
	function display_page_head() {
		if(method_exists($this, 'before_display_page_head_callback'))
			$this->before_display_page_head_callback();
		
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
		
		if ($config->get('sysUseWidgets') && isset($this->BO)) {
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
			require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/StringBox.js.php';
			require_once $config->get('sysRoot').'alpha/view/widgets/TextBox.js.php';
		
			require_once $config->get('sysRoot').'alpha/view/widgets/form_validator.js.php';
		
			echo '<script type="text/javascript">';
			$validator = new form_validator($this->BO);
			echo '</script>';
		}
		
		if(method_exists($this, 'during_display_page_head_callback'))
			$this->during_display_page_head_callback();
		
		echo '</head>';
		echo '<body>';
			
		echo '<h1>'.$this->get_title().'</h1>';
		
		if (isset($_SESSION["current_user"])) {	
			echo '<p>You are logged in as '.$_SESSION["current_user"]->getDisplayname().'.  <a href="'.$config->get('sysURL').'/alpha/controller/logout.php">Logout</a></p>';
		}else{
			echo '<p>You are not logged in</p>';
		}
		
		if(method_exists($this, 'after_display_page_head_callback'))
			$this->after_display_page_head_callback();
	}
	
	/**
	 * method to display the page footer
	 */
	function display_page_foot() {
		if(method_exists($this, 'before_display_page_foot_callback'))
			$this->before_display_page_foot_callback();
		
		echo '</body>';
		echo '</html>';
		
		if(method_exists($this, 'after_display_page_foot_callback'))
			$this->after_display_page_foot_callback();
	}
	
	/**
	 * getter for the page title
	 * @return string page title
	 */
	function get_title() {
		return $this->title;	
	}
	
	/**
	 * setter for the page title
	 * @param string title
	 */
	function set_title($title) {
		$this->title = $title;	
	}
	
	/**
	 * getter for the page description
	 * @return string page description
	 */
	function get_description() {
		return $this->description;	
	}
	
	/**
	 * setter for the page description
	 * @param string description
	 */
	function set_description($description) {
		$this->description = $description;	
	}
	
	/**
	 * getter for the page keywords
	 * @return string page keywords
	 */
	function get_keywords() {
		return $this->keywords;	
	}
	
	/**
	 * setter for the page keywords
	 * @param string keywords
	 */
	function set_keywords($keywords) {
		$this->keywords = $keywords;	
	}
	
	/**
	 * method to display an access error for trespassing users
	 */
	function access_error() {
		if(method_exists($this, 'before_access_error_callback'))
			$this->before_access_error_callback();
		
		global $config;		
		
		// set up the title and meta details
		$this->set_title("Access Error: You Cannot View This Page");
		$this->set_description("Access Error: You Cannot View This Page.");
		$this->set_keywords("access,error");		
		
		
		$this->display_page_head();
		
		$error = new handle_error($_SERVER["PHP_SELF"],'You do not have the correct access rights to view this page.  If you have not logged in yet, try going back to the home page and logging in from there.' ,'access_error()','validation');
		echo '<p align="center"><a href="'.$config->get('sysURL').'">Home Page</a></p>';
		
		$this->display_page_foot();
		
		if(method_exists($this, 'after_access_error_callback'))
			$this->after_access_error_callback();
	}
	
	/**
	 * checks the user rights of the currently logged-in person against the page visibility
	 * @return boolean true if valid user, false otherwise
	 */
	function check_rights() {
		if(method_exists($this, 'before_check_rights_callback'))
			$this->before_check_rights_callback();
		
		// firstly if the page is public then there is no issue
		if ($this->get_visibility() == 'public') {
			if(method_exists($this, 'after_check_rights_callback'))
				$this->after_check_rights_callback();
		
			return true;
		}else{
			// the person is logged in
			if (isset($_SESSION["current_user"])) {
				// checking for admins (can access everything)
				if ($_SESSION["current_user"]->getAccessLevel() == 'Administrator') {
					if(method_exists($this, 'after_check_rights_callback'))
						$this->after_check_rights_callback();
					
					return true;
				} elseif ($_SESSION["current_user"]->getAccessLevel() == $this->get_visibility()) {
					if(method_exists($this, 'after_check_rights_callback'))
						$this->after_check_rights_callback();
					
					return true;
				// the person is editing their own profile which is allowed
				} elseif (get_class($this->BO) == "person_object" && $_SESSION["current_user"]->getDisplayName() == $this->BO->getDisplayName()) {
					if(method_exists($this, 'after_check_rights_callback'))
						$this->after_check_rights_callback();
					
					return true;
				}else{
					$this->access_error();
					return false;
				}
			}else{ // the person is NOT logged in
				$this->access_error();
				return false;
			}
		}
		
	}
	
	/**
	 * method to render a hidden form for posting the OID of an object to be deleted
	 */
	function render_delete_form() {
		if(method_exists($this, 'before_render_delete_form_callback'))
			$this->before_render_delete_form_callback();
		
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST" id="delete_form">';
		echo '<input type="hidden" name="delete_oid" id="delete_oid" value=""/>';
		if(class_exists("View")) {
			echo View::renderSecurityFields();
		}
		echo '</form>';
		
		if(method_exists($this, 'after_render_delete_form_callback'))
			$this->after_render_delete_form_callback();
	}
	
	/**
	 * method to check the validity of the two hidden form security
	 * fields which aim to ensure that a post to the controller is being sent from 
	 * the same server as hosting it.
	 * 
	 * @return bool True if the fields are valid, false otherwise 
	 */
	function check_security_fields() {		
		if(method_exists($this, 'before_check_security_fields_callback'))
			$this->before_check_security_fields_callback();
		
		// the server hostname + today's date
		$var1 = md5($_SERVER["HTTP_HOST"].date("Ymd"));
		// the server's IP plus $var1
		$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
		
		if(empty($_REQUEST["var1"]) || empty($_REQUEST["var2"]))
			return false;
		
		if ($var1 == $_REQUEST["var1"] && $var2 == $_REQUEST["var2"]) {			
			if(method_exists($this, 'after_check_security_fields_callback'))
				$this->after_check_security_fields_callback();
				
			return true;
		}else{
			/*
			 * Here we are implementing a "grace period" of one hour if the time is < 1:00AM, we will accept
			 * a match for yesterday's date in the security fields
			 * 
			 */			
			
			// the server hostname + today's date less 1 hour (i.e. yesterday where time is < 1:00AM)
			$var1 = md5($_SERVER["HTTP_HOST"].date("Ymd", (time()-3600)));
			// the server's IP plus $var1
			$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
			
			if ($var1 == $_REQUEST["var1"] && $var2 == $_REQUEST["var2"]) {				
				if(method_exists($this, 'after_check_security_fields_callback'))
					$this->after_check_security_fields_callback();
				
				return true;
			}else{
				return false;
			}
		}
	}
}

?>
