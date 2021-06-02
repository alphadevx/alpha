<?php

namespace Alpha\Model;

use Alpha\Model\Type\Date;
use Alpha\Model\Type\Integer;
use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\TypeInterface;
use Alpha\Model\Type\Relation;
use Alpha\Model\Type\RelationLookup;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Exception\AlphaException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\FailedDeleteException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\LockingException;
use Alpha\Exception\NotImplementedException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Base active record class definition providing database storage via the configured provider.
 *
 * @since 1.0
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
abstract class ActiveRecord
{
    /**
     * The object ID.
     *
     * @var int
     *
     * @since 1.0
     */
    protected $ID;

    /**
     * The last database query run by this object.  Useful for tracing an error.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $lastQuery = '';

    /**
     * The version number of the object, used for locking mechanism.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $version_num;

    /**
     * The timestamp of creation.
     *
     * @var \Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $created_ts;

    /**
     * The ID of the person who created this record.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $created_by;

    /**
     * The timestamp of the last update.
     *
     * @var \Alpha\Model\Type\Timestamp
     *
     * @since 1.0
     */
    protected $updated_ts;

    /**
     * The ID of the person who last updated this record.
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    protected $updated_by;

    /**
     * An array of the names of all of the default attributes of a persistent Record defined in this class.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $defaultAttributes = array('ID', 'lastQuery', 'version_num', 'dataLabels', 'created_ts', 'created_by', 'updated_ts', 'updated_by', 'defaultAttributes', 'transientAttributes', 'uniqueAttributes', 'TABLE_NAME', 'logger');

    /**
     * An array of the names of all of the transient attributes of a persistent Record which are not saved to the DB.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $transientAttributes = array('lastQuery', 'dataLabels', 'defaultAttributes', 'transientAttributes', 'uniqueAttributes', 'TABLE_NAME', 'logger');

    /**
     * An array of the uniquely-constained attributes of this persistent record.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $uniqueAttributes = array();

    /**
     * An array of the data labels used for displaying class attributes.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $dataLabels = array();

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Determines if we will maintain a _history table for this record (default is false).
     *
     * @var bool
     *
     * @since 1.2
     */
    private $maintainHistory = false;

    /**
     * The constructor which sets up some housekeeping attributes.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ActiveRecord');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
        set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));

        $this->version_num = new Integer(0);
        $this->created_ts = new Timestamp(date('Y-m-d H:i:s'));
        $person_ID = ($session->get('currentUser') != null ? $session->get('currentUser')->getID() : 0);
        $this->created_by = new Integer($person_ID);
        $this->updated_ts = new Timestamp(date('Y-m-d H:i:s'));
        $this->updated_by = new Integer($person_ID);

        self::$logger->debug('<<__construct');
    }

    /**
     * Disconnects the current database connection if one exists.
     *
     * @since 1.0
     */
    public static function disconnect(): void
    {
        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->disconnect();
    }

    /**
     * Returns a 2d array, where each element in the array is another array representing a database row.
     *
     * @param string $sqlQuery
     *
     * @since 1.1
     *
     * @throws \Alpha\Exception\CustomQueryException
     */
    public function query($sqlQuery): array
    {
        self::$logger->debug('>>query(sqlQuery=['.$sqlQuery.'])');

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $result = $provider->query($sqlQuery);

        self::$logger->debug('<<query ['.print_r($result, true).']');

        return $result;
    }

    /**
     * Populates the child object with the properties retrived from the database for the object $ID.
     *
     * @param int $ID     The object ID of the business object to load.
     * @param int $version Optionaly, provide the version to load that version from the [tablename]_history table.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function load($ID, $version = 0): void
    {
        self::$logger->debug('>>load(ID=['.$ID.'], version=['.$version.'])');

        if (method_exists($this, 'beforeLoad')) {
            $this->{'beforeLoad'}();
        }

        $config = ConfigProvider::getInstance();

        $this->ID = $ID;

        if ($config->get('cache.provider.name') != '' && $this->loadFromCache()) {
            // Record was found in cache
        } else {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord($this);
            $provider->load($ID, $version);

            if ($config->get('cache.provider.name') != '') {
                $this->addToCache();
            }
        }

        $this->setEnumOptions();

        if (method_exists($this, 'afterLoad')) {
            $this->{'afterLoad'}();
        }

        self::$logger->debug('<<load');
    }

    /**
     * Load all old versions (if any) of this record from the [tablename]_history table.
     *
     * @param int $ID The object ID of the record to load.
     *
     * @since 2.0
     *
     * @throws \Alpha\Exception\RecordFoundException
     */
    public function loadAllOldVersions($ID): array
    {
        self::$logger->debug('>>loadAllOldVersions(ID=['.$ID.'])');

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllOldVersions($ID);

        self::$logger->debug('<<loadAllOldVersions['.count($objects).']');

        return $objects;
    }

    /**
     * Populates the child object from the database table by the given attribute value.
     *
     * @param string $attribute       The name of the attribute to load the object by.
     * @param string $value           The value of the attribute to load the object by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     * @param array  $loadAttributes  The attributes to load from the database to this object (leave blank to load all attributes)
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function loadByAttribute($attribute, $value, $ignoreClassType = false, $loadAttributes = array()): void
    {
        self::$logger->debug('>>loadByAttribute(attribute=['.$attribute.'], value=['.$value.'], ignoreClassType=['.$ignoreClassType.'], 
            loadAttributes=['.var_export($loadAttributes, true).'])');

        if (method_exists($this, 'beforeLoadByAttribute')) {
            $this->{'beforeLoadByAttribute'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->loadByAttribute($attribute, $value, $ignoreClassType, $loadAttributes);

        $this->setEnumOptions();

        if ($config->get('cache.provider.name') != '' && count($loadAttributes) == 0) { // we will only cache fully-populated records
            $this->addToCache();
        }

        if (method_exists($this, 'afterLoadByAttribute')) {
            $this->{'afterLoadByAttribute'}();
        }

        self::$logger->debug('<<loadByAttribute');
    }

    /**
     * Loads all of the objects of this class into an array which is returned.
     *
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of objects to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the objects by.
     * @param string $order           The order to sort the objects by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function loadAll($start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAll(start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (method_exists($this, 'beforeLoadAll')) {
            $this->{'beforeLoadAll'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAll($start, $limit, $orderBy, $order, $ignoreClassType);

        if (method_exists($this, 'afterLoadAll')) {
            $this->{'afterLoadAll'}();
        }

        self::$logger->debug('<<loadAll ['.count($objects).']');

        return $objects;
    }

    /**
     * Loads all of the objects of this class by the specified attribute into an array which is returned.
     *
     * @param string $attribute       The attribute to load the objects by.
     * @param string $value           The value of the attribute to load the objects by.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of objects to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the objects by.
     * @param string $order           The order to sort the objects by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type.
     * @param array  $constructorArgs An optional array of contructor arguements to pass to the records that will be generated and returned.  Supports a maximum of 5 arguements.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function loadAllByAttribute($attribute, $value, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false, $constructorArgs = array()): array
    {
        self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

        if (method_exists($this, 'beforeLoadAllByAttribute')) {
            $this->{'beforeLoadAllByAttribute'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllByAttribute($attribute, $value, $start, $limit, $orderBy, $order, $ignoreClassType);

        if (method_exists($this, 'afterLoadAllByAttribute')) {
            $this->{'afterLoadAllByAttribute'}();
        }

        self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');

        return $objects;
    }

    /**
     * Loads all of the objects of this class by the specified attributes into an array which is returned.
     *
     * @param array  $attributes      The attributes to load the objects by.
     * @param array  $values          The values of the attributes to load the objects by.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of objects to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the objects by.
     * @param string $order           The order to sort the objects by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function loadAllByAttributes($attributes = array(), $values = array(), $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAllByAttributes(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'], start=['.
            $start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (method_exists($this, 'beforeLoadAllByAttributes')) {
            $this->{'beforeLoadAllByAttributes'}();
        }

        $config = ConfigProvider::getInstance();

        if (!is_array($attributes) || !is_array($values)) {
            throw new IllegalArguementException('Illegal arrays attributes=['.var_export($attributes, true).'] and values=['.var_export($values, true).
                '] provided to loadAllByAttributes');
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllByAttributes($attributes, $values, $start, $limit, $orderBy, $order, $ignoreClassType);

        if (method_exists($this, 'afterLoadAllByAttributes')) {
            $this->{'afterLoadAllByAttributes'}();
        }

        self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');

        return $objects;
    }

    /**
     * Loads all of the objects of this class that where updated (updated_ts value) on the date indicated.
     *
     * @param string $date            The date for which to load the objects updated on, in the format 'YYYY-MM-DD'.
     * @param int    $start           The start of the SQL LIMIT clause, useful for pagination.
     * @param int    $limit           The amount (limit) of objects to load, useful for pagination.
     * @param string $orderBy         The name of the field to sort the objects by.
     * @param string $order           The order to sort the objects by.
     * @param bool   $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function loadAllByDayUpdated($date, $start = 0, $limit = 0, $orderBy = 'ID', $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAllByDayUpdated(date=['.$date.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        if (method_exists($this, 'before_loadAllByDayUpdated_callback')) {
            $this->{'before_loadAllByDayUpdated_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $objects = $provider->loadAllByDayUpdated($date, $start, $limit, $orderBy, $order, $ignoreClassType);

        if (method_exists($this, 'after_loadAllByDayUpdated_callback')) {
            $this->{'after_loadAllByDayUpdated_callback'}();
        }

        self::$logger->debug('<<loadAllByDayUpdated ['.count($objects).']');

        return $objects;
    }

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
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order = 'ASC', $ignoreClassType = false): array
    {
        self::$logger->debug('>>loadAllFieldValuesByAttribute(attribute=['.$attribute.'], value=['.$value.'], returnAttribute=['.$returnAttribute.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $values = $provider->loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order, $ignoreClassType);

        self::$logger->debug('<<loadAllFieldValuesByAttribute ['.count($values).']');

        return $values;
    }

    /**
     * Saves the object.  If $this->ID is empty or null it will INSERT, otherwise UPDATE.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedSaveException
     * @throws \Alpha\Exception\LockingException
     * @throws \Alpha\Exception\ValidationException
     */
    public function save(): void
    {
        self::$logger->debug('>>save()');

        if (method_exists($this, 'beforeSave')) {
            $this->{'beforeSave'}();
        }

        $config = ConfigProvider::getInstance();

        // firstly we will validate the object before we try to save it
        $this->validate();

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        if ($this->getVersion() != $this->getVersionNumber()->getValue()) {
            throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
        }

        // set the "updated by" fields, we can only set the user id if someone is logged in
        if ($session->get('currentUser') != null) {
            $this->set('updated_by', $session->get('currentUser')->getID());
        }

        $this->set('updated_ts', new Timestamp(date('Y-m-d H:i:s')));

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->save();

        if ($config->get('cache.provider.name') != '') {
            $this->removeFromCache();
            $this->addToCache();
        }

        if (method_exists($this, 'afterSave')) {
            $this->{'afterSave'}();
        }
    }

    /**
     * Saves relationship values, including lookup entries, for this record.
     *
     * @since 3.0
     *
     * @throws \Alpha\Exception\FailedSaveException
     */
    public function saveRelations(): void
    {
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        try {
            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                if ($this->getPropObject($propName) instanceof Relation) {
                    $prop = $this->getPropObject($propName);

                    // handle the saving of MANY-TO-MANY relation values
                    if ($prop->getRelationType() == 'MANY-TO-MANY' && count($prop->getRelatedIDs()) > 0) {
                        try {
                            try {
                                // check to see if the rel is on this class
                                $side = $prop->getSide(get_class($this));
                            } catch (IllegalArguementException $iae) {
                                $side = $prop->getSide(get_parent_class($this));
                            }

                            $lookUp = $prop->getLookup();

                            // first delete all of the old RelationLookup objects for this rel
                            try {
                                if ($side == 'left') {
                                    $lookUp->deleteAllByAttribute('leftID', $this->getID());
                                } else {
                                    $lookUp->deleteAllByAttribute('rightID', $this->getID());
                                }
                            } catch (\Exception $e) {
                                throw new FailedSaveException('Failed to delete old RelationLookup objects on the table ['.$prop->getLookup()->getTableName().'], error is ['.$e->getMessage().']');
                            }

                            $IDs = $prop->getRelatedIDs();

                            if (isset($IDs) && !empty($IDs[0])) {
                                // now for each posted ID, create a new RelationLookup record and save
                                foreach ($IDs as $id) {
                                    $newLookUp = new RelationLookup($lookUp->get('leftClassName'), $lookUp->get('rightClassName'));
                                    if ($side == 'left') {
                                        $newLookUp->set('leftID', $this->getID());
                                        $newLookUp->set('rightID', $id);
                                    } else {
                                        $newLookUp->set('rightID', $this->getID());
                                        $newLookUp->set('leftID', $id);
                                    }
                                    $newLookUp->save();
                                }
                            }
                        } catch (\Exception $e) {
                            throw new FailedSaveException('Failed to update a MANY-TO-MANY relation on the object, error is ['.$e->getMessage().']');
                        }
                    }

                    // handle the saving of ONE-TO-MANY relation values
                    if ($prop->getRelationType() == 'ONE-TO-MANY') {
                        $prop->setValue($this->getID());
                    }
                }
            }
        } catch (\Exception $e) {
            throw new FailedSaveException('Failed to save object, error is ['.$e->getMessage().']');
        }
    }

    /**
     * Saves the field specified with the value supplied.  Only works for persistent records.  Note that no Alpha type
     * validation is performed with this method!
     *
     * @param string $attribute The name of the attribute to save.
     * @param mixed  $value     The value of the attribute to save.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\FailedSaveException
     */
    public function saveAttribute($attribute, $value): void
    {
        self::$logger->debug('>>saveAttribute(attribute=['.$attribute.'], value=['.$value.'])');

        if (method_exists($this, 'before_saveAttribute_callback')) {
            $this->{'before_saveAttribute_callback'}();
        }

        $config = ConfigProvider::getInstance();

        if (!isset($this->$attribute)) {
            throw new IllegalArguementException('Could not perform save, as the attribute ['.$attribute.'] is not present on the class['.get_class($this).']');
        }

        if ($this->isTransient()) {
            throw new FailedSaveException('Cannot perform saveAttribute method on transient record!');
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->saveAttribute($attribute, $value);

        if ($config->get('cache.provider.name') != '') {
            $this->removeFromCache();
            $this->addToCache();
        }

        if (method_exists($this, 'after_saveAttribute_callback')) {
            $this->{'after_saveAttribute_callback'}();
        }

        self::$logger->debug('<<saveAttribute');
    }

    /**
     * Saves the history of the object in the [tablename]_history table. It will always perform an insert.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\FailedSaveException
     */
    public function saveHistory(): void
    {
        self::$logger->debug('>>saveHistory()');

        if (method_exists($this, 'before_saveHistory_callback')) {
            $this->{'before_saveHistory_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->saveHistory();

        if (method_exists($this, 'after_saveHistory_callback')) {
            $this->{'after_saveHistory_callback'}();
        }
    }

    /**
     * Validates the object to be saved.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\ValidationException
     */
    protected function validate(): void
    {
        self::$logger->debug('>>validate()');

        if (method_exists($this, 'before_validate_callback')) {
            $this->{'before_validate_callback'}();
        }

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
                $propClass = new ReflectionClass($this->getPropObject($propName));
                $propClass = $propClass->getShortname();
                if (mb_strtoupper($propClass) != 'ENUM' &&
                mb_strtoupper($propClass) != 'DENUM' &&
                mb_strtoupper($propClass) != 'DENUMITEM' &&
                mb_strtoupper($propClass) != 'BOOLEAN') {
                    if ($this->getPropObject($propName) != false && !preg_match($this->getPropObject($propName)->getRule(), $this->getPropObject($propName)->getValue())) {
                        self::$logger->debug('<<validate');
                        throw new ValidationException('Failed to save, validation error is: '.$this->getPropObject($propName)->getHelper());
                    }
                }
            }
        }

        if (method_exists($this, 'after_validate_callback')) {
            $this->{'after_validate_callback'}();
        }

        self::$logger->debug('<<validate');
    }

    /**
     * Deletes the current object from the database.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedDeleteException
     */
    public function delete(): void
    {
        self::$logger->debug('>>delete()');

        if (method_exists($this, 'beforeDelete')) {
            $this->{'beforeDelete'}();
        }

        $config = ConfigProvider::getInstance();

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        // check for any relations on this object, then remove them to prevent orphaned data
        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {
                $prop = $this->getPropObject($propName);

                // Handle MANY-TO-MANY rels
                if ($prop->getRelationType() == 'MANY-TO-MANY') {
                    self::$logger->debug('Deleting MANY-TO-MANY lookup objects...');

                    try {
                        // check to see if the rel is on this class
                        $side = $prop->getSide(get_class($this));
                    } catch (IllegalArguementException $iae) {
                        $side = $prop->getSide(get_parent_class($this));
                    }

                    self::$logger->debug('Side is ['.$side.']'.$this->getID());

                    $lookUp = $prop->getLookup();
                    self::$logger->debug('Lookup object['.var_export($lookUp, true).']');

                    // delete all of the old RelationLookup objects for this rel
                    if ($side == 'left') {
                        $lookUp->deleteAllByAttribute('leftID', $this->getID());
                    } else {
                        $lookUp->deleteAllByAttribute('rightID', $this->getID());
                    }
                    self::$logger->debug('...done deleting!');
                }

                // should set related field values to null (MySQL is doing this for us as-is)
                if ($prop->getRelationType() == 'ONE-TO-MANY' && !$prop->getRelatedClass() == 'Alpha\Model\Tag') {
                    $relatedObjects = $prop->getRelated();

                    foreach ($relatedObjects as $object) {
                        $object->set($prop->getRelatedClassField(), null);
                        $object->save();
                    }
                }

                // in the case of tags, we will always remove the related tags once the Record is deleted
                if ($prop->getRelationType() == 'ONE-TO-MANY' && $prop->getRelatedClass() == 'Alpha\Model\Tag') {
                    // just making sure that the Relation is set to current ID as its transient
                    $prop->setValue($this->getID());
                    $relatedObjects = $prop->getRelated();

                    foreach ($relatedObjects as $object) {
                        $object->delete();
                    }
                }
            }
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->delete();

        if ($config->get('cache.provider.name') != '') {
            $this->removeFromCache();
        }

        if (method_exists($this, 'after_delete_callback')) {
            $this->{'after_delete_callback'}();
        }

        $this->clear();
        self::$logger->debug('<<delete');
    }

    /**
     * Delete all object instances from the database by the specified attribute matching the value provided. Returns the count of deleted records.
     *
     * @param string $attribute The name of the field to delete the objects by.
     * @param mixed  $value     The value of the field to delete the objects by.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedDeleteException
     */
    public function deleteAllByAttribute($attribute, $value): int
    {
        self::$logger->debug('>>deleteAllByAttribute(attribute=['.$attribute.'], value=['.$value.'])');

        if (method_exists($this, 'before_deleteAllByAttribute_callback')) {
            $this->{'before_deleteAllByAttribute_callback'}();
        }

        try {
            $doomedObjects = $this->loadAllByAttribute($attribute, $value);
            $deletedRowCount = 0;

            foreach ($doomedObjects as $object) {
                $object->delete();
                ++$deletedRowCount;
            }
        } catch (RecordNotFoundException $bonf) {
            // nothing found to delete
            self::$logger->warn($bonf->getMessage());

            return 0;
        } catch (AlphaException $e) {
            self::$logger->debug('<<deleteAllByAttribute [0]');
            throw new FailedDeleteException('Failed to delete objects, error is ['.$e->getMessage().']');
        }

        if (method_exists($this, 'after_deleteAllByAttribute_callback')) {
            $this->{'after_deleteAllByAttribute_callback'}();
        }

        self::$logger->debug('<<deleteAllByAttribute ['.$deletedRowCount.']');

        return $deletedRowCount;
    }

    /**
     * Gets the version_num of the object from the database (returns 0 if the Record is not saved yet).
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\RecordNotFoundException
     */
    public function getVersion(): int
    {
        self::$logger->debug('>>getVersion()');

        if (method_exists($this, 'before_getVersion_callback')) {
            $this->{'before_getVersion_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $ver = $provider->getVersion();

        if (method_exists($this, 'after_getVersion_callback')) {
            $this->{'after_getVersion_callback'}();
        }

        self::$logger->debug('<<getVersion ['.$ver.']');

        return $ver;
    }

    /**
     * Builds a new database table for the Record class.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function makeTable($checkIndexes = true): void
    {
        self::$logger->debug('>>makeTable()');

        if (method_exists($this, 'before_makeTable_callback')) {
            $this->{'before_makeTable_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->makeTable($checkIndexes);

        if (method_exists($this, 'after_makeTable_callback')) {
            $this->{'after_makeTable_callback'}();
        }

        self::$logger->info('Successfully created the table ['.$this->getTableName().'] for the class ['.get_class($this).']');

        self::$logger->debug('<<makeTable');
    }

    /**
     * Builds a new database table for the Record class to story it's history of changes.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function makeHistoryTable(): void
    {
        self::$logger->debug('>>makeHistoryTable()');

        if (method_exists($this, 'before_makeHistoryTable_callback')) {
            $this->{'before_makeHistoryTable_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->makeHistoryTable();

        if (method_exists($this, 'after_makeHistoryTable_callback')) {
            $this->{'after_makeHistoryTable_callback'}();
        }

        self::$logger->info('Successfully created the table ['.$this->getTableName().'_history] for the class ['.get_class($this).']');

        self::$logger->debug('<<makeHistoryTable');
    }

    /**
     * Re-builds the table if the model requirements have changed.  All data is lost!
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function rebuildTable(): void
    {
        self::$logger->debug('>>rebuildTable()');

        if (method_exists($this, 'before_rebuildTable_callback')) {
            $this->{'before_rebuildTable_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->rebuildTable();

        if (method_exists($this, 'after_rebuildTable_callback')) {
            $this->{'after_rebuildTable_callback'}();
        }

        self::$logger->debug('<<rebuildTable');
    }

    /**
     * Drops the table if the model requirements have changed.  All data is lost!
     *
     * @since 1.0
     *
     * @param string $tableName Optional table name, leave blank for the defined table for this class to be dropped
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function dropTable($tableName = null): void
    {
        self::$logger->debug('>>dropTable()');

        if (method_exists($this, 'before_dropTable_callback')) {
            $this->{'before_dropTable_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->dropTable($tableName);

        if (method_exists($this, 'after_dropTable_callback')) {
            $this->{'after_dropTable_callback'}();
        }

        self::$logger->debug('<<dropTable');
    }

    /**
     * Adds in a new class property without loosing existing data (does an ALTER TABLE query on the
     * database).
     *
     * @param string $propName The name of the new field to add to the database table.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function addProperty($propName): void
    {
        self::$logger->debug('>>addProperty(propName=['.$propName.'])');

        $config = ConfigProvider::getInstance();

        if (method_exists($this, 'before_addProperty_callback')) {
            $this->{'before_addProperty_callback'}();
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->addProperty($propName);

        if (method_exists($this, 'after_addProperty_callback')) {
            $this->{'after_addProperty_callback'}();
        }

        self::$logger->debug('<<addProperty');
    }

    /**
     * Populates the current business object from the provided hash array.
     *
     * @param array $hashArray
     *
     * @since 1.2.1
     */
    public function populateFromArray($hashArray): void
    {
        self::$logger->debug('>>populateFromArray(hashArray=['.print_r($hashArray, true).'])');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (isset($hashArray[$propName])) {
                if ($this->getPropObject($propName) instanceof Date || $this->getPropObject($propName) instanceof Timestamp) {
                    $this->getPropObject($propName)->populateFromString($hashArray[$propName]);
                } elseif ($this->getPropObject($propName) instanceof TypeInterface) {
                    $this->getPropObject($propName)->setValue($hashArray[$propName]);
                }

                if ($propName == 'version_num' && isset($hashArray['version_num'])) {
                    $this->version_num->setValue($hashArray['version_num']);
                }

                if ($this->getPropObject($propName) instanceof Relation) {
                    $rel = $this->getPropObject($propName);

                    if ($rel->getRelationType() == 'MANY-TO-MANY') {
                        $IDs = explode(',', $hashArray[$propName]);
                        $rel->setRelatedIDs($IDs);
                        $this->$propName = $rel;
                    }
                }
            }
        }

        self::$logger->debug('<<populateFromArray');
    }

    /**
     * Gets the maximum ID value from the database for this class type.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getMAX(): int
    {
        self::$logger->debug('>>getMAX()');

        if (method_exists($this, 'before_getMAX_callback')) {
            $this->{'before_getMAX_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $max = $provider->getMAX();

        if (method_exists($this, 'after_getMAX_callback')) {
            $this->{'after_getMAX_callback'}();
        }

        self::$logger->debug('<<getMAX ['.$max.']');

        return $max;
    }

    /**
     * Gets the count from the database for the amount of objects of this class.
     *
     * @param array $attributes The attributes to count the objects by (optional).
     * @param array $values     The values of the attributes to count the objects by (optional).
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getCount($attributes = array(), $values = array()): int
    {
        self::$logger->debug('>>getCount(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'])');

        if (method_exists($this, 'before_getCount_callback')) {
            $this->{'before_getCount_callback'}();
        }

        $config = ConfigProvider::getInstance();

        if (!is_array($attributes) || !is_array($values)) {
            throw new IllegalArguementException('Illegal arrays attributes=['.var_export($attributes, true).'] and values=['.var_export($values, true).'] provided to loadAllByAttributes');
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $count = $provider->getCount($attributes, $values);

        if (method_exists($this, 'after_getCount_callback')) {
            $this->{'after_getCount_callback'}();
        }

        self::$logger->debug('<<getCount ['.$count.']');

        return $count;
    }

    /**
     * Gets the count from the database for the amount of entries in the [tableName]_history table for this business object.  Only call
     * this method on classes where maintainHistory = true, otherwise an exception will be thrown.
     *
     * @since 1.2
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getHistoryCount(): int
    {
        self::$logger->debug('>>getHistoryCount()');

        if (method_exists($this, 'before_getHistoryCount_callback')) {
            $this->{'before_getHistoryCount_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $count = $provider->getHistoryCount();

        if (method_exists($this, 'after_getHistoryCount_callback')) {
            $this->{'after_getHistoryCount_callback'}();
        }

        self::$logger->debug('<<getHistoryCount ['.$count.']');

        return $count;
    }

    /**
     * Gets the ID for the object in 11 digit zero-padded format (same as getID()).
     *
     * @since 1.0
     */
    final public function getID(): string
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>getID()');
        $id = str_pad($this->ID, 11, '0', STR_PAD_LEFT);
        self::$logger->debug('<<getID ['.$id.']');

        return $id;
    }

    /**
     * Method for getting version number of the object.
     *
     * @since 1.0
     */
    public function getVersionNumber(): \Alpha\Model\Type\Integer
    {
        self::$logger->debug('>>getVersionNumber()');
        self::$logger->debug('<<getVersionNumber ['.$this->version_num.']');

        return $this->version_num;
    }

    /**
     * Populate all of the enum options for this object from the database.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    protected function setEnumOptions(): void
    {
        self::$logger->debug('>>setEnumOptions()');

        if (method_exists($this, 'before_setEnumOptions_callback')) {
            $this->{'before_setEnumOptions_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        try {
            $provider->setEnumOptions();
        } catch (NotImplementedException $e) {
            self::$logger->debug($e->getMessage());
        }

        self::$logger->debug('<<setEnumOptions');
    }

    /**
     * Generic getter method for accessing class properties.  Will use the method get.ucfirst($prop) instead if that
     * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use any
     * get.ucfirst($prop) method even if it exists, false otherwise (default).
     *
     * @param string $prop           The name of the object property to get.
     * @param bool   $noChildMethods Set to true if you do not want to use getters in the child object, defaults to false.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     * @throws \Alpha\Exception\AlphaException
     */
    public function get($prop, $noChildMethods = false): mixed
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }

        self::$logger->debug('>>get(prop=['.$prop.'], noChildMethods=['.$noChildMethods.'])');

        if (method_exists($this, 'before_get_callback')) {
            $this->{'before_get_callback'}();
        }

        if (empty($prop)) {
            throw new IllegalArguementException('Cannot call get with empty $prop arguement!');
        }

        // handle attributes with a get.ucfirst($prop) method
        if (!$noChildMethods && method_exists($this, 'get'.ucfirst($prop))) {
            if (method_exists($this, 'after_get_callback')) {
                $this->{'after_get_callback'}();
            }

            $methodName = 'get'.ucfirst($prop);

            self::$logger->debug('<<get ['.print_r($this->$methodName(), true).'])');

            return $this->$methodName();
        } else {
            // handle attributes with no dedicated child get.ucfirst($prop) method
            if (isset($this->$prop) && is_object($this->$prop) && method_exists($this->$prop, 'getValue')) {
                if (method_exists($this, 'after_get_callback')) {
                    $this->{'after_get_callback'}();
                }

                // complex types will have a getValue() method, return the value of that
                self::$logger->debug('<<get ['.$this->$prop->getValue().'])');

                return $this->$prop->getValue();
            } elseif (isset($this->$prop)) {
                if (method_exists($this, 'after_get_callback')) {
                    $this->{'after_get_callback'}();
                }

                // simple types returned as-is
                self::$logger->debug('<<get ['.print_r($this->$prop, true).'])');

                return $this->$prop;
            } else {
                self::$logger->debug('<<get');
                throw new AlphaException('Could not access the property ['.$prop.'] on the object of class ['.get_class($this).']');
            }
        }
    }

    /**
     * Generic setter method for setting class properties.  Will use the method set.ucfirst($prop) instead if that
     * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use
     * any get.ucfirst($prop) method even if it exists, false otherwise (default).
     *
     * @param string $prop           The name of the property to set.
     * @param mixed  $value          The value of the property to set.
     * @param bool   $noChildMethods Set to true if you do not want to use setters in the child object, defaults to false.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function set($prop, $value, $noChildMethods = false): void
    {
        self::$logger->debug('>>set(prop=['.$prop.'], $value=['.print_r($value, true).'], noChildMethods=['.$noChildMethods.'])');

        if (method_exists($this, 'beforeSet')) {
            $this->{'beforeSet'}();
        }

        // handle attributes with a set.ucfirst($prop) method
        if (!$noChildMethods && method_exists($this, 'set'.ucfirst($prop))) {
            if (method_exists($this, 'after_set_callback')) {
                $this->{'after_set_callback'}();
            }

            $methodName = 'set'.ucfirst($prop);

            $this->$methodName($value);
        } else {
            // handle attributes with no dedicated child set.ucfirst($prop) method
            if (isset($this->$prop)) {
                if (method_exists($this, 'after_set_callback')) {
                    $this->{'after_set_callback'}();
                }

                // complex types will have a setValue() method to call
                if (is_object($this->$prop) && get_class($this->$prop) !== false) {
                    if (mb_strtoupper(get_class($this->$prop)) != 'DATE' && mb_strtoupper(get_class($this->$prop)) != 'TIMESTAMP') {
                        $this->$prop->setValue($value);
                    } else {
                        // Date and Timestamp objects have a special setter accepting a string
                        $this->$prop->populateFromString($value);
                    }
                } else {
                    // simple types set directly
                    $this->$prop = $value;
                }
            } else {
                throw new AlphaException('Could not set the property ['.$prop.'] on the object of the class ['.get_class($this).'].  Property may not exist, or else does not have a setValue() method and is private or protected.');
            }
        }
        self::$logger->debug('<<set');
    }

    /**
     * Gets the property object rather than the value for complex attributes.  Returns false if
     * the property exists but is private.
     *
     * @param string $prop The name of the property we are getting.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getPropObject($prop): mixed
    {
        self::$logger->debug('>>getPropObject(prop=['.$prop.'])');

        if (method_exists($this, 'before_getPropObject_callback')) {
            $this->{'before_getPropObject_callback'}();
        }

        // get the class attributes
        $reflection = new \ReflectionObject($this);
        $properties = $reflection->getProperties();

        // firstly, check for private
        $attribute = new ReflectionProperty($this, $prop);

        if ($attribute->isPrivate()) {
            if (method_exists($this, 'after_getPropObject_callback')) {
                $this->{'after_getPropObject_callback'}();
            }

            self::$logger->debug('<<getPropObject [false]');

            return false;
        }

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if ($prop == $propName) {
                if (method_exists($this, 'after_getPropObject_callback')) {
                    $this->{'after_getPropObject_callback'}();
                }

                self::$logger->debug('<<getPropObject ['.var_export($this->$prop, true).']');

                return $this->$prop;
            }
        }

        self::$logger->debug('<<getPropObject');
        throw new IllegalArguementException('Could not access the property object ['.$prop.'] on the object of class ['.get_class($this).']');
    }

    /**
     * Checks to see if the table exists in the database for the current business class.
     *
     * @param bool $checkHistoryTable Set to true if you want to check for the existance of the _history table for this DAO.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkTableExists($checkHistoryTable = false): bool
    {
        self::$logger->debug('>>checkTableExists()');

        if (method_exists($this, 'before_checkTableExists_callback')) {
            $this->{'before_checkTableExists_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $tableExists = $provider->checkTableExists($checkHistoryTable);

        if (method_exists($this, 'after_checkTableExists_callback')) {
            $this->{'after_checkTableExists_callback'}();
        }

        self::$logger->debug('<<checkTableExists ['.$tableExists.']');

        return $tableExists;
    }

    /**
     * Static method to check the database and see if the table for the indicated Record class name
     * exists (assumes table name will be $recordClassName less "Object").
     *
     * @param string $recordClassName       The name of the business object class we are checking.
     * @param bool   $checkHistoryTable Set to true if you want to check for the existance of the _history table for this DAO.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public static function checkRecordTableExists($recordClassName, $checkHistoryTable = false): bool
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>checkRecordTableExists(RecordClassName=['.$recordClassName.'])');

        $config = ConfigProvider::getInstance();

        $provider = $config->get('db.provider.name');

        $tableExists = $provider::checkRecordTableExists($recordClassName, $checkHistoryTable);

        self::$logger->debug('<<checkRecordTableExists ['.($tableExists ? 'true' : 'false').']');

        return $tableExists;
    }

    /**
     * Checks to see if the table in the database matches (for fields) the business class definition, i.e. if the
     * database table is in sync with the class definition.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkTableNeedsUpdate(): bool
    {
        self::$logger->debug('>>checkTableNeedsUpdate()');

        $config = ConfigProvider::getInstance();

        if (method_exists($this, 'before_checkTableNeedsUpdate_callback')) {
            $this->{'before_checkTableNeedsUpdate_callback'}();
        }

        $tableExists = $this->checkTableExists();

        if (!$tableExists) {
            self::$logger->debug('<<checkTableNeedsUpdate [true]');

            return true;
        } else {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord($this);
            $updateRequired = $provider->checkTableNeedsUpdate();

            if (method_exists($this, 'after_checkTableNeedsUpdate_callback')) {
                $this->{'after_checkTableNeedsUpdate_callback'}();
            }

            self::$logger->debug('<<checkTableNeedsUpdate ['.$updateRequired.']');

            return $updateRequired;
        }
    }

    /**
     * Returns an array containing any properties on the class which have not been created on the database
     * table yet.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function findMissingFields(): array
    {
        self::$logger->debug('>>findMissingFields()');

        $config = ConfigProvider::getInstance();

        if (method_exists($this, 'before_findMissingFields_callback')) {
            $this->{'before_findMissingFields_callback'}();
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $missingFields = $provider->findMissingFields();

        if (method_exists($this, 'after_findMissingFields_callback')) {
            $this->{'after_findMissingFields_callback'}();
        }

        self::$logger->debug('<<findMissingFields ['.var_export($missingFields, true).']');

        return $missingFields;
    }

    /**
     * Getter for the TABLE_NAME, the name of the table in the database for this class, which should be set by a child of this class.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getTableName(): string
    {
        self::$logger->debug('>>getTableName()');

        $className = get_class($this);

        $tableName = $className::TABLE_NAME;

        if (!empty($tableName)) {
            self::$logger->debug('<<getTableName ['.$tableName.']');

            return $tableName;
        } else {
            throw new AlphaException('Error: no TABLE_NAME constant set for the class '.get_class($this));
        }
    }

    /**
     * Method for getting the ID of the person who created this record.
     *
     * @since 1.0
     */
    public function getCreatorId(): \Alpha\Model\Type\Integer
    {
        self::$logger->debug('>>getCreatorId()');
        self::$logger->debug('<<getCreatorId ['.$this->created_by.']');

        return $this->created_by;
    }

    /**
     * Method for getting the ID of the person who updated this record.
     *
     * @since 1.0
     */
    public function getUpdatorId(): \Alpha\Model\Type\Integer
    {
        self::$logger->debug('>>getUpdatorId()');
        self::$logger->debug('<<getUpdatorId ['.$this->updated_by.']');

        return $this->updated_by;
    }

    /**
     * Method for getting the date/time of when the Record was created.
     *
     * @since 1.0
     */
    public function getCreateTS(): \Alpha\Model\Type\Timestamp
    {
        self::$logger->debug('>>getCreateTS()');
        self::$logger->debug('<<getCreateTS ['.$this->created_ts.']');

        return $this->created_ts;
    }

    /**
     * Method for getting the date/time of when the Record was last updated.
     *
     * @since 1.0
     */
    public function getUpdateTS(): \Alpha\Model\Type\Timestamp
    {
        self::$logger->debug('>>getUpdateTS()');
        self::$logger->debug('<<getUpdateTS ['.$this->updated_ts.']');

        return $this->updated_ts;
    }

    /**
     * Adds the name of the attribute provided to the list of transient (non-saved) attributes for this record.
     *
     * @param string $attributeName The name of the attribute to not save.
     *
     * @since 1.0
     */
    public function markTransient($attributeName): void
    {
        self::$logger->debug('>>markTransient(attributeName=['.$attributeName.'])');
        self::$logger->debug('<<markTransient');
        array_push($this->transientAttributes, $attributeName);
    }

    /**
     * Removes the name of the attribute provided from the list of transient (non-saved) attributes for this record,
     * ensuring that it will be saved on the next attempt.
     *
     * @param string $attributeName The name of the attribute to save.
     *
     * @since 1.0
     */
    public function markPersistent($attributeName): void
    {
        self::$logger->debug('>>markPersistent(attributeName=['.$attributeName.'])');
        self::$logger->debug('<<markPersistent');
        $this->transientAttributes = array_diff($this->transientAttributes, array($attributeName));
    }

    /**
     * Adds the name of the attribute(s) provided to the list of unique (constrained) attributes for this record.
     *
     * @param string $attribute1Name The first attribute to mark unique in the database.
     * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
     * @param string $attribute3Name The third attribute to mark unique in the databse (optional, use only for composite keys).
     *
     * @since 1.0
     */
    protected function markUnique($attribute1Name, $attribute2Name = '', $attribute3Name = ''): void
    {
        self::$logger->debug('>>markUnique(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'], attribute3Name=['.$attribute3Name.'])');

        if (empty($attribute2Name)) {
            array_push($this->uniqueAttributes, $attribute1Name);
        } else {
            // Process composite unique keys: add them seperated by a + sign
            if ($attribute3Name == '') {
                $attributes = $attribute1Name.'+'.$attribute2Name;
            } else {
                $attributes = $attribute1Name.'+'.$attribute2Name.'+'.$attribute3Name;
            }

            array_push($this->uniqueAttributes, $attributes);
        }

        self::$logger->debug('<<markUnique');
    }

    /**
     * Returns the array of names of unique attributes on this record.
     *
     * @since 1.1
     */
    public function getUniqueAttributes(): array
    {
        self::$logger->debug('>>getUniqueAttributes()');
        self::$logger->debug('<<getUniqueAttributes: ['.print_r($this->uniqueAttributes, true).']');

        return $this->uniqueAttributes;
    }

    /**
     * Gets an array of all of the names of the active database indexes for this class.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function getIndexes(): array
    {
        self::$logger->debug('>>getIndexes()');

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $indexNames = $provider->getIndexes();

        self::$logger->debug('<<getIndexes ['.print_r($indexNames, true).']');

        return $indexNames;
    }

    /**
     * Creates a foreign key constraint (index) in the database on the given attribute.
     *
     * @param string $attributeName         The name of the attribute to apply the index on.
     * @param string $relatedClass          The name of the related class in the format "NameObject".
     * @param string $relatedClassAttribute The name of the field to relate to on the related class.
     * @param string $indexName             The optional name for the index, will calculate if not provided.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedIndexCreateException
     */
    public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute, $indexName = null): void
    {
        self::$logger->debug('>>createForeignIndex(attributeName=['.$attributeName.'], relatedClass=['.$relatedClass.'], relatedClassAttribute=['.$relatedClassAttribute.'], indexName=['.$indexName.']');

        $config = ConfigProvider::getInstance();

        if (method_exists($this, 'before_createForeignIndex_callback')) {
            $this->{'before_createForeignIndex_callback'}();
        }

        $relatedRecord = new $relatedClass();
        $tableName = $relatedRecord->getTableName();

        // if the relation is on itself (table-wise), exit without attempting to create the foreign keys
        if ($this->getTableName() == $tableName) {
            self::$logger->debug('<<createForeignIndex');

            return;
        }

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute, $indexName);

        if (method_exists($this, 'after_createForeignIndex_callback')) {
            $this->{'after_createForeignIndex_callback'}();
        }

        self::$logger->debug('<<createForeignIndex');
    }

    /**
     * Creates a unique index in the database on the given attribute(s).
     *
     * @param string $attribute1Name The first attribute to mark unique in the database.
     * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
     * @param string $attribute3Name The third attribute to mark unique in the databse (optional, use only for composite keys).
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedIndexCreateException
     */
    public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = ''): void
    {
        self::$logger->debug('>>createUniqueIndex(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'], attribute3Name=['.$attribute3Name.'])');

        if (method_exists($this, 'before_createUniqueIndex_callback')) {
            $this->{'before_createUniqueIndex_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $provider->createUniqueIndex($attribute1Name, $attribute2Name, $attribute3Name);

        if (method_exists($this, 'after_createUniqueIndex_callback')) {
            $this->{'before_createUniqueIndex_callback'}();
        }

        self::$logger->debug('<<createUniqueIndex');
    }

    /**
     * Gets the data labels array.
     *
     * @since 1.0
     */
    public function getDataLabels(): array
    {
        self::$logger->debug('>>getDataLabels()');
        self::$logger->debug('<<getDataLabels() ['.var_export($this->dataLabels, true).'])');

        return $this->dataLabels;
    }

    /**
     * Sets the data labels array.
     *
     * @param array $labels
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.2
     */
    public function setDataLabels($labels): void
    {
        self::$logger->debug('>>setDataLabels(labels=['.print_r($labels, true).'])');

        if (is_array($labels)) {
            $this->dataLabels = $labels;
        } else {
            throw new IllegalArguementException('The value ['.print_r($labels, true).'] provided to setDataLabels() is not a valid array!');
        }

        self::$logger->debug('<<setDataLabels()');
    }

    /**
     * Gets the data label for the given attribute name.
     *
     * @param $att The attribute name to get the label for.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    public function getDataLabel($att): string
    {
        self::$logger->debug('>>getDataLabel(att=['.$att.'])');

        if (in_array($att, array_keys($this->dataLabels))) {
            self::$logger->debug('<<getDataLabel ['.$this->dataLabels[$att].'])');

            return $this->dataLabels[$att];
        } else {
            self::$logger->debug('<<getDataLabel');
            throw new IllegalArguementException('No data label found on the class ['.get_class($this).'] for the attribute ['.$att.']');
        }
    }

    /**
     * Loops over the core and custom Record directories and builds an array of all of the Record class names in the system.
     *
     * @since 1.0
     */
    public static function getRecordClassNames(): array
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>getRecordClassNames()');

        $config = ConfigProvider::getInstance();

        $classNameArray = array();

        if (file_exists($config->get('app.root').'src/Model')) { // it is possible it has not been created yet...
            // first get any custom records
            $handle = opendir($config->get('app.root').'src/Model');

            // loop over the business object directory
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/.php/', $file)) {
                    $classname = 'Model\\'.mb_substr($file, 0, -4);

                    if (class_exists($classname)) {
                        array_push($classNameArray, $classname);
                    }
                }
            }
        }

        // now loop over the core records provided with Alpha
        if (file_exists($config->get('app.root').'Alpha/Model')) {
            $handle = opendir($config->get('app.root').'Alpha/Model');
        } else {
            $handle = opendir($config->get('app.root').'vendor/alphadevx/alpha/Alpha/Model');
        }

        // loop over the business object directory
        while (false !== ($file = readdir($handle))) {
            if (preg_match('/.php/', $file)) {
                $classname = 'Alpha\\Model\\'.mb_substr($file, 0, -4);

                if (class_exists($classname) && substr($classname, 0, 24) != 'Alpha\\Model\\ActiveRecord') {
                    array_push($classNameArray, $classname);
                }
            }
        }

        asort($classNameArray);
        self::$logger->debug('<<getRecordClassNames ['.var_export($classNameArray, true).']');

        return $classNameArray;
    }

    /**
     * Get the array of default attribute names.
     *
     * @since 1.0
     */
    public function getDefaultAttributes(): array
    {
        self::$logger->debug('>>getDefaultAttributes()');
        self::$logger->debug('<<getDefaultAttributes ['.var_export($this->defaultAttributes, true).']');

        return $this->defaultAttributes;
    }

    /**
     * Get the array of transient attribute names.
     *
     * @since 1.0
     */
    public function getTransientAttributes(): array
    {
        self::$logger->debug('>>getTransientAttributes()');
        self::$logger->debug('<<getTransientAttributes ['.var_export($this->transientAttributes, true).']');

        return $this->transientAttributes;
    }

    /**
     * Get the array of persistent attribute names, i.e. those that are saved in the database.
     *
     * @since 1.0
     */
    public function getPersistentAttributes(): array
    {
        self::$logger->debug('>>getPersistentAttributes()');

        $attributes = array();

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            // filter transient attributes
            if (!in_array($propName, $this->transientAttributes)) {
                array_push($attributes, $propName);
            }
        }

        self::$logger->debug('<<getPersistentAttributes ['.var_export($attributes, true).']');

        return $attributes;
    }

    /**
     * Setter for the Object ID (ID).
     *
     * @param int $ID The Object ID.
     *
     * @since 1.0
     */
    public function setID($ID): void
    {
        self::$logger->debug('>>setID(ID=['.$ID.'])');
        self::$logger->debug('<<setID');
        $this->ID = $ID;
    }

    /**
     * Inspector to see if the business object is transient (not presently stored in the database).
     *
     * @since 1.0
     */
    public function isTransient(): bool
    {
        self::$logger->debug('>>isTransient()');

        if (empty($this->ID) || !isset($this->ID) || $this->ID == '00000000000') {
            self::$logger->debug('<<isTransient [true]');

            return true;
        } else {
            self::$logger->debug('<<isTransient [false]');

            return false;
        }
    }

    /**
     * Get the last database query run on this object.
     *
     * @since 1.0
     */
    public function getLastQuery(): string
    {
        self::$logger->debug('>>getLastQuery()');
        self::$logger->debug('<<getLastQuery ['.$this->lastQuery.']');

        return $this->lastQuery;
    }

    /**
     * Unsets all of the attributes of this object to null.
     *
     * @since 1.0
     */
    private function clear(): void
    {
        self::$logger->debug('>>clear()');

        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!$propObj->isPrivate()) {
                unset($this->$propName);
            }
        }

        self::$logger->debug('<<clear');
    }

    /**
     * Reloads the object from the database, overwritting any attribute values in memory.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function reload(): void
    {
        self::$logger->debug('>>reload()');

        if (!$this->isTransient()) {
            $this->load($this->getID());
        } else {
            throw new AlphaException('Cannot reload transient object from database!');
        }
        self::$logger->debug('<<reload');
    }

    /**
     * Checks that a record exists for the Record in the database.
     *
     * @param int $ID The Object ID of the object we want to see whether it exists or not.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public function checkRecordExists($ID): bool
    {
        self::$logger->debug('>>checkRecordExists(ID=['.$ID.'])');

        if (method_exists($this, 'before_checkRecordExists_callback')) {
            $this->{'before_checkRecordExists_callback'}();
        }

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $recordExists = $provider->checkRecordExists($ID);

        if (method_exists($this, 'after_checkRecordExists_callback')) {
            $this->{'after_checkRecordExists_callback'}();
        }

        self::$logger->debug('<<checkRecordExists ['.$recordExists.']');

        return $recordExists;
    }

    /**
     * Checks to see if the table name matches the classname, and if not if the table
     * name matches the classname name of another record, i.e. the table is used to store
     * multiple types of records.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\BadTableNameException
     */
    public function isTableOverloaded(): bool
    {
        self::$logger->debug('>>isTableOverloaded()');

        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord($this);
        $isOverloaded = $provider->isTableOverloaded();

        self::$logger->debug('<<isTableOverloaded ['.$isOverloaded.']');

        return $isOverloaded;
    }

    /**
     * Starts a new database transaction.
     *
     * @param ActiveRecord $record The ActiveRecord instance to pass to the database provider. Leave empty to have a new Person passed.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public static function begin($record = null): void
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>begin()');

        $config = ConfigProvider::getInstance();

        if (isset($record)) {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord($record);
        } else {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord(new Person());
        }

        try {
            $provider->begin();
        } catch (\Exception $e) {
            throw new AlphaException('Error beginning a new transaction, error is ['.$e->getMessage().']');
        }

        self::$logger->debug('<<begin');
    }

    /**
     * Commits the current database transaction.
     *
     * @param ActiveRecord $record The ActiveRecord instance to pass to the database provider. Leave empty to have a new Person passed.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\FailedSaveException
     */
    public static function commit($record = null): void
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>commit()');

        $config = ConfigProvider::getInstance();

        if (isset($record)) {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord($record);
        } else {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord(new Person());
        }

        try {
            $provider->commit();
        } catch (\Exception $e) {
            throw new FailedSaveException('Error commiting a transaction, error is ['.$e->getMessage().']');
        }

        self::$logger->debug('<<commit');
    }

    /**
     * Aborts the current database transaction.
     *
     * @param ActiveRecord $record The ActiveRecord instance to pass to the database provider. Leave empty to have a new Person passed.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    public static function rollback($record = null): void
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>rollback()');

        $config = ConfigProvider::getInstance();

        if (isset($record)) {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord($record);
        } else {
            $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
            $provider->setRecord(new Person());
        }

        try {
            $provider->rollback();
        } catch (\Exception $e) {
            throw new FailedSaveException('Error aborting a transaction, error is ['.$e->getMessage().']');
        }

        self::$logger->debug('<<rollback');
    }

    /**
     * Static method that tries to determine if the system database has been installed or not.
     *
     * @since 1.0
     */
    public static function isInstalled(): bool
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecord');
        }
        self::$logger->debug('>>isInstalled()');

        /*
         * Install conditions are:
         *
         * 1. person table exists
         * 2. rights table exists
         */
        if (self::checkRecordTableExists('Alpha\Model\Person') && self::checkRecordTableExists('Alpha\Model\Rights')) {
            self::$logger->debug('<<isInstalled [true]');

            return true;
        } else {
            self::$logger->debug('<<isInstalled [false]');

            return false;
        }
    }

    /**
     * Returns true if the Record has a Relation property called tags, false otherwise.
     *
     * @since 1.0
     */
    public function isTagged(): bool
    {
        if (property_exists($this, 'taggedAttributes') && property_exists($this, 'tags') && $this->{'tags'} instanceof \Alpha\Model\Type\Relation) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the contents of the taggedAttributes array, or an empty array if that does not exist.
     *
     * @since 1.2.3
     */
    public function getTaggedAttributes(): array
    {
        if ($this->isTagged()) {
            return $this->{'taggedAttributes'};
        } else {
            return array();
        }
    }

    /**
     * Setter for the Record version number.
     *
     * @param int $versionNumber The version number.
     *
     * @since 1.0
     */
    private function setVersion($versionNumber): void
    {
        $this->version_num->setValue($versionNumber);
    }

    /**
     * Cast a Record to another type of record.  A new Record will be returned with the same ID and
     * version_num as the old record, so this is NOT a true cast but is a copy.  All attribute
     * values will be copied accross.
     *
     * @param string                    $targetClassName     The fully-qualified name of the target Record class.
     * @param \Alpha\Model\ActiveRecord $originalRecord      The original business object.
     *
     * @since 1.0
     */
    public function cast($targetClassName, $originalRecord): \Alpha\Model\ActiveRecord
    {
        $record = new $targetClassName();
        $record->setID($originalRecord->getID());
        $record->setVersion($originalRecord->getVersion());

        // get the class attributes
        $originalRecordreflection = new ReflectionClass(get_class($originalRecord));
        $originalRecordproperties = $originalRecordreflection->getProperties();
        $newRecordreflection = new ReflectionClass($targetClassName);
        $newRecordproperties = $newRecordreflection->getProperties();

        // copy the property values from the old Record to the new record

        if (count($originalRecordproperties) < count($newRecordproperties)) {
            // the original Record is smaller, so loop over its properties
            foreach ($originalRecordproperties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->transientAttributes)) {
                    $record->set($propName, $originalRecord->get($propName));
                }
            }
        } else {
            // the new Record is smaller, so loop over its properties
            foreach ($newRecordproperties as $propObj) {
                $propName = $propObj->name;
                if (!in_array($propName, $this->transientAttributes)) {
                    $record->set($propName, $originalRecord->get($propName));
                }
            }
        }

        return $record;
    }

    /**
     * Returns the simple class name, stripped of the namespace.
     *
     * @since 1.0
     */
    public function getFriendlyClassName(): string
    {
        $reflectClass = new ReflectionClass($this);

        return $reflectClass->getShortname();
    }

    /**
     * Check to see if an attribute exists on the record.
     *
     * @param string $attribute The attribute name.
     *
     * @since 1.0
     */
    public function hasAttribute($attribute): bool
    {
        return property_exists($this, $attribute);
    }

    /**
     * Stores the business object to the configured cache instance.
     *
     * @since 1.1
     */
    public function addToCache(): void
    {
        self::$logger->debug('>>addToCache()');
        $config = ConfigProvider::getInstance();

        try {
            $cache = ServiceFactory::getInstance($config->get('cache.provider.name'), 'Alpha\Util\Cache\CacheProviderInterface');
            $cache->set(get_class($this).'-'.$this->getID(), $this, 3600);
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to store a business object to the ['.$config->get('cache.provider.name').'] 
                instance: ['.$e->getMessage().']');
        }

        self::$logger->debug('<<addToCache');
    }

    /**
     * Removes the business object from the configured cache instance.
     *
     * @since 1.1
     */
    public function removeFromCache(): void
    {
        self::$logger->debug('>>removeFromCache()');
        $config = ConfigProvider::getInstance();

        try {
            $cache = ServiceFactory::getInstance($config->get('cache.provider.name'), 'Alpha\Util\Cache\CacheProviderInterface');
            $cache->delete(get_class($this).'-'.$this->getID());
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to remove a business object from ['.$config->get('cache.provider.name').']
                instance: ['.$e->getMessage().']');
        }

        self::$logger->debug('<<removeFromCache');
    }

    /**
     * Attempts to load the business object from the configured cache instance, returns true on a cache hit.
     *
     * @since 1.1
     */
    public function loadFromCache(): bool
    {
        self::$logger->debug('>>loadFromCache()');
        $config = ConfigProvider::getInstance();

        try {
            $cache = ServiceFactory::getInstance($config->get('cache.provider.name'), 'Alpha\Util\Cache\CacheProviderInterface');
            $record = $cache->get(get_class($this).'-'.$this->getID());

            if (!$record) {
                self::$logger->debug('Cache miss on key ['.get_class($this).'-'.$this->getID().']');
                self::$logger->debug('<<loadFromCache: [false]');

                return false;
            } else {
                // get the class attributes
                $reflection = new ReflectionClass(get_class($this));
                $properties = $reflection->getProperties();

                foreach ($properties as $propObj) {
                    $propName = $propObj->name;

                    // filter transient attributes
                    if (!in_array($propName, $this->transientAttributes)) {
                        $this->set($propName, $record->get($propName, true));
                    } elseif (!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {
                        $prop = $this->getPropObject($propName);

                        // handle the setting of ONE-TO-MANY relation values
                        if ($prop->getRelationType() == 'ONE-TO-MANY') {
                            $this->set($propObj->name, $this->getID());
                        }
                    }
                }

                self::$logger->debug('<<loadFromCache: [true]');

                return true;
            }
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to load a business object from ['.$config->get('cache.provider.name').']
             instance: ['.$e->getMessage().']');

            self::$logger->debug('<<loadFromCache: [false]');

            return false;
        }
    }

    /**
     * Sets the last query executed on this business object.
     *
     * @param string $query
     *
     * @since 1.1
     */
    public function setLastQuery($query): void
    {
        self::$logger->sql($query);
        $this->lastQuery = $query;
    }

    /**
     * Re-initialize the static logger property on the Record after de-serialize, as PHP does
     * not serialize static properties.
     *
     * @since 1.2
     */
    public function __wakeup(): void
    {
        if (self::$logger == null) {
            self::$logger = new Logger(get_class($this));
        }
    }

    /**
     * Sets maintainHistory attribute on this DAO.
     *
     * @param bool $maintainHistory
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.2
     */
    public function setMaintainHistory($maintainHistory): void
    {
        if (!is_bool($maintainHistory)) {
            throw new IllegalArguementException('Non-boolean value ['.$maintainHistory.'] passed to setMaintainHistory method!');
        }

        $this->maintainHistory = $maintainHistory;
    }

    /**
     * Gets the value of the  maintainHistory attribute.
     *
     * @since 1.2
     */
    public function getMaintainHistory(): bool
    {
        return $this->maintainHistory;
    }

    /**
     * Return a hash array of the object containing attribute names and simplfied values.
     *
     * @since  1.2.4
     */
    public function toArray(): array
    {
        // get the class attributes
        $reflection = new ReflectionClass(get_class($this));
        $properties = $reflection->getProperties();

        $propArray = array();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;

            if (!in_array($propName, $this->transientAttributes)) {
                $val = $this->get($propName);

                if (is_object($val)) {
                    $val = $val->getValue();
                }

                $propArray[$propName] = $val;
            }
        }

        return $propArray;
    }

    /**
     * Check to see if the configured database exists.
     *
     * @since 2.0
     */
    public static function checkDatabaseExists(): bool
    {
        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord(new Person());

        return $provider->checkDatabaseExists();
    }

    /**
     * Creates the configured database.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 2.0
     */
    public static function createDatabase(): void
    {
        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord(new Person());
        $provider->createDatabase();
    }

    /**
     * Drops the configured database.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 2.0
     */
    public static function dropDatabase(): void
    {
        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord(new Person());
        $provider->dropDatabase();
    }

    /**
     * Backup the configured database.
     *
     * @param string $targetFile The file that the backup data will be written to.
     *
     * @throws \Alpha\Exception\AlphaException
     *
     * @since 3.0
     */
    public static function backupDatabase($targetFile): void
    {
        $config = ConfigProvider::getInstance();

        $provider = ServiceFactory::getInstance($config->get('db.provider.name'), 'Alpha\Model\ActiveRecordProviderInterface');
        $provider->setRecord(new Person());
        $provider->backupDatabase($targetFile);
    }
}
