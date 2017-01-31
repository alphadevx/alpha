<?php

namespace Alpha\Model;

use Alpha\Model\Type\Integer;
use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\Relation;
use Alpha\Model\Type\RelationLookup;
use Alpha\Model\Type\Double;
use Alpha\Model\Type\Text;
use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\Enum;
use Alpha\Model\Type\Boolean;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Exception\AlphaException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\FailedDeleteException;
use Alpha\Exception\FailedIndexCreateException;
use Alpha\Exception\LockingException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\CustomQueryException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\BadTableNameException;
use Alpha\Exception\NotImplementedException;
use Alpha\Exception\PHPException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\IllegalArguementException;
use Exception;
use SQLite3Stmt;
use SQLite3;
use ReflectionClass;

/**
 * SQLite active record provider (uses the SQLite3 native API in PHP).
 *
 * @since 1.2
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class ActiveRecordProviderSQLite implements ActiveRecordProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.2
     */
    private static $logger = null;

    /**
     * Database connection.
     *
     * @var SQLite3
     *
     * @since 1.2
     */
    private static $connection;

    /**
     * The business object that we are mapping back to.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.2
     */
    private $BO;

    /**
     * An array of new foreign keys that need to be created.
     *
     * @var array
     *
     * @since 2.0.1
     */
    private $foreignKeys = array();

    /**
     * The constructor.
     *
     * @since 1.2
     */
    public function __construct()
    {
        self::$logger = new Logger('ActiveRecordProviderSQLite');
        self::$logger->debug('>>__construct()');

        self::$logger->debug('<<__construct');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getConnection()
     */
    public static function getConnection()
    {
        $config = ConfigProvider::getInstance();

        if (!isset(self::$connection)) {
            try {
                self::$connection = new SQLite3($config->get('db.file.path'));
            } catch (\Exception $e) {
                self::$logger->fatal('Could not open SQLite database: ['.$e->getMessage().']');
            }
        }

        return self::$connection;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::disconnect()
     */
    public static function disconnect()
    {
        if (isset(self::$connection)) {
            self::$connection->close();
            self::$connection = null;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getLastDatabaseError()
     */
    public static function getLastDatabaseError()
    {
        self::$connection->lastErrorMsg();
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::query()
     */
    public function query($sqlQuery)
    {
        $this->BO->setLastQuery($sqlQuery);

        $resultArray = array();

        if (!$result = self::getConnection()->query($sqlQuery)) {
            throw new CustomQueryException('Failed to run the custom query, SQLite error is ['.self::getLastDatabaseError().'], query ['.$sqlQuery.']');
        } else {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                array_push($resultArray, $row);
            }

            return $resultArray;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::load()
     */
    public function load($OID, $version = 0)
    {
        self::$logger->debug('>>load(OID=['.$OID.'], version=['.$version.'])');

        $attributes = $this->BO->getPersistentAttributes();
        $fields = '';
        foreach ($attributes as $att) {
            $fields .= $att.',';
        }
        $fields = mb_substr($fields, 0, -1);

        if ($version > 0) {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().'_history WHERE OID = :OID AND version_num = :version LIMIT 1;';
        } else {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE OID = :OID LIMIT 1;';
        }
        $this->BO->setLastQuery($sqlQuery);

        try {
            $stmt = self::getConnection()->prepare($sqlQuery);

            $row = array();

            if ($version > 0) {
                $stmt->bindValue(':version', $version, SQLITE3_INTEGER);
            }

            $stmt->bindValue(':OID', $OID, SQLITE3_INTEGER);

            $result = $stmt->execute();

            // there should only ever be one (or none)
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $stmt->close();
        } catch (PHPException $e) {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->BO->checkTableExists()) {
                $this->BO->makeTable();

                throw new RecordNotFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->BO->getTableName().'] did not exist so had to create!');
            }

            return;
        }

        if (!isset($row['OID']) || $row['OID'] < 1) {
            self::$logger->debug('<<load');
            throw new RecordNotFoundException('Failed to load object of OID ['.$OID.'] not found in database.');
        }

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        try {
            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                // filter transient attributes
                if (!in_array($propName, $this->BO->getTransientAttributes())) {
                    $this->BO->set($propName, $row[$propName]);
                } elseif (!$propObj->isPrivate() && $this->BO->getPropObject($propName) instanceof Relation) {
                    $prop = $this->BO->getPropObject($propName);

                    // handle the setting of ONE-TO-MANY relation values
                    if ($prop->getRelationType() == 'ONE-TO-MANY') {
                        $this->BO->set($propObj->name, $this->BO->getOID());
                    }

                    // handle the setting of MANY-TO-ONE relation values
                    if ($prop->getRelationType() == 'MANY-TO-ONE' && isset($row[$propName])) {
                        $this->BO->set($propObj->name, $row[$propName]);
                    }
                }
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn('Bad data stored in the table ['.$this->BO->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
        } catch (PHPException $e) {
            // it is possible that the load failed due to the table not being up-to-date
            if ($this->BO->checkTableNeedsUpdate()) {
                $missingFields = $this->BO->findMissingFields();

                $count = count($missingFields);

                for ($i = 0; $i < $count; ++$i) {
                    $this->BO->addProperty($missingFields[$i]);
                }

                self::$logger->debug('<<load');
                throw new RecordFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->BO->getTableName().'] was out of sync with the database so had to be updated!');
            }
        }

        self::$logger->debug('<<load');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllOldVersions()
     */
    public function loadAllOldVersions($OID)
    {
        self::$logger->debug('>>loadAllOldVersions(OID=['.$OID.'])');

        if (!$this->BO->getMaintainHistory()) {
            throw new RecordFoundException('loadAllOldVersions method called on an active record where no history is maintained!');
        }

        $sqlQuery = 'SELECT version_num FROM '.$this->BO->getTableName().'_history WHERE OID = \''.$OID.'\' ORDER BY version_num;';

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllOldVersions');
            throw new RecordNotFoundException('Failed to load object versions, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->BO);

        while ($row = $result->fetchArray()) {
            try {
                $obj = new $RecordClass();
                $obj->load($OID, $row['version_num']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        self::$logger->warn('<<loadAllOldVersions ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadByAttribute()
     */
    public function loadByAttribute($attribute, $value, $ignoreClassType = false, $loadAttributes = array())
    {
        self::$logger->debug('>>loadByAttribute(attribute=['.$attribute.'], value=['.$value.'], ignoreClassType=['.$ignoreClassType.'], 
            loadAttributes=['.var_export($loadAttributes, true).'])');

        if (count($loadAttributes) == 0) {
            $attributes = $this->BO->getPersistentAttributes();
        } else {
            $attributes = $loadAttributes;
        }

        $fields = '';
        foreach ($attributes as $att) {
            $fields .= $att.',';
        }
        $fields = mb_substr($fields, 0, -1);

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = :attribute AND classname = :classname LIMIT 1;';
        } else {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = :attribute LIMIT 1;';
        }

        self::$logger->debug('Query=['.$sqlQuery.']');

        $this->BO->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            if ($this->BO->getPropObject($attribute) instanceof Integer) {
                if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                } else {
                    $stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
                }
            } else {
                if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                } else {
                    $stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                }
            }

            $result = $stmt->execute();

            // there should only ever be one (or none)
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->BO->checkTableExists()) {
                $this->BO->makeTable();

                throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
            }

            return;
        }

        if (!isset($row['OID']) || $row['OID'] < 1) {
            self::$logger->debug('<<loadByAttribute');
            throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], not found in database.');
        }

        $this->BO->setOID($row['OID']);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        try {
            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                if (isset($row[$propName])) {
                    // filter transient attributes
                    if (!in_array($propName, $this->BO->getTransientAttributes())) {
                        $this->BO->set($propName, $row[$propName]);
                    } elseif (!$propObj->isPrivate() && $this->BO->get($propName) != '' && $this->BO->getPropObject($propName) instanceof Relation) {
                        $prop = $this->BO->getPropObject($propName);

                        // handle the setting of ONE-TO-MANY relation values
                        if ($prop->getRelationType() == 'ONE-TO-MANY') {
                            $this->BO->set($propObj->name, $this->BO->getOID());
                        }
                    }
                }
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn('Bad data stored in the table ['.$this->BO->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
        } catch (PHPException $e) {
            // it is possible that the load failed due to the table not being up-to-date
            if ($this->BO->checkTableNeedsUpdate()) {
                $missingFields = $this->BO->findMissingFields();

                $count = count($missingFields);

                for ($i = 0; $i < $count; ++$i) {
                    $this->BO->addProperty($missingFields[$i]);
                }

                self::$logger->debug('<<loadByAttribute');
                throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table ['.$this->BO->getTableName().'] was out of sync with the database so had to be updated!');
            }
        }

        self::$logger->debug('<<loadByAttribute');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAll()
     */
    public function loadAll($start = 0, $limit = 0, $orderBy = 'OID', $order = 'ASC', $ignoreClassType = false)
    {
        self::$logger->debug('>>loadAll(start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        // ensure that the field name provided in the orderBy param is legit
        try {
            $this->BO->get($orderBy);
        } catch (AlphaException $e) {
            throw new AlphaException('The field name ['.$orderBy.'] provided in the param orderBy does not exist on the class ['.get_class($this->BO).']');
        }

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            if ($limit == 0) {
                $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE classname = \''.addslashes(get_class($this->BO)).'\' ORDER BY '.$orderBy.' '.$order.';';
            } else {
                $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE classname = \''.addslashes(get_class($this->BO)).'\' ORDER BY '.$orderBy.' '.$order.' LIMIT '.
                    $limit.' OFFSET '.$start.';';
            }
        } else {
            if ($limit == 0) {
                $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
            } else {
                $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$limit.' OFFSET '.$start.';';
            }
        }

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAll');
            throw new RecordNotFoundException('Failed to load object OIDs, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->BO);

        while ($row = $result->fetchArray()) {
            try {
                $obj = new $RecordClass();
                $obj->load($row['OID']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        self::$logger->debug('<<loadAll ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllByAttribute()
     */
    public function loadAllByAttribute($attribute, $value, $start = 0, $limit = 0, $orderBy = 'OID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array())
    {
        self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        if ($start != 0 && $limit != 0) {
            $limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
        } else {
            $limit = ';';
        }

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName()." WHERE $attribute = :attribute AND classname = :classname ORDER BY ".$orderBy.' '.$order.$limit;
        } else {
            $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName()." WHERE $attribute = :attribute ORDER BY ".$orderBy.' '.$order.$limit;
        }

        $this->BO->setLastQuery($sqlQuery);
        self::$logger->debug($sqlQuery);

        $stmt = self::getConnection()->prepare($sqlQuery);

        $objects = array();

        if ($stmt instanceof SQLite3Stmt) {
            if ($this->BO->getPropObject($attribute) instanceof Integer) {
                if ($this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                } else {
                    $stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
                }
            } else {
                if ($this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                } else {
                    $stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                }
            }

            $result = $stmt->execute();

            // now build an array of objects to be returned
            $count = 0;
            $RecordClass = get_class($this->BO);

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                try {
                    $argsCount = count($constructorArgs);

                    if ($argsCount < 1) {
                        $obj = new $RecordClass();
                    } else {
                        switch ($argsCount) {
                            case 1:
                                $obj = new $RecordClass($constructorArgs[0]);
                                break;
                            case 2:
                                $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1]);
                                break;
                            case 3:
                                $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2]);
                                break;
                            case 4:
                                $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2], $constructorArgs[3]);
                                break;
                            case 5:
                                $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2], $constructorArgs[3], $constructorArgs[4]);
                                break;
                            default:
                                throw new IllegalArguementException('Too many elements in the $constructorArgs array passed to the loadAllByAttribute method!');
                        }
                    }

                    $obj->load($row['OID']);
                    $objects[$count] = $obj;
                    ++$count;
                } catch (ResourceNotAllowedException $e) {
                    // the resource not allowed will be absent from the list
                }
            }

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');

            if (!$this->BO->checkTableExists()) {
                $this->BO->makeTable();

                throw new RecordFoundException('Failed to load objects by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
            }

            self::$logger->debug('<<loadAllByAttribute []');

            return array();
        }

        self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllByAttributes()
     */
    public function loadAllByAttributes($attributes = array(), $values = array(), $start = 0, $limit = 0, $orderBy = 'OID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array())
    {
        self::$logger->debug('>>loadAllByAttributes(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'], start=['.
            $start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        $whereClause = ' WHERE';

        $count = count($attributes);

        for ($i = 0; $i < $count; ++$i) {
            $whereClause .= ' '.$attributes[$i].' = :'.$attributes[$i].' AND';
            self::$logger->debug($whereClause);
        }

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            $whereClause .= ' classname = :classname AND';
        }

        // remove the last " AND"
        $whereClause = mb_substr($whereClause, 0, -4);

        if ($limit != 0) {
            $limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
        } else {
            $limit = ';';
        }

        $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().$whereClause.' ORDER BY '.$orderBy.' '.$order.$limit;

        $this->BO->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            // bind params where required attributes are provided
            if (count($attributes) > 0 && count($attributes) == count($values)) {
                for ($i = 0; $i < count($attributes); ++$i) {
                    if (strcspn($values[$i], '0123456789') != strlen($values[$i])) {
                        $stmt->bindValue(':'.$attributes[$i], $values[$i], SQLITE3_INTEGER);
                    } else {
                        $stmt->bindValue(':'.$attributes[$i], $values[$i], SQLITE3_TEXT);
                    }
                }
            } else {
                // we'll still need to bind the "classname" for overloaded BOs...
                if ($this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                }
            }

            $result = $stmt->execute();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');

            if (!$this->BO->checkTableExists()) {
                $this->BO->makeTable();

                throw new RecordFoundException('Failed to load objects by attributes ['.var_export($attributes, true).'] and values ['.
                    var_export($values, true).'], table did not exist so had to create!');
            }

            self::$logger->debug('<<loadAllByAttributes []');

            return array();
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->BO);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            try {
                $argsCount = count($constructorArgs);

                if ($argsCount < 1) {
                    $obj = new $RecordClass();
                } else {
                    switch ($argsCount) {
                        case 1:
                            $obj = new $RecordClass($constructorArgs[0]);
                            break;
                        case 2:
                            $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1]);
                            break;
                        case 3:
                            $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2]);
                            break;
                        case 4:
                            $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2], $constructorArgs[3]);
                            break;
                        case 5:
                            $obj = new $RecordClass($constructorArgs[0], $constructorArgs[1], $constructorArgs[2], $constructorArgs[3], $constructorArgs[4]);
                            break;
                        default:
                            throw new IllegalArguementException('Too many elements in the $constructorArgs array passed to the loadAllByAttribute method!');
                    }
                }

                $obj->load($row['OID']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        $stmt->close();

        self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllByDayUpdated()
     */
    public function loadAllByDayUpdated($date, $start = 0, $limit = 0, $orderBy = 'OID', $order = 'ASC', $ignoreClassType = false)
    {
        self::$logger->debug('>>loadAllByDayUpdated(date=['.$date.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if ($start != 0 && $limit != 0) {
            $limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
        } else {
            $limit = ';';
        }

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' AND classname = '".addslashes(get_class($this->BO))."' ORDER BY ".$orderBy.' '.$order.$limit;
        } else {
            $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' ORDER BY ".$orderBy.' '.$order.$limit;
        }

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllByDayUpdated');
            throw new RecordNotFoundException('Failed to load object OIDs, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->BO);

        while ($row = $result->fetchArray()) {
            $obj = new $RecordClass();
            $obj->load($row['OID']);
            $objects[$count] = $obj;
            ++$count;
        }

        self::$logger->debug('<<loadAllByDayUpdated ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllFieldValuesByAttribute()
     */
    public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order = 'ASC', $ignoreClassType = false)
    {
        self::$logger->debug('>>loadAllFieldValuesByAttribute(attribute=['.$attribute.'], value=['.$value.'], returnAttribute=['.$returnAttribute.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (!$ignoreClassType && $this->BO->isTableOverloaded()) {
            $sqlQuery = 'SELECT '.$returnAttribute.' FROM '.$this->BO->getTableName()." WHERE $attribute = '$value' AND classname = '".addslashes(get_class($this->BO))."' ORDER BY OID ".$order.';';
        } else {
            $sqlQuery = 'SELECT '.$returnAttribute.' FROM '.$this->BO->getTableName()." WHERE $attribute = '$value' ORDER BY OID ".$order.';';
        }

        $this->BO->setLastQuery($sqlQuery);

        self::$logger->debug('lastQuery ['.$sqlQuery.']');

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllFieldValuesByAttribute');
            throw new RecordNotFoundException('Failed to load field ['.$returnAttribute.'] values, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        // now build an array of attribute values to be returned
        $values = array();
        $count = 0;

        while ($row = $result->fetchArray()) {
            $values[$count] = $row[$returnAttribute];
            ++$count;
        }

        self::$logger->debug('<<loadAllFieldValuesByAttribute ['.count($values).']');

        return $values;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::save()
     */
    public function save()
    {
        self::$logger->debug('>>save()');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        if ($this->BO->getVersion() != $this->BO->getVersionNumber()->getValue()) {
            throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
        }

        // set the "updated by" fields, we can only set the user id if someone is logged in
        if ($session->get('currentUser') != null) {
            $this->BO->set('updated_by', $session->get('currentUser')->getOID());
        }

        $this->BO->set('updated_ts', new Timestamp(date('Y-m-d H:i:s')));

        // check to see if it is a transient object that needs to be inserted
        if ($this->BO->isTransient()) {
            $savedFields = array();
            $sqlQuery = 'INSERT INTO '.$this->BO->getTableName().' (';

            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->BO->getTransientAttributes())) {
                    // Skip the OID, database auto number takes care of this.
                    if ($propName != 'OID' && $propName != 'version_num') {
                        $sqlQuery .= "$propName,";
                        $savedFields[] = $propName;
                    }

                    if ($propName == 'version_num') {
                        $sqlQuery .= 'version_num,';
                        $savedFields[] = 'version_num';
                    }
                }
            }
            if ($this->BO->isTableOverloaded()) {
                $sqlQuery .= 'classname,';
            }

            $sqlQuery = rtrim($sqlQuery, ',');

            $sqlQuery .= ') VALUES (';

            foreach ($savedFields as $savedField) {
                $sqlQuery .= ':'.$savedField.',';
            }

            if ($this->BO->isTableOverloaded()) {
                $sqlQuery .= ':classname,';
            }

            $sqlQuery = rtrim($sqlQuery, ',').')';

            $this->BO->setLastQuery($sqlQuery);
            self::$logger->debug('Query ['.$sqlQuery.']');

            $stmt = self::getConnection()->prepare($sqlQuery);

            if ($stmt instanceof SQLite3Stmt) {
                foreach ($savedFields as $savedField) {
                    if ($this->BO->get($savedField) instanceof Integer) {
                        $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_INTEGER);
                    } else {
                        $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_TEXT);
                    }
                }

                if ($this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                }

                $stmt->bindValue(':version_num', 1, SQLITE3_INTEGER); // on an initial save, this will always be 1
                $this->BO->set('version_num', 1);

                try {
                    $stmt->execute();
                } catch (Exception $e) {
                    if (self::getConnection()->lastErrorCode() == 19) {
                        throw new ValidationException('Unique key violation while trying to save object, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
                    } else {
                        throw new FailedSaveException('Failed to save object, exception ['.$e->getMessage().'], DB error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
                    }
                }
            } else {
                throw new FailedSaveException('Failed to save object, exception ['.$e->getMessage().'], DB error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
            }
        } else {
            // assume that it is a persistent object that needs to be updated
            $savedFields = array();
            $sqlQuery = 'UPDATE '.$this->BO->getTableName().' SET ';

            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->BO->getTransientAttributes())) {
                    // Skip the OID, database auto number takes care of this.
                    if ($propName != 'OID' && $propName != 'version_num') {
                        $sqlQuery .= "$propName = :$propName,";
                        $savedFields[] = $propName;
                    }

                    if ($propName == 'version_num') {
                        $sqlQuery .= 'version_num = :version_num,';
                        $savedFields[] = 'version_num';
                    }
                }
            }

            if ($this->BO->isTableOverloaded()) {
                $sqlQuery .= 'classname = :classname,';
            }

            $sqlQuery = rtrim($sqlQuery, ',');

            $sqlQuery .= ' WHERE OID=:OID;';

            $this->BO->setLastQuery($sqlQuery);
            $stmt = self::getConnection()->prepare($sqlQuery);

            if ($stmt instanceof SQLite3Stmt) {
                foreach ($savedFields as $savedField) {
                    if ($this->BO->get($savedField) instanceof Integer) {
                        $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_INTEGER);
                    } else {
                        $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_TEXT);
                    }
                }

                if ($this->BO->isTableOverloaded()) {
                    $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
                }

                $stmt->bindValue(':OID', $this->BO->getOID(), SQLITE3_INTEGER);

                $temp = $this->BO->getVersionNumber()->getValue();
                $this->BO->set('version_num', $temp + 1);
                $stmt->bindValue(':version_num', $temp + 1, SQLITE3_INTEGER);

                $stmt->execute();
            } else {
                throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
            }
        }

        if ($stmt != null && $stmt != false) {
            // populate the updated OID in case we just done an insert
            if ($this->BO->isTransient()) {
                $this->BO->setOID(self::getConnection()->lastInsertRowID());
            }

            try {
                foreach ($properties as $propObj) {
                    $propName = $propObj->name;

                    if ($this->BO->getPropObject($propName) instanceof Relation) {
                        $prop = $this->BO->getPropObject($propName);

                        // handle the saving of MANY-TO-MANY relation values
                        if ($prop->getRelationType() == 'MANY-TO-MANY' && count($prop->getRelatedOIDs()) > 0) {
                            try {
                                try {
                                    // check to see if the rel is on this class
                                    $side = $prop->getSide(get_class($this->BO));
                                } catch (IllegalArguementException $iae) {
                                    $side = $prop->getSide(get_parent_class($this->BO));
                                }

                                $lookUp = $prop->getLookup();

                                // first delete all of the old RelationLookup objects for this rel
                                try {
                                    if ($side == 'left') {
                                        $lookUp->deleteAllByAttribute('leftID', $this->BO->getOID());
                                    } else {
                                        $lookUp->deleteAllByAttribute('rightID', $this->BO->getOID());
                                    }
                                } catch (Exception $e) {
                                    throw new FailedSaveException('Failed to delete old RelationLookup objects on the table ['.$prop->getLookup()->getTableName().'], error is ['.$e->getMessage().']');
                                }

                                $OIDs = $prop->getRelatedOIDs();

                                if (isset($OIDs) && !empty($OIDs[0])) {
                                    // now for each posted OID, create a new RelationLookup record and save
                                    foreach ($OIDs as $oid) {
                                        $newLookUp = new RelationLookup($lookUp->get('leftClassName'), $lookUp->get('rightClassName'));
                                        if ($side == 'left') {
                                            $newLookUp->set('leftID', $this->BO->getOID());
                                            $newLookUp->set('rightID', $oid);
                                        } else {
                                            $newLookUp->set('rightID', $this->BO->getOID());
                                            $newLookUp->set('leftID', $oid);
                                        }
                                        $newLookUp->save();
                                    }
                                }
                            } catch (Exception $e) {
                                throw new FailedSaveException('Failed to update a MANY-TO-MANY relation on the object, error is ['.$e->getMessage().']');
                            }
                        }

                        // handle the saving of ONE-TO-MANY relation values
                        if ($prop->getRelationType() == 'ONE-TO-MANY') {
                            $prop->setValue($this->BO->getOID());
                        }
                    }
                }
            } catch (Exception $e) {
                throw new FailedSaveException('Failed to save object, error is ['.$e->getMessage().']');
            }

            $stmt->close();
        } else {
            // there has been an error, so decrement the version number back
            $temp = $this->BO->getVersionNumber()->getValue();
            $this->BO->set('version_num', $temp - 1);

            throw new FailedSaveException('Failed to save object, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        if ($this->BO->getMaintainHistory()) {
            $this->BO->saveHistory();
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::saveAttribute()
     */
    public function saveAttribute($attribute, $value)
    {
        self::$logger->debug('>>saveAttribute(attribute=['.$attribute.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        if ($this->BO->getVersion() != $this->BO->getVersionNumber()->getValue()) {
            throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
        }

        // set the "updated by" fields, we can only set the user id if someone is logged in
        if ($session->get('currentUser') != null) {
            $this->BO->set('updated_by', $session->get('currentUser')->getOID());
        }

        $this->BO->set('updated_ts', new Timestamp(date('Y-m-d H:i:s')));

        // assume that it is a persistent object that needs to be updated
        $sqlQuery = 'UPDATE '.$this->BO->getTableName().' SET '.$attribute.'=:attribute, version_num=:version, updated_by=:updated_by, updated_ts=:updated_ts WHERE OID=:OID;';

        $this->BO->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->prepare($sqlQuery);

        $newVersionNumber = $this->BO->getVersionNumber()->getValue() + 1;

        if ($stmt instanceof SQLite3Stmt) {
            if ($this->BO->getPropObject($attribute) instanceof Integer) {
                $stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
            }

            $updatedBy = $this->BO->get('updated_by');
            $updatedTS = $this->BO->get('updated_ts');

            $stmt->bindValue(':version', $newVersionNumber, SQLITE3_INTEGER);
            $stmt->bindValue(':updated_by', $updatedBy, SQLITE3_INTEGER);
            $stmt->bindValue(':updated_ts', $updatedTS, SQLITE3_TEXT);
            $stmt->bindValue(':OID', $this->BO->getOID(), SQLITE3_INTEGER);

            $stmt->execute();
        } else {
            throw new FailedSaveException('Failed to save attribute, error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        $stmt->close();

        $this->BO->set($attribute, $value);
        $this->BO->set('version_num', $newVersionNumber);

        if ($this->BO->getMaintainHistory()) {
            $this->BO->saveHistory();
        }

        self::$logger->debug('<<saveAttribute');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::saveHistory()
     */
    public function saveHistory()
    {
        self::$logger->debug('>>saveHistory()');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        $savedFields = array();
        $attributeNames = array();
        $attributeValues = array();

        $sqlQuery = 'INSERT INTO '.$this->BO->getTableName().'_history (';

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->BO->getTransientAttributes())) {
                $sqlQuery .= "$propName,";
                $attributeNames[] = $propName;
                $attributeValues[] = $this->BO->get($propName);
                $savedFields[] = $propName;
            }
        }

        if ($this->BO->isTableOverloaded()) {
            $sqlQuery .= 'classname,';
        }

        $sqlQuery = rtrim($sqlQuery, ',');

        $sqlQuery .= ') VALUES (';

        foreach ($savedFields as $savedField) {
            $sqlQuery .= ':'.$savedField.',';
        }

        if ($this->BO->isTableOverloaded()) {
            $sqlQuery .= ':classname,';
        }

        $sqlQuery = rtrim($sqlQuery, ',').')';

        $this->BO->setLastQuery($sqlQuery);
        self::$logger->debug('Query ['.$sqlQuery.']');

        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            foreach ($savedFields as $savedField) {
                if ($this->BO->get($savedField) instanceof Integer) {
                    $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_INTEGER);
                } else {
                    $stmt->bindValue(':'.$savedField, $this->BO->get($savedField), SQLITE3_TEXT);
                }
            }

            if ($this->BO->isTableOverloaded()) {
                $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
            }

            $stmt->execute();
        } else {
            throw new FailedSaveException('Failed to save object history, error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::delete()
     */
    public function delete()
    {
        self::$logger->debug('>>delete()');

        $sqlQuery = 'DELETE FROM '.$this->BO->getTableName().' WHERE OID = :OID;';

        $this->BO->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            $stmt->bindValue(':OID', $this->BO->getOID(), SQLITE3_INTEGER);
            $stmt->execute();
            self::$logger->debug('Deleted the object ['.$this->BO->getOID().'] of class ['.get_class($this->BO).']');
        } else {
            throw new FailedDeleteException('Failed to delete object ['.$this->BO->getOID().'], error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
        }

        $stmt->close();

        self::$logger->debug('<<delete');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getVersion()
     */
    public function getVersion()
    {
        self::$logger->debug('>>getVersion()');

        $sqlQuery = 'SELECT version_num FROM '.$this->BO->getTableName().' WHERE OID = :OID;';
        $this->BO->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            $stmt->bindValue(':OID', $this->BO->getOID(), SQLITE3_INTEGER);

            $result = $stmt->execute();

            // there should only ever be one (or none)
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->BO->checkTableExists()) {
                $this->BO->makeTable();

                throw new RecordNotFoundException('Failed to get the version number, table did not exist so had to create!');
            }

            return;
        }

        if (!isset($row['version_num']) || $row['version_num'] < 1) {
            self::$logger->debug('<<getVersion [0]');

            return 0;
        } else {
            $version_num = $row['version_num'];

            self::$logger->debug('<<getVersion ['.$version_num.']');

            return $version_num;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::makeTable()
     */
    public function makeTable()
    {
        self::$logger->debug('>>makeTable()');

        $sqlQuery = 'CREATE TABLE '.$this->BO->getTableName().' (OID INTEGER PRIMARY KEY,';

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        $foreignKeys = array();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!in_array($propName, $this->BO->getTransientAttributes()) && $propName != 'OID') {
                $prop = $this->BO->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().') NOT NULL,';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().'),';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName REAL(".$prop->getSize(true).'),';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName TEXT(".$prop->getSize().'),';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName INTEGER(1) DEFAULT '0',";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof DEnum) {
                    $tmp = new DEnum(get_class($this->BO).'::'.$propName);
                    $sqlQuery .= "$propName INTEGER(11),";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INTEGER(11),";

                    $rel = $this->BO->getPropObject($propName);

                    $relatedField = $rel->getRelatedClassField();
                    $relatedClass = $rel->getRelatedClass();
                    $relatedBO = new $relatedClass();
                    $tableName = $relatedBO->getTableName();
                    $foreignKeys[$propName] = array($tableName, $relatedField);
                } else {
                    $sqlQuery .= '';
                }
            }
        }

        if ($this->BO->isTableOverloaded()) {
            $sqlQuery .= 'classname TEXT(100)';
        } else {
            $sqlQuery = mb_substr($sqlQuery, 0, -1);
        }

        if (count($foreignKeys) > 0) {
            foreach ($foreignKeys as $field => $related) {
                $sqlQuery .= ', FOREIGN KEY ('.$field.') REFERENCES '.$related[0].'('.$related[1].')';
            }
        }

        if (count($this->foreignKeys) > 0) {
            foreach ($this->foreignKeys as $field => $related) {
                $sqlQuery .= ', FOREIGN KEY ('.$field.') REFERENCES '.$related[0].'('.$related[1].')';
            }
        }

        $sqlQuery .= ');';

        $this->BO->setLastQuery($sqlQuery);

        if (!self::getConnection()->exec($sqlQuery)) {
            self::$logger->debug('<<makeTable');
            throw new AlphaException('Failed to create the table ['.$this->BO->getTableName().'] for the class ['.get_class($this->BO).'], database error is ['.self::getLastDatabaseError().']');
        }

        // check the table indexes if any additional ones required
        $this->checkIndexes();

        if ($this->BO->getMaintainHistory()) {
            $this->BO->makeHistoryTable();
        }

        self::$logger->debug('<<makeTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::makeHistoryTable()
     */
    public function makeHistoryTable()
    {
        self::$logger->debug('>>makeHistoryTable()');

        $sqlQuery = 'CREATE TABLE '.$this->BO->getTableName().'_history (OID INTEGER NOT NULL,';

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!in_array($propName, $this->BO->getTransientAttributes()) && $propName != 'OID') {
                $prop = $this->BO->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().') NOT NULL,';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().'),';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName REAL(".$prop->getSize(true).'),';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName TEXT(".$prop->getSize().'),';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName INTEGER(1) DEFAULT '0',";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName TEXT,";
                } elseif ($prop instanceof DEnum) {
                    $tmp = new DEnum(get_class($this->BO).'::'.$propName);
                    $sqlQuery .= "$propName INTEGER(11),";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INTEGER(11),";

                    $rel = $this->BO->getPropObject($propName);

                    $relatedField = $rel->getRelatedClassField();
                    $relatedClass = $rel->getRelatedClass();
                    $relatedBO = new $relatedClass();
                    $tableName = $relatedBO->getTableName();
                } else {
                    $sqlQuery .= '';
                }
            }
        }

        if ($this->BO->isTableOverloaded()) {
            $sqlQuery .= 'classname TEXT(100),';
        }

        $sqlQuery .= 'PRIMARY KEY (OID, version_num));';

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<makeHistoryTable');
            throw new AlphaException('Failed to create the table ['.$this->BO->getTableName().'_history] for the class ['.get_class($this->BO).'], database error is ['.self::getLastDatabaseError().']');
        }

        self::$logger->debug('<<makeHistoryTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::rebuildTable()
     */
    public function rebuildTable()
    {
        self::$logger->debug('>>rebuildTable()');

        // the use of "IF EXISTS" here requires SQLite 3.3.0 or above.
        $sqlQuery = 'DROP TABLE IF EXISTS '.$this->BO->getTableName().';';

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<rebuildTable');
            throw new AlphaException('Failed to drop the table ['.$this->BO->getTableName().'] for the class ['.get_class($this->BO).'], database error is ['.self::getLastDatabaseError().']');
        }

        $this->BO->makeTable();

        self::$logger->debug('<<rebuildTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::dropTable()
     */
    public function dropTable($tableName = null)
    {
        self::$logger->debug('>>dropTable()');

        if ($tableName === null) {
            $tableName = $this->BO->getTableName();
        }

        // the use of "IF EXISTS" here requires SQLite 3.3.0 or above.
        $sqlQuery = 'DROP TABLE IF EXISTS '.$tableName.';';

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<dropTable');
            throw new AlphaException('Failed to drop the table ['.$tableName.'] for the class ['.get_class($this->BO).'], query is ['.$this->BO->getLastQuery().']');
        }

        if ($this->BO->getMaintainHistory()) {
            $sqlQuery = 'DROP TABLE IF EXISTS '.$tableName.'_history;';

            $this->BO->setLastQuery($sqlQuery);

            if (!$result = self::getConnection()->query($sqlQuery)) {
                self::$logger->debug('<<dropTable');
                throw new AlphaException('Failed to drop the table ['.$tableName.'_history] for the class ['.get_class($this->BO).'], query is ['.$this->BO->getLastQuery().']');
            }
        }

        self::$logger->debug('<<dropTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::addProperty()
     */
    public function addProperty($propName)
    {
        self::$logger->debug('>>addProperty(propName=['.$propName.'])');

        $sqlQuery = 'ALTER TABLE '.$this->BO->getTableName().' ADD ';

        if ($this->isTableOverloaded() && $propName == 'classname') {
            $sqlQuery .= 'classname TEXT(100)';
        } else {
            if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
                $prop = $this->BO->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().') NOT NULL';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INTEGER(".$prop->getSize().')';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName REAL(".$prop->getSize(true).')';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName TEXT(".$prop->getSize().')';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName INTEGER(1) DEFAULT '0'";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName TEXT";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName TEXT";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName TEXT";
                } elseif ($prop instanceof DEnum) {
                    $tmp = new DEnum(get_class($this->BO).'::'.$propName);
                    $sqlQuery .= "$propName INTEGER(11)";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INTEGER(11)";

                    $rel = $this->BO->getPropObject($propName);

                    $relatedField = $rel->getRelatedClassField();
                    $relatedClass = $rel->getRelatedClass();
                    $relatedBO = new $relatedClass();
                    $tableName = $relatedBO->getTableName();
                } else {
                    $sqlQuery .= '';
                }
            }
        }

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<addProperty');
            throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
        } else {
            self::$logger->info('Successfully added the ['.$propName.'] column onto the ['.$this->BO->getTableName().'] table for the class ['.get_class($this->BO).']');
        }

        if ($this->BO->getMaintainHistory()) {
            $sqlQuery = str_replace($this->BO->getTableName(), $this->BO->getTableName().'_history', $sqlQuery);

            if (!$result = self::getConnection()->query($sqlQuery)) {
                self::$logger->debug('<<addProperty');
                throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->BO->getTableName().'_history], query is ['.$this->BO->getLastQuery().']');
            } else {
                self::$logger->info('Successfully added the ['.$propName.'] column onto the ['.$this->BO->getTableName().'_history] table for the class ['.get_class($this->BO).']');
            }
        }

        self::$logger->debug('<<addProperty');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getMAX()
     */
    public function getMAX()
    {
        self::$logger->debug('>>getMAX()');

        $sqlQuery = 'SELECT MAX(OID) AS max_OID FROM '.$this->BO->getTableName();

        $this->BO->setLastQuery($sqlQuery);

        try {
            $result = $this->BO->query($sqlQuery);

            $row = $result[0];

            if (isset($row['max_OID'])) {
                self::$logger->debug('<<getMAX ['.$row['max_OID'].']');

                return $row['max_OID'];
            } else {
                throw new AlphaException('Failed to get the MAX ID for the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
            }
        } catch (Exception $e) {
            self::$logger->debug('<<getMAX');
            throw new AlphaException($e->getMessage());
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getCount()
     */
    public function getCount($attributes = array(), $values = array())
    {
        self::$logger->debug('>>getCount(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'])');

        if ($this->BO->isTableOverloaded()) {
            $whereClause = ' WHERE classname = \''.addslashes(get_class($this->BO)).'\' AND';
        } else {
            $whereClause = ' WHERE';
        }

        $count = count($attributes);

        for ($i = 0; $i < $count; ++$i) {
            $whereClause .= ' '.$attributes[$i].' = \''.$values[$i].'\' AND';
            self::$logger->debug($whereClause);
        }
        // remove the last " AND"
        $whereClause = mb_substr($whereClause, 0, -4);

        if ($whereClause != ' WHERE') {
            $sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->BO->getTableName().$whereClause;
        } else {
            $sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->BO->getTableName();
        }

        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<getCount');
            throw new AlphaException('Failed to get the count for the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
        } else {
            $row = $result->fetchArray(SQLITE3_ASSOC);

            self::$logger->debug('<<getCount ['.$row['class_count'].']');

            return $row['class_count'];
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getHistoryCount()
     */
    public function getHistoryCount()
    {
        self::$logger->debug('>>getHistoryCount()');

        if (!$this->BO->getMaintainHistory()) {
            throw new AlphaException('getHistoryCount method called on a DAO where no history is maintained!');
        }

        $sqlQuery = 'SELECT COUNT(OID) AS object_count FROM '.$this->BO->getTableName().'_history WHERE OID='.$this->BO->getOID();

        $this->BO->setLastQuery($sqlQuery);
        self::$logger->debug('query ['.$sqlQuery.']');

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<getHistoryCount');
            throw new AlphaException('Failed to get the history count for the business object ['.$this->BO->getOID().'] from the table ['.$this->BO->getTableName().'_history], query is ['.$this->BO->getLastQuery().']');
        } else {
            $row = $result->fetchArray(SQLITE3_ASSOC);

            self::$logger->debug('<<getHistoryCount ['.$row['object_count'].']');

            return $row['object_count'];
        }
    }

    /**
     * Given that Enum values are not saved in the database for SQLite, an implementation is not required here.
     *
     * (non-PHPdoc)
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::setEnumOptions()
     *
     * @throws \Alpha\Exception\NotImplementedException
     */
    public function setEnumOptions()
    {
        throw new NotImplementedException('ActiveRecordProviderInterface::setEnumOptions() not implemented by the SQLite3 provider');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkTableExists()
     */
    public function checkTableExists($checkHistoryTable = false)
    {
        self::$logger->debug('>>checkTableExists(checkHistoryTable=['.$checkHistoryTable.'])');

        $tableExists = false;

        $sqlQuery = 'SELECT name FROM sqlite_master WHERE type = "table";';
        $this->BO->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        $tableName = ($checkHistoryTable ? $this->BO->getTableName().'_history' : $this->BO->getTableName());

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (strtolower($row['name']) == mb_strtolower($tableName)) {
                $tableExists = true;
            }
        }

        if ($result) {
            self::$logger->debug('<<checkTableExists ['.$tableExists.']');

            return $tableExists;
        } else {
            self::$logger->debug('<<checkTableExists');
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getLastDatabaseError().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkBOTableExists()
     */
    public static function checkBOTableExists($BOClassName, $checkHistoryTable = false)
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderSQLite');
        }
        self::$logger->debug('>>checkBOTableExists(BOClassName=['.$BOClassName.'], checkHistoryTable=['.$checkHistoryTable.'])');

        if (!class_exists($BOClassName)) {
            throw new IllegalArguementException('The classname provided ['.$checkHistoryTable.'] is not defined!');
        }

        $tableName = $BOClassName::TABLE_NAME;

        if (empty($tableName)) {
            $tableName = mb_substr($BOClassName, 0, mb_strpos($BOClassName, '_'));
        }

        if ($checkHistoryTable) {
            $tableName .= '_history';
        }

        $tableExists = false;

        $sqlQuery = 'SELECT name FROM sqlite_master WHERE type = "table";';

        $result = self::getConnection()->query($sqlQuery);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] == $tableName) {
                $tableExists = true;
            }
        }

        if ($result) {
            self::$logger->debug('<<checkBOTableExists ['.($tableExists ? 'true' : 'false').']');

            return $tableExists;
        } else {
            self::$logger->debug('<<checkBOTableExists');
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getLastDatabaseError().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkTableNeedsUpdate()
     */
    public function checkTableNeedsUpdate()
    {
        self::$logger->debug('>>checkTableNeedsUpdate()');

        if (!$this->BO->checkTableExists()) {
            return false;
        }

        $updateRequired = false;

        $matchCount = 0;

        $query = 'PRAGMA table_info('.$this->BO->getTableName().')';
        $result = self::getConnection()->query($query);
        $this->BO->setLastQuery($query);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->BO->getTransientAttributes())) {
                $foundMatch = false;

                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if ($propName == $row['name']) {
                        $foundMatch = true;
                        break;
                    }
                }

                if (!$foundMatch) {
                    --$matchCount;
                }

                $result->reset();
            }
        }

        // check for the "classname" field in overloaded tables
        if ($this->BO->isTableOverloaded()) {
            $foundMatch = false;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if ('classname' == $row['name']) {
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                --$matchCount;
            }
        }

        if ($matchCount != 0) {
            $updateRequired = true;
        }

        if (!$result) {
            self::$logger->debug('<<checkTableNeedsUpdate');
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getLastDatabaseError().']');
        } else {
            // check the table indexes
            try {
                $this->checkIndexes();
            } catch (AlphaException $ae) {
                self::$logger->warn("Error while checking database indexes:\n\n".$ae->getMessage());
            }

            self::$logger->debug('<<checkTableNeedsUpdate ['.$updateRequired.']');

            return $updateRequired;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::findMissingFields()
     */
    public function findMissingFields()
    {
        self::$logger->debug('>>findMissingFields()');

        $missingFields = array();
        $matchCount = 0;

        $sqlQuery = 'PRAGMA table_info('.$this->BO->getTableName().')';
        $result = self::getConnection()->query($sqlQuery);
        $this->BO->setLastQuery($sqlQuery);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->BO));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->BO->getTransientAttributes())) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if ($propName == $row['name']) {
                        ++$matchCount;
                        break;
                    }
                }
                $result->reset();
            } else {
                ++$matchCount;
            }

            if ($matchCount == 0) {
                array_push($missingFields, $propName);
            } else {
                $matchCount = 0;
            }
        }

        // check for the "classname" field in overloaded tables
        if ($this->BO->isTableOverloaded()) {
            $foundMatch = false;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if ('classname' == $row['name']) {
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                array_push($missingFields, 'classname');
            }
        }

        if (!$result) {
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getLastDatabaseError().']');
        }

        self::$logger->debug('<<findMissingFields ['.var_export($missingFields, true).']');

        return $missingFields;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getIndexes()
     */
    public function getIndexes()
    {
        self::$logger->debug('>>getIndexes()');

        $sqlQuery = "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='".$this->BO->getTableName()."'";

        $this->BO->setLastQuery($sqlQuery);

        $indexNames = array();

        if (!$result = self::getConnection()->query($sqlQuery)) {
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getLastDatabaseError().']');
        } else {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                array_push($indexNames, $row['name']);
            }
        }

        // in SQLite foreign keys are not stored in sqlite_master, so we have to run a different query and append the results
        $sqlQuery = 'PRAGMA foreign_key_list('.$this->BO->getTableName().')';
        
        $this->BO->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->warn('Error during pragma table foreign key lookup ['.self::getLastDatabaseError().']');
        } else {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // SQLite does not name FK indexes, so we will return a fake name based the same convention used in MySQL
                $fakeIndexName = $this->BO->getTableName().'_'.$row['from'].'_fk_idx';
                array_push($indexNames, $fakeIndexName);
            }
        }

        self::$logger->debug('<<getIndexes');

        return $indexNames;
    }

    /**
     * Checks to see if all of the indexes are in place for the BO's table, creates those that are missing.
     *
     * @since 1.2
     */
    private function checkIndexes()
    {
        self::$logger->debug('>>checkIndexes()');

        $indexNames = $this->BO->getIndexes();

        // process unique keys
        foreach ($this->BO->getUniqueAttributes() as $prop) {
            // check for composite indexes
            if (mb_strpos($prop, '+')) {
                $attributes = explode('+', $prop);

                $index_exists = false;
                foreach ($indexNames as $index) {
                    if ($attributes[0].'_'.$attributes[1].'_unq_idx' == $index) {
                        $index_exists = true;
                    }
                    if (count($attributes) == 3) {
                        if ($attributes[0].'_'.$attributes[1].'_'.$attributes[2].'_unq_idx' == $index) {
                            $index_exists = true;
                        }
                    }
                }

                if (!$index_exists) {
                    if (count($attributes) == 3) {
                        $this->BO->createUniqueIndex($attributes[0], $attributes[1], $attributes[2]);
                    } else {
                        $this->BO->createUniqueIndex($attributes[0], $attributes[1]);
                    }
                }
            } else {
                $index_exists = false;
                foreach ($indexNames as $index) {
                    if ($prop.'_unq_idx' == $index) {
                        $index_exists = true;
                    }
                }

                if (!$index_exists) {
                    $this->createUniqueIndex($prop);
                }
            }
        }

        self::$logger->debug('<<checkIndexes');
    }

    /**
     * Note that SQLite 3.6.19 is requrired for foreign key support.
     *
     * (non-PHPdoc)
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createForeignIndex()
     */
    public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute, $indexName = null)
    {
        self::$logger->info('>>createForeignIndex(attributeName=['.$attributeName.'], relatedClass=['.$relatedClass.'], relatedClassAttribute=['.$relatedClassAttribute.'], indexName=['.$indexName.']');

        /*
         * High-level approach
         *
         * 1. Rename the source table to [tablename]_temp
         * 2. Create a new [tablename] table, with the new FK in place.
         * 3. Copy all of the data from [tablename]_temp to [tablename].
         * 4. Drop [tablename]_temp.
         */
        try {
            ActiveRecord::begin($this->BO);

            // rename the table to [tablename]_temp
            $query = 'ALTER TABLE '.$this->BO->getTableName().' RENAME TO '.$this->BO->getTableName().'_temp;';
            $this->BO->setLastQuery($query);
            self::getConnection()->query($query);

            self::$logger->info('Renamed the table ['.$this->BO->getTableName().'] to ['.$this->BO->getTableName().'_temp]');

            // now create the new table with the FK in place
            $record = new $relatedClass();
            $tableName = $record->getTableName();
            $this->foreignKeys[$attributeName] = array($tableName, $relatedClassAttribute);

            $this->makeTable();

            self::$logger->info('Made a new copy of the table ['.$this->BO->getTableName().']');

            // copy all of the old data to the new table
            $query = 'INSERT INTO '.$this->BO->getTableName().' SELECT * FROM '.$this->BO->getTableName().'_temp;';
            $this->BO->setLastQuery($query);
            self::getConnection()->query($query);

            self::$logger->info('Copied all of the data from ['.$this->BO->getTableName().'] to ['.$this->BO->getTableName().'_temp]');

            // finally, drop the _temp table and commit the changes
            $this->BO->dropTable($this->BO->getTableName().'_temp');

            self::$logger->info('Dropped the table ['.$this->BO->getTableName().'_temp]');

            ActiveRecord::commit($this->BO);
        } catch (Exception $e) {
            ActiveRecord::rollback($this->BO);

            throw new FailedIndexCreateException('Failed to create the index ['.$attributeName.'] on ['.$this->BO->getTableName().'], error is ['.$e->getMessage().'], query ['.$this->BO->getLastQuery().']');
        }

        self::$logger->info('<<createForeignIndex');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createUniqueIndex()
     */
    public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '')
    {
        self::$logger->debug('>>createUniqueIndex(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'], attribute3Name=['.$attribute3Name.'])');

        $sqlQuery = '';

        if ($attribute2Name != '' && $attribute3Name != '') {
            $sqlQuery = 'CREATE UNIQUE INDEX IF NOT EXISTS '.$attribute1Name.'_'.$attribute2Name.'_'.$attribute3Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.','.$attribute2Name.','.$attribute3Name.');';
        }

        if ($attribute2Name != '' && $attribute3Name == '') {
            $sqlQuery = 'CREATE UNIQUE INDEX IF NOT EXISTS '.$attribute1Name.'_'.$attribute2Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.','.$attribute2Name.');';
        }

        if ($attribute2Name == '' && $attribute3Name == '') {
            $sqlQuery = 'CREATE UNIQUE INDEX IF NOT EXISTS '.$attribute1Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.');';
        }

        $this->BO->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        if ($result) {
            self::$logger->debug('Successfully created the unique index on ['.$this->BO->getTableName().']');
        } else {
            throw new FailedIndexCreateException('Failed to create the unique index on ['.$this->BO->getTableName().'], error is ['.self::getConnection()->lastErrorMsg().']');
        }

        self::$logger->debug('<<createUniqueIndex');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::reload()
     */
    public function reload()
    {
        self::$logger->debug('>>reload()');

        if (!$this->BO->isTransient()) {
            $this->BO->load($this->BO->getOID());
        } else {
            throw new AlphaException('Cannot reload transient object from database!');
        }

        self::$logger->debug('<<reload');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkRecordExists()
     */
    public function checkRecordExists($OID)
    {
        self::$logger->debug('>>checkRecordExists(OID=['.$OID.'])');

        $sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE OID = :OID;';
        $this->BO->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->prepare($sqlQuery);

        if ($stmt instanceof SQLite3Stmt) {
            $stmt->bindValue(':OID', $OID, SQLITE3_INTEGER);

            $result = $stmt->execute();

            // there should only ever be one (or none)
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $stmt->close();
        } else {
            self::$logger->debug('<<checkRecordExists');
            throw new AlphaException('Failed to check for the record ['.$OID.'] on the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
        }

        if (!isset($row['OID'])) {
            self::$logger->debug('<<checkRecordExists [false]');

            return false;
        } else {
            self::$logger->debug('<<checkRecordExists [true]');

            return true;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::isTableOverloaded()
     */
    public function isTableOverloaded()
    {
        self::$logger->debug('>>isTableOverloaded()');

        $reflection = new ReflectionClass($this->BO);
        $classname = $reflection->getShortName();
        $tablename = ucfirst($this->BO->getTableName());

        // use reflection to check to see if we are dealing with a persistent type (e.g. DEnum) which are never overloaded
        $implementedInterfaces = $reflection->getInterfaces();

        foreach ($implementedInterfaces as $interface) {
            if ($interface->name == 'Alpha\Model\Type\TypeInterface') {
                self::$logger->debug('<<isTableOverloaded [false]');

                return false;
            }
        }

        if ($classname != $tablename) {
            // loop over all BOs to see if there is one using the same table as this BO

            $BOclasses = ActiveRecord::getBOClassNames();

            foreach ($BOclasses as $BOclassName) {
                $reflection = new ReflectionClass($BOclassName);
                $classname = $reflection->getShortName();
                if ($tablename == $classname) {
                    self::$logger->debug('<<isTableOverloaded [true]');

                    return true;
                }
            }
            self::$logger->debug('<<isTableOverloaded');
            throw new BadTableNameException('The table name ['.$tablename.'] for the class ['.$classname.'] is invalid as it does not match a BO definition in the system!');
        } else {
            // check to see if there is already a "classname" column in the database for this BO
            $sqlQuery = 'PRAGMA table_info('.$this->BO->getTableName().')';
            $result = self::getConnection()->query($sqlQuery);
            $this->BO->setLastQuery($sqlQuery);

            if (!$result) {
                self::$logger->warn('Error during pragma table info lookup ['.self::getLastDatabaseError().']');
            } else {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if ('classname' == $row['name']) {
                        self::$logger->debug('<<isTableOverloaded [true]');

                        return true;
                    }
                }
            }

            self::$logger->debug('<<isTableOverloaded [false]');

            return false;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::begin()
     */
    public static function begin()
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderSQLite');
        }
        self::$logger->debug('>>begin()');

        if (!self::getConnection()->exec('BEGIN')) {
            throw new AlphaException('Error beginning a new transaction, error is ['.self::getLastDatabaseError().']');
        }

        self::$logger->debug('<<begin');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::commit()
     */
    public static function commit()
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderSQLite');
        }
        self::$logger->debug('>>commit()');

        if (!self::getConnection()->exec('COMMIT')) {
            throw new AlphaException('Error commiting a transaction, error is ['.self::getLastDatabaseError().']');
        }

        self::$logger->debug('<<commit');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::rollback()
     */
    public static function rollback()
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderSQLite');
        }

        self::$logger->debug('>>rollback()');

        try {
            self::getConnection()->exec('ROLLBACK');
            self::disconnect();
        } catch (Exception $e) {
            if (mb_strpos($e->getMessage(), 'cannot rollback - no transaction is active') === false) { // just filtering out errors where the rollback failed due to no current transaction
                throw new AlphaException('Error rolling back a transaction, error is ['.self::getLastDatabaseError().']');
            }
        }

        self::$logger->debug('<<rollback');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::setBO()
     */
    public function setBO($BO)
    {
        $this->BO = $BO;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkDatabaseExists()
     */
    public static function checkDatabaseExists()
    {
        $config = ConfigProvider::getInstance();

        return file_exists($config->get('db.file.path'));
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createDatabase()
     */
    public static function createDatabase()
    {
        $config = ConfigProvider::getInstance();

        if (!self::checkDatabaseExists()) {
            fopen($config->get('db.file.path'), 'x+');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::dropDatabase()
     */
    public static function dropDatabase()
    {
        $config = ConfigProvider::getInstance();

        if (self::checkDatabaseExists()) {
            unlink($config->get('db.file.path'));
        }
    }
}
