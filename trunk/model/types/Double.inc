<?php

// $Id$

require_once $sysRoot."config/validation_rules.inc";

/**
 * The Double complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class Double {

	/**
	 * the value of the string
	 * @var string
	 */
 	var $value;
	
	/**
	 * the validation rule for the double type
	 * @var string
	 */
	var $validation_rule ; 
	
	/**
	 * the error message for the double type when validation fails
	 * @var string
	 */
	var $validation_helper = 'Error: not a valid Double value!';
	
	/**
	 * the size of the value for the double
	 * @var string
	 */
	var $size = 13;
	
	/**
	 * the absolute maximum size of the value for the this double
	 * @var string
	 */
	var $MAX_SIZE = 13;
	
	function Double($val=0.0) {
	
		$this->validation_rule = DEFAULT_DOUBLE;
		$this->validation_helper = 'Error: not a valid double value!  A maximum of '.$this->size.' characters is allowed, in the format 0.00';
		
		if (strlen($val) <= $this->size) {
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;					
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Double','framework');	
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Double','framework');	
		}
	}
	
	function set_value($val) {		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;				
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$this->val,'Double','framework');	
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$this->val,'Double','framework');	
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
	
	function get_helper() {
		return $this->validation_helper;
	}
	
	function set_size($size) {
		if ($size <= $this->MAX_SIZE)
			$this->size = $size;
		else
			return 'Error: the value '.$size.' provided by set_size is greater than the MAX_SIZE '.$this->MAX_SIZE.' of this data type.';	
	}
	
	function get_size(){
		return $this->size.',2';
	}	
}
 
?>