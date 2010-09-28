<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Integer complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Integer extends AlphaType implements AlphaTypeInterface {
	/**
	 * The value of the Integer
	 *
	 * @var int
	 */
 	private $value;
 	
	/**
	 * The validation rule (reg-ex) applied to Integer values
	 *
	 * @var string
	 */
 	private $validationRule;
 	
	/**
	 * The error message for the Integer type when validation fails
	 * 
	 * @var string
	 */
	protected $helper;
	
	/**
	 * The size of the value for the Integer
	 * 
	 * @var int
	 */
	private $size = 11;
	
	/**
	 * The absolute maximum size of the value for the this Integer
	 * 
	 * @var int
	 */
	const MAX_SIZE = 11;
	
	/**
	 * Constructor
	 *
	 * @param int $val	
	 */
	public function __construct($val=0) {		
		
		$this->validationRule = DEFAULT_INTEGER;
		$this->helper = 'Error: not a valid integer value!  A maximum of '.$this->size.' characters is allowed';
		
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
	 * Setter for the Integer value
	 *
	 * @param int $val	 
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
	 * Getter for the Integer value
	 *
	 * @return int
	 */
	public function getValue() {
		return intval($this->value);
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
	 * Used to set the allowable size of the Integer in the database field
	 *
	 * @param int $size	 
	 */
	public function setSize($size) {
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
			$this->helper = 'Error: not a valid integer value!  A maximum of '.$this->size.' characters is allowed';
		}else{
			throw new IllegalArguementException('Error: the value '.$size.' provided by set_size is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
		}	
	}
	
	/**
	 * Get the allowable size of the Integer in the database field
	 *	
	 * @return int
	 */
	public function getSize(){
		return $this->size;
	}
	
	/**
	 * Returns the integer value provided but padded with zeros to MAX_SIZE
	 * 
	 * @param int $val
	 */
	public static function zeroPad($val) {
		return str_pad($val, Integer::MAX_SIZE, '0', STR_PAD_LEFT);
	}
}
 
?>