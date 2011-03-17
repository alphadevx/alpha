<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Date complex data type
 * 
 * @package alpha::model::types
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class Date extends AlphaType implements AlphaTypeInterface {
	/**
	 * The year part
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $year;
	
	/**
	 * The month part
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $month;
	
	/**
	 * The day part
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $day;
	
	/**
	 * The textual version of the day, e.g. Monday
	 *
	 * @var string
	 * @since 1.0
	 */
	private $weekday;
	
	/**
	 * The validation rule (reg-ex) applied to Date values
	 *
	 * @var string
	 * @since 1.0
	 */
 	private $validationRule;
 	
 	/**
	 * The error message returned for invalid values
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $helper= 'Not a valid date value!  A date should be in the ISO format YYYY-MM-DD.';
 	
	/**
	 * Constructor.  Leave $date param empty to default to now.
	 *
	 * @param string $date Date string in the ISO format YYYY-MM-DD.
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
 	public function __construct($date='') {
 		global $config;
 		
 		$this->validationRule = AlphaValidator::ALLOW_ALL;
		
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
 	 * Accepts a full date string in ISO YYYY-mm-dd format and populates relevent Date attributes.
 	 * 
 	 * @param string $date
 	 * @since 1.0
 	 * @throws IllegalArguementException
 	 */
 	public function setValue($date) {
 		$this->populateFromString($date);
 	}
 	
 	/**
 	 * Set the Date attributes to match the three values provided
 	 *
 	 * @param integer $year
 	 * @param integer $month
 	 * @param integer $day
 	 * @throws IllegalArguementException
 	 * @since 1.0
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
 	 * @since 1.0
 	 */
 	public function getValue() {
 		return $this->year.'-'.$this->month.'-'.$this->day;
 	}
 	
	/**
 	 * Return the value in UNIX timestamp format
 	 * 
 	 * @return integer
 	 * @since 1.0
 	 */
 	public function getUnixValue() {
 		return mktime(0, 0, 0, $this->month, $this->day, $this->year);
 	}
 	
 	/**
 	 * Get the date value as a string in the format "DD/MM/YYYY"
 	 *
 	 * @return string
 	 * @since 1.0
 	 */
 	public function getEuroValue() {
 		return $this->day.'/'.$this->month.'/'.substr($this->year, 2, 2);
 	}
 	
	/**
 	 * Get the date value as a string in the format "MM/DD/YYYY"
 	 *
 	 * @return string
 	 * @since 1.0
 	 */
 	public function getUSValue() {
 		return $this->month.'/'.$this->day.'/'.substr($this->year, 2, 2);
 	}
 	
 	/**
 	 * Get the year part
 	 *
 	 * @return integer
 	 * @since 1.0
 	 */
 	public function getYear() {
 		return $this->year;	
 	}
 	
 	/**
 	 * Get the month part
 	 *
 	 * @return integer
 	 * @since 1.0
 	 */
 	public function getMonth() {
 		return $this->month;	
 	}
 	
 	/**
 	 * Get the day part
 	 *
 	 * @return integer
 	 * @since 1.0
 	 */
 	public function getDay() {
 		return $this->day;	
 	}
 	
 	/**
 	 * Get the textual weekday part, e.g. Monday
 	 *
 	 * @return string
 	 * @since 1.0
 	 */
 	public function getWeekday() {
 		return $this->weekday;
 	}
 	 	
 	/**
 	 * Accepts a full date string in YYYY-MM-DD format and populates relevent Date attributes.
 	 * 
 	 * @param string $date
 	 * @throws IllegalArguementException
 	 * @since 1.0
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
 	 * @since 1.0
 	 */
 	public function getRule() {
		return $this->validationRule;
	}
	
	/**
	 * Set the validation rule
	 *
	 * @param string $rule
	 * @since 1.0
	 */
	public function setRule($rule) {
		$this->validationRule = $rule;
	}	
}

?>