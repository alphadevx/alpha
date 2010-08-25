<?php

require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * A customer sequence datatype, which is stored as a string and is made up of a string prefix
 * and an integer sequence, which is stored in a database.
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Sequence extends DAO implements AlphaTypeInterface{	
	/**
	 * The string prefix (must be capital alphabet characters only)
	 * 
	 * @var String
	 */
	protected $prefix;
	
	/**
	 * The integer sequence number incremented for each Sequence value with this prefix
	 *
	 * @var Integer
	 */
	protected $sequence;
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'Sequence';
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"Sequence ID#",
		"prefix"=>"Sequence prefix",
		"sequence"=>"Sequence number"		
	);
	
	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 */
	protected $helper = 'Error: not a valid sequence value!';
	
	/**
	 * The size of the value for the this Sequence
	 * 
	 * @var int
	 */
	protected $size = 255;
	
	/**
	 * The validation rule for the Sequence type
	 * 
	 * @var string
	 */
	protected $validationRule;
	
	/**
	 * The absolute maximum size of the value for the this Sequence
	 * 
	 * @var int
	 */
	const MAX_SIZE = 255;
	
	/**
	 * The constructor
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->validationRule = DEFAULT_STRING;
		
		$this->sequence = new Integer();
		
		$this->prefix = new String();
		$this->prefix->setRule(ALPHA_UPPER);
		$this->prefix->setHelper('Sequence prefix must be uppercase string!');
		$this->markUnique('prefix');
		
		$this->markTransient('helper');
		$this->markTransient('validationRule');
		$this->markTransient('size');
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
	 * Sets the sequence number to be the maximum value matching the prefix in the database
	 * plus one.  Note that calling this method increments the maximum value in the database.
	 * 
	 */
	public function setSequenceToNext() {
		
		try {
			$this->loadByAttribute('prefix', $this->prefix->getValue());
		}catch (BONotFoundException $e) {
			$this->set('sequence', 0);
		}
		
		$this->set('sequence', $this->get('sequence')+1);
		$this->save();
	}
	
	/**
	 * Getter for the validation helper string
	 * 
	 * @return string
	 */	
	public function getHelper() {
		return $this->helper;
	}
	
	/**
	 * Set the validation helper text
	 *
	 * @param string $helper
	 */
	public function setHelper($helper) {
		$this->helper = $helper;
	}
	
	/**
	 * Used to get the Sequence value as a string
	 * 
	 * @return string
	 */
	public function getValue() {
		if($this->prefix->getValue() != '' && $this->sequence->getValue() != 0)		
			return $this->prefix->getValue().'-'.$this->sequence->getValue();
		else
			return '';
	}
	
	/**
	 * Accepts a string to set the Sequence prefix/sequence values to, in the 
	 * format PREFIX-00000000000
	 * 
	 * @param string $val
	 */
	public function setValue($val) {
		if (strlen($val) <= $this->size) {
			if(!empty($val)) {
				$parts = explode('-', $val);
				if(count($parts) != 2)
					throw new IllegalArguementException($this->helper);
				$this->prefix->setValue($parts[0]);
				$this->sequence->setValue($parts[1]);
			}
		}else{
			throw new IllegalArguementException($this->helper);
		}
	}
	
	/**
	 * Get the allowable size of the Sequence in the database field
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
		return $this->prefix->getValue().'-'.$this->sequence->getValue();
	}
}

?>