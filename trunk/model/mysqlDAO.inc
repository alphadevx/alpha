<?php

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedSaveException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/LockingException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ValidationException.inc';
require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';
require_once $config->get('sysRoot').'alpha/model/types/Relation.inc';

/**
 * Base business object class definition
 * 
 * @package Alpha Core
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 *  
 */
class mysqlDAO {
	/**
	 * The object ID
	 * 
	 * @var integer
	 */
	protected $OID;
	
	/**
	 * The last database query run by this object.  Useful for tracing an error.
	 * 
	 * @var string
	 */
	protected $lastQuery;
	
	/**
	 * The version number of the object, used for locking mechanism
	 * 
	 * @var Integer
	 */
	protected $version_num;
	
	/**
	 * The timestamp of creation
	 * 
	 * @var Timestamp
	 */
	protected $created_ts;
	
	/**
	 * The OID of the person who created this BO
	 * 
	 * @var Integer 
	 */
	protected $created_by;
	
	/**
	 * The timestamp of the last update
	 * 
	 * @var Timestamp
	 */
	protected $updated_ts;
	
	/**
	 * The OID of the person who last updated this BO
	 * 
	 * @var Integer 
	 */
	protected $updated_by;
	
	/**
	 * An array of the names of all of the default attributes of a persistent BO defined in this class
	 * 
	 * @var array
	 */
	protected $defaultAttributes = array("OID","lastQuery","version_num","dataLabels","render_mode","created_ts","created_by","updated_ts","updated_by","defaultAttributes","transientAttributes","uniqueAttributes","TABLE_NAME");
	
	/**
	 * An array of the names of all of the transient attributes of a persistent BO which are not saved to the DB
	 * 
	 * @var array
	 */
	protected $transientAttributes = array("lastQuery","dataLabels","render_mode","defaultAttributes","transientAttributes","uniqueAttributes","TABLE_NAME");
	
	/**
	 * An array of the uniquely-constained attributes of this persistent BO
	 * 
	 * @var array
	 */
	protected $uniqueAttributes = array();
	
	/**
	 * An array of the data labels used for displaying class attributes
	 * 
	 * @var array
	 */
	protected $dataLabels = array();
	
	/**
	 * The constructor which sets up some housekeeping attributes
	 */
	public function __construct() {
		$this->version_num = new Integer(0);
		$this->created_ts = new Timestamp();
		$person_ID = (isset($_SESSION["current_user"])? $_SESSION["current_user"]->get_ID(): 0);
		$this->created_by = new Integer($person_ID);
		$this->updated_ts = new Timestamp();
		$this->updated_by = new Integer($person_ID);
	}
	
	/**
	 * Populates the child object with the properties retrived from the database for the object $OID
	 * 
	 * @param integer $OID	
	 */
	public function load($OID) {
		if(method_exists($this, 'before_load_callback'))
			$this->before_load_callback();
		
		$this->OID = $OID;
		$sqlQuery = "SELECT * FROM ".$this->getTableName()." WHERE OID = '$OID';";		

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);
		
		if(mysql_num_rows($result) == 0) {
			throw new BONotFoundException('Failed to load object of OID ['.$OID.'] not found in database.');			
		}
		
		$row = mysql_fetch_assoc($result);
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			// filter transient attributes
			if(!in_array($propObj->name, $this->transientAttributes)) {
				$this->set($propObj->name, $row[$propObj->name]);
			}
		}
		
		if(mysql_error() == '' && $this->set_enum_options()) {	
			if(method_exists($this, 'after_load_callback'))
				$this->after_load_callback();			
		}else{			
			throw new BONotFoundException('Failed to load object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
		}		
	}
	
	/**
	 * Populates the child object from the database table by the given attribute value
	 * 
	 * @param string $atribute
	 * @param string $value	 
	 */
	public function loadByAttribute($attribute, $value)	{
		if(method_exists($this, 'before_loadByAttribute_callback'))
				$this->before_loadByAttribute_callback();
		
		$sqlQuery = "SELECT * FROM ".$this->getTableName()." WHERE $attribute = '$value';";

		$this->lastQuery = $sqlQuery;
		
		$result = mysql_query($sqlQuery);
		
		if(mysql_num_rows($result) == 0) {
			throw new BONotFoundException('Failed to load object where attribute ['.$attribute.'] equals ['.$value.'].');			
		}
		
		$row = mysql_fetch_assoc($result);
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			// filter transient attributes
			if(!in_array($propObj->name, $this->transientAttributes)) {
				$this->set($propObj->name, $row[$propObj->name]);
			}
		}
		
		if(mysql_error() == '' && $this->set_enum_options()) {
			if(method_exists($this, 'after_loadByAttribute_callback'))
				$this->after_loadByAttribute_callback();			
		}else{			
			throw new BONotFoundException('Failed to load object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
		}		
	}
	
	/**
	 * Loads all of the objects of this class into an array which is returned
	 * 
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $orderBy the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is ASC
	 * @return array an array containing objects of this type of business object.
	 */
	public function loadAll($start=0, $limit=0, $orderBy="OID", $order="ASC") {
		global $config;
		
		if(method_exists($this, 'before_loadAll_callback'))
			$this->before_loadAll_callback();
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		$sqlQuery = "SELECT OID FROM ".$this->getTableName()." ORDER BY ".$orderBy." ".$order." LIMIT ".$start.", ".$limit.";";
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);
		
		if(mysql_error() != '') {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load($row["OID"]);
			$objects[$count] = $obj;
			$count++;			
		}
		
		if(method_exists($this, 'after_loadAll_callback'))
			$this->after_loadAll_callback();
		
		return $objects;	
	}
	
	/**
	 * Loads all of the objects of this class by the specified attribute into an array which is returned
	 * 
	 * @param string $atribute The attribute to load the object by
	 * @param string $value The value of the attribute to load the object by
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $orderBy the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is ASC
	 * @return array an array containing objects of this type of business object.
	 */
	function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC") {
		global $config;
		
		if(method_exists($this, 'before_loadAllByAttribute_callback'))
			$this->before_loadAllByAttribute_callback();
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE $attribute = '$value' ORDER BY ".$orderBy." ".$order." LIMIT ".$start.", ".$limit.";";
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);
		
		if(mysql_error() != '') {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load($row["OID"]);
			$objects[$count] = $obj;
			$count++;
		}
		
		if(method_exists($this, 'after_loadAllByAttribute_callback'))
			$this->after_loadAllByAttribute_callback();
		
		return $objects;	
	}

	/**
	 * Saves the object.  If $this->OID is empty or null it will INSERT, otherwise UPDATE 	
	 */
	public function save() {
		if(method_exists($this, 'before_save_callback'))
			$this->before_save_callback();
		
		// firstly we will validate the object before we try to save it
		if(!$this->validate()) {
			throw new FailedSaveException('Could not save due to a validation error.');
		}else{		
			// get the class attributes
			$reflection = new ReflectionClass(get_class($this));
			$properties = $reflection->getProperties();
			$sqlQuery = "";		
			
			if($this->getVersion() != $this->version_num->getValue()){
				throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
			}
			
			// set the "updated by" fields, we can only set the user id if someone is logged in
			if(isset($_SESSION["current_user"]))
				$this->updated_by->setValue($_SESSION["current_user"]->get_ID());
			
			$this->updated_ts = new Timestamp();
			
			// check to see if it is a transient object that needs to be inserted
			if(empty($this->OID) || !isset($this->OID) || $this->OID == '00000000000') {
				$sqlQuery = "INSERT INTO ".$this->getTableName()." (";
	
				foreach($properties as $propObj) {
					$propName = $propObj->name;				
					if (!in_array($propName, $this->transientAttributes)) {
						$sqlQuery .= "$propName,";
					}				
				}
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= ") VALUES (";
				
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					$propClass = get_class($this->get($propName));				
					if ($propName != "version_num" && !in_array($propName, $this->transientAttributes)) {
						if(empty($propClass))
							$sqlQuery .= "'".$this->get($propName)."',";
						else
							$sqlQuery .= "'".$this->get($propName)->getValue()."',";
					}
					if ($propName == "version_num") {					
						$temp = $this->version_num->getValue();
						$this->version_num->setValue($temp+1);
						$sqlQuery .= "'".$this->version_num->getValue()."',";
					}
				}
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= ");";
			}else{
				// assume that it is a persistent object that needs to be updated
				$sqlQuery = "UPDATE ".$this->getTableName()." SET ";
				
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					$propClass = get_class($this->get($propName));
					if ($propName != "version_num" && !in_array($propName, $this->transientAttributes)) {
						if(empty($propClass))
							$sqlQuery .= "$propName = '".$this->get($propName)."',";
						else
							$sqlQuery .= "$propName = '".$this->get($propName)->getValue()."',";
					}
					if ($propName == "version_num") {					
						$temp = $this->version_num->getValue();					
						$this->version_num->setValue($temp+1);					
						$sqlQuery .= "version_num = '".$this->version_num->getValue()."',";
					}
				}
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= " WHERE OID=".$this->OID.";";
			}
	
			$this->lastQuery = $sqlQuery;
			
			$result = mysql_query($sqlQuery);
	
			if (mysql_error() == '') {
				if(method_exists($this, 'after_save_callback'))
					$this->after_save_callback();				
			}else{
				// there has been an error, so decrement the version number back
				$temp = $this->version_num->getValue();					
				$this->version_num->setValue($temp-1);	
				
				// check for unique violations			
				if(mysql_errno() == '1062') {					
					throw new ValidationException('Failed to save, the value '.$this->findOffendingValue(mysql_error()).' is already in use!');
				}else{					
					throw new FailedSaveException('Failed to save object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');			
				}
			}
		}
	}
	
	/**
	 * Validates the object to be saved
	 * 
	 * @return boolean
	 */
	protected function validate() {
		if(method_exists($this, 'before_validate_callback'))
			$this->before_validate_callback();
		
		$valid = true;
				
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$propClass = $propObj->class;
			if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				if (strtoupper($propClass) != "ENUM" &&
				strtoupper($propClass) != "DENUM" &&
				strtoupper($propClass) != "DENUMITEM" && 
				strtoupper($propClass) != "BOOLEAN") {
					if (!preg_match($this->get($propName)->getRule(), $this->get($propName)->getValue())) {
						throw new ValidationException('Failed to save, validation error is: '.$this->get($propName)->getHelper());
						$valid = false;
					}
				}
			}
		}
		
		if(method_exists($this, 'after_validate_callback'))
			$this->after_validate_callback();
		
		return $valid;
	}
	
	/**
	 * Delete the current object from the database
	 */
	protected function delete() {		
		if(method_exists($this, 'before_delete_callback'))
				$this->before_delete_callback();
		
		if(!empty($this->OID))
			$sqlQuery = "DELETE FROM ".$this->getTableName()." WHERE OID = '".$this->OID."';";		
		else
			throw new FailedDeleteException('Cannot delete transient business object!');
			
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		
		
		if(mysql_error() == '') {
			if(method_exists($this, 'after_delete_callback'))
				$this->after_delete_callback();			
		}else{			
			throw new FailedDeleteException('Failed to delete object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
		}
	}
	
	/**
	 * Gets the version_num of the object from the database
	 * 
	 * @return integer
	 */
	protected function getVersion() {
		if(method_exists($this, 'before_getVersion_callback'))
			$this->before_getVersion_callback();
		
		$sqlQuery = 'SELECT version_num FROM '.$this->getTableName().' WHERE OID = \''.$this->OID.'\';';
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);

		$row = mysql_fetch_assoc($result);
		
		if (isset($row['version_num']))
			$version_num = $row['version_num'];
		else
			$version_num = 0;
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_getVersion_callback'))
				$this->after_getVersion_callback();
			
			return $version_num;
		}else{			
			throw new AlphaException('Failed to get version number for object ['.$this->OID.'], MySql error is ['.mysql_error().']');
			return 0;
		}
	}

	/**
	 * Builds a new database table for the BO class
	 */	
	protected function makeTable()
	{
		if(method_exists($this, 'before_makeTable_callback'))
			$this->before_makeTable_callback();
		
		$sqlQuery = "CREATE TABLE ".$this->getTableName()." (OID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT,";
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$propClass = $propObj->class;
			
			if(!in_array($propName, $this->transientAttributes) && $propName != "OID") {				

				switch (strtoupper($propClass)) {
					case "INTEGER":
						$sqlQuery .= "$propName INT(".$this->get($propName)->getSize()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$propName DOUBLE(".$this->get($propName)->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$propName VARCHAR(".$this->get($propName)->getSize()."),";
					break;
					case "TEXT":
						$sqlQuery .= "$propName TEXT,";
					break;
					case "BOOLEAN":
						$sqlQuery .= "$propName CHAR(1) DEFAULT '0',";
					break;
					case "DATE":
						$sqlQuery .= "$propName DATE,";
					break;
					case "TIMESTAMP":
						$sqlQuery .= "$propName DATETIME,";
					break;
					case "ENUM":
						$sqlQuery .= "$propName ENUM(";
						$enumVals = $this->get($propName)->getOptions();
						foreach($enumVals as $val) {
							$sqlQuery .= "'".$val."',";
						}
						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this).'::'.$propName);						
						$tmp->save();
						$sqlQuery .= "$propName INT(11) ZEROFILL,";
					break;
					default:
						$sqlQuery .= "";
					break;
				}
			}			
		}

		$sqlQuery .= "PRIMARY KEY (OID)) TYPE=InnoDB;";
		
		$this->lastQuery = $sqlQuery;
		
		$result = mysql_query($sqlQuery);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_makeTable_callback'))
				$this->after_makeTable_callback();
		}else{
			throw new AlphaException('Failed to create the table ['.$this->getTableName().'] for the class ['.get_class($this).'], query is ['.$this->lastQuery.']');
		}
	}

	/**
	 * Re-builds the table if the model requirements have changed.  All data is lost!
	 */	
	protected function rebuildTable()
	{
		if(method_exists($this, 'before_rebuildTable_callback'))
			$this->before_rebuildTable_callback();
		
		$sqlQuery = "DROP TABLE IF EXISTS ".$this->getTableName().";";

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		if (mysql_error() == '') {
			$this->makeTable();
			if(method_exists($this, 'after_rebuildTable_callback'))
				$this->after_rebuildTable_callback();			
		}else{			
			throw new AlphaException('Failed to drop the table ['.$this->getTableName().'] for the class ['.get_class($this).'], query is ['.$this->lastQuery.']');
		}		
	}

	/**
	 * adds in a new class property without loosing existing data
	 * @param string $prop the name of the property to add
	 * @return bool True if the table attribute was added, false otherwise
	 */
	function add_property($prop)
	{
		if(method_exists($this, 'before_add_property_callback'))
			$this->before_add_property_callback();
		
		$sqlQuery = "ALTER TABLE ".$this->getTableName()." ADD ";

		if(!in_array($prop, $this->defaultAttributes)) {
			
			$obj_type = get_class($this->$prop);

			switch (strtoupper($obj_type)) {
					case "INTEGER":
						$sqlQuery .= "$prop INT(".$this->$prop->get_size()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$prop DOUBLE(".$this->$prop->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$prop VARCHAR(".$this->$prop->get_size()."),";
					break;
					case "TEXT":
						$sqlQuery .= "$prop TEXT,";
					break;
					case "BOOLEAN":
						$sqlQuery .= "$prop CHAR(1) DEFAULT '0',";
					break;
					case "DATE":
						$sqlQuery .= "$prop DATE,";
					break;
					case "TIMESTAMP":
						$sqlQuery .= "$prop DATETIME,";
					break;
					case "ENUM":
						$sqlQuery .= "$prop ENUM(";
						$enum_vals = $this->$prop->get_options();
						foreach($enum_vals as $enum_val) {
							$sqlQuery .= "'".$enum_val."',";
						}
						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this).'::'.$prop);						
						$tmp->save();
						$sqlQuery .= "$prop INT(11) ZEROFILL,";
					break;
					default:
						$sqlQuery .= "";
					break;
				}
		}
		
		$sqlQuery = rtrim($sqlQuery, ",");

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_add_property_callback'))
				$this->after_add_property_callback();		
			return true;
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to add the new attribute ['.$prop.'] to the table '.$this->getTableName().', database error: '.$this->lastQuery, 'add_property()', 'framework');
			return false;
		}
	}

	/**
	 * populates the object from global POST data
	 * @requires POST data from a web form
	 */	
	function populate_from_post() {
		if(method_exists($this, 'before_populate_from_post_callback'))
			$this->before_populate_from_post_callback();
		
		$properties = get_class_vars(get_class($this));
		
		foreach(array_keys($properties) as $prop) {
			if(isset($_POST[$prop])) {
				if(!in_array($prop, $this->defaultAttributes) && !in_array($prop, $this->transientAttributes)) {
					// checking to see if we need to use addslashes() on the posted data
					if(!get_magic_quotes_gpc()) {
						if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
							$this->$prop->setValue(addslashes($_POST[$prop]));					
						}else{						
							$this->$prop->populateFromString(addslashes($_POST[$prop]));
						}
					}else{
						if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
							$this->$prop->setValue($_POST[$prop]);					
						}else{						
							$this->$prop->populateFromString($_POST[$prop]);
						}
					}
				}
				if ($prop == "version_num" && isset($_POST["version_num"]))
					$this->version_num->setValue($_POST["version_num"]);
			}
		}
		if(method_exists($this, 'after_populate_from_post_callback'))
			$this->after_populate_from_post_callback();	
	}

	/**
	 * gets the maximum ID value from the database for this class
	 * @return integer The MAX ID
	 */
	function get_MAX() {
		if(method_exists($this, 'before_get_MAX_callback'))
			$this->before_get_MAX_callback();
		
		$sqlQuery = "SELECT MAX(OID) AS max_ID FROM ".$this->getTableName();

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_get_MAX_callback'))
				$this->after_get_MAX_callback();
					
			return $row["max_ID"];
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get the MAX ID for the class','get_MAX()','framework');
			return 0;
		}
	}
	
	/**
	 * gets the count from the database for the amount of objects of this class
	 * @return integer The count
	 */
	function get_count() {
		if(method_exists($this, 'before_get_count_callback'))
			$this->before_get_count_callback();
		
		$sqlQuery = "SELECT COUNT(OID) AS class_count FROM ".$this->getTableName();

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_get_count_callback'))
				$this->after_get_count_callback();
				
			return $row["class_count"];
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get the count for the class','get_count()','framework');
			return 0;
		}
	}

	/**
	 * gets the OID for the object in zero-padded format
	 * @return integer The OID of the object
	 */
	function get_ID() {
		return str_pad($this->OID, 11, "0", STR_PAD_LEFT);
	}
	
	/**
	 * populate all of the enum options for this object from the database
	 * @return bool true on success, false otherwise
	 */
	function set_enum_options() {
		if(method_exists($this, 'before_set_enum_options_callback'))
			$this->before_set_enum_options_callback();
		
		$properties = get_object_vars($this);		
		$obj_type = '';

		foreach(array_keys($properties) as $prop) {
			if(!in_array($prop, $this->defaultAttributes)) {

				$obj_type = get_class($properties[$prop]);				

				if ($obj_type == 'Enum') {
					$sqlQuery = "SHOW COLUMNS FROM ".$this->getTableName()." LIKE '".$prop."'";
					
					$this->lastQuery = $sqlQuery;
					
					$result = mysql_query($sqlQuery);
							
					$row = mysql_fetch_row($result);
					$options = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2",$row[1]));
					
					$properties[$prop]->setOptions($options);
				}
			}
		}
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_set_enum_options_callback'))
				$this->after_set_enum_options_callback();
				
			return true;
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load enum options correctly','set_enum_options()','framework');
			return false;
		}
	}
	
	/**
	 * Generic getter method for accessing class properties.  Will use the method get_$prop instead if that
	 * method exists at a child level (by default)
	 * 
	 * @param string $prop the name of the property to get
	 * @param boolean $no_child_methods true if you don't want to use any get_$prop method even if it exists, false otherwise (default)
	 * @return <variable> the value of the property
	 */
	function get($prop, $no_child_methods = false) {
		if(method_exists($this, 'before_get_callback'))
			$this->before_get_callback();
		
		// handle private and protected attributes (should always have a getter)
		if(!$no_child_methods && method_exists($this, "get".ucfirst($prop))) {
			if(method_exists($this, 'after_get_callback'))
				$this->after_get_callback();
				
			return eval('return $this->get'.ucfirst($prop).'();');
		}else{
			// handle public attributes
			if(isset($this->$prop) && method_exists($this->$prop, "getValue")) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();
				
				return $this->$prop->getValue();
			}elseif(isset($this->$prop)) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();
					
				return $this->$prop;
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"],'Could not access the property '.$prop.' on the object '.get_class($this).'.','get()','framework');
				return false;
			}
		}
	}
	
	/**
	 * Generic setter method for setting class properties.  Will use the method set_$prop instead if that
	 * method exists at a child level (by default)
	 * 
	 * @param string $prop the name of the property to set
	 * @param string $value value to attribute to the object property
	 * @param boolean $no_child_methods true if you don't want to use any get_$prop method even if it exists, false otherwise (default)
	 */
	function set($prop, $value, $no_child_methods = false) {
		if(method_exists($this, 'before_set_callback'))
			$this->before_set_callback();
		
		// handle private and protected attributes (should always have a setter)
		if(!$no_child_methods && method_exists($this, "set".ucfirst($prop))) {
			if(method_exists($this, 'after_set_callback'))
				$this->after_set_callback();
			
			return eval("return \$this->set".ucfirst($prop)."('$value');");
		}else{
			// handle public attributes
			if(isset($this->$prop)) {
				if(method_exists($this, 'after_set_callback'))
					$this->after_set_callback();
					
				if (get_class($this->$prop) != false) {				
					if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {					
						$this->$prop->setValue($value);
					}else{
						$this->$prop->populateFromString($value);
					}
				}else{
					$this->$prop = $value;
				}				
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"],'Could not set the property '.$prop.' on the object '.get_class($this).'.  Property may not exist, or else does not have a setValue() method.','set()','framework');
			}
		}
	}
	
	/**
	 * gets the property object rather than the value
	 * @param string $prop the name of the property to get
	 * @return <object> the property object
	 */
	function get_prop_object($prop) {
		if(method_exists($this, 'before_get_prop_object_callback'))
			$this->before_get_prop_object_callback();
		
		if(isset($this->$prop) || method_exists($this, 'get'.ucfirst($prop))) {
			if(method_exists($this, 'after_get_prop_object_callback'))
				$this->after_get_prop_object_callback();
			
			if(method_exists($this, 'get'.ucfirst($prop))) {
				eval('$obj = $this->get'.ucfirst($prop).'();');
				
				return $obj;
			}else{
				return $this->$prop;
			}
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not access the property '.$prop.' on the object '.get_class($this).'.','get_prop_object()','framework');
			return false;
		}
	}
	
	/**
	 * checks to see if the table exists in the database for the current business class
	 * @return boolean true if the table exists, false otherwise 
	 */
	function check_table_exists() {
		if(method_exists($this, 'before_check_table_exists_callback'))
			$this->before_check_table_exists_callback();
		
		global $config;
		
		$table_exists = false;
		
		$result = mysql_list_tables($config->get('sysDB'));
		
		while ($row = mysql_fetch_row($result)) {    		
    		if ($row[0] == $this->getTableName())
    			$table_exists = true;
		}		
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_check_table_exists_callback'))
				$this->after_check_table_exists_callback();
			
			return $table_exists;
		}else{			
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_table_exists()','framework');
			return false;
		}
	}
	
	/**
	 * checks to see if the table in the database matches (for fields) the business class definition
	 * @return boolean true if the table needs updating, false otherwise 
	 */
	function check_table_needs_update() {
		if(method_exists($this, 'before_check_table_needs_update_callback'))
			$this->before_check_table_needs_update_callback();
		
		$table_exists = $this->check_table_exists();
		
		if (!$table_exists) {
			return true;
		}else{
			$update_required = false;
			
			$match_count = 0;
			
			$result = mysql_query("SHOW COLUMNS FROM ".$this->getTableName());
			
			$properties = get_class_vars(get_class($this));			
			
			foreach(array_keys($properties) as $prop) {
				if (!in_array($prop, $this->transientAttributes)) {
				
					$found_match = false;	
					
					while ($row = mysql_fetch_assoc($result)) {						
			    		if ($prop == $row["Field"]) {
			    			$found_match = true;
			    			break;
			    		}
					}
					
					if(!$found_match) {						
						$match_count--;						
					}
					mysql_data_seek($result, 0);					
				}
			}		
			
			if ($match_count != 0)
				$update_required = true;
			
			if (mysql_error() == '') {
				if(method_exists($this, 'before_check_table_needs_update_callback'))
					$this->before_check_table_needs_update_callback();
				
				// check the table indexes
				$this->checkIndexes();
				
				return $update_required;
			}else{			
				$this->lastQuery .= " Error: ".mysql_error();
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_table_needs_update()','framework');
				return false;
			}
		}
	}
	
	/**
	 * returns an array containing any properties on the class which have not been created on the database table yet
	 * @return array an array of class properties (may be empty)
	 */
	function find_missing_fields() {
		if(method_exists($this, 'before_find_missing_fields_callback'))
			$this->before_find_missing_fields_callback();
		
		$missing_fields = array();
		$found_fields = array();
			
		$match_count = 0;
			
		$result = mysql_query("SHOW COLUMNS FROM ".$this->getTableName());
			
		$properties = get_class_vars(get_class($this));		
			
		foreach(array_keys($properties) as $prop) {			
			if (!in_array($prop, $this->transientAttributes)) {
				while ($row = mysql_fetch_assoc($result)) {				
		    		if ($prop == $row["Field"]) {		    			
		    			$match_count++;		    			
		    			break;
		    		}	    		
				}				
				mysql_data_seek($result, 0);					
			}else{
				$match_count++;
			}
			
			if($match_count==0) {					
				array_push($missing_fields, $prop);
			}else{
				$match_count = 0;
			}
		}
		
		if (mysql_error() != '') {
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_table_needs_update()','framework');
		}
		
		if(method_exists($this, 'after_find_missing_fields_callback'))
			$this->after_find_missing_fields_callback();
		
		return $missing_fields;	
	}
	
	/**
	 * Getter for the TABLE_NAME, which should be set by a child of this class
	 * 
	 * @return string
	 */
	function getTableName() {
		eval('$TABLE_NAME = '.get_class($this).'::TABLE_NAME;');
		
		if(!empty($TABLE_NAME)) {
    		return $TABLE_NAME;        
    	}else{
    		throw new AlphaFrameworkException('Error: no TABLE_NAME constant set for the class '.get_class($this));		
    	}
	}
	
	/**
	 * method for getting the OID of the person who created this BO
	 * @return Integer
	 */
	function get_creator_ID() {
		return $this->created_by;
	}
	
	/**
	 * method for getting the OID of the person who updated this BO
	 * @return Integer
	 */
	function get_updator_ID() {
		return $this->updated_by;
	}
	
	/**
	 * method for getting the date/time of when the BO was created
	 * @return Timestamp
	 */
	function get_create_TS() {
		return $this->created_ts;
	}
	
	/**
	 * method for getting the date/time of when the BO was last updated
	 * @return Timestamp
	 */
	function get_update_TS() {
		return $this->updated_ts;
	}
	
	/**
	 * adds the name of the atribute provided to the list of transient (non-saved) attributes for this BO
	 * @param string attribute_name the name of the attribute to mark as transient
	 */
	function markTransient($attribute_name) {
		array_push($this->transientAttributes, $attribute_name);		
	}
	
	/**
	 * adds the name of the atribute provided to the list of unique (constrained) attributes for this BO
	 * @param string attribute_name the name of the attribute to mark as unique
	 */
	function mark_unique($attribute_name) {
		array_push($this->uniqueAttributes, $attribute_name);		
	}
	
	/**
	 * checks to see if all of the idexes are in place for the BO's table, creates those that are missing
	 */
	function checkIndexes() {
		if(method_exists($this, 'before_check_indexes_callback'))
			$this->before_check_indexes_callback();
		
		$result = mysql_query("SHOW INDEX FROM ".$this->getTableName());
		
		// process unique keys
		foreach($this->uniqueAttributes as $prop) {
			$index_exists = false;
			while ($row = mysql_fetch_assoc($result)) {					
				if ($prop.'_unq_idx' == $row["Key_name"]) {
			    	$index_exists = true;
				}
			}

			if(!$index_exists)
				$this->createUniqueIndex($prop);
		}
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_check_indexes_callback'))
				$this->after_check_indexes_callback();
		}else{
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_indexes()','framework');	
		}
	}
	
	/**
	 * creates a unique index in the database on the given property
	 */
	function createUniqueIndex($prop) {
		if(method_exists($this, 'before_create_unique_index_callback'))
			$this->before_create_unique_index_callback();
		
		$sqlQuery = 'CREATE UNIQUE INDEX '.$prop.'_unq_idx ON '.$this->getTableName().' ('.$prop.');';
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_create_unique_index_callback'))
				$this->after_create_unique_index_callback();
		}else{
			$this->lastQuery .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to create the index '.$prop.'_unq_idx on '.$this->getTableName().', query is : '.$this->lastQuery,'createUniqueIndex()','framework');
		}
	}
	
	/**
	 * parses a MySQL error for the value that violated a unique constraint
	 */
	function findOffendingValue($error) {		
		$singleQuote1 = strpos($error,"'");
		$singleQuote2 = strrpos($error,"'");
		
		return substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
	}
	
	/**
	 * gets the data labels array
	 * 
	 * @return array
	 */
	function getDataLabels() {		
		return $this->dataLabels;
	}
	
	/**
	 * Loops over the core and custom BO directories and builds an array of all of the BO class names in the system
	 *
	 * @return array
	 */
	function getBOClassNames() {
		global $config;
		
		$classNameArray = array();
		
		// first get any custom BOs
		$handle = opendir($config->get('sysRoot').'model');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/_object.inc/", $file)) {
	    		$classname = substr($file, 0, -4);	    		
	    		
	    		array_push($classNameArray, $classname);
	    	}
	    }
	    
	    // now loop over the core BOs provided with Alpha
	    
	    $handle = opendir($config->get('sysRoot').'alpha/model');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/_object.inc/", $file)) {
	    		$classname = substr($file, 0, -4);	    		
	    		
	    		array_push($classNameArray, $classname);
	    	}
	    }

	    return $classNameArray;
	}
}

?>