<?php

namespace Alpha\Model;

use Alpha\Model\Type\Integer;
use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\Relation;
use Alpha\Model\Type\RelationLookup;
use Alpha\Model\Type\Double;
use Alpha\Model\Type\Text;
use Alpha\Model\Type\LargeText;
use Alpha\Model\Type\HugeText;
use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\Enum;
use Alpha\Model\Type\Boolean;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Exception\AlphaException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\FailedDeleteException;
use Alpha\Exception\FailedIndexCreateException;
use Alpha\Exception\LockingException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\CustomQueryException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\BadTableNameException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\PHPException;
use Exception;
use ReflectionClass;
use Mysqli;

/**
 * MySQL active record provider (uses the MySQLi native API in PHP).
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
class ActiveRecordProviderMySQL implements ActiveRecordProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.1
     */
    private static $logger = null;

    /**
     * Datebase connection.
     *
     * @var Mysqli
     *
     * @since 1.1
     */
    private static $connection;

    /**
     * The business object that we are mapping back to.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.1
     */
    private $record;

    /**
     * The constructor.
     *
     * @since 1.1
     */
    public function __construct()
    {
        self::$logger = new Logger('ActiveRecordProviderMySQL');
        self::$logger->debug('>>__construct()');

        self::$logger->debug('<<__construct');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getConnection()
     */
    public static function getConnection(): \Mysqli
    {
        $config = ConfigProvider::getInstance();

        if (!isset(self::$connection)) {
            try {
                self::$connection = new Mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'), $config->get('db.name'));
            } catch (\Exception $e) {
                // if we failed to connect because the database does not exist, create it and try again
                if (strpos($e->getMessage(), 'HY000/1049') !== false) {
                    self::createDatabase();
                    self::$connection = new Mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'), $config->get('db.name'));
                }
            }

            self::$connection->set_charset('utf8');

            if (mysqli_connect_error()) {
                self::$logger->fatal('Could not connect to database: ['.mysqli_connect_errno().'] '.mysqli_connect_error());
            }
        }

        return self::$connection;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::disconnect()
     */
    public static function disconnect(): void
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
    public static function getLastDatabaseError(): string
    {
        return self::getConnection()->error;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::query()
     */
    public function query($sqlQuery)
    {
        $this->record->setLastQuery($sqlQuery);

        $resultArray = array();

        if (!$result = self::getConnection()->query($sqlQuery)) {
            throw new CustomQueryException('Failed to run the custom query, MySql error is ['.self::getConnection()->error.'], query ['.$sqlQuery.']');
        } else {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
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
    public function load($ID, $version = 0): void
    {
        self::$logger->debug('>>load(ID=['.$ID.'], version=['.$version.'])');

        $attributes = $this->record->getPersistentAttributes();
        $fields = '';
        foreach ($attributes as $att) {
            $fields .= $att.',';
        }
        $fields = mb_substr($fields, 0, -1);

        if ($version > 0) {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->record->getTableName().'_history WHERE ID = ? AND version_num = ? LIMIT 1;';
        } else {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->record->getTableName().' WHERE ID = ? LIMIT 1;';
        }
        $this->record->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->stmt_init();

        $row = array();

        if ($stmt->prepare($sqlQuery)) {
            if ($version > 0) {
                $stmt->bind_param('ii', $ID, $version);
            } else {
                $stmt->bind_param('i', $ID);
            }

            $stmt->execute();

            $result = $this->bindResult($stmt);
            if (isset($result[0])) {
                $row = $result[0];
            }

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.'], ID is ['.print_r($ID, true).'], MySql error is ['.self::getConnection()->error.']');
            if (!$this->record->checkTableExists()) {
                $this->record->makeTable();

                throw new RecordNotFoundException('Failed to load object of ID ['.$ID.'], table ['.$this->record->getTableName().'] did not exist so had to create!');
            }

            return;
        }

        if (!isset($row['ID']) || $row['ID'] < 1) {
            self::$logger->debug('<<load');
            throw new RecordNotFoundException('Failed to load object of ID ['.$ID.'] not found in database.');
        }

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        try {
            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                // filter transient attributes
                if (!in_array($propName, $this->record->getTransientAttributes())) {
                    $this->record->set($propName, $row[$propName]);
                } elseif (!$propObj->isPrivate() && $this->record->getPropObject($propName) instanceof Relation) {
                    $prop = $this->record->getPropObject($propName);

                    // handle the setting of ONE-TO-MANY relation values
                    if ($prop->getRelationType() == 'ONE-TO-MANY') {
                        $this->record->set($propObj->name, $this->record->getID());
                    }

                    // handle the setting of MANY-TO-ONE relation values
                    if ($prop->getRelationType() == 'MANY-TO-ONE' && isset($row[$propName])) {
                        $this->record->set($propObj->name, $row[$propName]);
                    }
                }
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn('Bad data stored in the table ['.$this->record->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
        } catch (PHPException $e) {
            // it is possible that the load failed due to the table not being up-to-date
            if ($this->record->checkTableNeedsUpdate()) {
                $missingFields = $this->record->findMissingFields();

                $count = count($missingFields);

                for ($i = 0; $i < $count; ++$i) {
                    $this->record->addProperty($missingFields[$i]);
                }

                self::$logger->warn('<<load');
                throw new RecordNotFoundException('Failed to load object of ID ['.$ID.'], table ['.$this->record->getTableName().'] was out of sync with the database so had to be updated!');
            }
        }

        self::$logger->debug('<<load ['.$ID.']');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllOldVersions()
     */
    public function loadAllOldVersions($ID): array
    {
        self::$logger->debug('>>loadAllOldVersions(ID=['.$ID.'])');

        if (!$this->record->getMaintainHistory()) {
            throw new RecordFoundException('loadAllOldVersions method called on an active record where no history is maintained!');
        }

        $sqlQuery = 'SELECT version_num FROM '.$this->record->getTableName().'_history WHERE ID = \''.$ID.'\' ORDER BY version_num;';

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllOldVersions [0]');
            throw new RecordNotFoundException('Failed to load object versions, MySQL error is ['.self::getLastDatabaseError().'], query ['.$this->record->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->record);

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            try {
                $obj = new $RecordClass();
                $obj->load($ID, $row['version_num']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        self::$logger->debug('<<loadAllOldVersions ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadByAttribute()
     */
    public function loadByAttribute($attribute, $value, $ignoreClassType = false, $loadAttributes = array()): void
    {
        self::$logger->debug('>>loadByAttribute(attribute=['.$attribute.'], value=['.$value.'], ignoreClassType=['.$ignoreClassType.'],
			loadAttributes=['.var_export($loadAttributes, true).'])');

        if (count($loadAttributes) == 0) {
            $attributes = $this->record->getPersistentAttributes();
        } else {
            $attributes = $loadAttributes;
        }

        $fields = '';
        foreach ($attributes as $att) {
            $fields .= $att.',';
        }
        $fields = mb_substr($fields, 0, -1);

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->record->getTableName().' WHERE '.$attribute.' = ? AND classname = ? LIMIT 1;';
        } else {
            $sqlQuery = 'SELECT '.$fields.' FROM '.$this->record->getTableName().' WHERE '.$attribute.' = ? LIMIT 1;';
        }

        self::$logger->debug('Query=['.$sqlQuery.']');

        $this->record->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->stmt_init();

        $row = array();

        if ($stmt->prepare($sqlQuery)) {
            if ($this->record->getPropObject($attribute) instanceof Integer) {
                if (!$ignoreClassType && $this->record->isTableOverloaded()) {
                    $classname = get_class($this->record);
                    $stmt->bind_param('is', $value, $classname);
                } else {
                    $stmt->bind_param('i', $value);
                }
            } else {
                if (!$ignoreClassType && $this->record->isTableOverloaded()) {
                    $classname = get_class($this->record);
                    $stmt->bind_param('ss', $value, $classname);
                } else {
                    $stmt->bind_param('s', $value);
                }
            }

            $stmt->execute();

            $result = $this->bindResult($stmt);

            if (isset($result[0])) {
                $row = $result[0];
            }

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->record->checkTableExists()) {
                $this->record->makeTable();

                throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
            }

            return;
        }

        if (!isset($row['ID']) || $row['ID'] < 1) {
            self::$logger->debug('<<loadByAttribute');
            throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], not found in database.');
        }

        $this->record->setID($row['ID']);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        try {
            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                if (isset($row[$propName])) {
                    // filter transient attributes
                    if (!in_array($propName, $this->record->getTransientAttributes())) {
                        $this->record->set($propName, $row[$propName]);
                    } elseif (!$propObj->isPrivate() && $this->record->get($propName) != '' && $this->record->getPropObject($propName) instanceof Relation) {
                        $prop = $this->record->getPropObject($propName);

                        // handle the setting of ONE-TO-MANY relation values
                        if ($prop->getRelationType() == 'ONE-TO-MANY') {
                            $this->record->set($propObj->name, $this->record->getID());
                        }
                    }
                }
            }
        } catch (IllegalArguementException $e) {
            self::$logger->warn('Bad data stored in the table ['.$this->record->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
        } catch (PHPException $e) {
            // it is possible that the load failed due to the table not being up-to-date
            if ($this->record->checkTableNeedsUpdate()) {
                $missingFields = $this->record->findMissingFields();

                $count = count($missingFields);

                for ($i = 0; $i < $count; ++$i) {
                    $this->record->addProperty($missingFields[$i]);
                }

                self::$logger->debug('<<loadByAttribute');
                throw new RecordNotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table ['.$this->record->getTableName().'] was out of sync with the database so had to be updated!');
            }
        }

        self::$logger->debug('<<loadByAttribute');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAll()
     */
    public function loadAll($start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAll(start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        // ensure that the field name provided in the orderBy param is legit
        try {
            $this->record->get($orderBy);
        } catch (AlphaException $e) {
            throw new AlphaException('The field name ['.$orderBy.'] provided in the param orderBy does not exist on the class ['.get_class($this->record).']');
        }

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            if ($limit == 0) {
                $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().' WHERE classname = \''.addslashes(get_class($this->record)).'\' ORDER BY '.$orderBy.' '.$order.';';
            } else {
                $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().' WHERE classname = \''.addslashes(get_class($this->record)).'\' ORDER BY '.$orderBy.' '.$order.' LIMIT '.
                    $start.', '.$limit.';';
            }
        } else {
            if ($limit == 0) {
                $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
            } else {
                $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$start.', '.$limit.';';
            }
        }

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAll [0]');
            throw new RecordNotFoundException('Failed to load object IDs, MySql error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->record);

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            try {
                $obj = new $RecordClass();
                $obj->load($row['ID']);
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
    public function loadAllByAttribute($attribute, $value, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array()): array
    {
        self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        if ($limit != 0) {
            $limit = ' LIMIT '.$start.', '.$limit.';';
        } else {
            $limit = ';';
        }

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName()." WHERE $attribute = ? AND classname = ? ORDER BY ".$orderBy.' '.$order.$limit;
        } else {
            $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName()." WHERE $attribute = ? ORDER BY ".$orderBy.' '.$order.$limit;
        }

        $this->record->setLastQuery($sqlQuery);
        self::$logger->debug($sqlQuery);

        $stmt = self::getConnection()->stmt_init();

        $row = array();

        if ($stmt->prepare($sqlQuery)) {
            if ($this->record->getPropObject($attribute) instanceof Integer) {
                if ($this->record->isTableOverloaded()) {
                    $classname = get_class($this->record);
                    $stmt->bind_param('is', $value, $classname);
                } else {
                    $stmt->bind_param('i', $value);
                }
            } else {
                if ($this->record->isTableOverloaded()) {
                    $classname = get_class($this->record);
                    $stmt->bind_param('ss', $value, $classname);
                } else {
                    $stmt->bind_param('s', $value);
                }
            }

            $stmt->execute();

            $result = $this->bindResult($stmt);

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->record->checkTableExists()) {
                $this->record->makeTable();

                throw new RecordNotFoundException('Failed to load objects by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
            }
            self::$logger->debug('<<loadAllByAttribute []');

            return array();
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->record);

        foreach ($result as $row) {
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

                $obj->load($row['ID']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllByAttributes()
     */
    public function loadAllByAttributes($attributes = array(), $values = array(), $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array()): array
    {
        self::$logger->debug('>>loadAllByAttributes(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'], start=['.
            $start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        $whereClause = ' WHERE';

        $count = count($attributes);

        for ($i = 0; $i < $count; ++$i) {
            $whereClause .= ' '.$attributes[$i].' = ? AND';
            self::$logger->debug($whereClause);
        }

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            $whereClause .= ' classname = ? AND';
        }

        // remove the last " AND"
        $whereClause = mb_substr($whereClause, 0, -4);

        if ($limit != 0) {
            $limit = ' LIMIT '.$start.', '.$limit.';';
        } else {
            $limit = ';';
        }

        $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().$whereClause.' ORDER BY '.$orderBy.' '.$order.$limit;

        $this->record->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->stmt_init();

        if ($stmt->prepare($sqlQuery)) {
            // bind params where required attributes are provided
            if (count($attributes) > 0 && count($attributes) == count($values)) {
                $stmt = $this->bindParams($stmt, $attributes, $values);
            } else {
                // we'll still need to bind the "classname" for overloaded records...
                if ($this->record->isTableOverloaded()) {
                    $classname = get_class($this->record);
                    $stmt->bind_param('s', $classname);
                }
            }
            $stmt->execute();

            $result = $this->bindResult($stmt);

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');

            if (!$this->record->checkTableExists()) {
                $this->record->makeTable();

                throw new RecordNotFoundException('Failed to load objects by attributes ['.var_export($attributes, true).'] and values ['.
                    var_export($values, true).'], table did not exist so had to create!');
            }

            self::$logger->debug('<<loadAllByAttributes []');

            return array();
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->record);

        foreach ($result as $row) {
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

                $obj->load($row['ID']);
                $objects[$count] = $obj;
                ++$count;
            } catch (ResourceNotAllowedException $e) {
                // the resource not allowed will be absent from the list
            }
        }

        self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');

        return $objects;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::loadAllByDayUpdated()
     */
    public function loadAllByDayUpdated($date, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAllByDayUpdated(date=['.$date.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if ($start != 0 && $limit != 0) {
            $limit = ' LIMIT '.$start.', '.$limit.';';
        } else {
            $limit = ';';
        }

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' AND classname = '".addslashes(get_class($this->record))."' ORDER BY ".$orderBy.' '.$order.$limit;
        } else {
            $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' ORDER BY ".$orderBy.' '.$order.$limit;
        }

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllByDayUpdated []');
            throw new RecordNotFoundException('Failed to load object IDs, MySql error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
        }

        // now build an array of objects to be returned
        $objects = array();
        $count = 0;
        $RecordClass = get_class($this->record);

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $obj = new $RecordClass();
            $obj->load($row['ID']);
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
    public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAllFieldValuesByAttribute(attribute=['.$attribute.'], value=['.$value.'], returnAttribute=['.$returnAttribute.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (!$ignoreClassType && $this->record->isTableOverloaded()) {
            $sqlQuery = 'SELECT '.$returnAttribute.' FROM '.$this->record->getTableName()." WHERE $attribute = '$value' AND classname = '".addslashes(get_class($this->record))."' ORDER BY ID ".$order.';';
        } else {
            $sqlQuery = 'SELECT '.$returnAttribute.' FROM '.$this->record->getTableName()." WHERE $attribute = '$value' ORDER BY ID ".$order.';';
        }

        $this->record->setLastQuery($sqlQuery);

        self::$logger->debug('lastQuery ['.$sqlQuery.']');

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<loadAllFieldValuesByAttribute []');
            throw new RecordNotFoundException('Failed to load field ['.$returnAttribute.'] values, MySql error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
        }

        // now build an array of attribute values to be returned
        $values = array();
        $count = 0;

        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
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
    public function save(): void
    {
        self::$logger->debug('>>save()');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        // check to see if it is a transient object that needs to be inserted
        if ($this->record->isTransient()) {
            $savedFieldsCount = 0;
            $sqlQuery = 'INSERT INTO '.$this->record->getTableName().' (';

            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->record->getTransientAttributes())) {
                    // Skip the ID, database auto number takes care of this.
                    if ($propName != 'ID' && $propName != 'version_num') {
                        $sqlQuery .= "$propName,";
                        ++$savedFieldsCount;
                    }

                    if ($propName == 'version_num') {
                        $sqlQuery .= 'version_num,';
                        ++$savedFieldsCount;
                    }
                }
            }

            if ($this->record->isTableOverloaded()) {
                $sqlQuery .= 'classname,';
            }

            $sqlQuery = rtrim($sqlQuery, ',');

            $sqlQuery .= ') VALUES (';

            for ($i = 0; $i < $savedFieldsCount; ++$i) {
                $sqlQuery .= '?,';
            }

            if ($this->record->isTableOverloaded()) {
                $sqlQuery .= '?,';
            }

            $sqlQuery = rtrim($sqlQuery, ',').')';

            $this->record->setLastQuery($sqlQuery);
            self::$logger->debug('Query ['.$sqlQuery.']');

            $stmt = self::getConnection()->stmt_init();

            if ($stmt->prepare($sqlQuery)) {
                $stmt = $this->bindParams($stmt);
                $stmt->execute();
            } else {
                throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->record->getLastQuery().']');
            }
        } else {
            // assume that it is a persistent object that needs to be updated
            $savedFieldsCount = 0;
            $sqlQuery = 'UPDATE '.$this->record->getTableName().' SET ';

            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->record->getTransientAttributes())) {
                    // Skip the ID, database auto number takes care of this.
                    if ($propName != 'ID' && $propName != 'version_num') {
                        $sqlQuery .= "$propName = ?,";
                        ++$savedFieldsCount;
                    }

                    if ($propName == 'version_num') {
                        $sqlQuery .= 'version_num = ?,';
                        ++$savedFieldsCount;
                    }
                }
            }

            if ($this->record->isTableOverloaded()) {
                $sqlQuery .= 'classname = ?,';
            }

            $sqlQuery = rtrim($sqlQuery, ',');

            $sqlQuery .= ' WHERE ID = ?;';

            $this->record->setLastQuery($sqlQuery);
            $stmt = self::getConnection()->stmt_init();

            if ($stmt->prepare($sqlQuery)) {
                $this->bindParams($stmt);
                $stmt->execute();
            } else {
                throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->record->getLastQuery().']');
            }
        }

        if ($stmt != null && $stmt->error == '') {
            // populate the updated ID in case we just done an insert
            if ($this->record->isTransient()) {
                $this->record->setID(self::getConnection()->insert_id);
            }

            $this->record->saveRelations();

            $stmt->close();
        } else {
            // there has been an error, so decrement the version number back
            $temp = $this->record->getVersionNumber()->getValue();
            $this->record->set('version_num', $temp-1);

            // check for unique violations
            if (self::getConnection()->errno == '1062') {
                throw new ValidationException('Failed to save, the value '.$this->findOffendingValue(self::getConnection()->error).' is already in use!');
            } else {
                throw new FailedSaveException('Failed to save object, MySql error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
            }
        }

        if ($this->record->getMaintainHistory()) {
            $this->record->saveHistory();
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::saveAttribute()
     */
    public function saveAttribute($attribute, $value): void
    {
        self::$logger->debug('>>saveAttribute(attribute=['.$attribute.'], value=['.$value.'])');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($this->record->getVersion() != $this->record->getVersionNumber()->getValue()) {
            throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
        }

        // set the "updated by" fields, we can only set the user id if someone is logged in
        if ($session->get('currentUser') != null) {
            $this->record->set('updated_by', $session->get('currentUser')->getID());
        }

        $this->record->set('updated_ts', new Timestamp(date('Y-m-d H:i:s')));

        // assume that it is a persistent object that needs to be updated
        $sqlQuery = 'UPDATE '.$this->record->getTableName().' SET '.$attribute.' = ?, version_num = ? , updated_by = ?, updated_ts = ? WHERE ID = ?;';

        $this->record->setLastQuery($sqlQuery);
        $stmt = self::getConnection()->stmt_init();

        $newVersionNumber = $this->record->getVersionNumber()->getValue()+1;

        if ($stmt->prepare($sqlQuery)) {
            if ($this->record->getPropObject($attribute) instanceof Integer) {
                $bindingsType = 'i';
            } else {
                $bindingsType = 's';
            }
            $ID = $this->record->getID();
            $updatedBy = $this->record->get('updated_by');
            $updatedTS = $this->record->get('updated_ts');
            $stmt->bind_param($bindingsType.'iisi', $value, $newVersionNumber, $updatedBy, $updatedTS, $ID);
            self::$logger->debug('Binding params ['.$bindingsType.'iisi, '.$value.', '.$newVersionNumber.', '.$updatedBy.', '.$updatedTS.', '.$ID.']');
            $stmt->execute();
        } else {
            throw new FailedSaveException('Failed to save attribute, error is ['.$stmt->error.'], query ['.$this->record->getLastQuery().']');
        }

        $stmt->close();

        $this->record->set($attribute, $value);
        $this->record->set('version_num', $newVersionNumber);

        if ($this->record->getMaintainHistory()) {
            $this->record->saveHistory();
        }

        self::$logger->debug('<<saveAttribute');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::saveHistory()
     */
    public function saveHistory(): void
    {
        self::$logger->debug('>>saveHistory()');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        $savedFieldsCount = 0;
        $attributeNames = array();
        $attributeValues = array();

        $sqlQuery = 'INSERT INTO '.$this->record->getTableName().'_history (';

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->record->getTransientAttributes())) {
                $sqlQuery .= "$propName,";
                $attributeNames[] = $propName;
                $attributeValues[] = $this->record->get($propName);
                ++$savedFieldsCount;
            }
        }

        if ($this->record->isTableOverloaded()) {
            $sqlQuery .= 'classname,';
        }

        $sqlQuery = rtrim($sqlQuery, ',');

        $sqlQuery .= ') VALUES (';

        for ($i = 0; $i < $savedFieldsCount; ++$i) {
            $sqlQuery .= '?,';
        }

        if ($this->record->isTableOverloaded()) {
            $sqlQuery .= '?,';
        }

        $sqlQuery = rtrim($sqlQuery, ',').')';

        $this->record->setLastQuery($sqlQuery);
        self::$logger->debug('Query ['.$sqlQuery.']');

        $stmt = self::getConnection()->stmt_init();

        if ($stmt->prepare($sqlQuery)) {
            $stmt = $this->bindParams($stmt, $attributeNames, $attributeValues);
            $stmt->execute();
        } else {
            throw new FailedSaveException('Failed to save object history, error is ['.$stmt->error.'], query ['.$this->record->getLastQuery().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::delete()
     */
    public function delete(): void
    {
        self::$logger->debug('>>delete()');

        $sqlQuery = 'DELETE FROM '.$this->record->getTableName().' WHERE ID = ?;';

        $this->record->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->stmt_init();

        if ($stmt->prepare($sqlQuery)) {
            $ID = $this->record->getID();
            $stmt->bind_param('i', $ID);
            $stmt->execute();
            self::$logger->debug('Deleted the object ['.$this->record->getID().'] of class ['.get_class($this->record).']');
        } else {
            throw new FailedDeleteException('Failed to delete object ['.$this->record->getID().'], error is ['.$stmt->error.'], query ['.$this->record->getLastQuery().']');
        }

        $stmt->close();

        self::$logger->debug('<<delete');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getVersion()
     */
    public function getVersion(): int
    {
        self::$logger->debug('>>getVersion()');

        $sqlQuery = 'SELECT version_num FROM '.$this->record->getTableName().' WHERE ID = ?;';
        $this->record->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->stmt_init();

        if ($stmt->prepare($sqlQuery)) {
            $ID = $this->record->getID();
            $stmt->bind_param('i', $ID);

            $stmt->execute();

            $result = $this->bindResult($stmt);
            if (isset($result[0])) {
                $row = $result[0];
            }

            $stmt->close();
        } else {
            self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
            if (!$this->record->checkTableExists()) {
                $this->record->makeTable();

                throw new RecordNotFoundException('Failed to get the version number, table did not exist so had to create!');
            }
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
    public function makeTable($checkIndexes = true): void
    {
        self::$logger->debug('>>makeTable()');

        $sqlQuery = 'CREATE TABLE '.$this->record->getTableName().' (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT,';

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!in_array($propName, $this->record->getTransientAttributes()) && $propName != 'ID') {
                $prop = $this->record->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().') ZEROFILL NOT NULL,';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().'),';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName DOUBLE(".$prop->getSize(true).'),';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName VARCHAR(".$prop->getSize().') CHARACTER SET utf8,';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof LargeText) {
                    $sqlQuery .= "$propName MEDIUMTEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof HugeText) {
                    $sqlQuery .= "$propName LONGTEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName CHAR(1) DEFAULT '0',";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName DATE,";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName DATETIME,";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName ENUM(";
                    $enumVals = $prop->getOptions();
                    foreach ($enumVals as $val) {
                        $sqlQuery .= "'".$val."',";
                    }
                    $sqlQuery = rtrim($sqlQuery, ',');
                    $sqlQuery .= ') CHARACTER SET utf8,';
                } elseif ($prop instanceof DEnum) {
                    $denum = new DEnum(get_class($this->record).'::'.$propName);
                    $denum->saveIfNew();
                    $sqlQuery .= "$propName INT(11) ZEROFILL,";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED,";
                } else {
                    $sqlQuery .= '';
                }
            }
        }
        if ($this->record->isTableOverloaded()) {
            $sqlQuery .= 'classname VARCHAR(100),';
        }

        $sqlQuery .= 'PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;';

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<makeTable');
            throw new AlphaException('Failed to create the table ['.$this->record->getTableName().'] for the class ['.get_class($this->record).'], database error is ['.self::getConnection()->error.']');
        }

        // check the table indexes if any additional ones required
        if ($checkIndexes) {
            $this->checkIndexes();
        }

        if ($this->record->getMaintainHistory()) {
            $this->record->makeHistoryTable();
        }

        self::$logger->debug('<<makeTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::makeHistoryTable()
     */
    public function makeHistoryTable(): void
    {
        self::$logger->debug('>>makeHistoryTable()');

        $sqlQuery = 'CREATE TABLE '.$this->record->getTableName().'_history (ID INT(11) ZEROFILL NOT NULL,';

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!in_array($propName, $this->record->getTransientAttributes()) && $propName != 'ID') {
                $prop = $this->record->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().') ZEROFILL NOT NULL,';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().'),';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName DOUBLE(".$prop->getSize(true).'),';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName VARCHAR(".$prop->getSize().') CHARACTER SET utf8,';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof LargeText) {
                    $sqlQuery .= "$propName MEDIUMTEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof HugeText) {
                    $sqlQuery .= "$propName LONGTEXT CHARACTER SET utf8,";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName CHAR(1) DEFAULT '0',";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName DATE,";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName DATETIME,";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName ENUM(";
                    $enumVals = $prop->getOptions();
                    foreach ($enumVals as $val) {
                        $sqlQuery .= "'".$val."',";
                    }
                    $sqlQuery = rtrim($sqlQuery, ',');
                    $sqlQuery .= ') CHARACTER SET utf8,';
                } elseif ($prop instanceof DEnum) {
                    $denum = new DEnum(get_class($this->record).'::'.$propName);
                    $denum->saveIfNew();
                    $sqlQuery .= "$propName INT(11) ZEROFILL,";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED,";
                } else {
                    $sqlQuery .= '';
                }
            }
        }

        if ($this->record->isTableOverloaded()) {
            $sqlQuery .= 'classname VARCHAR(100),';
        }

        $sqlQuery .= 'PRIMARY KEY (ID, version_num)) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;';

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<makeHistoryTable');
            throw new AlphaException('Failed to create the table ['.$this->record->getTableName().'_history] for the class ['.get_class($this->record).'], database error is ['.self::getConnection()->error.']');
        }

        self::$logger->debug('<<makeHistoryTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::rebuildTable()
     */
    public function rebuildTable(): void
    {
        self::$logger->debug('>>rebuildTable()');

        $sqlQuery = 'DROP TABLE IF EXISTS '.$this->record->getTableName().';';

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<rebuildTable');
            throw new AlphaException('Failed to drop the table ['.$this->record->getTableName().'] for the class ['.get_class($this->record).'], database error is ['.self::getConnection()->error.']');
        }

        $this->record->makeTable();

        self::$logger->debug('<<rebuildTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::dropTable()
     */
    public function dropTable($tableName = null): void
    {
        self::$logger->debug('>>dropTable()');

        if ($tableName === null) {
            $tableName = $this->record->getTableName();
        }

        $sqlQuery = 'DROP TABLE IF EXISTS '.$tableName.';';

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<dropTable');
            throw new AlphaException('Failed to drop the table ['.$tableName.'] for the class ['.get_class($this->record).'], query is ['.$this->record->getLastQuery().']');
        }

        if ($this->record->getMaintainHistory()) {
            $sqlQuery = 'DROP TABLE IF EXISTS '.$tableName.'_history;';

            $this->record->setLastQuery($sqlQuery);

            if (!$result = self::getConnection()->query($sqlQuery)) {
                self::$logger->debug('<<dropTable');
                throw new AlphaException('Failed to drop the table ['.$tableName.'_history] for the class ['.get_class($this->record).'], query is ['.$this->record->getLastQuery().']');
            }
        }

        self::$logger->debug('<<dropTable');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::addProperty()
     */
    public function addProperty($propName): void
    {
        self::$logger->debug('>>addProperty(propName=['.$propName.'])');

        $sqlQuery = 'ALTER TABLE '.$this->record->getTableName().' ADD ';

        if ($this->isTableOverloaded() && $propName == 'classname') {
            $sqlQuery .= 'classname VARCHAR(100)';
        } else {
            if (!in_array($propName, $this->record->getDefaultAttributes()) && !in_array($propName, $this->record->getTransientAttributes())) {
                $prop = $this->record->getPropObject($propName);

                if ($prop instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID')) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().') ZEROFILL NOT NULL';
                } elseif ($prop instanceof Integer) {
                    $sqlQuery .= "$propName INT(".$prop->getSize().')';
                } elseif ($prop instanceof Double) {
                    $sqlQuery .= "$propName DOUBLE(".$prop->getSize(true).')';
                } elseif ($prop instanceof SmallText) {
                    $sqlQuery .= "$propName VARCHAR(".$prop->getSize().') CHARACTER SET utf8';
                } elseif ($prop instanceof Text) {
                    $sqlQuery .= "$propName TEXT CHARACTER SET utf8";
                } elseif ($prop instanceof Boolean) {
                    $sqlQuery .= "$propName CHAR(1) DEFAULT '0'";
                } elseif ($prop instanceof Date) {
                    $sqlQuery .= "$propName DATE";
                } elseif ($prop instanceof Timestamp) {
                    $sqlQuery .= "$propName DATETIME";
                } elseif ($prop instanceof Enum) {
                    $sqlQuery .= "$propName ENUM(";
                    $enumVals = $prop->getOptions();
                    foreach ($enumVals as $val) {
                        $sqlQuery .= "'".$val."',";
                    }
                    $sqlQuery = rtrim($sqlQuery, ',');
                    $sqlQuery .= ') CHARACTER SET utf8';
                } elseif ($prop instanceof DEnum) {
                    $denum = new DEnum(get_class($this->record).'::'.$propName);
                    $denum->saveIfNew();
                    $sqlQuery .= "$propName INT(11) ZEROFILL";
                } elseif ($prop instanceof Relation) {
                    $sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED";
                } else {
                    $sqlQuery .= '';
                }
            }
        }

        $this->record->setLastQuery($sqlQuery);

        if (!$result = self::getConnection()->query($sqlQuery)) {
            self::$logger->debug('<<addProperty');
            throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->record->getTableName().'], query is ['.$this->record->getLastQuery().']');
        } else {
            self::$logger->info('Successfully added the ['.$propName.'] column onto the ['.$this->record->getTableName().'] table for the class ['.get_class($this->record).']');
        }

        if ($this->record->getMaintainHistory()) {
            $sqlQuery = str_replace($this->record->getTableName(), $this->record->getTableName().'_history', $sqlQuery);

            if (!$result = self::getConnection()->query($sqlQuery)) {
                self::$logger->debug('<<addProperty');
                throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->record->getTableName().'_history], query is ['.$this->record->getLastQuery().']');
            } else {
                self::$logger->info('Successfully added the ['.$propName.'] column onto the ['.$this->record->getTableName().'_history] table for the class ['.get_class($this->record).']');
            }
        }

        self::$logger->debug('<<addProperty');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getMAX()
     */
    public function getMAX(): int
    {
        self::$logger->debug('>>getMAX()');

        $sqlQuery = 'SELECT MAX(ID) AS max_ID FROM '.$this->record->getTableName();

        $this->record->setLastQuery($sqlQuery);

        try {
            $result = $this->record->query($sqlQuery);

            $row = $result[0];

            if (isset($row['max_ID'])) {
                self::$logger->debug('<<getMAX ['.$row['max_ID'].']');

                return $row['max_ID'];
            } else {
                throw new AlphaException('Failed to get the MAX ID for the class ['.get_class($this->record).'] from the table ['.$this->record->getTableName().'], query is ['.$this->record->getLastQuery().']');
            }
        } catch (\Exception $e) {
            self::$logger->debug('<<getMAX');
            throw new AlphaException($e->getMessage());
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getCount()
     */
    public function getCount($attributes = array(), $values = array()): int
    {
        self::$logger->debug('>>getCount(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'])');

        if ($this->record->isTableOverloaded()) {
            $whereClause = ' WHERE classname = \''.addslashes(get_class($this->record)).'\' AND';
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
            $sqlQuery = 'SELECT COUNT(ID) AS class_count FROM '.$this->record->getTableName().$whereClause;
        } else {
            $sqlQuery = 'SELECT COUNT(ID) AS class_count FROM '.$this->record->getTableName();
        }

        $this->record->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        if ($result) {
            $row = $result->fetch_array(MYSQLI_ASSOC);

            self::$logger->debug('<<getCount ['.$row['class_count'].']');

            return $row['class_count'];
        } else {
            self::$logger->debug('<<getCount');
            throw new AlphaException('Failed to get the count for the class ['.get_class($this->record).'] from the table ['.$this->record->getTableName().'], query is ['.$this->record->getLastQuery().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getHistoryCount()
     */
    public function getHistoryCount(): int
    {
        self::$logger->debug('>>getHistoryCount()');

        if (!$this->record->getMaintainHistory()) {
            throw new AlphaException('getHistoryCount method called on a DAO where no history is maintained!');
        }

        $sqlQuery = 'SELECT COUNT(ID) AS object_count FROM '.$this->record->getTableName().'_history WHERE ID='.$this->record->getID();

        $this->record->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        if ($result) {
            $row = $result->fetch_array(MYSQLI_ASSOC);

            self::$logger->debug('<<getHistoryCount ['.$row['object_count'].']');

            return $row['object_count'];
        } else {
            self::$logger->debug('<<getHistoryCount');
            throw new AlphaException('Failed to get the history count for the business object ['.$this->record->getID().'] from the table ['.$this->record->getTableName().'_history], query is ['.$this->record->getLastQuery().']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::setEnumOptions()
     * @since 1.1
     */
    public function setEnumOptions(): void
    {
        self::$logger->debug('>>setEnumOptions()');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        // flag for any database errors
        $dbError = false;

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->record->getDefaultAttributes()) && !in_array($propName, $this->record->getTransientAttributes())) {
                $propClass = get_class($this->record->getPropObject($propName));
                if ($propClass == 'Alpha\Model\Type\Enum') {
                    $sqlQuery = 'SHOW COLUMNS FROM '.$this->record->getTableName()." LIKE '$propName'";

                    $this->record->setLastQuery($sqlQuery);

                    $result = self::getConnection()->query($sqlQuery);

                    if ($result) {
                        $row = $result->fetch_array(MYSQLI_NUM);
                        $options = explode("','", preg_replace("/(enum|set)\('(.+?)'\)/", '\\2', $row[1]));

                        $this->record->getPropObject($propName)->setOptions($options);
                    } else {
                        $dbError = true;
                        break;
                    }
                }
            }
        }

        if (!$dbError) {
            if (method_exists($this, 'after_setEnumOptions_callback')) {
                $this->{'after_setEnumOptions_callback'}();
            }
        } else {
            throw new AlphaException('Failed to load enum options correctly for object instance of class ['.get_class($this).']');
        }
        self::$logger->debug('<<setEnumOptions');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkTableExists()
     */
    public function checkTableExists($checkHistoryTable = false): bool
    {
        self::$logger->debug('>>checkTableExists(checkHistoryTable=['.$checkHistoryTable.'])');

        $tableExists = false;

        $sqlQuery = 'SHOW TABLES;';
        $this->record->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        if ($result) {
            $tableName = ($checkHistoryTable ? $this->record->getTableName().'_history' : $this->record->getTableName());

            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                if (strtolower($row[0]) == mb_strtolower($tableName)) {
                    $tableExists = true;
                }
            }

            self::$logger->debug('<<checkTableExists ['.$tableExists.']');

            return $tableExists;
        } else {
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkRecordTableExists()
     */
    public static function checkRecordTableExists($RecordClassName, $checkHistoryTable = false): bool
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderMySQL');
        }
        self::$logger->debug('>>checkRecordTableExists(RecordClassName=['.$RecordClassName.'], checkHistoryTable=['.$checkHistoryTable.'])');

        if (!class_exists($RecordClassName)) {
            throw new IllegalArguementException('The classname provided ['.$checkHistoryTable.'] is not defined!');
        }

        $tableName = $RecordClassName::TABLE_NAME;

        if (empty($tableName)) {
            $tableName = mb_substr($RecordClassName, 0, mb_strpos($RecordClassName, '_'));
        }

        if ($checkHistoryTable) {
            $tableName .= '_history';
        }

        $tableExists = false;

        $sqlQuery = 'SHOW TABLES;';

        $result = self::getConnection()->query($sqlQuery);

        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            if ($row[0] == $tableName) {
                $tableExists = true;
            }
        }

        if ($result) {
            self::$logger->debug('<<checkRecordTableExists ['.($tableExists ? 'true' : 'false').']');

            return $tableExists;
        } else {
            self::$logger->debug('<<checkRecordTableExists');
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkTableNeedsUpdate()
     */
    public function checkTableNeedsUpdate(): bool
    {
        self::$logger->debug('>>checkTableNeedsUpdate()');

        $updateRequired = false;

        $matchCount = 0;

        $query = 'SHOW COLUMNS FROM '.$this->record->getTableName();
        $result = self::getConnection()->query($query);
        $this->record->setLastQuery($query);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->record->getTransientAttributes())) {
                $foundMatch = false;

                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    if ($propName == $row['Field']) {
                        $foundMatch = true;
                        break;
                    }
                }

                if (!$foundMatch) {
                    --$matchCount;
                }

                $result->data_seek(0);
            }
        }

        // check for the "classname" field in overloaded tables
        if ($this->record->isTableOverloaded()) {
            $foundMatch = false;

            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ('classname' == $row['Field']) {
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

        if ($result) {
            // check the table indexes
            try {
                $this->checkIndexes();
            } catch (AlphaException $ae) {
                self::$logger->warn("Error while checking database indexes:\n\n".$ae->getMessage());
            }

            self::$logger->debug('<<checkTableNeedsUpdate ['.$updateRequired.']');

            return $updateRequired;
        } else {
            self::$logger->debug('<<checkTableNeedsUpdate');
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::findMissingFields()
     */
    public function findMissingFields(): array
    {
        self::$logger->debug('>>findMissingFields()');

        $missingFields = array();
        $matchCount = 0;

        $sqlQuery = 'SHOW COLUMNS FROM '.$this->record->getTableName();

        $result = self::getConnection()->query($sqlQuery);

        $this->record->setLastQuery($sqlQuery);

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->record->getTransientAttributes())) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    if ($propName == $row['Field']) {
                        ++$matchCount;
                        break;
                    }
                }
                $result->data_seek(0);
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
        if ($this->record->isTableOverloaded()) {
            $foundMatch = false;

            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ('classname' == $row['Field']) {
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                array_push($missingFields, 'classname');
            }
        }

        if (!$result) {
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
        }

        self::$logger->debug('<<findMissingFields ['.var_export($missingFields, true).']');

        return $missingFields;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::getIndexes()
     */
    public function getIndexes(): array
    {
        self::$logger->debug('>>getIndexes()');

        $query = 'SHOW INDEX FROM '.$this->record->getTableName();

        $result = self::getConnection()->query($query);

        $this->record->setLastQuery($query);

        $indexNames = array();

        if (!$result) {
            throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
        } else {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($indexNames, $row['Key_name']);
            }
        }

        self::$logger->debug('<<getIndexes');

        return $indexNames;
    }

    /**
     * Checks to see if all of the indexes are in place for the record's table, creates those that are missing.
     *
     * @since 1.1
     */
    private function checkIndexes(): void
    {
        self::$logger->debug('>>checkIndexes()');

        $indexNames = $this->getIndexes();

        // process unique keys
        foreach ($this->record->getUniqueAttributes() as $prop) {
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
                        $this->record->createUniqueIndex($attributes[0], $attributes[1], $attributes[2]);
                    } else {
                        $this->record->createUniqueIndex($attributes[0], $attributes[1]);
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

        // process foreign-key indexes
        // get the class attributes
        $reflection = new ReflectionClass(get_class($this->record));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            $prop = $this->record->getPropObject($propName);
            if ($prop instanceof Relation) {
                if ($prop->getRelationType() == 'MANY-TO-ONE') {
                    $indexExists = false;
                    foreach ($indexNames as $index) {
                        if ($this->record->getTableName().'_'.$propName.'_fk_idx' == $index) {
                            $indexExists = true;
                        }
                    }

                    if (!$indexExists) {
                        $this->createForeignIndex($propName, $prop->getRelatedClass(), $prop->getRelatedClassField());
                    }
                }

                if ($prop->getRelationType() == 'MANY-TO-MANY') {
                    $lookup = $prop->getLookup();

                    if ($lookup != null) {
                        try {
                            $lookupIndexNames = $lookup->getIndexes();

                            // handle index check/creation on left side of Relation
                            $indexExists = false;
                            foreach ($lookupIndexNames as $index) {
                                if ($lookup->getTableName().'_leftID_fk_idx' == $index) {
                                    $indexExists = true;
                                }
                            }

                            if (!$indexExists) {
                                $lookup->createForeignIndex('leftID', $prop->getRelatedClass('left'), 'ID');
                            }

                            // handle index check/creation on right side of Relation
                            $indexExists = false;
                            foreach ($lookupIndexNames as $index) {
                                if ($lookup->getTableName().'_rightID_fk_idx' == $index) {
                                    $indexExists = true;
                                }
                            }

                            if (!$indexExists) {
                                $lookup->createForeignIndex('rightID', $prop->getRelatedClass('right'), 'ID');
                            }
                        } catch (AlphaException $e) {
                            self::$logger->error($e->getMessage());
                        }
                    }
                }
            }
        }

        self::$logger->debug('<<checkIndexes');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createForeignIndex()
     */
    public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute, $indexName = null): void
    {
        self::$logger->debug('>>createForeignIndex(attributeName=['.$attributeName.'], relatedClass=['.$relatedClass.'], relatedClassAttribute=['.$relatedClassAttribute.'], indexName=['.$indexName.']');

        $relatedRecord = new $relatedClass();
        $tableName = $relatedRecord->getTableName();

        $result = false;

        if (self::checkRecordTableExists($relatedClass)) {
            $sqlQuery = '';

            if ($attributeName == 'leftID') {
                if ($indexName === null) {
                    $indexName = $this->record->getTableName().'_leftID_fk_idx';
                }
                $sqlQuery = 'ALTER TABLE '.$this->record->getTableName().' ADD INDEX '.$indexName.' (leftID);';
            }
            if ($attributeName == 'rightID') {
                if ($indexName === null) {
                    $indexName = $this->record->getTableName().'_rightID_fk_idx';
                }
                $sqlQuery = 'ALTER TABLE '.$this->record->getTableName().' ADD INDEX '.$indexName.' (rightID);';
            }

            if (!empty($sqlQuery)) {
                $this->record->setLastQuery($sqlQuery);

                $result = self::getConnection()->query($sqlQuery);

                if (!$result) {
                    throw new FailedIndexCreateException('Failed to create an index on ['.$this->record->getTableName().'], error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
                }
            }

            if ($indexName === null) {
                $indexName = $this->record->getTableName().'_'.$attributeName.'_fk_idx';
            }

            $sqlQuery = 'ALTER TABLE '.$this->record->getTableName().' ADD FOREIGN KEY '.$indexName.' ('.$attributeName.') REFERENCES '.$tableName.' ('.$relatedClassAttribute.') ON DELETE SET NULL;';

            $this->record->setLastQuery($sqlQuery);
            $result = self::getConnection()->query($sqlQuery);
        }

        if ($result) {
            self::$logger->debug('Successfully created the foreign key index ['.$indexName.']');
        } else {
            throw new FailedIndexCreateException('Failed to create the index ['.$indexName.'] on ['.$this->record->getTableName().'], error is ['.self::getConnection()->error.'], query ['.$this->record->getLastQuery().']');
        }

        self::$logger->debug('<<createForeignIndex');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createUniqueIndex()
     */
    public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = ''): void
    {
        self::$logger->debug('>>createUniqueIndex(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'], attribute3Name=['.$attribute3Name.'])');

        $sqlQuery = '';

        if ($attribute2Name != '' && $attribute3Name != '') {
            $sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_'.$attribute2Name.'_'.$attribute3Name.'_unq_idx ON '.$this->record->getTableName().' ('.$attribute1Name.','.$attribute2Name.','.$attribute3Name.');';
        }

        if ($attribute2Name != '' && $attribute3Name == '') {
            $sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_'.$attribute2Name.'_unq_idx ON '.$this->record->getTableName().' ('.$attribute1Name.','.$attribute2Name.');';
        }

        if ($attribute2Name == '' && $attribute3Name == '') {
            $sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_unq_idx ON '.$this->record->getTableName().' ('.$attribute1Name.');';
        }

        $this->record->setLastQuery($sqlQuery);

        $result = self::getConnection()->query($sqlQuery);

        if ($result) {
            self::$logger->debug('Successfully created the unique index on ['.$this->record->getTableName().']');
        } else {
            throw new FailedIndexCreateException('Failed to create the unique index on ['.$this->record->getTableName().'], error is ['.self::getConnection()->error.']');
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

        if (!$this->record->isTransient()) {
            $this->record->load($this->record->getID());
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
    public function checkRecordExists($ID)
    {
        self::$logger->debug('>>checkRecordExists(ID=['.$ID.'])');

        $sqlQuery = 'SELECT ID FROM '.$this->record->getTableName().' WHERE ID = ?;';

        $this->record->setLastQuery($sqlQuery);

        $stmt = self::getConnection()->stmt_init();

        if ($stmt->prepare($sqlQuery)) {
            $stmt->bind_param('i', $ID);

            $stmt->execute();

            $result = $this->bindResult($stmt);

            $stmt->close();

            if (is_array($result)) {
                if (count($result) > 0) {
                    self::$logger->debug('<<checkRecordExists [true]');

                    return true;
                } else {
                    self::$logger->debug('<<checkRecordExists [false]');

                    return false;
                }
            } else {
                self::$logger->debug('<<checkRecordExists');
                throw new AlphaException('Failed to check for the record ['.$ID.'] on the class ['.get_class($this->record).'] from the table ['.$this->record->getTableName().'], query is ['.$this->record->getLastQuery().']');
            }
        } else {
            self::$logger->debug('<<checkRecordExists');
            throw new AlphaException('Failed to check for the record ['.$ID.'] on the class ['.get_class($this->record).'] from the table ['.$this->record->getTableName().'], query is ['.$this->record->getLastQuery().']');
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

        $reflection = new ReflectionClass($this->record);
        $classname = $reflection->getShortName();
        $tablename = ucfirst($this->record->getTableName());

        // use reflection to check to see if we are dealing with a persistent type (e.g. DEnum) which are never overloaded
        $implementedInterfaces = $reflection->getInterfaces();

        foreach ($implementedInterfaces as $interface) {
            if ($interface->name == 'Alpha\Model\Type\TypeInterface') {
                self::$logger->debug('<<isTableOverloaded [false]');

                return false;
            }
        }

        if ($classname != $tablename) {
            // loop over all records to see if there is one using the same table as this record

            $Recordclasses = ActiveRecord::getRecordClassNames();

            foreach ($Recordclasses as $RecordclassName) {
                $reflection = new ReflectionClass($RecordclassName);
                $classname = $reflection->getShortName();
                if ($tablename == $classname) {
                    self::$logger->debug('<<isTableOverloaded [true]');

                    return true;
                }
            }

            self::$logger->debug('<<isTableOverloaded');
            throw new BadTableNameException('The table name ['.$tablename.'] for the class ['.$classname.'] is invalid as it does not match a Record definition in the system!');
        } else {
            // check to see if there is already a "classname" column in the database for this record

            $query = 'SHOW COLUMNS FROM '.$this->record->getTableName();

            $result = self::getConnection()->query($query);

            if ($result) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    if ('classname' == $row['Field']) {
                        self::$logger->debug('<<isTableOverloaded [true]');

                        return true;
                    }
                }
            } else {
                self::$logger->warn('Error during show columns ['.self::getConnection()->error.']');
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
            self::$logger = new Logger('ActiveRecordProviderMySQL');
        }
        self::$logger->debug('>>begin()');

        if (!self::getConnection()->autocommit(false)) {
            throw new AlphaException('Error beginning a new transaction, error is ['.self::getConnection()->error.']');
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
            self::$logger = new Logger('ActiveRecordProviderMySQL');
        }
        self::$logger->debug('>>commit()');

        if (!self::getConnection()->commit()) {
            throw new FailedSaveException('Error commiting a transaction, error is ['.self::getConnection()->error.']');
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
            self::$logger = new Logger('ActiveRecordProviderMySQL');
        }
        self::$logger->debug('>>rollback()');

        if (!self::getConnection()->rollback()) {
            throw new AlphaException('Error rolling back a transaction, error is ['.self::getConnection()->error.']');
        }

        self::$logger->debug('<<rollback');
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::setRecord()
     */
    public function setRecord($Record)
    {
        $this->record = $Record;
    }

    /**
     * Dynamically binds all of the attributes for the current Record to the supplied prepared statement
     * parameters.  If arrays of attribute names and values are provided, only those will be bound to
     * the supplied statement.
     *
     * @param \mysqli_stmt $stmt The SQL statement to bind to.
     * @param array Optional array of Record attributes.
     * @param array Optional array of Record values.
     *
     * @return \mysqli_stmt
     *
     * @since 1.1
     */
    private function bindParams($stmt, $attributes = array(), $values = array())
    {
        self::$logger->debug('>>bindParams(stmt=['.var_export($stmt, true).'])');

        $bindingsTypes = '';
        $params = array();

        // here we are only binding the supplied attributes
        if (count($attributes) > 0 && count($attributes) == count($values)) {
            $count = count($values);

            for ($i = 0; $i < $count; ++$i) {
                if (Validator::isInteger($values[$i])) {
                    $bindingsTypes .= 'i';
                } else {
                    $bindingsTypes .= 's';
                }
                array_push($params, $values[$i]);
            }

            if ($this->record->isTableOverloaded()) {
                $bindingsTypes .= 's';
                array_push($params, get_class($this->record));
            }
        } else { // bind all attributes on the business object

            // get the class attributes
            $reflection = new ReflectionClass(get_class($this->record));
            $properties = $reflection->getProperties();

            foreach ($properties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->record->getTransientAttributes())) {
                    // Skip the ID, database auto number takes care of this.
                    if ($propName != 'ID' && $propName != 'version_num') {
                        if ($this->record->getPropObject($propName) instanceof Integer) {
                            $bindingsTypes .= 'i';
                        } else {
                            $bindingsTypes .= 's';
                        }
                        array_push($params, $this->record->get($propName));
                    }

                    if ($propName == 'version_num') {
                        $temp = $this->record->getVersionNumber()->getValue();
                        $this->record->set('version_num', $temp+1);
                        $bindingsTypes .= 'i';
                        array_push($params, $this->record->getVersionNumber()->getValue());
                    }
                }
            }

            if ($this->record->isTableOverloaded()) {
                $bindingsTypes .= 's';
                array_push($params, get_class($this->record));
            }

            // the ID may be on the WHERE clause for UPDATEs and DELETEs
            if (!$this->record->isTransient()) {
                $bindingsTypes .= 'i';
                array_push($params, $this->record->getID());
            }
        }

        self::$logger->debug('bindingsTypes=['.$bindingsTypes.'], count: ['.mb_strlen($bindingsTypes).']');
        self::$logger->debug('params ['.var_export($params, true).']');

        if ($params != null) {
            $bind_names[] = $bindingsTypes;

            $count = count($params);

            for ($i = 0; $i < $count; ++$i) {
                $bind_name = 'bind'.$i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }

            call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        }

        self::$logger->debug('<<bindParams ['.var_export($stmt, true).']');

        return $stmt;
    }

    /**
     * Dynamically binds the result of the supplied prepared statement to a 2d array, where each element in the array is another array
     * representing a database row.
     *
     * @param \mysqli_stmt $stmt
     *
     * @return array A 2D array containing the query result.
     *
     * @since 1.1
     */
    private function bindResult($stmt)
    {
        $result = array();

        $metadata = $stmt->result_metadata();
        $fields = $metadata->fetch_fields();

        while (true) {
            $pointers = array();
            $row = array();

            $pointers[] = $stmt;
            foreach ($fields as $field) {
                $fieldname = $field->name;
                $pointers[] = &$row[$fieldname];
            }

            call_user_func_array('mysqli_stmt_bind_result', $pointers);

            if (!$stmt->fetch()) {
                break;
            }

            $result[] = $row;
        }

        $metadata->free();

        return $result;
    }

    /**
     * Parses a MySQL error for the value that violated a unique constraint.
     *
     * @param string $error The MySQL error string.
     *
     * @since 1.1
     */
    private function findOffendingValue($error)
    {
        self::$logger->debug('>>findOffendingValue(error=['.$error.'])');

        $singleQuote1 = mb_strpos($error, "'");
        $singleQuote2 = mb_strrpos($error, "'");

        $value = mb_substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
        self::$logger->debug('<<findOffendingValue ['.$value.'])');

        return $value;
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::checkDatabaseExists()
     */
    public static function checkDatabaseExists()
    {
        $config = ConfigProvider::getInstance();

        $connection = new Mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'));

        $result = $connection->query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \''.$config->get('db.name').'\'');

        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::createDatabase()
     */
    public static function createDatabase()
    {
        $config = ConfigProvider::getInstance();

        $connection = new Mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'));

        $connection->query('CREATE DATABASE '.$config->get('db.name'));
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::dropDatabase()
     */
    public static function dropDatabase()
    {
        $config = ConfigProvider::getInstance();

        $connection = new Mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'));

        $connection->query('DROP DATABASE '.$config->get('db.name'));
    }

    /**
     * (non-PHPdoc).
     *
     * @see Alpha\Model\ActiveRecordProviderInterface::backupDatabase()
     */
    public static function backupDatabase($targetFile)
    {
        $config = ConfigProvider::getInstance();

        exec('mysqldump  --host="'.$config->get('db.hostname').'" --user="'.$config->get('db.username').'" --password="'.$config->get('db.password').'" --opt '.$config->get('db.name').' 2>&1 >'.$targetFile);
    }
}
