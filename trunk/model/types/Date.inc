<?php

// $Id$

require_once $sysRoot."config/validation_rules.inc";

/**
 * The Date complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins
 * @todo move all of the validation logic to the set_day, set_hour etc. methods.
 * 
 */
class Date {
	var $year;
	var $month;
	var $day;
	var $weekday;
 	var $validation_rule;
	var $validation_helper;
 	
 	function Date($date="") {
 		$this->validation_rule = DEFAULT_DATE;
		$this->validation_helper = 'Error: not a valid date value!  A date should be in the format YYYY-MM-DD HH:MM:SS.';
		
		if(empty($date)) {
	 		$this->year=date('Y');
	 		$this->month=date('m');
	 		$this->day=date('d');	 		
		}else{
			$this->populate_from_string($date);
		}
 	}
 	
 	function set_value($year, $month, $day) {
 		$valid = 1;
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!'; 		
 		
 		if ($valid != 1){
 			return $valid;
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT); 			
 			return true;
 		}
 	}
 	
 	function get_value() {
 		return $this->year.'-'.$this->month.'-'.$this->day;
 	}
 	
 	function get_euro_value() {
 		return $this->day.'/'.$this->month.'/'.substr($this->year, 2, 2);
 	}
 	 	
 	function get_year() {
 		return $this->year;	
 	}
 	
 	function get_month() {
 		return $this->month;	
 	}
 	
 	function get_day() {
 		return $this->day;	
 	}
 	 	
 	/**
 	 * accepts a full date string in YYYY-mm-dd format
 	 * @param string date to be parsed
 	 */
 	function populate_from_string($date) { 		
 		$valid = 1;
 		
 		// this is just here for legacy to ensure that any old time value from a Date object is ignored
 		$spilt_by_space = explode(" ", $date);
 		
 		if(isset($spilt_by_space[0])) {
 			$date = $spilt_by_space[0];
 		}else{
 			$error = new handle_error($_SERVER["PHP_SELF"],'Invalid Date value ['.$date.'] provided!' ,'populate_from_string($date)','framework');
 			return false; 			
 		}
 		
 		$split_by_dash = explode("-", $date);
 		
 		if(isset($split_by_dash[0]) && isset($split_by_dash[1]) && isset($split_by_dash[2])) {
	 		$year = $split_by_dash[0];
	 		$month = $split_by_dash[1];
	 		$day = $split_by_dash[2];
 		}else{
 			$error = new handle_error($_SERVER["PHP_SELF"],'Invalid Date value ['.$date.'] provided!' ,'populate_from_string($date)','framework');
 			return false; 			
 		}
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
 		 		
 		if ($valid != 1){
 			return $valid;
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT); 			
 			return true;
 		}
 	}
 	
 	function set_validation($rule, $helper='Error: validation rule not met!') {
		$this->validation_rule = $rule;
		$this->validation_helper = $helper;
	}
	
	function get_rule() {
		return $this->validation_rule;
	}
	
	function set_rule($rule) {
		$this->validation_rule = $rule;
	}
	
	function get_helper() {
		return $this->validation_helper;
	}
	
	function set_helper($helper) {
		$this->validation_helper = $helper;
	}
}

?>
