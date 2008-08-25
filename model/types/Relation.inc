<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The Relation complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Relation extends AlphaType implements AlphaTypeInterface {
	/**
	 * The name of the business object class which this class is related to
	 *
	 * @var string
	 */
	private $relatedClass;
	
	/**
	 * The name of the fields of the business object class by which this class is related by
	 *
	 * @var string
	 */
	private $relatedClassField;
	
	/**
	 * The name of the field from the related business object class which is displayed by the selection widget
	 *
	 * @var string
	 */
	private $relatedClassDisplayField;
	
	/**
	 * The type of relation ('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY')
	 *
	 * @var string
	 */
	private $relationType;
	
	/**
	 * An array of the allowable relationship types ('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY')
	 *
	 * @var array
	 */
	private $allowableRelationTypes = array('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY');
	
	/**
	 * The object ID (OID) value of the related object
	 *
	 * @var int
	 */
	private $value;
	
	/**
	 * The validation rule for the Relation type
	 * 
	 * @var string
	 */
	private $validationRule;
	
	/**
	 * The error message for the Relation type when validation fails
	 * 
	 * @var string
	 */
	protected $helper;
	
	/**
	 * The size of the value for the this Relation
	 * 
	 * @var int
	 */
	private $size = 11;
	
	/**
	 * The absolute maximum size of the value for the this Relation
	 * 
	 * @var int
	 */
	const MAX_SIZE = 11;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->validationRule = DEFAULT_INTEGER;		
		$this->helper = 'Error: not a valid Relation value!  A maximum of '.$this->size.' characters is allowed.';		
	}
	
	/**
	 * Set the name of the business object class that this class is related to
	 *
	 * @param string $RC
	 */
	public function setRelatedClass($RC) {		
		if(in_array($RC, mysql_DAO::getBOClassNames()))
			$this->relatedClass = $RC;
		else
			throw new AlphaException('The class ['.$RC.'] is not defined anywhere!');
	}
	
	/**
	 * Getter for the relatedClass
	 *
	 * @return string
	 */
	public function getRelatedClass() {
		return $this->relatedClass;
	}
	
	/**
	 * Setter for the field of the related class
	 *
	 * @param string $RCF
	 */
	public function setRelatedClassField($RCF) {
		try{
			// use reflection to sure the related class has the field $RCF
			$reflection = new ReflectionClass($this->relatedClass);			
			$properties = $reflection->getProperties();			
			$fieldFound = false;
		
			foreach($properties as $propObj) {				
				if($RCF == $propObj->name) {
					$fieldFound = true;
					break;
				}
			}

			if($fieldFound)
				$this->relatedClassField = $RCF;
			else
				throw new AlphaException('The field ['.$RCF.'] was not found in the class ['.$this->relatedClass.']');
		}catch (Exception $e) {
			throw new AlphaException($e->getMessage());
		}
	}
	
	/**
	 * Getter for the field of the related class
	 *
	 * @return string
	 */
	public function getRelatedClassField() {
		return $this->relatedClassField;
	}
	
	/**
	 * Setter for the display field from the related class
	 *
	 * @param string $RCDF
	 */
	public function setRelatedClassDisplayField($RCDF) {		
		$this->relatedClassDisplayField = $RCDF;
	}
	
	/**
	 * Getter for the display field from the related class
	 *
	 * @return string
	 */
	public function getRelatedClassDisplayField() {		
		return $this->relatedClassDisplayField;
	}
	
	/**
	 * Setter for the relation type
	 *
	 * @param string $RT
	 */
	public function setRelationType($RT) {
		if(in_array($RT, $this->allowableRelationTypes))
			$this->relationType = $RT;
		else
			throw new AlphaException('Relation type of ['.$RT.'] is invalid!');
	}
	
	/**
	 * Getter for the relation type
	 *
	 * @return string
	 */
	public function getRelationType() {
		return $this->relationType;
	}
	
	/**
	 * Setter for the value (OID of related object) of this relation
	 *
	 * @param int $val	 
	 */
	public function setValue($val) {		
		if (strlen($val) <= $this->size) {			
			if (preg_match($this->validationRule, $val)) {				
				$this->value = str_pad($val, 11, "0", STR_PAD_LEFT);				
			}else{			
				throw new AlphaException($this->helper);
			}
		}else{
			throw new AlphaException($this->helper);
		}
	}
	
	/**
	 * Getter for the Integer value
	 *
	 * @return int
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
	 * Getter for the display value of the related class field
	 *
	 * @return string
	 */
	public function getRelatedClassDisplayFieldValue() {
		global $config;
		
		if (file_exists($config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc';
		}elseif (file_exists($config->get('sysRoot').'model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'model/'.$this->relatedClass.'.inc';
		}else{
			throw new AlphaException('Could not load the definition for the BO class ['.$this->relatedClass.']');
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
	
	/**
	 * For one-to-many relations, get the objects on the many side
	 * 
	 * @return array
	 */
	public function getRelatedObjects() {
		global $config;
		
		if (file_exists($config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'alpha/model/'.$this->relatedClass.'.inc';
		}elseif (file_exists($config->get('sysRoot').'model/'.$this->relatedClass.'.inc')) {
			require_once $config->get('sysRoot').'model/'.$this->relatedClass.'.inc';
		}else{
			throw new AlphaException('Could not load the definition for the BO class ['.$this->relatedClass.']');
		}
					
		$obj = new $this->relatedClass;
		$objects = $obj->load_all_by_attribute($this->getRelatedClassField(), $this->getValue());
		
		return $objects;
	}
	
	/**
	 * Get the allowable size of the Relation in the database field
	 *	
	 * @return int
	 */
	public function getSize(){
		return $this->size;
	}	
}

?>