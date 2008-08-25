<?php

require_once $config->get('sysRoot').'alpha/model/types/AlphaTypeInterface.inc';

/**
 * The DEnum (Dynamic Enum) complex data type.  Similiar to Enum, 
 * except list items are stored in a database table and are editable.
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class DEnum extends mysqlDAO implements AlphaTypeInterface{
	/**
	 * An array of valid DEnum options
	 * 
	 * @var array
	 */
	protected $options;
	
	/**
	 * The currently selected DEnum option
	 * 
	 * @var int
	 */
	protected $value;
	
	/**
	 * The name of the DEnum used in the database
	 * 
	 * @var String
	 */
	protected $name;	
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 */
	const TABLE_NAME = 'DEnum';
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 */
	protected $dataLabels = array(
		"OID"=>"DEnum ID#",
		"name"=>"Name"		
	);
	
	/**
	 * The message to display to the user when validation fails
	 *
	 * @var string
	 */
	protected $helper = 'Error: not a valid denum option!';
	
	/**
	 * Constructor that sets up the DEnum options
	 * 
	 * @param String $name
	 */
	public function __construct($name=null) {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->markTransient('options');
		$this->markTransient('value');
		$this->markTransient('helper');
		
		$this->name = new String($name);
		
		if(isset($name)) {
			$this->loadByAttribute('name',$name);
			$this->getOptions();
		}
	}
	
	/**
	 * Setter for the name of the DEnum used in the database
	 * 
	 * @param String $name
	 */
	public function setName($name) {		
		$this->name->setValue($name);	
	}
	
	/**
	 * Get the array of DEnum options from the database
	 * 
	 * @param bool $alphaSort
	 * @return array
	 */
	public function getOptions($alphaSort = false) {
		
		$sql_query = 'SELECT * FROM '.self::TABLE_NAME.' WHERE name = \''.$this->name->getValue().'\';';

		$this->lastQuery = $sql_query;
		
		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			throw new AlphaException('Failed to load DEnum '.$this->name->getValue().', not found in database.');
		}
		
		// now build an array of item indexes to be returned		
		$row = mysql_fetch_assoc($result);
		$count = 0;
		$this->options = array();
		
		$tmp = new DEnumItem();		
		
		foreach($tmp->loadItems($row["OID"]) as $DEnumItem) {			
			$this->options[$DEnumItem->getID()] = $DEnumItem->getValue();
			$count++;			
		}
		
		if($alphaSort)
			asort($this->options, SORT_STRING);
		return $this->options;
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
	 * Getter for the name
	 * 
	 * @return String
	 */	
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Used to get the current DEnum item selected index value
	 * 
	 * @return int
	 */
	public function getValue() {		
		return $this->value;		
	}
	
	/**
	 * Used to get the current DEnum item string value
	 * 
	 * @return string
	 */
	public function getDisplayValue() {
		// check to see if the options have already been loaded from the DB
		if(empty($this->options))
			$this->getOptions();		
		
		if(isset($this->options[$this->value]))
			return $this->options[$this->value];
		else
			return 'Unknown';		
	}
	
	/**
	 * Used to select the current DEnum item
	 * 
	 * @param string $item 
	 */
	public function setValue($item) {
		// check to see if the options have already been loaded from the DB
		if(empty($this->options))
			$this->getOptions();
		
		// confirm that the item ID provided is a valid key for the options array
		if (in_array($item, array_keys($this->options))) {
			$this->value = $item;			
		}else{
			throw new AlphaException($this->getHelper());			
		}
	}
	
	/**
	 * Gets the count from the database of the DEnumItems associated with this object
	 * 
	 * @return integer
	 */
	public function getItemCount() {		
		
		$sql_query = 'SELECT COUNT(OID) AS item_count FROM DEnumItem WHERE DEnumID = \''.$this->get_ID().'\';';

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {				
			return $row["item_count"];
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			throw new AlphaException('Failed to get the item count for the DEnum.');			
		}
	}

	/**
	 * Used to get the DenumItem ID for the given option name
	 * 
	 * @param string $optionName
	 */
	public function getOptionID($optionName) {
		$denumItem = new DEnumItem();
		$denumItem->loadByAttribute('value', $optionName);
		$id = $denumItem->get_ID();
		
		if(!empty($id))
			return $id;
		else
			return 0;
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