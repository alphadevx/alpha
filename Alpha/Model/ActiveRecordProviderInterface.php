<?php

namespace Alpha\Model;

/**
 * An interface that defines all of the active record methods that should be
 * included in a provider that implements this interface.
 *
 * @since 1.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
 */
interface ActiveRecordProviderInterface
{
    /**
     * Gets the current connection singleton, or creates a new one if none exists.
     *
     * @since 1.1
     */
    public static function getConnection(): \Mysqli|\SQLite3;

    /**
     * Disconnects the current database connection if one exists (self::$connection is set).
     *
     * @since 1.1
     */
    public static function disconnect(): void;

    /**
     * Returns the last database error string for the current connection.
     *
     * @since 1.1
     */
    public static function getLastDatabaseError(): string;

    /**
     * Populates the record object with the properties retrived from the database for the record $ID.
     *
     * @param int $ID     The object ID of the record to load.
     * @param int $version Optionaly, provide the version to load that version from the [tablename]_history table.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function load($ID, $version = 0): void;

    /**
     * Load all old versions (if any) of this record from the [tablename]_history table.
     *
     * @param int $ID The object ID of the record to load.
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadAllOldVersions($ID): array;

    /**
     * Populates the record object from the database table by the given attribute value.
     *
     * @param string $attribute        The name of the attribute to load the record by.
     * @param string $value           The value of the attribute to load the record by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     * @param array  $loadAttributes  The attributes to load from the database to this object (leave blank to load all attributes)
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadByAttribute($attribute, $value, $ignoreClassType = false, $loadAttributes = array()): void;

    /**
     * Loads all of the record objects of this class into an array which is returned.
     *
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of records to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the records by.
     * @param string $order           The order to sort the records by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadAll($start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array;

    /**
     * Loads all of the objects of this class by the specified attribute into an array which is returned.
     *
     * @param string $attribute        The attribute to load the objects by.
     * @param string $value           The value of the attribute to load the objects by.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of objects to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the objects by.
     * @param string $order           The order to sort the objects by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type.
     * @param string[]  $constructorArgs An optional array of contructor arguements to pass to the records that will be generated and returned.  Supports a maximum of 5 arguements.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function loadAllByAttribute($attribute, $value, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array()): array;

    /**
     * Loads all of the record objects of this class by the specified attributes into an array which is returned.
     *
     * @param array  $attributes       The attributes to load the records by.
     * @param array  $values          The values of the attributes to load the records by.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of records to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the records by.
     * @param string $order           The order to sort the records by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     * @param string[]  $constructorArgs An optional array of contructor arguements to pass to the records that will be generated and returned.  Supports a maximum of 5 arguements.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function loadAllByAttributes($attributes = array(), $values = array(), $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array()): array;

    /**
     * Loads all of the record objects of this class that where updated (updated_ts value) on the date indicated.
     *
     * @param string $date            The date for which to load the records updated on, in the format 'YYYY-MM-DD'.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of records to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the records by.
     * @param string $order           The order to sort the records by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadAllByDayUpdated($date, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array;

    /**
     * Loads all of the specified attribute values of this class by the specified attribute into an
     * array which is returned.
     *
     * @param string $attribute       The attribute name to load the field values by.
     * @param string $value           The value of the attribute to load the field values by.
     * @param string $returnAttribute The name of the attribute to return.
     * @param string $order           The order to sort the records by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order = 'ASC', $ignoreClassType = false): array;

    /**
     * Saves the record.  If $this->ID is empty or null it will INSERT, otherwise UPDATE.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\FailedSaveException
     * @throws \Alpha\Exception\LockingException
     * @throws \Alpha\Exception\ValidationException
     */
    public function save(): void;

    /**
     * Saves the field specified with the value supplied.  Only works for persistent records.  Note that no Alpha type
     * validation is performed with this method!
     *
     * @param string $attribute The name of the attribute to save.
     * @param mixed  $value     The value of the attribute to save.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\FailedSaveException
     * @throws \Alpha\Exception\LockingException
     */
    public function saveAttribute($attribute, $value): void;

    /**
     * Saves the object history to the [tablename]_history table. It always does an INSERT.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\FailedSaveException
     */
    public function saveHistory(): void;

    /**
     * Deletes the current object from the database.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\FailedDeleteException
     */
    public function delete(): void;

    /**
     * Gets the version_num of the object from the database (returns 0 if the Record is not saved yet).
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function getVersion(): int;

    /**
     * Builds a new database table for the Record class.
     *
     * @since 1.1
     *
     * @param bool $checkIndexes Set to false if you do not want to check for any additional required indexes while creating the table (default is true).
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function makeTable($checkIndexes = true): void;

    /**
     * Builds a new database table for the Record class to store it's history.
     *
     * @since 1.2
     *
     * @throws \AlphaException
     */
    public function makeHistoryTable(): void;

    /**
     * Re-builds the table if the model requirements have changed.  All data is lost!
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function rebuildTable(): void;

    /**
     * Drops the table if the model requirements have changed.  All data is lost!
     *
     * @since 1.1
     *
     * @param string $tableName Optional table name, leave blank for the defined table for this class to be dropped
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function dropTable($tableName = null): void;

    /**
     * Adds in a new class property without loosing existing data (does an ALTER TABLE query on the
     * database).
     *
     * @param string $propName The name of the new field to add to the database table.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function addProperty($propName): void;

    /**
     * Gets the maximum ID value from the database for this class type.
     *
     * @return int The maximum ID value in the class table.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getMAX();

    /**
     * Gets the count from the database for the amount of objects of this class.
     *
     * @param array $attributes The attributes to count the objects by (optional).
     * @param array $values    The values of the attributes to count the objects by (optional).
     *
     * @return int
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getCount($attributes = array(), $values = array());

    /**
     * Gets the count from the database for the amount of entries in the [tableName]_history table for this business object.  Only call
     * this method on classes where maintainHistory = true, otherwise an exception will be thrown.
     *
     * @return int
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getHistoryCount();

    /**
     * Populate all of the enum options for this object from the database.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     * @return void
     */
    public function setEnumOptions();

    /**
     * Checks to see if the table exists in the database for the current business class.
     *
     * @param bool $checkHistoryTable Set to true if you want to check for the existance of the _history table for this DAO.
     *
     * @return bool
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkTableExists($checkHistoryTable = false);

    /**
     * Static method to check the database and see if the table for the indicated Record class name
     * exists (assumes table name will be $RecordClassName less "Object").
     *
     * @param string $RecordClassName       The name of the business object class we are checking.
     * @param bool   $checkHistoryTable Set to true if you	want to	check for the existance	of the _history	table for this DAO.
     *
     * @return bool
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public static function checkRecordTableExists($RecordClassName, $checkHistoryTable = false);

    /**
     * Checks to see if the table in the database matches (for fields) the business class definition, i.e. if the
     * database table is in sync with the class definition.
     *
     * @return bool
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkTableNeedsUpdate();

    /**
     * Returns an array containing any properties on the class which have not been created on the database
     * table yet.
     *
     * @return array An array of missing fields in the database table.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function findMissingFields();

    /**
     * Gets an array of all of the names of the active database indexes for this class.
     *
     * @return array An array of database indexes on this table.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getIndexes();

    /**
     * Creates a foreign key constraint (index) in the database on the given attribute.
     *
     * @param string $attributeName         The name of the attribute to apply the index on.
     * @param string $relatedClass          The fully-qualified name of the related class.
     * @param string $relatedClassAttribute The name of the field to relate to on the related class.
     * @param string $indexName             The optional name for the index, will calculate if not provided.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\FailedIndexCreateException
     * @return void
     */
    public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute, $indexName = null);

    /**
     * Creates a unique index in the database on the given attribute(s).
     *
     * @param string $attribute1Name The first attribute to mark unique in the database.
     * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
     * @param string $attribute3Name The third attribute to mark unique in the databse (optional, use only for composite keys).
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\FailedIndexCreateException
     * @return void
     */
    public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '');

    /**
     * Reloads the object from the database, overwritting any attribute values in memory.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     * @return void
     */
    public function reload();

    /**
     * Checks that a record exists for the Record in the database.
     *
     * @param int $ID The Object ID of the object we want to see whether it exists or not.
     *
     * @return bool
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkRecordExists($ID);

    /**
     * Checks to see if the table name matches the classname, and if not if the table
     * name matches the classname name of another record, i.e. the table is used to store
     * multiple types of records.
     *
     * @return bool
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\BadTableNameException
     */
    public function isTableOverloaded();

    /**
     * Starts a new database transaction.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     * @return void
     */
    public static function begin();

    /**
     * Commits the current database transaction.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\FailedSaveException
     * @return void
     */
    public static function commit();

    /**
     * Aborts the current database transaction.
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\AlphaException
     * @return void
     */
    public static function rollback();

    /**
     * Provide the Record that we are going to map the data to from this provider.
     *
     * @param \Alpha\Model\ActiveRecord $Record
     *
     * @since 1.1
     * @return void
     */
    public function setRecord($Record);

    /**
     * Returns a 2d array, where each element in the array is another array
     * representing a database row.
     *
     * @param string $sqlQuery
     *
     * @throws \Alpha\Exception\CustomQueryException
     *
     * @return array
     *
     * @since 1.1
     */
    public function query($sqlQuery);

    /**
     * Check to see if the configured database exists.
     *
     * @return bool
     *
     * @since 2.0
     */
    public static function checkDatabaseExists();

    /**
     * Creates the configured database.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 2.0
     * @return void
     */
    public static function createDatabase();

    /**
     * Drops the configured database.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 2.0
     * @return void
     */
    public static function dropDatabase();
}
