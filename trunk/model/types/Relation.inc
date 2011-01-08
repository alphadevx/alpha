<?php

require_once $config->get('sysRoot').'config/validation_rules.inc';
require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaType.inc';
require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';
require_once $config->get('sysRoot').'alpha/model/types/RelationLookup.inc';

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
	 * An array of fields to use the values of while rendering related display values via the selection widget
	 * 
	 * @var array
	 */
	private $relatedClassHeaderFields = array();
		
	/**
	 * The name of the business object class on the left of a MANY-TO-MANY relation 
	 *
	 * @var string
	 */
	private $relatedClassLeft;
	
	/**
	 * The name of the field from the related business object class on the left of a 
	 * MANY-TO-MANY relation which is displayed by the selection widget
	 *
	 * @var string
	 */
	private $relatedClassLeftDisplayField;
	
	/**
	 * The name of the business object class on the right of a MANY-TO-MANY relation 
	 *
	 * @var string
	 */
	private $relatedClassRight;
	
	/**
	 * The name of the field from the related business object class on the right of a 
	 * MANY-TO-MANY relation which is displayed by the selection widget
	 *
	 * @var string
	 */
	private $relatedClassRightDisplayField;	
	
	/**
	 * The type of relation ('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY')
	 *
	 * @var string
	 */
	private $relationType;
	
	/**
	 * In the case of MANY-TO-MANY relationship, a lookup object will be required
	 *
	 * @var RelationLookup
	 */
	private $lookup;
	
	/**
	 * When building a relation with the tag_object BO, set this to the name of the tagged class
	 * 
	 * @var string
	 */
	private $taggedClass;
	
	/**
	 * An array of the allowable relationship types ('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY')
	 *
	 * @var array
	 */
	private $allowableRelationTypes = array('MANY-TO-ONE','ONE-TO-MANY','ONE-TO-ONE','MANY-TO-MANY');
	
	/**
	 * The object ID (OID) value of the related object.  In the special case of a MANY-TO-MANY
	 * relation, contains the OID of the object on the current, accessing side.  Can contain NULL.
	 *
	 * @var mixed
	 */
	private $value = NULL;
	
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
		$this->helper = ' not a valid Relation value!  A maximum of '.$this->size.' characters is allowed.';		
	}
	
	/**
	 * Set the name of the business object class that this class is related to
	 *
	 * @param string $RC
	 * @param string $side Only required for MANY-TO-MANY relations
	 */
	public function setRelatedClass($RC, $side='') {		
		if(in_array($RC, AlphaDAO::getBOClassNames())) {
			// load the class definition if it has not been loaded already
			AlphaDAO::loadClassDef($RC);
			switch($side) {
				case '':
					$this->relatedClass = $RC;
				break;
				case 'left':
					$this->relatedClassLeft = $RC;
				break;
				case 'right':
					$this->relatedClassRight = $RC;
				break;
				default:
					throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');							
			}
		}else{
			throw new IllegalArguementException('The class ['.$RC.'] is not defined anywhere!');
		}
	}
	
	/**
	 * Get the name of the business object class that this class is related to
	 *
	 * @param string $RC
	 * @return string
	 */
	public function getRelatedClass($side='') {		
		switch($side) {
			case '':
				return $this->relatedClass;
			break;
			case 'left':
				return $this->relatedClassLeft;
			break;
			case 'right':
				return $this->relatedClassRight;
			break;
			default:
				throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');
				return '';							
		}
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
				throw new IllegalArguementException('The field ['.$RCF.'] was not found in the class ['.$this->relatedClass.']');
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
	 * Setter for ONE-TO-MANY relations, which sets the header fields to
	 * render from the related class
	 * 
	 * @param array $fieldNames
	 */
	public function setRelatedClassHeaderFields($fieldNames) {
		$this->relatedClassHeaderFields = $fieldNames;
	}
	
	/**
	 * Getter for the selection widget field headings of the related class
	 *
	 * @return array
	 */
	public function getRelatedClassHeaderFields() {
		return $this->relatedClassHeaderFields;
	}
	
	/**
	 * Setter for the display field from the related class
	 *
	 * @param string $RCDF
	 * @param string $side Only required for MANY-TO-MANY relations
	 */
	public function setRelatedClassDisplayField($RCDF, $side='') {
		switch($side) {
			case '':
				$this->relatedClassDisplayField = $RCDF;
			break;
			case 'left':
				$this->relatedClassLeftDisplayField = $RCDF;
			break;
			case 'right':
				$this->relatedClassRightDisplayField = $RCDF;
			break;
			default:
				throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');							
		}
	}
	
	/**
	 * Getter for the display field from the related class
	 *
	 * @param string $side Only required for MANY-TO-MANY relations
	 * @return string
	 */
	public function getRelatedClassDisplayField($side='') {
		switch($side) {
			case '':
				return $this->relatedClassDisplayField;
			break;
			case 'left':
				return $this->relatedClassLeftDisplayField;
			break;
			case 'right':
				return $this->relatedClassRightDisplayField;
			break;
			default:
				throw new IllegalArguementException('The side paramter ['.$RC.'] is not valid!');
				return '';							
		}
	}
	
	/**
	 * Setter for the relation type
	 *
	 * @param string $RT
	 * @throws IllegalArguementException
	 * @throws FailedLookupCreateException
	 */
	public function setRelationType($RT) {
		if(in_array($RT, $this->allowableRelationTypes)) {
			$this->relationType = $RT;
			if($RT == 'MANY-TO-MANY') {
				try {
					$this->lookup = new RelationLookup($this->relatedClassLeft, $this->relatedClassRight);
				}catch (FailedLookupCreateException $flce) {
					throw $flce;
				}catch (IllegalArguementException $iae) {
					throw $iae;
				}
			}
		}else{
			throw new IllegalArguementException('Relation type of ['.$RT.'] is invalid!');
		}
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
		if(empty($val)) {
			$this->value = NULL;
		}else{					
			if (strlen($val) <= $this->size) {			
				if (preg_match($this->validationRule, $val)) {				
					$this->value = str_pad($val, 11, '0', STR_PAD_LEFT);				
				}else{			
					throw new IllegalArguementException("[$val]".$this->helper);
				}
			}else{
				throw new IllegalArguementException("[$val]".$this->helper);
			}
		}
	}
	
	/**
	 * Getter for the Relation value
	 *
	 * @return mixed
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
	 * Setter to override the default validation rule
	 *
	 * @param string $rule
	 */
	public function setRule($rule) {
		$this->validationRule = $rule;
	}
	
	/**
	 * Getter for the display value of the related class field.  In the case of a 
	 * MANY-TO-MANY Relation, a comma-seperated sorted list of values is returned.
	 *
	 * @param string $accessingClassName Used to indicate the reading side when accessing from MANY-TO-MANY relation (leave blank for other relation types)
	 * @return string
	 */
	public function getRelatedClassDisplayFieldValue($accessingClassName='') {
		global $config;
		
		if($this->relationType == 'MANY-TO-MANY') {
			/*
			 * 1. Use RelationLookup to get OIDs of related objects
			 * 2. Load related objects
			 * 3. Access the value of the field on the object to build the
			 * comma-seperated list.
			 */
			if(empty($this->lookup)) {
				throw new AlphaException('Tried to load related MANY-TO-MANY fields but no RelationLookup set on the Relation object!');
				return '';
			}
			
			if(empty($accessingClassName)) {
				throw new IllegalArguementException('Tried to load related MANY-TO-MANY fields but no accessingClassName parameter set on the call to getRelatedClassDisplayFieldValue!');
				return '';
			}
			
			// load objects on the right from accessing on the left			
			if($accessingClassName == $this->relatedClassLeft) {
				AlphaDAO::loadClassDef($this->relatedClassRight);
				$obj = new $this->relatedClassRight;
				
				$lookupObjects = $this->lookup->loadAllByAttribute('leftID', $this->value);
				
				$values = array();
				foreach($lookupObjects as $lookupObject) {
					$obj->load($lookupObject->get('rightID'));
					array_push($values, $obj->get($this->relatedClassRightDisplayField));
				}
				// sort array, then return as comma-seperated string
				asort($values);
				return implode(',', $values);
			}
			// load objects on the left from accessing on the right
			if($accessingClassName == $this->relatedClassRight) {
				AlphaDAO::loadClassDef($this->relatedClassLeft);
				$obj = new $this->relatedClassLeft;
				
				$lookupObjects = $this->lookup->loadAllByAttribute('rightID', $this->value);
			
				$values = array();
				foreach($lookupObjects as $lookupObject) {
					$obj->load($lookupObject->get('leftID'));
					array_push($values, $obj->get($this->relatedClassLeftDisplayField));
				}
				// sort array, then return as comma-seperated string
				asort($values);
				return implode(',', $values);
			}
		}else{
			AlphaDAO::loadClassDef($this->relatedClass);
			$obj = new $this->relatedClass;
			// making sure we have an object to load
			if(empty($this->value) || $this->value == '00000000000') {
				return '';
			}else{
				$obj->load($this->value);		
				return $obj->get($this->relatedClassDisplayField);
			}
		}
	}
	
	/**
	 * For one-to-many and many-to-many relations, get the objects on the other side
	 * 
	 * string $accessingClassName Used to indicate the reading side when accessing from MANY-TO-MANY relation (leave blank for other relation types)
	 * @return array
	 */
	public function getRelatedObjects($accessingClassName='') {
		global $config;
		
		if($this->relationType == 'ONE-TO-MANY') {
			try {
				AlphaDAO::loadClassDef($this->relatedClass);
			}catch(IllegalArguementException $e) {		
				throw new AlphaException('Could not load the definition for the BO class ['.$this->relatedClass.']');
			}
			
			$obj = new $this->relatedClass;
			if($this->relatedClass == 'tag_object') {
				$objects = $obj->loadTags($this->taggedClass, $this->getValue());
			}else{
				$objects = $obj->loadAllByAttribute($this->getRelatedClassField(), $this->getValue());
			}
			
			return $objects;
		}else{ // MANY-TO-MANY
			if(empty($this->lookup)) {
				throw new AlphaException('Tried to load related MANY-TO-MANY objects but no RelationLookup set on the Relation object!');
				return '';
			}
			
			if(empty($accessingClassName)) {
				throw new IllegalArguementException('Tried to load related MANY-TO-MANY objects but no accessingClassName parameter set on the call to getRelatedObjects!');
				return '';
			}
			
			$objects = array();
			
			// load objects on the right from accessing on the left			
			if($accessingClassName == $this->relatedClassLeft) {
				AlphaDAO::loadClassDef($this->relatedClassRight);
				
				$lookupObjects = $this->lookup->loadAllByAttribute('leftID', $this->value);
				
				foreach($lookupObjects as $lookupObject) {
					$obj = new $this->relatedClassRight;
					$obj->load($lookupObject->get('rightID'));
					array_push($objects, $obj);
				}
			}
			// load objects on the left from accessing on the right
			if($accessingClassName == $this->relatedClassRight) {
				AlphaDAO::loadClassDef($this->relatedClassLeft);
				
				$lookupObjects = $this->lookup->loadAllByAttribute('rightID', $this->value);

				foreach($lookupObjects as $lookupObject) {
					$obj = new $this->relatedClassLeft;
					$obj->load($lookupObject->get('leftID'));
					array_push($objects, $obj);
				}				
			}
			
			return $objects;
		}
	}
	
	/**
	 * For one-to-one relations, get the object on the other side
	 * 
	 * @return array
	 */
	public function getRelatedObject() {
		global $config;
		
		try {
			AlphaDAO::loadClassDef($this->relatedClass);
		}catch(IllegalArguementException $e) {		
			throw new AlphaException('Could not load the definition for the BO class ['.$this->relatedClass.']');
		}
		
		$obj = new $this->relatedClass;
		$obj->loadByAttribute($this->getRelatedClassField(), $this->getValue());		
		
		return $obj;
	}
	
	/**
	 * Get the allowable size of the Relation in the database field
	 *	
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}
	
	/**
	 * Get the lookup object if available (only on MANY-TO-MANY relations, null otherwise)
	 * 
	 * @return RelationLookup
	 */
	public function getLookup() {
		return $this->lookup;
	}
	
	/**
	 * Gets the side ('left' or 'right') of the passed classname on the current Relation object
	 *
	 * @param string $BOClassname
	 * @return string
	 */
	public function getSide($BOClassname) {		
		if($BOClassname == $this->relatedClassLeft) { 
			return 'left';
		}elseif($BOClassname == $this->relatedClassRight) {
			return 'right';
		}else{
			throw new IllegalArguementException('Error trying to determine the MANY-TO-MANY relationship side for the classname ['.$BOClassname.']');
			return '';
		}
	}
	
	/**
	 * Set the taggedClass property to the name of the tagged class when building relations
	 * to the tag_object BO.
	 * 
	 * @param $taggedClass
	 */
	public function setTaggedClass($taggedClass) {
		$this->taggedClass = $taggedClass;
	}
}

?>