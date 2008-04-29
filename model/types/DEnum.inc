<?php

// $Id$

/**
 * The DEnum (Dynamic Enum) complex data type.  Similiar to Enum, 
 * except list items are stored in a database table and are editable.
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * 
 */
class DEnum extends mysql_DAO {
	/**
	 * an array of valid DEnum options
	 * @var array
	 */
	var $options;
	
	/**
	 * the currently selected DEnum option
	 * @var int
	 */
	var $value;
	
	/**
	 * The name of the DEnum used in the database
	 * @var String
	 */
	var $name;	
	
	/**
	 * the name of the database table for the class
	 * @var string
	 */
	var $TABLE_NAME = "DEnum";
	
	/**
	 * an array of data display labels for the class properties
	 * @var array
	 */
	var $data_labels = array(
		"OID"=>"DEnum ID#",
		"name"=>"Name"		
	);
	
	/**
	 * constructor that sets up the DEnum options
	 * @param String $name The name of the DEnum used in the database
	 */
	function DEnum($name=null) {
		// ensure to call the parent constructor
		$this->mysql_DAO();
		
		$this->mark_transient("options");
		$this->mark_transient("value");
		
		$this->name = new String($name);
		
		if(isset($name))
			$this->get_options();
	}
	
	/**
	 * setter for the name
	 * @param String $name The name of the DEnum used in the database
	 */
	function set_name($name) {		
		$this->name->setValue($name);	
	}
	
	/**
	 * Get the array of DEnum options from the database
	 * @param bool $alphaSort set to true if you want the DEnum options in alphabetical order (default false)
	 * @return array enum options
	 */
	function get_options($alphaSort = false) {
		
		$sql_query = "SELECT * FROM ".$this->TABLE_NAME." WHERE name = '".$this->name->getValue()."';";

		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load DEnum '.$this->name->getValue().', not found in database.' ,'get_options()','framework');
			return false;
		}
		
		// now build an array of item indexes to be returned		
		$row = mysql_fetch_assoc($result);
		$count = 0;
		$this->options = array();
		
		$tmp = new DEnumItem();		
		
		foreach($tmp->load_items($row["OID"]) as $DEnumItem) {			
			$this->options[$DEnumItem->get_ID()] = $DEnumItem->getValue();
			$count++;			
		}
		
		if($alphaSort)
			asort($this->options, SORT_STRING);
		return $this->options;
	}
	
	/**
	 * getter for the validation helper string
	 * @return string validation_helper
	 */	
	function get_helper() {
		return $this->validation_helper;
	}
	
	/**
	 * used to get the current DEnum item selected index value
	 * @return int selected
	 */
	function getValue() {		
		return $this->value;		
	}
	
	/**
	 * used to get the current DEnum item string value
	 * @return string selected
	 */
	function get_display_value() {
		// check to see if the options have already been loaded from the DB
		if(empty($this->options))
			$this->get_options();		
		
		if(isset($this->options[$this->value]))
			return $this->options[$this->value];
		else
			return 'Unknown';		
	}
	
	/**
	 * used to select the current DEnum item
	 * @param string $item
	 * @return bool success status
	 */
	function setValue($item) {
		// check to see if the options have already been loaded from the DB
		if(empty($this->options))
			$this->get_options();
		
		// confirm that the item ID provided is a valid key for the options array
		if (in_array($item, array_keys($this->options))) {
			$this->value = $item;
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * gets the count from the database of the DEnumItems associated with this object
	 * @return integer The count
	 */
	function get_item_count() {		
		
		$sql_query = "SELECT COUNT(OID) AS item_count FROM DEnumItem WHERE DEnumID = '".$this->get_ID()."'";

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {				
			return $row["item_count"];
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get the item count for the DEnum','get_item_count()','framework');
			return 0;
		}
	}

	/**
	 * used to get the DenumItem ID for the given option name
	 */
	function getOptionID($optionName) {
		$denumItem = new DEnumItem();
		$denumItem->load_by_attribute('value', $optionName);
		$id = $denumItem->get_ID();
		
		if(!empty($id))
			return $id;
		else
			return 0;
	}
}

?>