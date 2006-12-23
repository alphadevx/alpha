<?php

// $Id$

require_once $sysRoot."config/validation_rules.inc";

/**
 * The Boolean complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class Boolean {

	var $value;
	var $validation_rule = DEFAULT_BOOLEAN; // only allows 1 or 0
	var $validation_helper = 'Error: not a valid boolean value!';
	var $size = 1;
	var $MAX_SIZE = 1;
	
	function Boolean($val=1) {
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;
				return true;			
			}else{
				return $this->validation_helper;
			}
		}else{
			return 'Error: the value '.$val.' provided by Boolean is greater than the size '.$this->size.' of this data type.';
		}
	}
	
	function set_value($val) {		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;
				return true;				
			}else{
				return $this->validation_helper;
			}
		}else{
			return 'Error: the value '.$val.' provided by set_value is greater than the size '.$this->size.' of this data type.';
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
		return $this->size;
	}	
}

?>
