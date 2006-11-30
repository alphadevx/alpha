<?php

// $Id$

require_once $sysRoot."config/validation_rules.inc";

/**
 * The String complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class String {
	/**
	 * the value of the string
	 * @var string
	 */
	var $value;
	
	/**
	 * the validation rule for the string type
	 * @var string
	 */
	var $validation_rule;
	
	/**
	 * the error message for the string type when validation fails
	 * @var string
	 */
	var $validation_helper;
	
	/**
	 * the size of the value for the this string
	 * @var int
	 */
	var $size = 255;
	
	/**
	 * the absolute maximum size of the value for the this string
	 * @var string
	 */
	var $MAX_SIZE = 255;
	
	/**
	 * simple boolean to determine if the string is a password or not
	 * @var bool
	 */
	var $password = false;
	
	function String($val='') {
		$this->validation_rule = DEFAULT_STRING;		
		$this->validation_helper = 'Error: ['.$val.'] is not a valid string value!  A maximum of '.$this->size.' characters is allowed.';
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'String','framework');	
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'String','framework');	
		}
	}
	
	function set_value($val) {		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'String','framework');	
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'String','framework');	
		}
	}
	
	function get_value() {
		return $this->value;
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
	
	function set_size($size) {
		if ($size <= $this->MAX_SIZE)
			$this->size = $size;
		else
			return 'Error: the value '.$size.' provided by set_size is greater than the MAX_SIZE '.$this->MAX_SIZE.' of this data type.';	
	}
	
	function get_size(){
		return $this->size;
	}
	
	function get_MAX_SIZE(){
		return $this->MAX_SIZE;
	}
	
	/**
	 * sets up an appropriate validation rule for a required field
	 */
	function is_required($req=true) {
		if ($req) {
			$this->set_validation("/^([a-zA-Z0-9]+)$/", "This string requires a value!");
		}else{
			$this->set_rule("/.*/i");
		}
	}
	
	/**
	 * define the string as a password (making it required by validation rule)
	 * @param bool $pass whether it is a password or not	 
	 */
	function is_password($pass=true) {
		$this->password = $pass;
		
		if($pass)
			$this->is_required();
	}
	
	/**
	 * checks to see if the string is a password or not
	 * @return bool whether it is a password or not
	 */
	function check_is_password() {
		return $this->password;
	}
}

?>