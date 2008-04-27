<?php

// $Id$

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';

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
* @copyright 2006 John Collins
* @todo Write increment_version method
* @todo Write set_last_query method
* @todo clone_object has bug due to complex attributes not being cloned (referenced instead!)
*  
*/
class mysql_DAO
{
	/**
	 * the object ID
	 * @var integer
	 */
	var $OID;
	/**
	 * the last database query run by this object.  Useful for tracing an error.
	 * @var string
	 */
	var $last_query = "";
	/**
	 * the version number of the object, used for locking mechanism
	 * @var Integer
	 */
	var $version_num;
	/**
	 * the timestamp of creation
	 * @var Timestamp
	 */
	var $created_ts;
	/**
	 * the OID of the person who created this BO
	 * @var Integer 
	 */
	var $created_by;
	/**
	 * the timestamp of the last update
	 * @var Timestamp
	 */
	var $updated_ts;
	/**
	 * the OID of the person who last updated this BO
	 * @var Integer 
	 */
	var $updated_by;
	/**
	 * an array of the names of all of the default attributes of a persistent BO defined in this class
	 * @var array
	 */
	var $default_attributes = array("OID","last_query","version_num","data_labels","render_mode","created_ts","created_by","updated_ts","updated_by","default_attributes","transient_attributes","unique_attributes","TABLE_NAME");
	/**
	 * an array of the names of all of the transient attributes of a persistent BO which are not saved to the DB
	 * @var array
	 */
	var $transient_attributes = array("last_query","data_labels","render_mode","default_attributes","transient_attributes","unique_attributes","TABLE_NAME");
	/**
	 * an array of the uniquely-constained attributes of this persistent BO
	 * @var array
	 */
	var $unique_attributes = array();
	
	/**
	 * the constructor which sets up some housekeeping attributes
	 */
	function mysql_DAO() {
		$this->version_num = new Integer(0);
		$this->created_ts = new Timestamp();
		$person_ID = (isset($_SESSION["current_user"])? $_SESSION["current_user"]->get_ID(): 0);
		$this->created_by = new Integer($person_ID);
		$this->updated_ts = new Timestamp();
		$this->updated_by = new Integer($person_ID);
	}
	
	/**
	 * populates the child object with the properties retrived from the database for the object $id
	 * @param integer $OID The id of the object to load
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function load_object($OID)
	{
		if(method_exists($this, 'before_load_callback'))
			$this->before_load_callback();
		
		$this->OID = $OID;
		$sql_query = "SELECT * FROM ".$this->TABLE_NAME." WHERE OID = '$OID';";		

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load object '.$OID.', not found in database.' ,'load_object($OID)','framework');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		$properties = get_class_vars(get_class($this));

		foreach(array_keys($properties) as $prop) {
			if (get_class($this->$prop) != false && !in_array($prop, $this->transient_attributes)) {				
				if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {					
					$this->$prop->set_value($row[$prop]);
				}else{
					$this->$prop->populate_from_string($row[$prop]);
				}
			}			
		}		
		
		if(mysql_error() == '' && $this->set_enum_options()) {	
			if(method_exists($this, 'after_load_callback'))
				$this->after_load_callback();
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load object, MySql error is: '.mysql_error().', query: '.$this->last_query,'load_object($OID)','framework');
			return false;
		}		
	}
	
	/**
	 * populates the child object from the database table by the given attribute
	 * @param string $atribute The attribute to load the object by
	 * @param string $value The value of the attribute to load the object by
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function load_by_attribute($attribute, $value)
	{
		if(method_exists($this, 'before_load_by_attribute_callback'))
				$this->before_load_by_attribute_callback();
		
		$sql_query = "SELECT * FROM ".$this->TABLE_NAME." WHERE $attribute = '$value';";

		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);
		
		if(mysql_num_rows($result) == 0) {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load object, MySql error is: '.mysql_error().', query: '.$this->last_query,"load_by_attribute([$attribute], [$value])",'framework');
			return false;
		}
		
		$row = mysql_fetch_assoc($result);
		
		$properties = get_class_vars(get_class($this));

		foreach(array_keys($properties) as $prop) {
			if (get_class($this->$prop) != false) {	
				if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {					
					$this->$prop->set_value(stripslashes($row[$prop]));
				}else{
					$this->$prop->populate_from_string(stripslashes($row[$prop]));
				}
			}
			if($prop == "OID"){				
				$this->OID = $row[$prop];
			}	
			if($prop == "version_num"){				
				$this->version_num = $row[$prop];
			}
		}		
		
		if(mysql_error() == '' && $this->set_enum_options()) {
			if(method_exists($this, 'after_load_by_attribute_callback'))
				$this->after_load_by_attribute_callback();
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load object, MySql error is: '.mysql_error().', query: '.$this->last_query,"load_by_attribute([$attribute], [$value])",'framework');
			return false;
		}		
	}
	
	/**
	 * loads all of the objects of this class into an array, which is returned
	 * 
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $order_by the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is ASC
	 * @return array an array containing objects of this type of business object.
	 */
	function load_all($start=0, $limit=0, $order_by="OID", $order="ASC")
	{
		if(method_exists($this, 'before_load_all_callback'))
			$this->before_load_all_callback();
		
		global $config;
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." ORDER BY ".$order_by." ".$order." LIMIT ".$start.", ".$limit.";";
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load objects, query: '.$this->last_query.'.' ,'load_all()','framework');
			return false;
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load_object($row["OID"]);
			$objects[$count] = $obj;
			$count++;			
		}
		
		if(method_exists($this, 'after_load_all_callback'))
			$this->after_load_all_callback();
		
		return $objects;	
	}
	
	/**
	 * loads all of the objects of this class by the specified attribute into an array, which is returned
	 * 
	 * @param string $atribute The attribute to load the object by
	 * @param string $value The value of the attribute to load the object by
	 * @param integer $start The start of the SQL LIMIT clause
	 * @param integer $limit The amount (limit) of objects to load
	 * @param string $order_by the name of the field to sort the BOs by
	 * @param string $order the order to sort the BOs by, default is ASC
	 * @return array an array containing objects of this type of business object.
	 */
	function load_all_by_attribute($attribute, $value, $start=0, $limit=0, $order_by="OID", $order="ASC")
	{
		if(method_exists($this, 'before_load_all_by_attribute_callback'))
			$this->before_load_all_by_attribute_callback();
		
		global $config;
		
		if ($limit == 0)
			$limit = $config->get('sysListPageAmount');
		
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." WHERE $attribute = '$value' ORDER BY ".$order_by." ".$order." LIMIT ".$start.", ".$limit.";";
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);
		
		if(mysql_error() != '') {
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load objects, query: '.$this->last_query.'.' ,'load_all_by_attribute()','framework');
			return false;
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = mysql_fetch_assoc($result)) {
			$obj = new $BO_Class();
			$obj->load_object($row["OID"]);
			$objects[$count] = $obj;
			$count++;
		}
		
		if(method_exists($this, 'after_load_all_by_attribute_callback'))
			$this->after_load_all_by_attribute_callback();
		
		return $objects;	
	}

	/**
	 * saves the object.  If $this->OID is blank it will INSERT, otherwise UPDATE
	 * @return boolean true if saved correctly, false otherwise 
	 */
	function save_object()
	{
		if(method_exists($this, 'before_save_callback'))
			$this->before_save_callback();
		
		// firstly we will validate the object before we try to save it
		$validate = $this->validate();
		
		if(!$validate)
			return $validate;
		
		$properties = get_class_vars(get_class($this));
		$sql_query = "";		
		
		if($this->get_version() != $this->version_num->get_value()){
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not save the object as it has been updated by another user.  Please try saving again.','save_object()','warning');
			return false;
		}
		
		// set the "updated by" fields, we can only set the user id if someone is logged in
		if(isset($_SESSION["current_user"]))
			$this->updated_by->set_value($_SESSION["current_user"]->get_ID());
		$this->updated_ts = new Timestamp();

		if(empty($this->OID) || !isset($this->OID)) {
			$sql_query = "INSERT INTO ".$this->TABLE_NAME." (";

			foreach(array_keys($properties) as $prop) {
				if (get_class($this->$prop) != false && !in_array($prop, $this->transient_attributes)) {
					$sql_query .= "$prop,";
				}				
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= ") VALUES (";

			foreach(array_keys($properties) as $prop) {				
				if (get_class($this->$prop) != false && $prop != "version_num" && !in_array($prop, $this->transient_attributes)) {
					$sql_query .= "'".$this->$prop->get_value()."',";
				}
				if ($prop == "version_num") {					
					$temp = $this->version_num->get_value();
					$this->version_num->set_value($temp+1);
					$sql_query .= "'".$this->version_num->get_value()."',";
				}
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= ");";
		}else{
			$sql_query = "UPDATE ".$this->TABLE_NAME." SET ";

			foreach(array_keys($properties) as $prop) {
				if (get_class($this->$prop) != false && $prop != "version_num" && !in_array($prop, $this->transient_attributes)) {
					$sql_query .= "$prop = '".$this->$prop->get_value()."',";
				}
				if ($prop == "version_num") {					
					$temp = $this->version_num->get_value();					
					$this->version_num->set_value($temp+1);					
					$sql_query .= "version_num = '".$this->version_num->get_value()."',";
				}
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= " WHERE OID=".$this->OID.";";
		}

		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_save_callback'))
				$this->after_save_callback();
			return true;
		}else{
			// check for unique violations			
			if(mysql_errno() == '1062') {
				$this->last_query .= " Error: ".mysql_error();
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to save, the value '.$this->findOffendingValue(mysql_error()).' is already in use!','save_object()','validation');
				return false;
			}else{
				$this->last_query .= " Error: ".mysql_error();
				$error = new handle_error($_SERVER["PHP_SELF"],'Failed to save object, MySql error is: '.mysql_error().', query: '.$this->last_query,'save_object()','framework');
				return false;
			}
		}
	}
	
	/**
	 * validates the object to be saved
	 * @return boolean true if the object is valid, false otherwise
	 */
	function validate() {
		if(method_exists($this, 'before_validate_callback'))
				$this->before_validate_callback();
		
		$valid = true;
		
		$properties = get_object_vars($this);		
		
		foreach(array_keys($properties) as $prop) {
			if(!in_array($prop, $this->default_attributes) && !in_array($prop, $this->transient_attributes)) {
				if (strtoupper(get_class($properties[$prop])) != "ENUM" &&
				strtoupper(get_class($properties[$prop])) != "DENUM" &&
				strtoupper(get_class($properties[$prop])) != "DENUMITEM") {
					if (!preg_match($this->$prop->get_rule(), $this->$prop->get_value())) {
						$error = new handle_error($_SERVER["PHP_SELF"],'Failed to save, validation error is: '.$this->$prop->get_helper(),'validate()','validation');
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
	 * delete the object specified from the database
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function delete_object() {		
		if(method_exists($this, 'before_delete_callback'))
				$this->before_delete_callback();
			
		$sql_query = "DELETE FROM ".$this->TABLE_NAME." WHERE OID = '".$this->OID."';";		
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		
		
		if(mysql_error() == '') {
			if(method_exists($this, 'after_delete_callback'))
				$this->after_delete_callback();
			
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to delete object, MySql error is: '.mysql_error(),'delete_object()','framework');
			return false;
		}
	}
	
	/**
	 * Gets the version_num of the object from the database
	 * @return integer version_num
	 */
	function get_version() {
		if(method_exists($this, 'before_get_version_callback'))
			$this->before_load_callback();
		
		$sql_query = 'SELECT version_num FROM '.$this->TABLE_NAME.' WHERE OID = \''.$this->OID.'\';';
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		$row = mysql_fetch_assoc($result);
		
		if (isset($row['version_num']))
			$version_num = $row['version_num'];
		else
			$version_num = 0;
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_get_version_callback'))
				$this->after_get_version_callback();
			
			return $version_num;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get version number for object '.$this->OID.', MySql error is: '.mysql_error(),'get_version()','framework');
			return 0;
		}
	}

	/**
	 * builds new database table for the BO class
	 * @return bool True if the table was created, false otherwise
	 */	
	function make_table()
	{
		if(method_exists($this, 'before_make_table_callback'))
				$this->before_make_table_callback();
		
		$properties = get_object_vars($this);
		$obj_type = '';
		
		$sql_query = "DROP TABLE IF EXISTS ".$this->TABLE_NAME.";";
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		if (mysql_error() != '') {
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to create the table for this class: '.$this->TABLE_NAME.', query is : '.$this->last_query,'make_table()','framework');
		}

		$sql_query = "CREATE TABLE ".$this->TABLE_NAME." (OID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT,";

		foreach(array_keys($properties) as $prop) {			
			if(!in_array($prop, $this->transient_attributes) && $prop != "OID") {				
				$obj_type = get_class($properties[$prop]);
				
				switch (strtoupper($obj_type)) {
					case "INTEGER":
						$sql_query .= "$prop INT(".$properties[$prop]->get_size()."),";
					break;
					case "DOUBLE":
						$sql_query .= "$prop DOUBLE(".$properties[$prop]->get_size()."),";
					break;
					case "STRING":
						$sql_query .= "$prop VARCHAR(".$properties[$prop]->get_size()."),";
					break;
					case "TEXT":
						$sql_query .= "$prop TEXT,";
					break;
					case "BOOLEAN":
						$sql_query .= "$prop CHAR(".$properties[$prop]->get_size().") DEFAULT '0',";
					break;
					case "DATE":
						$sql_query .= "$prop DATE,";
					break;
					case "TIMESTAMP":
						$sql_query .= "$prop DATETIME,";
					break;
					case "ENUM":
						$sql_query .= "$prop ENUM(";
						$enum_vals = $properties[$prop]->get_options();
						foreach($enum_vals as $enum_val) {
							$sql_query .= "'".$enum_val."',";
						}
						$sql_query = rtrim($sql_query, ",");
						$sql_query .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this).'::'.$prop);						
						$tmp->save_object();
						$sql_query .= "$prop INT(11) ZEROFILL,";
					break;
					default:
						$sql_query .= "";
					break;
				}
			}			
		}

		$sql_query .= "PRIMARY KEY (OID)) TYPE=InnoDB;";
		
		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_make_table_callback'))
				$this->after_make_table_callback();
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to create the table for this class: '.$this->TABLE_NAME.', query is : '.$this->last_query,'make_table()','framework');
			return false;
		}
	}

	/**
	 * re-builds the table if the model requirements have changed.  all data is lost!
	 * @return bool True if the table was re-created, false otherwise
	 */	
	function rebuild_table()
	{
		if(method_exists($this, 'before_rebuild_table_callback'))
			$this->before_rebuild_table_callback();
		
		$sql_query = "DROP TABLE IF EXISTS ".$this->TABLE_NAME.";";

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		if (mysql_error() == '') {
			$this->make_table();
			if(method_exists($this, 'after_rebuild_table_callback'))
				$this->after_rebuild_table_callback();
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to drop the table for this class','rebuild_table()','framework');
			return false;
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
		
		$sql_query = "ALTER TABLE ".$this->TABLE_NAME." ADD ";

		if(!in_array($prop, $this->default_attributes)) {
			
			$obj_type = get_class($this->$prop);

			switch (strtoupper($obj_type)) {
					case "INTEGER":
						$sql_query .= "$prop INT(".$this->$prop->get_size()."),";
					break;
					case "DOUBLE":
						$sql_query .= "$prop DOUBLE(".$this->$prop->get_size()."),";
					break;
					case "STRING":
						$sql_query .= "$prop VARCHAR(".$this->$prop->get_size()."),";
					break;
					case "TEXT":
						$sql_query .= "$prop TEXT,";
					break;
					case "BOOLEAN":
						$sql_query .= "$prop CHAR(".$this->$prop->get_size().") DEFAULT '0',";
					break;
					case "DATE":
						$sql_query .= "$prop DATE,";
					break;
					case "TIMESTAMP":
						$sql_query .= "$prop DATETIME,";
					break;
					case "ENUM":
						$sql_query .= "$prop ENUM(";
						$enum_vals = $this->$prop->get_options();
						foreach($enum_vals as $enum_val) {
							$sql_query .= "'".$enum_val."',";
						}
						$sql_query = rtrim($sql_query, ",");
						$sql_query .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this).'::'.$prop);						
						$tmp->save_object();
						$sql_query .= "$prop INT(11) ZEROFILL,";
					break;
					default:
						$sql_query .= "";
					break;
				}
		}
		
		$sql_query = rtrim($sql_query, ",");

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_add_property_callback'))
				$this->after_add_property_callback();		
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to add the new attribute ['.$prop.'] to the table '.$this->TABLE_NAME.', database error: '.$this->last_query, 'add_property()', 'framework');
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
				if(!in_array($prop, $this->default_attributes) && !in_array($prop, $this->transient_attributes)) {
					// checking to see if we need to use addslashes() on the posted data
					if(!get_magic_quotes_gpc()) {
						if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
							$this->$prop->set_value(addslashes($_POST[$prop]));					
						}else{						
							$this->$prop->populate_from_string(addslashes($_POST[$prop]));
						}
					}else{
						if (strtoupper(get_class($this->$prop)) != 'DATE' && strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
							$this->$prop->set_value($_POST[$prop]);					
						}else{						
							$this->$prop->populate_from_string($_POST[$prop]);
						}
					}
				}
				if ($prop == "version_num" && isset($_POST["version_num"]))
					$this->version_num->set_value($_POST["version_num"]);
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
		
		$sql_query = "SELECT MAX(OID) AS max_ID FROM ".$this->TABLE_NAME;

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_get_MAX_callback'))
				$this->after_get_MAX_callback();
					
			return $row["max_ID"];
		}else{			
			$this->last_query .= " Error: ".mysql_error();
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
		
		$sql_query = "SELECT COUNT(OID) AS class_count FROM ".$this->TABLE_NAME;

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_get_count_callback'))
				$this->after_get_count_callback();
				
			return $row["class_count"];
		}else{			
			$this->last_query .= " Error: ".mysql_error();
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
			if(!in_array($prop, $this->default_attributes)) {

				$obj_type = get_class($properties[$prop]);				

				if ($obj_type == 'Enum') {
					$sql_query = "SHOW COLUMNS FROM ".$this->TABLE_NAME." LIKE '".$prop."'";
					
					$this->last_query = $sql_query;
					
					$result = mysql_query($sql_query);
							
					$row = mysql_fetch_row($result);
					$options = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2",$row[1]));
					
					$properties[$prop]->set_options($options);
				}
			}
		}
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_set_enum_options_callback'))
				$this->after_set_enum_options_callback();
				
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
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
		
		if(!$no_child_methods && method_exists($this, "get_".$prop)) {
			if(method_exists($this, 'after_get_callback'))
				$this->after_get_callback();
				
			return eval('return $this->get_'.$prop.'();');
		}else{
			if(isset($this->$prop) && method_exists($this->$prop, "get_value")) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();
				
				return $this->$prop->get_value();
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
		
		if(!$no_child_methods && method_exists($this, "set_".$prop)) {
			if(method_exists($this, 'after_set_callback'))
				$this->after_set_callback();
			
			return eval("return \$this->set_".$prop."('$value');");
		}else{
			if(isset($this->$prop) && method_exists($this->$prop, "set_value")) {
				if(method_exists($this, 'after_set_callback'))
					$this->after_set_callback();
				
				$this->$prop->set_value($value);
			}elseif(isset($this->$prop)) {
				if(method_exists($this, 'after_set_callback'))
					$this->after_set_callback();
				
				$this->$prop = $value;
			}else{
				$error = new handle_error($_SERVER["PHP_SELF"],'Could not set the property '.$prop.' on the object '.get_class($this).'.  Property may not exist, or else does not have a set_value() method.','set()','framework');
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
		
		if(isset($this->$prop)) {
			if(method_exists($this, 'after_get_prop_object_callback'))
				$this->after_get_prop_object_callback();
			
			return $this->$prop;
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
    		if ($row[0] == $this->TABLE_NAME)
    			$table_exists = true;
		}		
		
		if (mysql_error() == '') {
			if(method_exists($this, 'after_check_table_exists_callback'))
				$this->after_check_table_exists_callback();
			
			return $table_exists;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
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
			
			$result = mysql_query("SHOW COLUMNS FROM ".$this->TABLE_NAME);
			
			$properties = get_class_vars(get_class($this));			
			
			foreach(array_keys($properties) as $prop) {
				if (!in_array($prop, $this->transient_attributes)) {
				
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
				$this->last_query .= " Error: ".mysql_error();
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
			
		$result = mysql_query("SHOW COLUMNS FROM ".$this->TABLE_NAME);
			
		$properties = get_class_vars(get_class($this));		
			
		foreach(array_keys($properties) as $prop) {			
			if (!in_array($prop, $this->transient_attributes)) {
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
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_table_needs_update()','framework');
		}
		
		if(method_exists($this, 'after_find_missing_fields_callback'))
			$this->after_find_missing_fields_callback();
		
		return $missing_fields;	
	}
	
	/**
	 * getter for the TABLE_NAME, which should be set by a child of this class
	 * @return string $TABLE_NAME
	 */
	function get_table_name() {
		if (isset($this->TABLE_NAME))
			return $this->TABLE_NAME;
		else
			return "unset";
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
	function mark_transient($attribute_name) {
		array_push($this->transient_attributes, $attribute_name);		
	}
	
	/**
	 * adds the name of the atribute provided to the list of unique (constrained) attributes for this BO
	 * @param string attribute_name the name of the attribute to mark as unique
	 */
	function mark_unique($attribute_name) {
		array_push($this->unique_attributes, $attribute_name);		
	}
	
	/**
	 * checks to see if all of the idexes are in place for the BO's table, creates those that are missing
	 */
	function checkIndexes() {
		if(method_exists($this, 'before_check_indexes_callback'))
			$this->before_check_indexes_callback();
		
		$result = mysql_query("SHOW INDEX FROM ".$this->TABLE_NAME);
		
		// process unique keys
		foreach($this->unique_attributes as $prop) {
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
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_indexes()','framework');	
		}
	}
	
	/**
	 * creates a unique index in the database on the given property
	 */
	function createUniqueIndex($prop) {
		if(method_exists($this, 'before_create_unique_index_callback'))
			$this->before_create_unique_index_callback();
		
		$sql_query = 'CREATE UNIQUE INDEX '.$prop.'_unq_idx ON '.$this->TABLE_NAME.' ('.$prop.');';
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
			if(method_exists($this, 'after_create_unique_index_callback'))
				$this->after_create_unique_index_callback();
		}else{
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to create the index '.$prop.'_unq_idx on '.$this->TABLE_NAME.', query is : '.$this->last_query,'createUniqueIndex()','framework');
		}
	}
	
	/**
	 * parces a MySQL error for the value that violated a unique constraint
	 */
	function findOffendingValue($error) {		
		$singleQuote1 = strpos($error,"'");
		$singleQuote2 = strrpos($error,"'");
		
		return substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
	}
}

?>