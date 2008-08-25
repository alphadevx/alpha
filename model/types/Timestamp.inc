<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Timestamp complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id: Timestamp.inc 141 2006-12-24 23:07:44Z john $
 * 
 */
class Timestamp extends AlphaType implements AlphaTypeInterface {
	/**
	 * The year part
	 *
	 * @var int
	 */
	private $year;
	
	/**
	 * The month part
	 *
	 * @var int
	 */
	private $month;
	
	/**
	 * The day part
	 *
	 * @var int
	 */
	private $day;
	
	/**
	 * The hour part
	 *
	 * @var int
	 */
	private $hour;
	
	/**
	 * The minute part
	 *
	 * @var int
	 */
	private $minute;
 	
	/**
	 * The second part
	 *
	 * @var int
	 */
	private $second;
 	
 	/**
	 * The textual version of the day, e.g. Monday
	 *
	 * @var string
	 */
	private $weekday;
	
 	/**
	 * The validation rule (reg-ex) applied to Date values
	 *
	 * @var string
	 */
 	private $validationRule;
 	
	/**
	 * The error message returned for invalid values
	 *
	 * @var string
	 */
	private $helper= 'Error: not a valid timestamp value!  A timestamp should be in the format YYYY-MM-DD HH:MM:SS.';
 	
	/**
	 * Constructor
	 */
 	public function __construct($timestamp='') {
 		$this->validationRule = DEFAULT_TIMESTAMP;
		
 		if(empty($timestamp)) {
	 		$this->year=date('Y');
	 		$this->month=date('m');
	 		$this->day=date('d');
	 		$this->weekday=date('l');
	 		$this->hour=date('H');
	 		$this->minute=date('i');
	 		$this->second=date('s');
 		}else{
			if (preg_match($this->validationRule, $timestamp)) {
				$this->populateFromString($timestamp);
			}else{
				throw new AlphaException($this->helper);
			}
		}
 	}
 	
	/**
 	 * Accepts a full date/time string in YYYY-mm-dd hh:ii:ss format
 	 * 
 	 * @param string $dateTime
 	 */
 	public function setValue($dateTime) {
 		$this->populateFromString($dateTime);
 	}
 	
 	/**
 	 * Setter for the timestamp value
 	 *
 	 * @param int $year
 	 * @param int $month
 	 * @param int $day
 	 * @param int $hour
 	 * @param int $minute
 	 * @param int $second
 	 */
 	public function setTimestampValue($year, $month, $day, $hour, $minute, $second) {
 		$valid = null;
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!isset($valid) && !checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'-'.$month.'-'.$day.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 		
 		if (isset($valid)){
 			throw new AlphaException($valid);
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
 			$unixTime = mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year); 			
 			$this->weekday=date('l', $unixTime);
 		}
 	}
 	
 	/**
 	 * Getter for the Timestamp value
 	 *
 	 * @return string
 	 */
 	public function getValue() {
 		return $this->year.'-'.$this->month.'-'.$this->day.' '.$this->hour.':'.$this->minute.':'.$this->second;
 	}
 	
 	/**
 	 * Getter for the date part
 	 *
 	 * @return string
 	 */
 	public function getDate() {
 		return $this->year.'-'.$this->month.'-'.$this->day;
 	}
 	
	/**
 	 * Get the date value as a string in the format "DD/MM/YYYY"
 	 *
 	 * @return string
 	 */
 	public function getEuroValue() {
 		return $this->day.'/'.$this->month.'/'.substr($this->year, 2, 2);
 	}
 	
 	/**
 	 * Setter for the date part
 	 *
 	 * @param int $year
 	 * @param int $month
 	 * @param int $day 	 
 	 */
 	public function setDate($year, $month, $day) {
 		$valid = null;
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!isset($valid) && !checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
 		
 		if (isset($valid)){
 			throw new AlphaException($valid);
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$unixTime = mktime(0, 0, 0, $this->month, $this->day, $this->year); 			
 			$this->weekday=date('l', $unixTime);
 		}
 	}
 	
 	/**
 	 * Getter for the time part
 	 *
 	 * @return string
 	 */
 	public function getTime() {
 		return $this->hour.':'.$this->minute.':'.$this->second;
 	}
 	
 	/**
 	 * Getter for the year part
 	 *
 	 * @return string
 	 */
 	public function getYear() {
 		return $this->year;	
 	}
 	
 	/**
 	 * Getter for the month part
 	 *
 	 * @return string
 	 */
 	public function getMonth() {
 		return $this->month;	
 	}
 	
 	/**
 	 * Getter for the day part
 	 *
 	 * @return string
 	 */
 	public function getDay() {
 		return $this->day;	
 	}
 	
	/**
 	 * Get the textual weekday part, e.g. Monday
 	 *
 	 * @return string
 	 */
 	public function getWeekday() {
 		return $this->weekday;
 	}
 	
 	/**
 	 * Getter for the hour part
 	 *
 	 * @return string
 	 */
 	public function getHour() {
 		return $this->hour;	
 	}
 	
 	/**
 	 * Getter for the minute part
 	 *
 	 * @return string
 	 */
 	public function getMinute() {
 		return $this->minute;	
 	}
 	
 	/**
 	 * Getter for the second part
 	 *
 	 * @return string
 	 */
 	public function getSecond() {
 		return $this->second;	
 	}
 	
 	/**
 	 * Setter for the time part
 	 *
 	 * @param int $hour
 	 * @param int $minute
 	 * @param int $second 	 
 	 */
 	function setTime($hour, $minute, $second) {
 		$valid = null;
 		
 		if(!isset($valid) && !preg_match('/^[0-9]{2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 			
 		if (isset($valid)) {
 			throw new AlphaException($valid);
 		}else{
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT); 			
 		}
 	}
 	
 	/**
 	 * Accepts a full date/time string in YYYY-mm-dd hh:ii:ss format
 	 * 
 	 * @param string $dateTime
 	 */
 	public function populateFromString($dateTime) {
 		$valid = null;
 			
 		$spilt_by_space = explode(" ", $dateTime);
 		
 		if(isset($spilt_by_space[0])) {
 			$date = $spilt_by_space[0];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(isset($spilt_by_space[1])) {
 			$time = $spilt_by_space[1];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		$split_by_dash = explode("-", $date);
 		
 		if(isset($split_by_dash[0])) {
 			$year = $split_by_dash[0];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(isset($split_by_dash[1])) {
 			$month = $split_by_dash[1];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(isset($split_by_dash[2])) {
 			$day = $split_by_dash[2];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		$split_by_colon = explode(":", $time);
 		
 		if(isset($split_by_colon[0])) {
 			$hour = $split_by_colon[0];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(isset($split_by_colon[1])) {
 			$minute = $split_by_colon[1];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(isset($split_by_colon[2])) {
 			$second = $split_by_colon[2];
 		}else{
 			throw new AlphaException($this->helper);
 		}
 		
 		if(!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if(!isset($valid) && !checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24))
 			$valid = 'Error: the hour value '.$hour.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60))
 			$valid = 'Error: the minute value '.$minute.' provided is invalid!';
 		if(!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60))
 			$valid = 'Error: the second value '.$second.' provided is invalid!';
 		
 		if (isset($valid)){
 			throw new AlphaException($valid);
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
 			$this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
 			$this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
 			$unixTime = mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year); 			
 			$this->weekday=date('l', $unixTime); 			
 		}
 	}
 	
	/**
 	 * Get the validation rule
 	 *
 	 * @return string
 	 */
 	public function getRule() {
		return $this->validationRule;
	}
	
	/**
	 * Set the validation rule
	 *
	 * @param string $rule
	 */
	public function setRule($rule) {
		$this->validationRule = $rule;
	}
	
	/**
	 * Get the validation helper text
	 *
	 * @return string
	 */
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Set the validation helper text
	 *
	 * @param string $helper
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}
}

?>