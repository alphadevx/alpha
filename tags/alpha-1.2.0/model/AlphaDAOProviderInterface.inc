<?php

/**
 * An interface that defines all of the Data Access Object (DAO) methods that should be
 * included in a provider that implements this interface.
 * 
 * @package alpha::model
 * @since 1.1
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
interface AlphaDAOProviderInterface {	
	/**
	 * Gets the current connection singleton, or creates a new one if none exists
	 *  
	 * @return mysqli
	 * @since 1.1
	 */
	public static function getConnection();
	
	/**
	 * Disconnects the current database connection if one exists (self::$connection is set)
	 * 
	 * @since 1.1
	 */
	public static function disconnect();
	
	/**
	 * Returns the last database error string for the current connection.
	 * 
	 * @since 1.1
	 * @return string
	 */
	public static function getLastDatabaseError();
	
	/**
	 * Populates the child object with the properties retrived from the database for the object $OID.
	 * 
	 * @param integer $OID The object ID of the business object to load.
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function load($OID);
	
	/**
	 * Populates the child object from the database table by the given attribute value.
	 * 
	 * @param string $atribute The name of the attribute to load the object by.
	 * @param string $value The value of the attribute to load the object by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
	 * @param array $loadAttributes The attributes to load from the database to this object (leave blank to load all attributes)
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function loadByAttribute($attribute, $value, $ignoreClassType=false, $loadAttributes=array());
	
	/**
	 * Loads all of the objects of this class into an array which is returned.
	 * 
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
	 * @return array An array containing objects of this type of business object.
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function loadAll($start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false);
	
	/**
	 * Loads all of the objects of this class by the specified attribute into an array which is returned.
	 * 
	 * @param string $atribute The attribute to load the objects by.
	 * @param string $value The value of the attribute to load the objects by.
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type.
	 * @param array $constructorArgs An optional array of contructor arguements to pass to the BOs that will be generated and returned.  Supports a maximum of 5 arguements.
	 * @return array An array containing objects of this type of business object.
	 * @since 1.1
	 * @throws BONotFoundException
	 * @throws IllegalArguementException
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false, $constructorArgs=array());
	
	/**
	 * Loads all of the objects of this class by the specified attributes into an array which is returned.
	 * 
	 * @param array $atributes The attributes to load the objects by.
	 * @param array $values The values of the attributes to load the objects by.
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
	 * @return array An array containing objects of this type of business object.
	 * @since 1.1
	 * @throws BONotFoundException
	 * @throws IllegalArguementException
	 */
	public function loadAllByAttributes($attributes=array(), $values=array(), $start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false);
	
	/**
	 * Loads all of the objects of this class that where updated (updated_ts value) on the date indicated.
	 * 
	 * @param string $date The date for which to load the objects updated on, in the format 'YYYY-MM-DD'.	 
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
	 * @return array An array containing objects of this type of business object.
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function loadAllByDayUpdated($date, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false);
	
	/**
	 * Loads all of the specified attribute values of this class by the specified attribute into an 
	 * array which is returned. 
	 * 
	 * @param string $attribute The attribute name to load the field values by.
	 * @param string $value The value of the attribute to load the field values by.
	 * @param string $returnAttribute The name of the attribute to return.
	 * @param string $order The order to sort the BOs by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type.
	 * @return array An array of field values.
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order='ASC', $ignoreClassType=false);

	/**
	 * Saves the object.  If $this->OID is empty or null it will INSERT, otherwise UPDATE.
	 * 
	 * @since 1.1
	 * @throws FailedSaveException
	 * @throws LockingException
	 * @throws ValidationException
	 */
	public function save();
	
	/**
	 * Saves the field specified with the value supplied.  Only works for persistent BOs.  Note that no Alpha type
	 * validation is performed with this method!
	 * 
	 * @param string $attribute The name of the attribute to save.
	 * @param mixed $value The value of the attribute to save.
	 * @since 1.1
	 * @throws IllegalArguementException
	 * @throws FailedSaveException
	 */
	public function saveAttribute($attribute, $value);

        /**
         * Saves the object history to the [tablename]_history table. It always does an INSERT.
         *
         * @since 1.2
         * @throws FailedSaveException
         */
	public function saveHistory();
	
	/**
	 * Deletes the current object from the database.
	 * 
	 * @since 1.1
	 * @throws FailedDeleteException
	 */
	public function delete();
	
	/**
	 * Gets the version_num of the object from the database (returns 0 if the BO is not saved yet).
	 * 
	 * @return integer
	 * @since 1.1
	 * @throws BONotFoundException
	 */
	public function getVersion();

	/**
	 * Builds a new database table for the BO class.
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */	
	public function makeTable();

       	/**
         * Builds a new database table for the BO class to store it's history
         *
         * @since 1.2
         * @throws AlphaException
         */
        public function makeHistoryTable();

	/**
	 * Re-builds the table if the model requirements have changed.  All data is lost!
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function rebuildTable();
	
	/**
	 * Drops the table if the model requirements have changed.  All data is lost!
	 * 
	 * @since 1.1
	 * @param string $tableName Optional table name, leave blank for the defined table for this class to be dropped
	 * @throws AlphaException
	 */
	public function dropTable($tableName=null);

	/**
	 * Adds in a new class property without loosing existing data (does an ALTER TABLE query on the
	 * database).
	 * 
	 * @param string $propName The name of the new field to add to the database table.
	 * @since 1.1
	 * @throws AlphaException	 
	 */
	public function addProperty($propName);

	/**
	 * Gets the maximum OID value from the database for this class type.
	 * 
	 * @return integer The maximum OID value in the class table.
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function getMAX();
	
	/**
	 * Gets the count from the database for the amount of objects of this class.
	 * 
	 * @param array $atributes The attributes to count the objects by (optional).
	 * @param array $values The values of the attributes to count the objects by (optional).
	 * @return integer
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function getCount($attributes=array(), $values=array());

        /**
         * Gets the count from the database for the amount of entries in the [tableName]_history table for this business object.  Only call
	 * this method on classes where maintainHistory = true, otherwise an exception will be thrown.
         *
         * @return integer
         * @since 1.2
         * @throws AlphaException
         */
        public function getHistoryCount();
	
	/**
	 * Populate all of the enum options for this object from the database.
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function setEnumOptions();
	
	/**
	 * Checks to see if the table exists in the database for the current business class.
	 * 
	 * @param boolean $checkHistoryTable Set to true if you want to check for the existance of the _history table for this DAO.
	 * @return boolean
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function checkTableExists($checkHistoryTable = false);
	
	/**
	 * Static method to check the database and see if the table for the indicated BO class name
	 * exists (assumes table name will be $BOClassName less "Object").
	 * 
	 * @param string $BOClassName The name of the business object class we are checking.
	 * @param boolean $checkHistoryTable Set to true if you	want to	check for the existance	of the _history	table for this DAO.
	 * @return boolean
	 * @since 1.1
	 * @throws AlphaException
	 */
	public static function checkBOTableExists($BOClassName, $checkHistoryTable = false);
	
	/**
	 * Checks to see if the table in the database matches (for fields) the business class definition, i.e. if the
	 * database table is in sync with the class definition.
	 * 
	 * @return boolean
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function checkTableNeedsUpdate();
	
	/**
	 * Returns an array containing any properties on the class which have not been created on the database 
	 * table yet.
	 * 
	 * @return array An array of missing fields in the database table.
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function findMissingFields();
	
	/**
	 * Gets an array of all of the names of the active database indexes for this class.
	 *
	 * @return array An array of database indexes on this table.
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function getIndexes();
	
	/**
	 * Creates a foreign key constraint (index) in the database on the given attribute.
	 * 
	 * @param string $attributeName The name of the attribute to apply the index on.
	 * @param string $relatedClass The name of the related class in the format "NameObject".
	 * @param string $relatedClassAttribute The name of the field to relate to on the related class.
	 * @param bool $allowNullValues For foreign key indexes that don't allow null values, set this to false (default is true).
	 * @since 1.1
	 * @throws FailedIndexCreateException
	 */
	public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute);
	
	/**
	 * Creates a unique index in the database on the given attribute(s).
	 * 
	 * @param string $attribute1Name The first attribute to mark unique in the database.
	 * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
	 * @param string $attribute3Name The third attribute to mark unique in the databse (optional, use only for composite keys).
	 * @since 1.1
	 * @throws FailedIndexCreateException
	 */
	public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '');
	
	/**
	 * Reloads the object from the database, overwritting any attribute values in memory.
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function reload();
	
	/**
	 * Checks that a record exists for the BO in the database.
	 * 
	 * @param int $OID The Object ID of the object we want to see whether it exists or not.
	 * @return boolean
	 * @since 1.1
	 * @throws AlphaException
	 */
	public function checkRecordExists($OID);
	
	/**
	 * Checks to see if the table name matches the classname, and if not if the table
	 * name matches the classname name of another BO, i.e. the table is used to store 
	 * multiple types of BOs.
	 * 
	 * @return bool
	 * @since 1.1
	 * @throws BadBOTableNameException
	 */
	public function isTableOverloaded();
	
	/**
	 * Starts a new database transaction.
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */
	public static function begin();
	
	/**
	 * Commits the current database transaction.
	 * 
	 * @since 1.1
	 * @throws FailedSaveException
	 */
	public static function commit();
  	
	/**
	 * Aborts the current database transaction.
	 * 
	 * @since 1.1
	 * @throws AlphaException
	 */
	public static function rollback();
    
   	/**
   	 * Provide the BO that we are going to map the data to from this provider.
   	 * 
   	 * @param AlphaDAO $BO
   	 * @since 1.1
   	 */
   	public function setBO($BO);
   	
   	/**
   	 * Returns a 2d array, where each element in the array is another array
	 * representing a database row.
   	 * 
   	 * @param string $sqlQuery
   	 * @throws CustomQueryException
   	 * @return array
   	 * @since 1.1
   	 */
   	public function query($sqlQuery);
}

?>