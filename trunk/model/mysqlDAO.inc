<?php

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedSaveException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/LockingException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ValidationException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/MailNotSentException.inc';
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
	protected $defaultAttributes = array("OID","lastQuery","version_num","dataLabels","created_ts","created_by","updated_ts","updated_by","defaultAttributes","transientAttributes","uniqueAttributes","TABLE_NAME");
	
	/**
	 * An array of the names of all of the transient attributes of a persistent BO which are not saved to the DB
	 * 
	 * @var array
	 */
	protected $transientAttributes = array("lastQuery","dataLabels","defaultAttributes","transientAttributes","uniqueAttributes","TABLE_NAME");
	
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
		$person_ID = (isset($_SESSION["current_user"])? $_SESSION["current_user"]->getID(): 0);
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
		
		if(mysql_error() == '') {
			$this->setEnumOptions();	
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
		
		if(mysql_error() == '') {
			$this->setEnumOptions();
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
		
		if($limit == 0)
			$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
		else
			$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$start.', '.$limit.';';
		
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
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC") {
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
				$this->updated_by->setValue($_SESSION["current_user"]->getID());
			
			$this->updated_ts = new Timestamp();
			
			// check to see if it is a transient object that needs to be inserted
			if($this->isTransient()) {
				$sqlQuery = "INSERT INTO ".$this->getTableName()." (";
	
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					// skip the OID, database auto number takes care of this 
					if($propName != 'OID') {				
						if (!in_array($propName, $this->transientAttributes)) {
							$sqlQuery .= "$propName,";
						}
					}				
				}
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= ") VALUES (";
				
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					// skip the OID, database auto number takes care of this 
					if($propName != 'OID') {
						if ($propName != 'version_num' && !in_array($propName, $this->transientAttributes)) {
							$sqlQuery .= "'".$this->get($propName)."',";
						}
						if ($propName == 'version_num') {					
							$temp = $this->version_num->getValue();
							$this->version_num->setValue($temp+1);
							$sqlQuery .= "'".$this->version_num->getValue()."',";
						}
					}
				}
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= ");";
			}else{
				// assume that it is a persistent object that needs to be updated
				$sqlQuery = "UPDATE ".$this->getTableName()." SET ";
				
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					// skip the OID, database auto number takes care of this 
					if($propName != 'OID') {
						if ($propName != "version_num" && !in_array($propName, $this->transientAttributes)) {
							$sqlQuery .= "$propName = '".$this->get($propName)."',";
						}
						if ($propName == "version_num") {					
							$temp = $this->version_num->getValue();					
							$this->version_num->setValue($temp+1);					
							$sqlQuery .= "version_num = '".$this->version_num->getValue()."',";
						}
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
				// populate the updated OID in case we just done an insert				
				if($this->isTransient())
					$this->setOID(mysql_insert_id());				
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
			if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				$propClass = get_class($this->getPropObject($propName));
				if (strtoupper($propClass) != "ENUM" &&
				strtoupper($propClass) != "DENUM" &&
				strtoupper($propClass) != "DENUMITEM" && 
				strtoupper($propClass) != "BOOLEAN") {
					if (!preg_match($this->getPropObject($propName)->getRule(), $this->getPropObject($propName)->getValue())) {
						throw new ValidationException('Failed to save, validation error is: '.$this->getPropObject($propName)->getHelper());
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
	public function delete() {		
		if(method_exists($this, 'before_delete_callback'))
				$this->before_deleteAllByAttribute_callback();
		
		$sqlQuery = "DELETE FROM ".$this->getTableName()." WHERE OID = '".$this->OID."';";		
			
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		
		
		if(mysql_error() == '') {
			if(method_exists($this, 'after_delete_callback'))
				$this->after_deleteAllByAttribute_callback();
			$this->clear();			
		}else{			
			throw new FailedDeleteException('Failed to delete object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
		}		
	}
	
	/**
	 * Delete all object instances from the database by the specified attribute matching the value provided
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return int The number of rows deleted
	 */
	public function deleteAllByAttribute($attribute, $value) {		
		if(method_exists($this, 'before_delete_callback'))
				$this->before_delete_callback();
		
		$sqlQuery = "DELETE FROM ".$this->getTableName()." WHERE $attribute = '$value';";		
			
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		
		
		if(mysql_error() == '') {
			if(method_exists($this, 'after_delete_callback'))
				$this->after_delete_callback();
			return mysql_affected_rows(); 			
		}else{
			throw new FailedDeleteException('Failed to delete object, MySql error is ['.mysql_error().'], query ['.$this->lastQuery.']');
			return 0;
		}
	}
	
	/**
	 * Gets the version_num of the object from the database
	 * 
	 * @return int
	 */
	public function getVersion() {
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
	public function makeTable() {
		if(method_exists($this, 'before_makeTable_callback'))
			$this->before_makeTable_callback();
		
		$sqlQuery = "CREATE TABLE ".$this->getTableName()." (OID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT,";
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if(!in_array($propName, $this->transientAttributes) && $propName != "OID") {
				$propClass = get_class($this->getPropObject($propName));

				switch (strtoupper($propClass)) {
					case "INTEGER":
						$sqlQuery .= "$propName INT(".$this->getPropObject($propName)->getSize()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$propName DOUBLE(".$this->getPropObject($propName)->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$propName VARCHAR(".$this->getPropObject($propName)->getSize()."),";
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
						$enumVals = $this->getPropObject($propName)->getOptions();
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
	public function rebuildTable() {
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
	 * Adds in a new class property without loosing existing data
	 * 
	 * @param string $propName 	 
	 */
	public function addProperty($propName) {
		if(method_exists($this, 'before_addProperty_callback'))
			$this->before_addProperty_callback();
			
		$sqlQuery = "ALTER TABLE ".$this->getTableName()." ADD ";

		if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				$propClass = get_class($this->getPropObject($propName));

				switch (strtoupper($propClass)) {
					case "INTEGER":
						$sqlQuery .= "$propName INT(".$this->getPropObject($propName)->getSize()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$propName DOUBLE(".$this->getPropObject($propName)->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$propName VARCHAR(".$this->getPropObject($propName)->getSize()."),";
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
						$enumVals = $this->getPropObject($propName)->getOptions();
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
		
		$sqlQuery = rtrim($sqlQuery, ",");

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_addProperty_callback'))
				$this->after_addProperty_callback();			
		}else{			
			throw new AlphaException('Failed to add the new attribute ['.$prop.'] to the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');
		}
	}

	/**
	 * Populates the current business object from global POST data
	 */	
	public function populateFromPost() {
		if(method_exists($this, 'before_populateFromPost_callback'))
			$this->before_populateFromPost_callback();
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;

			if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				$propClass = get_class($this->getPropObject($propName));
				
				if(isset($_POST[$propName])) {
					// checking to see if we need to use addslashes() on the posted data
					if(!get_magic_quotes_gpc()) {
						if (strtoupper($propClass) != 'DATE' && strtoupper($propClass) != 'TIMESTAMP') {
							$this->getPropObject($propName)->setValue(addslashes($_POST[$propName]));					
						}else{						
							$this->getPropObject($propName)->populateFromString(addslashes($_POST[$propName]));
						}
					}else{
						if (strtoupper($propClass) != 'DATE' && strtoupper($propClass) != 'TIMESTAMP') {
							$this->getPropObject($propName)->setValue($_POST[$propName]);					
						}else{						
							$this->getPropObject($propName)->populateFromString($_POST[$propName]);
						}
					}
				}
			}
			if ($propName == "version_num" && isset($_POST["version_num"]))
				$this->version_num->setValue($_POST["version_num"]);
		}
		if(method_exists($this, 'after_populateFromPost_callback'))
			$this->after_populateFromPost_callback();	
	}

	/**
	 * Gets the maximum OID value from the database for this class
	 * 
	 * @return integer
	 */
	public function getMAX() {
		if(method_exists($this, 'before_getMAX_callback'))
			$this->before_getMAX_callback();
		
		$sqlQuery = 'SELECT MAX(OID) AS max_OID FROM '.$this->getTableName();

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_getMAX_callback'))
				$this->after_getMAX_callback();
					
			return $row['max_OID'];
		}else{			
			throw new AlphaException('Failed to get the MAX ID for the class ['.get_class($this).'] from the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');
			return 0;
		}
	}
	
	/**
	 * Gets the count from the database for the amount of objects of this class
	 * 
	 * @return integer
	 */
	public function getCount() {
		if(method_exists($this, 'before_getCount_callback'))
			$this->before_getCount_callback();
		
		$sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->getTableName();

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_getCount_callback'))
				$this->after_getCount_callback();
				
			return $row['class_count'];
		}else{
			throw new AlphaException('Failed to get the count for the class ['.get_class($this).'] from the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');			
			return 0;
		}
	}

	/**
	 * Gets the OID for the object in zero-padded format
	 * 
	 * @return integer
	 */
	public function getID() {
		return str_pad($this->OID, 11, '0', STR_PAD_LEFT);
	}
	
	/**
	 * Method for getting version number of the object
	 * 
	 * @return Integer
	 */
	public function getVersionNumber() {
		return $this->version_num;
	}
	
	/**
	 * Populate all of the enum options for this object from the database
	 */
	protected function setEnumOptions() {
		if(method_exists($this, 'before_setEnumOptions_callback'))
			$this->before_setEnumOptions_callback();
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		// flag for any database errors
		$dbError = false;
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				$propClass = get_class($this->getPropObject($propName));
				if ($propClass == 'Enum') {
					$sqlQuery = "SHOW COLUMNS FROM ".$this->getTableName()." LIKE '".$propName."'";
					
					$this->lastQuery = $sqlQuery;
					
					$result = mysql_query($sqlQuery);
					
					if (mysql_error() == '') {							
						$row = mysql_fetch_row($result);
						$options = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2",$row[1]));
						
						$this->getPropObject($propName)->setOptions($options);						
					}else{
						$dbError = true;
						break;
					}
				}
			}
		}
		
		if (!$dbError) {
			if(method_exists($this, 'after_setEnumOptions_callback'))
				$this->after_setEnumOptions_callback();
		}else{			
			throw new AlphaException('Failed to load enum options correctly for object instance of class ['.get_class($this).']');
		}
	}
	
	/**
	 * Generic getter method for accessing class properties.  Will use the method get.ucfirst($prop) instead if that
	 * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use any 
	 * get.ucfirst($prop) method even if it exists, false otherwise (default).
	 * 
	 * @param string $prop
	 * @param boolean $noChildMethods
	 * @return mixed
	 */
	public function get($prop, $noChildMethods = false) {
		if(method_exists($this, 'before_get_callback'))
			$this->before_get_callback();
		
		// handle attributes with a get.ucfirst($prop) method
		if(!$noChildMethods && method_exists($this, 'get'.ucfirst($prop))) {
			if(method_exists($this, 'after_get_callback'))
				$this->after_get_callback();
				
			return eval('return $this->get'.ucfirst($prop).'();');
		}else{
			// handle attributes with no dedicated child get.ucfirst($prop) method
			if(isset($this->$prop) && method_exists($this->$prop, 'getValue')) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();

				// complex types will have a getValue() method, return the value of that
				return $this->$prop->getValue();
			}elseif(isset($this->$prop)) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();

				// simple types returned as-is
				return $this->$prop;
			}else{
				throw new AlphaException('Could not access the property ['.$prop.'] on the object of class ['.get_class($this).']');
				return false;
			}
		}
	}
	
	/**
	 * Generic setter method for setting class properties.  Will use the method set.ucfirst($prop) instead if that
	 * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use 
	 * any get.ucfirst($prop) method even if it exists, false otherwise (default).
	 * 
	 * @param string $prop
	 * @param mixed $value
	 * @param boolean $noChildMethods
	 */
	public function set($prop, $value, $noChildMethods = false) {
		if(method_exists($this, 'before_set_callback'))
			$this->before_set_callback();
		
		// handle attributes with a set.ucfirst($prop) method
		if(!$noChildMethods && method_exists($this, 'set'.ucfirst($prop))) {
			if(method_exists($this, 'after_set_callback'))
				$this->after_set_callback();
			
			return eval('return $this->set'.ucfirst($prop)."('$value');");
		}else{
			// handle attributes with no dedicated child set.ucfirst($prop) method
			if(isset($this->$prop)) {
				if(method_exists($this, 'after_set_callback'))
					$this->after_set_callback();
				
				// complex types will have a setValue() method to call
				if (get_class($this->$prop) != false) {				
					if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {					
						$this->$prop->setValue($value);
					}else{
						// Date and Timestamp objects have a special setter accepting a string
						$this->$prop->populateFromString($value);
					}
				}else{
					// simple types set directly
					$this->$prop = $value;
				}				
			}else{
				throw new AlphaException('Could not set the property ['.$prop.'] on the object of the class ['.get_class($this).'].  Property may not exist, or else does not have a setValue() method and is private or protected.');
			}
		}
	}
	
	/**
	 * Gets the property object rather than the value for complex attributes
	 * 
	 * @param string $prop
	 * @return mixed
	 */
	public function getPropObject($prop) {
		if(method_exists($this, 'before_getPropObject_callback'))
			$this->before_getPropObject_callback();
		
		if(isset($this->$prop)) {
			if(method_exists($this, 'after_getPropObject_callback'))
				$this->after_getPropObject_callback();
			
			return $this->$prop;			
		}else{
			throw new AlphaException('Could not access the property object ['.$prop.'] on the object of class ['.get_class($this).']');
			return false;
		}
	}
	
	/**
	 * Checks to see if the table exists in the database for the current business class
	 * 
	 * @return boolean
	 */
	public function checkTableExists() {
		global $config;
		
		if(method_exists($this, 'before_checkTableExists_callback'))
			$this->before_checkTableExists_callback();
				
		$tableExists = false;
		
		$result = mysql_list_tables($config->get('sysDB'));
		
		while ($row = mysql_fetch_row($result)) {    		
    		if ($row[0] == $this->getTableName())
    			$tableExists = true;
		}		
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_checkTableExists_callback'))
				$this->after_checkTableExists_callback();
			
			return $tableExists;
		}else{			
			throw new AlphaException('Failed to access the system database correctly, error is ['.mysql_error().']');
			return false;
		}
	}
	
	/**
	 * Checks to see if the table in the database matches (for fields) the business class definition
	 * 
	 * @return boolean
	 */
	public function checkTableNeedsUpdate() {
		if(method_exists($this, 'before_checkTableNeedsUpdate_callback'))
			$this->before_checkTableNeedsUpdate_callback();
		
		$tableExists = $this->checkTableExists();
		
		if (!$tableExists) {
			return true;
		}else{
			$updateRequired = false;
			
			$matchCount = 0;
			
			$query = 'SHOW COLUMNS FROM '.$this->getTableName();
			$result = mysql_query($query);
			$this->lastQuery = $query;			
			
			// get the class attributes
			$reflection = new ReflectionClass(get_class($this));
			$properties = $reflection->getProperties();
		
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->transientAttributes)) {
				
					$foundMatch = false;	
					
					while ($row = mysql_fetch_assoc($result)) {						
			    		if ($propName == $row['Field']) {
			    			$foundMatch = true;
			    			break;
			    		}
					}
					
					if(!$foundMatch) {						
						$matchCount--;						
					}
					mysql_data_seek($result, 0);					
				}
			}		
			
			if ($matchCount != 0)
				$updateRequired = true;
			
			if (mysql_error() == '') {
				if(method_exists($this, 'before_checkTableNeedsUpdate_callback'))
					$this->before_checkTableNeedsUpdate_callback();
				
				// check the table indexes
				$this->checkIndexes();
				
				return $updateRequired;
			}else{				
				throw new AlphaException('Failed to access the system database correctly, error is ['.mysql_error().']');
				return false;
			}
		}
	}
	
	/**
	 * Returns an array containing any properties on the class which have not been created on the database 
	 * table yet
	 * 
	 * @return array
	 */
	public function findMissingFields() {
		if(method_exists($this, 'before_findMissingFields_callback'))
			$this->before_findMissingFields_callback();
		
		$missingFields = array();			
		$matchCount = 0;
			
		$result = mysql_query('SHOW COLUMNS FROM '.$this->getTableName());
			
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if (!in_array($propName, $this->transientAttributes)) {
				while ($row = mysql_fetch_assoc($result)) {				
		    		if ($propName == $row['Field']) {		    			
		    			$matchCount++;		    			
		    			break;
		    		}	    		
				}				
				mysql_data_seek($result, 0);					
			}else{
				$matchCount++;
			}
			
			if($matchCount==0) {					
				array_push($missingFields, $propName);
			}else{
				$matchCount = 0;
			}
		}
		
		if (mysql_error() != '') {			
			throw new AlphaException('Failed to access the system database correctly, error is ['.mysql_error().']');
		}
		
		if(method_exists($this, 'after_findMissingFields_callback'))
			$this->after_findMissingFields_callback();
		
		return $missingFields;	
	}
	
	/**
	 * Getter for the TABLE_NAME, which should be set by a child of this class
	 * 
	 * @return string
	 */
	public function getTableName() {
		eval('$TABLE_NAME = '.get_class($this).'::TABLE_NAME;');
		
		if(!empty($TABLE_NAME)) {
    		return $TABLE_NAME;        
    	}else{
    		throw new AlphaException('Error: no TABLE_NAME constant set for the class '.get_class($this));		
    	}
	}
	
	/**
	 * Method for getting the OID of the person who created this BO
	 * 
	 * @return Integer
	 */
	public function getCreatorId() {
		return $this->created_by;
	}
	
	/**
	 * Method for getting the OID of the person who updated this BO
	 * 
	 * @return Integer
	 */
	public function getUpdatorId() {
		return $this->updated_by;
	}
	
	/**
	 * Method for getting the date/time of when the BO was created
	 * 
	 * @return Timestamp
	 */
	public function getCreateTS() {
		return $this->created_ts;
	}
	
	/**
	 * Method for getting the date/time of when the BO was last updated
	 * 
	 * @return Timestamp
	 */
	public function getUpdateTS() {
		return $this->updated_ts;
	}
	
	/**
	 * Adds the name of the attribute provided to the list of transient (non-saved) attributes for this BO
	 * 
	 * @param string attributeName
	 */
	public function markTransient($attributeName) {
		array_push($this->transientAttributes, $attributeName);		
	}
	
	/**
	 * Removes the name of the attribute provided from the list of transient (non-saved) attributes for this BO, 
	 * ensuring that it will be saved on the next attempt.
	 * 
	 * @param string attributeName
	 */
	public function markPersistent($attributeName) {
		$this->transientAttributes = array_diff($this->transientAttributes, array($attributeName));
	}
	
	/**
	 * Adds the name of the atribute provided to the list of unique (constrained) attributes for this BO
	 * 
	 * @param string attributeName
	 */
	protected function markUnique($attributeName) {
		array_push($this->uniqueAttributes, $attributeName);		
	}
	
	/**
	 * Checks to see if all of the indexes are in place for the BO's table, creates those that are missing
	 */
	protected function checkIndexes() {
		if(method_exists($this, 'before_check_indexes_callback'))
			$this->before_check_indexes_callback();
		
		$query = 'SHOW INDEX FROM '.$this->getTableName();
		$result = mysql_query($query);
		$this->lastQuery = $query;
		
		// process unique keys
		foreach($this->uniqueAttributes as $prop) {
			$index_exists = false;
			// reset the MySQL result set pointer
			mysql_data_seek($result, 0);
			while ($row = mysql_fetch_assoc($result)) {					
				if ($prop.'_unq_idx' == $row['Key_name']) {
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
			$this->lastQuery = $query;
			throw new AlphaException('Failed to access the system database correctly, error is ['.mysql_error().']');
		}
	}
	
	/**
	 * Creates a unique index in the database on the given property
	 * 
	 * @param string $prop
	 */
	protected function createUniqueIndex($prop) {
		if(method_exists($this, 'before_create_unique_index_callback'))
			$this->before_create_unique_index_callback();
		
		$sqlQuery = 'CREATE UNIQUE INDEX '.$prop.'_unq_idx ON '.$this->getTableName().' ('.$prop.');';
		
		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_create_unique_index_callback'))
				$this->after_create_unique_index_callback();
		}else{			
			throw new AlphaException('Failed to create the index ['.$prop.'_unq_idx] on ['.$this->getTableName().'], error is ['.mysql_error().']');
		}
	}
	
	/**
	 * Parses a MySQL error for the value that violated a unique constraint
	 * 
	 * @param string $error
	 */
	protected function findOffendingValue($error) {		
		$singleQuote1 = strpos($error,"'");
		$singleQuote2 = strrpos($error,"'");
		
		return substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
	}
	
	/**
	 * Gets the data labels array
	 * 
	 * @return array
	 */
	public function getDataLabels() {		
		return $this->dataLabels;
	}
	
	/**
	 * Gets the data label for the given attribute name
	 * 
	 * @return string
	 */
	public function getDataLabel($att) {
		if(in_array($att, array_keys($this->dataLabels)))		
			return $this->dataLabels[$att];
		else
			throw new IllegalArguementException('Error: no data label found on the class ['.get_class($this).'] for the attribute ['.$att.']');
	}
	
	/**
	 * Loops over the core and custom BO directories and builds an array of all of the BO class names in the system
	 *
	 * @return array
	 */
	public function getBOClassNames() {
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
	
	/**
	 * Get the array of default attribute names
	 *
	 * @return array
	 */
	public function getDefaultAttributes() {
		return $this->defaultAttributes;
	}
	
	/**
	 * Get the array of transient attribute names
	 *
	 * @return array
	 */
	public function getTransientAttributes() {
		return $this->transientAttributes;
	}
	
	/**
	 * Private setter for the object ID, used from load methods
	 *
	 * @param int $OID
	 */
	private function setOID($OID) {
		$this->OID = $OID;
	}
	
	/**
	 * Inspector to see if the business object is transient (not presently stored in the database)
	 * 
	 * @return boolean
	 */
	public function isTransient() {
		if(empty($this->OID) || !isset($this->OID) || $this->OID == '00000000000')
			return true;
		else
			return false;
	}
	
	/**
	 * Get the last database query run on this object
	 *
	 * @return string
	 */
	public function getLastQuery() {
		return $this->lastQuery;
	}
	
	/**
	 * unsets all of the attributes of this object
	 */
	private function clear() {
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			unset($this->$propName);
		}
	}
	
	/**
	 * Reloads the object from the database, overwritting any attribute values in memory 
	 */
	public function reload() {
		if(!$this->isTransient()) {
			$this->load($this->getID());			
		}else{
			throw new AlphaException('Cannot reload transient object from database!');
		}
	}
	
	/**
	 * Loads the definition for the BO classname provided
	 * 
	 * @param string $classname 
	 */
	public static function loadClassDef($classname) {
		global $config;
		
		if(file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
			require_once $config->get('sysRoot').'model/'.$classname.'.inc';
		elseif(file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
			require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
		else
			throw new IllegalArguementException('The class ['.$classname.'] is not defined anywhere!');
	}
}

?>