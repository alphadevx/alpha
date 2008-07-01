<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaFrameworkException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Double complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Double extends AlphaType implements AlphaTypeInterface {
	/**
	 * the value of the Double
	 * 
	 * @var double
	 */
 	private $value;
	
	/**
	 * The validation rule (reg-ex) applied to Double values
	 *
	 * @var string
	 */
 	private $validationRule;
	
	/**
	 * The error message for the Double type when validation fails
	 * 
	 * @var string
	 */
	protected $helper = 'Error: not a valid Double value!';
	
	/**
	 * The size of the value for the Double
	 * 
	 * @var int
	 */
	private $size = 13;
	
	/**
	 * The absolute maximum size of the value for the this double
	 * 
	 * @var int
	 */
	const MAX_SIZE = 13;
	
	/**
	 * Constructor
	 *
	 * @param double $val
	 */
	public function __construct($val=0.0) {	
		$this->validationRule = DEFAULT_DOUBLE;
		$this->helper = 'Error: not a valid double value!  A maximum of '.$this->size.' characters is allowed, in the format 0.00';
		
		if (strlen($val) <= $this->size) {
			if ($val == 0 || preg_match($this->validationRule, strval($val))) {				
				$this->value = $val;					
			}else{
				throw new AlphaFrameworkException($this->helper);
			}
		}else{
			throw new AlphaFrameworkException($this->helper);
		}
	}
	
	/**
	 * Setter for the Double value
	 *
	 * @param double $val
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
	 * Getter for the Double value
	 *
	 * @return double
	 */
	public function getValue() {
		return $this->value;
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
	 * Used to set the allowable size of the Double in the database field
	 *
	 * @param int $size	 
	 */
	public function setSize($size) {
		if ($size <= self::MAX_SIZE) {
			$this->size = $size;
			$this->helper = 'Error: not a valid double value!  A maximum of '.$this->size.' characters is allowed, in the format 0.00';
		}else{
			throw new AlphaFrameworkException('Error: the value '.$size.' provided by setSize is greater than the MAX_SIZE '.self::MAX_SIZE.' of this data type.');
		}	
	}
	
	/**
	 * Get the allowable size of the Double in the database field
	 *
	 * @param bool $databaseDimension
	 * @return mixed
	 */
	public function getSize($databaseDimension=false){
		if($databaseDimension)
			return $this->size.',2';
		else
			return $this->size;
	}

	/**
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 */
	public function __toString() {		
		return strval(sprintf("%01.2f", $this->value));
	}
}
 
?>