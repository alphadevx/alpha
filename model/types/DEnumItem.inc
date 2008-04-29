<?php

// $Id$

/**
 * The DEnumItem (Dynamic Enum Item) complex data type.  Has a one-to-many
 * relationship with the DEnum type. 
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * 
 */
class DEnumItem extends mysql_DAO {
	/**
	 * The value that will appear in the drop-down.
	 * @var String
	 */
	var $value;
	
	/**
	 * The ID of the parent DEnum object.
	 * @var Integer
	 */
	var $DEnumID;
	
	/**
	 * The name of the database table for the class
	 * @var string
	 */
	var $TABLE_NAME = "DEnumItem";
	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $data_labels = array(
		"OID"=>"DEnumItem ID#",
		"value"=>"Dropdown value"		
	);
	
	/**
	 * The constructor
	 */
	function DEnumItem() {
		// ensure to call the parent constructor
		$this->mysql_DAO();
		$this->value = new String();
		$this->value->set_rule(REQUIRED_TEXT);
		$this->value->set_helper("A blank dropwdown value is not allowed!");
		$this->DEnumID = new Integer();		
	}
	
	/**
	 * Loads all of the items for the given parent DEnum ID.
	 * 
	 * @param integer $EnumID The ID of the parent DEnum object.
	 * @return array An array containing DEnumItem objects related to the parent DEnum.
	 */
	function load_items($EnumID)
	{		
		global $config;
		
		$this->DEnumID->setValue($EnumID);
		
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." WHERE DEnumID = '".$EnumID."';";
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load objects, query: '.$this->last_query.'.' ,'load_items()','framework');
			return false;
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;		
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new DEnumItem();
			$obj->load_object($row["OID"]);
			$objects[$count] = $obj;
			$count++;			
		}		
		
		return $objects;	
	}
	
	/**
	 * used to get the current DEnum item
	 * @return string selected
	 */
	function getValue() {
		return $this->value->getValue();
	}
}