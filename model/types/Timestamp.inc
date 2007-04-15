<?php

// $Id: Timestamp.inc 141 2006-12-24 23:07:44Z john $

require_once $sysRoot."config/validation_rules.inc";

/**
 * The Timestamp complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins
 * 
 */
class Timestamp {
	var $year;
	var $month;
	var $day;
	var $hour;
	var $minute;
 	var $second;
 	var $weekday;
 	var $validation_rule;
	var $validation_helper;
 	
 	function Timestamp() {
 		$this->validation_rule = DEFAULT_DATE;
		$this->validation_helper = 'Error: not a valid date value!  A date should be in the format YYYY-MM-DD HH:MM:SS.';
		
 		$this->year=date('Y');
 		$this->month=date('m');
 		$this->day=date('d');
 		$this->hour=date('H');
 		$this->minute=date('i');
 		$this->second=date('s'); 		
 	}
 	
 	function set_value($year, $month, $day, $hour, $minute, $second) {
 		$valid = 1;
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 		
 		if ($valid != 1){
 			return $valid;
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
 			return true;
 		}
 	}
 	
 	function get_value() {
 		return $this->year.'-'.$this->month.'-'.$this->day.' '.$this->hour.':'.$this->minute.':'.$this->second;
 	}
 	
 	function get_date() {
 		return $this->year.'-'.$this->month.'-'.$this->day;
 	}
 	
 	function set_date($year, $month, $day) {
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
 	
 	
 	function get_time() {
 		return $this->hour.':'.$this->minute.':'.$this->second;
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
 	
 	function get_hour() {
 		return $this->hour;	
 	}
 	
 	function get_minute() {
 		return $this->minute;	
 	}
 	
 	function get_second() {
 		return $this->second;	
 	}
 	
 	
 	function set_time($hour, $minute, $second) {
 		$valid = 1;
 		
 		if(!preg_match('/^[0-9]{2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 			
 		if ($valid != 1) {
 			return $valid;
 		}else{
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT); 			
 			return true;
 		}
 	}
 	
 	/**
 	 * accepts a full date/time string in YYYY-mm-dd hh:ii:ss format
 	 * @param string date/time to be parsed
 	 */
 	function populate_from_string($date_time) {
 		$valid = 1;
 			
 		$spilt_by_space = explode(" ", $date_time);
 		
 		$date = $spilt_by_space[0];
 		$time = $spilt_by_space[1];
 		
 		$split_by_dash = explode("-", $date);
 		$year = $split_by_dash[0];
 		$month = $split_by_dash[1];
 		$day = $split_by_dash[2];
 		
 		$split_by_colon = explode(":", $time);
 		$hour = $split_by_colon[0];
 		$minute = $split_by_colon[1];
 		$second = $split_by_colon[2];
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 		
 		if ($valid != 1){
 			return $valid;
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
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
