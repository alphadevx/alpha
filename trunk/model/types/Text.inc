<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaFrameworkException.inc';

/**
 * The Text complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Text {
	/**
	 * The value of the Text object
	 * 
	 * @var string
	 */
	private $value;
	
	/**
	 * The validation rule for the Text type
	 * 
	 * @var string
	 */
	private $validationRule;
	
	/**
	 * The error message for the string type when validation fails
	 * 
	 * @var string
	 */
	private $helper;
	
	/**
	 * The size of the value for the this Text
	 * 
	 * @var int
	 */
	private $size = 65535;
	
	/**
	 * The absolute maximum size of the value for the this Text
	 * 
	 * @var int
	 */
	const MAX_SIZE = 65535;
	
	/**
	 * Constructor
	 *
	 * @param string $val	 
	 */
	public function __construct($val='') {
		
		$this->validationRule = DEFAULT_TEXT;
		$this->helper = 'Error: not a valid text value!  A maximum of '.$this->size.' characters is allowed.';
		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validationRule, $val)) {				
				$this->value = $val;				
			}else{
				throw new AlphaFrameworkException($this->helper);
			}
		}else{
			throw new AlphaFrameworkException($this->helper);
		}
	}
	
	/**
	 * Setter for the value
	 *
	 * @param string $val
	 */
	public function setValue($val) {	
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validationRule, $val)) {				
				$this->value = $val;				
			}else{
				throw new AlphaFrameworkException($this->helper);
			}
		}else{
			throw new AlphaFrameworkException($this->helper);
		}
	}
	
	/**
	 * Getter for the value
	 *
	 * @return string
	 */
	public function getValue() {		
		return $this->value;
	}	
	
	/**
	 * Setter to override the default validation rule
	 *
	 * @param string $rule
	 */
	public function setRule($rule) {
		$this->validationRule = $rule;
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
	 * Setter to override the default validation helper message
	 *
	 * @param string $helper
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}
	
	/**
	 * Used to get the validation helper message string
	 *
	 * @return string
	 */
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Used to set the allowable size of the Text in the database field
	 *
	 * @param int $size
	 */
	public function setSize($size) {
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
			$this->helper = 'Error: not a valid text value!  A maximum of '.$this->size.' characters is allowed.';
		}else{
			throw new AlphaFrameworkException('Error: the value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
		}	
	}
	
	/**
	 * Get the allowable size of the Double in the database field
	 *	
	 * @return int
	 */
	public function getSize(){
		return $this->size;
	}
	
	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 */
	public function __toString() {		
		return strval($this->value);
	}
}

?>