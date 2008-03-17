<?php

// $Id$

require_once $config->get('sysRoot')."config/validation_rules.inc";

/**
 * The Relation complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * 
 */
class Relation {

	var $relatedClass;
	var $relatedClassField;
	var $relatedClassDisplayField;
	var $relationType;
	var $allowableRelationTypes = array('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY');
	var $value;
	var $validation_rule = DEFAULT_INTEGER; // allows only numbers
	var $validation_helper = 'Error: not a valid Integer value!';
	var $size = 11;
	var $MAX_SIZE = 11;
	
	function Relation() {		
	}
	
	function setRelatedClass($RC) {
		// TODO: check to see if the related class is defined somewhere
		$this->relatedClass = $RC;
	}
	
	function getRelatedClass() {
		return $this->relatedClass;
	}
	
	function setRelatedClassField($RCF) {		
		$this->relatedClassField = $RCF;
	}
	
	function getRelatedClassField() {
		return $this->relatedClassField;
	}
	
	function setRelatedClassDisplayField($RCDF) {		
		$this->relatedClassDisplayField = $RCDF;
	}
	
	function getRelatedClassDisplayField() {		
		return $this->relatedClassDisplayField;
	}
	
	function setRelationType($RT) {
		if(in_array($RT, $this->allowableRelationTypes))
			$this->relationType = $RT;
		else
			$error = new handle_error('Relation.inc', 'Relation type of ['.$RT.'] is invalid!','setRelationType','framework');
	}
	
	function getRelationType() {
		return $this->relationType;
	}
	
	function set_value($val) {		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validation_rule, $val)) {				
				$this->value = str_pad($val, 11, "0", STR_PAD_LEFT);
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
	
	function getRelatedClassDisplayFieldValue() {
		global $config;
		
		if (file_exists($config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc';
		}elseif (file_exists($config->get('sysRoot').'model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'model/'.$this->relatedClass.'.inc';
		}else{
			$error = new handle_error('Relation.inc','Could not load the defination for the BO class '.$this->relatedClass,'framework');
			exit;
		}
					
		$obj = new $this->relatedClass;
		// making sure we have an object to load
		if(empty($this->value) || $this->value == '00000000000') {
			return '[Please select]';
		}else{
			$obj->load_object($this->value);		
			return $obj->get($this->relatedClassDisplayField);
		}
	}
}

?>