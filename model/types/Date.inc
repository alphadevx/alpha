<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Date complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Date extends AlphaType implements AlphaTypeInterface {
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
	protected $helper= 'Error: not a valid date value!  A date should be in the format YYYY-MM-DD HH:MM:SS.';
 	
	/**
	 * Constructor.  Leave $date param empty to default to now.
	 *
	 * @param string $date
	 */
 	public function __construct($date='') {
 		global $config;
 		
 		$this->validationRule = DEFAULT_DATE;
		
 		if(empty($date)) {
 			if($config->get('sysDefaultDateTime') == 'now') {
				$this->year=date('Y');
				$this->month=date('m');
				$this->day=date('d');
				$this->weekday=date('l');
 			}else{
 				$this->year='0000';
				$this->month='00';
				$this->day='00';
 			}
		}else{
			if (preg_match($this->validationRule, $date)) {
				$this->populateFromString($date);
			}else{
				throw new IllegalArguementException($this->helper);
			}
		}
 	}
 	
 	/**
 	 * Accepts a full date string in YYYY-mm-dd format and populates relevent Date attributes.
 	 * 
 	 * @param string $date
 	 */
 	public function setValue($date) {
 		$this->populateFromString($date);
 	}
 	
 	/**
 	 * Set the Date attributes to match the three values provided
 	 *
 	 * @param int $year
 	 * @param int $month
 	 * @param int $day
 	 */
 	public function setDateValue($year, $month, $day) {
 		$valid = null;
 		
 		if (!preg_match('/^[0-9]{4}$/', $year))
 			$valid = 'Error: the year value '.$year.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month))
 			$valid = 'Error: the month value '.$month.' provided is invalid!';
 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day))
 			$valid = 'Error: the day value '.$day.' provided is invalid!';
 		if (!isset($valid) && !checkdate($month, $day, $year))
 			$valid = 'Error: the day value '.$year.'-'.$month.'-'.$day.' provided is invalid!'; 		
 		
 		if (isset($valid)){
 			throw new IllegalArguementException($valid);
 		}else{
 			$this->year = $year;
 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
 			$unixTime = mktime(0, 0, 0, $this->month, $this->day, $this->year); 			
 			$this->weekday=date('l', $unixTime); 			
 		}
 	}
 	
 	/**
 	 * Get the date value as a string in the format "YYYY-MM-DD"
 	 *
 	 * @return string
 	 */
 	public function getValue() {
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
 	 * Get the year part
 	 *
 	 * @return string
 	 */
 	public function getYear() {
 		return $this->year;	
 	}
 	
 	/**
 	 * Get the month part
 	 *
 	 * @return string
 	 */
 	public function getMonth() {
 		return $this->month;	
 	}
 	
 	/**
 	 * Get the day part
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
 	 * Accepts a full date string in YYYY-mm-dd format and populates relevent Date attributes.
 	 * 
 	 * @param string $date
 	 */
 	public function populateFromString($date) { 		
 		$valid = null;
 		
 		if($date == '' || $date == '0000-00-00') {
 			$this->year = '0000';
	 		$this->month = '00';
	 		$this->day = '00';
 		}else{
	 		// This is just here for legacy to ensure that any old time value from a Date object is ignored
	 		$spilt_by_space = explode(" ", $date);
	 		
	 		if(isset($spilt_by_space[0])) {
	 			$date = $spilt_by_space[0];
	 		}else{
	 			throw new IllegalArguementException('Invalid Date value ['.$date.'] provided!');
	 		}
	 		
	 		$split_by_dash = explode("-", $date);
	 		
	 		// Parse for the date parts, seperated by "-"
	 		if(isset($split_by_dash[0]) && isset($split_by_dash[1]) && isset($split_by_dash[2])) {
		 		$year = $split_by_dash[0];
		 		$month = $split_by_dash[1];
		 		$day = $split_by_dash[2];
	 		}else{
	 			throw new IllegalArguementException('Invalid Date value ['.$date.'] provided!');
	 		}
	 		
	 		if (!preg_match('/^[0-9]{4}$/', $year))
	 			$valid = 'Error: the year value '.$year.' provided is invalid!';
	 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month))
	 			$valid = 'Error: the month value '.$month.' provided is invalid!';
	 		if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day))
	 			$valid = 'Error: the day value '.$day.' provided is invalid!';
	 		if (!isset($valid) && !checkdate($month, $day, $year))
	 			$valid = 'Error: the day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
	 		 		
	 		if (isset($valid)){
	 			throw new IllegalArguementException($valid);
	 		}else{
	 			$this->year = $year;
	 			$this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
	 			$this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
	 			$unixTime = mktime(0, 0, 0, $this->month, $this->day, $this->year); 			
	 			$this->weekday=date('l', $unixTime); 			
	 		}
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
}

?>