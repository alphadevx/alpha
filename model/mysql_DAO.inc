<?php

// $Id$

require_once $sysRoot.'alpha/util/catch_error.inc';

require_once $sysRoot.'alpha/model/types/Date.inc';
require_once $sysRoot.'alpha/model/types/Double.inc';
require_once $sysRoot.'alpha/model/types/Integer.inc';
require_once $sysRoot.'alpha/model/types/String.inc';
require_once $sysRoot.'alpha/model/types/Text.inc';
require_once $sysRoot.'alpha/model/types/Enum.inc';
require_once $sysRoot.'alpha/model/types/Boolean.inc';

/**
* Base business object class definition
* 
* @package Alpha Core
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
* @todo Re-write add property method!
* @todo Write increment_version method
* @todo Write set_last_query method
* @todo booleans not being set/saved correctly to db!
* @todo clone_object has bug due to complex attributes not being cloned (referenced instead!)
*  
*/
class mysql_DAO
{
	/**
	 * the object ID
	 * @var integer
	 */
	var $OID = 0;
	/**
	 * the last database query run by this object.  Useful for tracing an error.
	 * @var string
	 */
	var $last_query = "";
	/**
	 * the version number of the object, used for locking mechanism
	 * @var integer
	 */
	var $version_num = 0;

	/**
	 * populates the child object with the properties retrived from the database for the object $id
	 * @param integer $OID The id of the object to load
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function load_object($OID)
	{		
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
			if (get_class($this->$prop) != false) {	
				if (strtoupper(get_class($this->$prop)) != 'DATE') {					
					$this->$prop->set_value($row[$prop]);
				}else{
					$this->$prop->populate_from_string($row[$prop]);
				}
			}		
			if($prop == "version_num"){
				$this->version_num = $row[$prop];
			}				
		}		
		
		if(mysql_error() == '' && $this->set_enum_options()) {	
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load object, MySql error is: '.mysql_error().', query: '.$this->last_query,'load_object($OID)','framework');
			return false;
		}		
	}
	
	/**
	 * loads all of the objects of this class into an array, which is returned
	 * @param integer $start The start of the SQL LIMIT clause
	 * @return array an array containing objects of this type of business object.
	 */
	function load_all($start=0)
	{
		global $sysListPageAmount;
		
		$sql_query = "SELECT OID FROM ".$this->TABLE_NAME." ORDER BY OID LIMIT ".$start.", ".$sysListPageAmount.";";
		
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
		
		return $objects;	
	}

	/**
	 * saves the object.  If $this->OID is blank it will INSERT, otherwise UPDATE
	 * @return boolean true if saved correctly, false otherwise 
	 */
	function save_object()
	{
		// firstly we will validate the object before we try to save it
		$validate = $this->validate();
		
		if(!$validate)
			return $validate;
		
		$properties = get_class_vars(get_class($this));
		$sql_query = "";		
		
		if($this->get_version() != $this->version_num){
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not save the object as it has been updated by another user.  Please try saving again.','save_object()','warning');
			return false;
		}

		if(empty($this->OID) || !isset($this->OID)) {
			$sql_query = "INSERT INTO ".$this->TABLE_NAME." (";

			foreach(array_keys($properties) as $prop) {
				if (get_class($this->$prop) != false) {
					$sql_query .= "$prop,";
				}
				if ($prop == "version_num") {
					$sql_query .= "$prop,";
				}			
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= ") VALUES (";

			foreach(array_keys($properties) as $prop) {				
				if (get_class($this->$prop) != false) {
					$sql_query .= "'".$this->$prop->get_value()."',";
				}
				if ($prop == "version_num") {
					$this->version_num++;
					$sql_query .= "'".$this->version_num."',";
				}
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= ");";
		}else{
			$sql_query = "UPDATE ".$this->TABLE_NAME." SET ";

			foreach(array_keys($properties) as $prop) {
				if (get_class($this->$prop) != false) {
					$sql_query .= "$prop = '".$this->$prop->get_value()."',";
				}
				if ($prop == "version_num") {
					$this->version_num++;
					$sql_query .= "version_num = '".$this->version_num."',";
				}
			}

			$sql_query = rtrim($sql_query, ",");

			$sql_query .= " WHERE OID=".$this->OID.";";
		}

		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to save object, MySql error is: '.mysql_error().', query: '.$this->last_query,'save_object()','framework');
			return false;
		}
	}
	
	/**
	 * validates the object to be saved
	 * @return boolean true if the object is valid, false otherwise
	 */
	function validate() {
		$valid = true;
		
		$properties = get_object_vars($this);		
		
		foreach(array_keys($properties) as $prop) {
			if($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num" && $prop != "access_level") {
				if (strtoupper(get_class($properties[$prop])) != "ENUM") {
					if (!preg_match($this->$prop->get_rule(), $this->$prop->get_value())) {
						$error = new handle_error($_SERVER["PHP_SELF"],'Failed to save, validation error is: '.$this->$prop->get_helper(),'validate()','validation');
						$valid = false;
					}
				}
			}
		}
		return $valid;
	}
	
	/**
	 * delete the object specified from the database
	 * @return bool True if no database error occured, else false and error is stored in $this->last_query
	 */
	function delete_object() {
			
		$sql_query = "DELETE FROM ".$this->TABLE_NAME." WHERE OID = '".$this->OID."';";		
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		
		
		if(mysql_error() == '') {
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
		$sql_query = 'SELECT version_num FROM '.$this->TABLE_NAME.' WHERE OID = \''.$this->OID.'\';';
		
		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		$row = mysql_fetch_assoc($result);
		
		if (isset($row['version_num']))
			$version_num = $row['version_num'];
		else
			$version_num = 0;
		
		if (mysql_error() == '') {
			return $version_num;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to get version number for object '.$this->OID.', MySql error is: '.mysql_error(),'get_version()','framework');
			return 0;
		}
	}

	/**
	 * builds new database table for the photo class
	 * @return bool True if the table was created, false otherwise
	 */	
	function make_table()
	{
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
			if($prop != "OID" && $prop != "TABLE_NAME" && $prop != "render_mode" && $prop != "data_labels" && $prop != "last_query" && $prop != "version_num") {				
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
					default:
						$sql_query .= "";
					break;
				}				
			}
			if ($prop == "version_num") {					
				$sql_query .= "version_num INT(11) DEFAULT '1',";
			}
		}

		$sql_query .= "PRIMARY KEY (OID)) TYPE=InnoDB;";
		
		$this->last_query = $sql_query;
		
		$result = mysql_query($sql_query);

		if (mysql_error() == '') {
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
		$sql_query = "DROP TABLE IF EXISTS ".$this->TABLE_NAME.";";

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		if (mysql_error() == '') {
			$this->make_table();	
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
		$sql_query = "ALTER TABLE ".$this->TABLE_NAME." ADD ";

		if($prop != "OID" && $prop != "TABLE_NAME" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {
			//$db_type = substr($prop, 0, 3);
			$obj_type = get_class($prop);

			switch ($obj_type) {
				case "Integer":
					$sql_query .= "$prop INT(11),";
				break;
				case "Double":
					$sql_query .= "$prop DOUBLE(13,5),";
				break;
				case "String":
					$sql_query .= "$prop VARCHAR(100),";
				break;
				case "Text":
					$sql_query .= "$prop VARCHAR(255),";
				break;
				case "Boolean":
					$sql_query .= "$prop CHAR(1) DEFAULT 'N',";
				break;
				case "Enum":
					$sql_query .= "$prop ENUM(";
					$enum_vals = $this->$prop->get_options();
					foreach($enum_vals as $enum_val) {
						$sql_query .= "'".$enum_val."',";
					}
					$sql_query = rtrim($sql_query, ",");
					$sql_query .= "),";
				break;
				default:
					$sql_query .= ",";
				break;
			}
		}

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);

		if (mysql_error() == '') {			
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to add the new attribute to the table '.$this->TABLE_NAME,'add_property($prop)','framework');
			return false;
		}
	}

	/**
	 * populates the object from global POST data
	 * @requires POST data from a web form
	 */	
	function populate_from_post() {
		$properties = get_class_vars(get_class($this));
		
		foreach(array_keys($properties) as $prop) {
			if($prop != "OID" && $prop != "TABLE_NAME" && $prop != "render_mode" && $prop != "data_labels" && $prop != "last_query" && $prop != "version_num") {
				// checking to see if we need to use addslashes() on the posted data
				if(!get_magic_quotes_gpc()) {
					if (strtoupper(get_class($this->$prop)) != 'DATE') {
						$this->$prop->set_value(addslashes($_POST[$prop]));					
					}else{						
						$this->$prop->populate_from_string(addslashes($_POST[$prop]));
					}
				}else{
					if (strtoupper(get_class($this->$prop)) != 'DATE') {
						$this->$prop->set_value($_POST[$prop]);					
					}else{						
						$this->$prop->populate_from_string($_POST[$prop]);
					}
				}
			}
			if ($prop == "version_num" && isset($_POST["version_num"]))
				$this->version_num = $_POST["version_num"];
		}		
	}

	/**
	 * gets the maximum ID value from the database for this class
	 * @return integer The MAX ID
	 */
	function get_MAX() {
		$sql_query = "SELECT MAX(OID) AS max_ID FROM ".$this->TABLE_NAME;

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {			
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
		$sql_query = "SELECT COUNT(OID) AS class_count FROM ".$this->TABLE_NAME;

		$this->last_query = $sql_query;

		$result = mysql_query($sql_query);		

		$row = mysql_fetch_assoc($result);

		if (mysql_error() == '') {			
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
		return str_pad($this->OID, 11, "0", STR_PAD_LEFT);  ;
	}
	
	/**
	 * populate all of the enum options for this oject from the database
	 * @return bool true on success, false otherwise
	 */
	function set_enum_options() {
		$properties = get_object_vars($this);		
		$obj_type = '';

		foreach(array_keys($properties) as $prop) {
			if($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {

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
			return true;
		}else{			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load enum options correctly','set_enum_options()','framework');
			return false;
		}
	}
	
	/**
	 * Generic getter method for accessing class properties.  A get_value() method must exist on the attribute to use
	 * this method.
	 * @param string $prop the name of the property to get
	 * @return <variable> the value of the property
	 */
	function get($prop) {
		if(isset($this->$prop) && method_exists($this->$prop, "get_value")) {
			return $this->$prop->get_value();
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not access the property '.$prop.' on the object '.get_class($this).'.','get()','framework');
			return false;
		}
	}
	
	/**
	 * Generic setter method for setting class properties.  A set_value() method must exist on the attribute to use
	 * this method.
	 * @param string $prop the name of the property to set
	 * @param string $value value to attribute to the object property
	 */
	function set($prop, $value) {
		if(isset($this->$prop) && method_exists($this->$prop, "set_value")) {
			$this->$prop->set_value($value);
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Could not set the property '.$prop.' on the object '.get_class($this).'.  Property may not exist, or else does not have a set_value() method.','set()','framework');
		}
	}
	
	/**
	 * gets the property object rather than the value
	 * @param string $prop the name of the property to get
	 * @return <object> the property object
	 */
	function get_prop_object($prop) {
		if(isset($this->$prop)) {
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
		global $sysDB;
		
		$table_exists = false;
		
		$result = mysql_list_tables($sysDB);
		
		while ($row = mysql_fetch_row($result)) {    		
    		if ($row[0] == $this->TABLE_NAME)
    			$table_exists = true;
		}		
		
		if (mysql_error() == '') {			
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
		global $sysDB;
		
		$table_exists = $this->check_table_exists();
		
		if (!$table_exists) {
			return true;
		}else{
			$update_required = false;
			
			$match_count = 0;
			
			$result = mysql_query("SHOW COLUMNS FROM ".$this->TABLE_NAME);
			
			$properties = get_class_vars(get_class($this));			
			
			foreach(array_keys($properties) as $prop) {
				if ($prop != "TABLE_NAME" && $prop != "data_labels" && $prop != "last_query") {
				
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
		global $sysDB;
		
		$missing_fields = array();
			
		$match_count = 0;
			
		$result = mysql_query("SHOW COLUMNS FROM ".$this->TABLE_NAME);
			
		$properties = get_class_vars(get_class($this));			
			
		foreach(array_keys($properties) as $prop) {
			if ($prop != "TABLE_NAME" && $prop != "data_labels" && $prop != "last_query") {
				
				$found_match = false;	
					
				while ($row = mysql_fetch_assoc($result)) {						
		    		if ($prop == $row["Field"]) {
		    			$found_match = true;
		    			break;
		    		}else{
		    			$missing_fields[$match_count] = $properties[$prop];
		    			$match_count++;
		    		}
				}					
				mysql_data_seek($result, 0);					
			}
		}			
			
		if (mysql_error() != '') {			
			$this->last_query .= " Error: ".mysql_error();
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to access the system database correctly','check_table_needs_update()','framework');
		}
			
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
}

?>