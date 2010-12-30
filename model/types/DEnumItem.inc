<?php

require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The DEnumItem (Dynamic Enum Item) complex data type.  Has a one-to-many
 * relationship with the DEnum type. 
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class DEnumItem extends AlphaDAO implements AlphaTypeInterface{
	/**
	 * The value that will appear in the drop-down.
	 * 
	 * @var String
	 */
	protected $value;
	
	/**
	 * The ID of the parent DEnum object.
	 * 
	 * @var Integer
	 */
	protected $DEnumID;
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'DEnumItem';
	
	/**
	 * an array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"DEnumItem ID#",
		"value"=>"Dropdown value"		
	);
	
	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 */
	protected $helper = 'Error: not a valid DEnumItem value!';
	
	/**
	 * The constructor
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->value = new String();
		$this->value->setRule(REQUIRED_TEXT);
		$this->value->setHelper("A blank dropdown value is not allowed!");
		$this->DEnumID = new Integer();
		$this->markTransient('helper');		
	}
	
	/**
	 * Loads all of the items for the given parent DEnum ID.
	 * 
	 * @param integer $EnumID The ID of the parent DEnum object.
	 * @return array
	 */
	public function loadItems($EnumID)
	{		
		global $config;
		
		$this->DEnumID->setValue($EnumID);
		
		$sqlQuery = "SELECT OID FROM ".self::TABLE_NAME." WHERE DEnumID = '".$EnumID."';";
		
		$this->lastQuery = $sqlQuery;

		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to load objects, query: ['.$this->lastQuery.']');
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;		
		
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$obj = new DEnumItem();
			$obj->load($row['OID']);
			$objects[$count] = $obj;
			$count++;			
		}		
		
		return $objects;	
	}
	
	/**
	 * used to get the current DEnum item
	 * 
	 * @return String
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * used to set the current DEnum item
	 * 
	 * @param string $item
	 */
	public function setValue($item) {
		$this->value->setValue($item);		
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
	 * Used to convert the object to a printable string
	 *
	 * @return string
	 */
	public function __toString() {		
		return strval($this->value);
	}
}