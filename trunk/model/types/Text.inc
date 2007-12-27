<?php

// $Id$

require_once $config->get('sysRoot')."config/validation_rules.inc";

/**
 * The Text complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class Text {

	var $value;
	var $validation_rule;
	var $validation_helper;
	var $size = 65535;
	var $MAX_SIZE = 65535;
	
	function Text($val='') {
		$this->validation_rule = DEFAULT_TEXT;
		$this->validation_helper = 'Error: ['.$val.'] is not a valid text value!  A maximum of '.$this->MAX_SIZE.' characters is allowed.';
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;
				return true;				
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Text','framework');
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Text','framework');
		}
	}
	
	function set_value($val) {	
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = $val;				
				return true;				
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Text','framework');
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"], $this->validation_helper.' :- '.$val,'Text','framework');
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
}

?>
