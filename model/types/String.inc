<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The String complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class String extends AlphaType implements AlphaTypeInterface {
	/**
	 * The value of the string
	 * 
	 * @var string
	 */
	private $value;
	
	/**
	 * The validation rule for the string type
	 * 
	 * @var string
	 */
	private $validationRule;
	
	/**
	 * The error message for the string type when validation fails
	 * 
	 * @var string
	 */
	protected $helper;
	
	/**
	 * The size of the value for the this String
	 * 
	 * @var int
	 */
	private $size = 255;
	
	/**
	 * The absolute maximum size of the value for the this String
	 * 
	 * @var int
	 */
	const MAX_SIZE = 255;
	
	/**
	 * Simple boolean to determine if the string is a password or not
	 * 
	 * @var bool
	 */
	private $password = false;
	
	/**
	 * Constructor
	 *
	 * @param string $val
	 */
	public function __construct($val='') {		
		
		$this->validationRule = DEFAULT_STRING;		
		$this->helper = 'Error: not a valid string value!  A maximum of '.$this->size.' characters is allowed.';
		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validationRule, $val)) {				
				$this->value = $val;
			}else{
				throw new IllegalArguementException($this->helper);
			}
		}else{
			throw new IllegalArguementException($this->helper);
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
				throw new IllegalArguementException($this->helper);
			}
		}else{
			throw new IllegalArguementException($this->helper);
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
	 * Used to set the allowable size of the String in the database field
	 *
	 * @param int $size
	 */
	public function setSize($size) {
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
			$this->helper = 'Error: not a valid string value!  A maximum of '.$this->size.' characters is allowed.';
		}else{
			throw new IllegalArguementException('Error: the value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
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
	 * Sets up an appropriate validation rule for a required field
	 * 
	 * @param bool $req
	 */
	public function isRequired($req=true) {
		if ($req) {
			$this->validationRule = REQUIRED_STRING;
			$this->helper = 'This string requires a value!';
		}else{
			$this->validationRule = DEFAULT_STRING;
		}
	}
	
	/**
	 * Define the string as a password (making it required by validation rule)
	 * 
	 * @param bool $pass
	 */
	public function isPassword($pass=true) {
		$this->password = $pass;
		
		if($pass) {
			$this->validationRule = '/\w+/';
			$this->helper = 'Password is required!';
		}
	}
	
	/**
	 * Checks to see if the string is a password or not
	 * 
	 * @return bool
	 */
	public function checkIsPassword() {
		return $this->password;
	}
}

?>