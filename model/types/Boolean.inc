<?php

require_once $config->get('sysRoot')."config/validation_rules.inc";

/**
 * The Boolean complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Boolean {
	/**
	 * The value of the Boolean
	 *
	 * @var bool
	 */
	private $booleanValue;
	
	/**
	 * The binary (1/0) value of the Boolean.  This is the value stored in the database.
	 *
	 * @var int
	 */
	private $value;
	
	/**
	 * The error message returned for invalid values
	 *
	 * @var string
	 */
	private $validation_helper = 'Error: not a valid Boolean value!';
	
	/**
	 * Constructor
	 *
	 * @param boolean $val
	 * @return mixed True if the value provided is valid, otherwise a string containing the error message
	 */
	public function __construct($val=true) {
		if ($val === true || $val == 1) {
			$this->value = 1;
			$this->booleanValue = true;
			return true;
		}elseif ($val === false || $val == 0) {
			$this->value = 0;
			$this->booleanValue = false;
			return true;			
		}else{
			return $this->validation_helper;
		}
	}
	
	/**
	 * Used to set the Boolean value
	 *
	 * @param boolean $val
	 * @return mixed True if the value provided is valid, otherwise a string containing the error message
	 */
	public function setValue($val) {		
	if ($val === true || $val == 1) {
			$this->value = 1;
			$this->booleanValue = true;
			return true;
		}elseif ($val === false || $val == 0) {
			$this->value = 0;
			$this->booleanValue = false;
			return true;			
		}else{
			return $this->validation_helper;
		}
	}
	
	/**
	 * Used to get the binary (1/0) value of the Boolean.  This is the value stored in the database.
	 *
	 * @return int
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Used to get the boolean value of the Boolean
	 *
	 * @return boolean
	 */
	public function getBooleanValue() {
		return $this->booleanValue;
	}
	
	/**
	 * Used to get the validation helper message string
	 *
	 * @return string
	 */
	function getHelper() {
		return $this->validation_helper;
	}
}

?>
