<?php

/**
 * MySQL DAO provider (uses the MySQLi native API in PHP).
 *
 * @package alpha::model
 * @since 1.1
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class AlphaDAOProviderMySQL implements AlphaDAOProviderInterface {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.1
	 */
	private static $logger = null;

	/**
	 * Datebase connection
	 *
	 * @var mysqli
	 * @since 1.1
	 */
	private static $connection;

	/**
	 * The business object that we are mapping back to
	 *
	 * @var AlphaDAO
	 * @since 1.1
	 */
	private $BO;

	/**
	 * The constructor
	 *
	 * @since 1.1
	 */
	public function __construct() {
		self::$logger = new Logger('AlphaDAOProviderMySQL');
		self::$logger->debug('>>__construct()');

		self::$logger->debug('<<__construct');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getConnection()
	 */
	public static function getConnection() {
		global $config;

		if (!isset(self::$connection)) {
			self::$connection = new mysqli($config->get('db.hostname'), $config->get('db.username'), $config->get('db.password'), $config->get('db.name'));

			if (mysqli_connect_error()) {
			    self::$logger->fatal('Could not connect to database: ['.mysqli_connect_errno().'] '.mysqli_connect_error());
			}
		}

		return self::$connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::disconnect()
	 */
	public static function disconnect() {
		if (isset(self::$connection)) {
			self::$connection->close();
			self::$connection = null;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getLastDatabaseError()
	 */
	public static function getLastDatabaseError() {
		return self::getConnection()->error;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::query()
	 */
	public function query($sqlQuery) {
		$this->BO->setLastQuery($sqlQuery);

		$resultArray = array();

		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new CustomQueryException('Failed to run the custom query, MySql error is ['.self::getConnection()->error.'], query ['.$sqlQuery.']');
			return array();
		}else{
			while($row = $result->fetch_array(MYSQLI_ASSOC)) {
				array_push($resultArray, $row);
			}

			return $resultArray;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::load()
	 */
	public function load($OID) {
		self::$logger->debug('>>load(OID=['.print_r($OID, true).'])');

        global $config;

		$attributes = $this->BO->getPersistentAttributes();
		$fields = '';
		foreach($attributes as $att)
			$fields .= $att.',';
		$fields = mb_substr($fields, 0, -1);

		$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE OID = ? LIMIT 1;';
		$this->BO->setLastQuery($sqlQuery);
		$stmt = self::getConnection()->stmt_init();

		$row = array();

		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $OID);
			$stmt->execute();

			$result = $this->bindResult($stmt);
			if(isset($result[0]))
				$row = $result[0];

			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.'], OID is ['.print_r($OID, true).'], MySql error is ['.self::getConnection()->error.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();

				throw new BONotFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->BO->getTableName().'] did not exist so had to create!');
			}

			return;
		}

		if(!isset($row['OID']) || $row['OID'] < 1) {
			throw new BONotFoundException('Failed to load object of OID ['.$OID.'] not found in database.');
			self::$logger->debug('<<load');
			return;
		}

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		try {
			foreach($properties as $propObj) {
				$propName = $propObj->name;

				// filter transient attributes
				if(!in_array($propName, $this->BO->getTransientAttributes())) {
					$this->BO->set($propName, $row[$propName]);
				}elseif(!$propObj->isPrivate() && $this->BO->getPropObject($propName) instanceof Relation) {
					$prop = $this->BO->getPropObject($propName);

					// handle the setting of ONE-TO-MANY relation values
					if($prop->getRelationType() == 'ONE-TO-MANY') {
						$this->BO->set($propObj->name, $this->BO->getOID());
					}

					// handle the setting of MANY-TO-ONE relation values
					if($prop->getRelationType() == 'MANY-TO-ONE') {
						$this->BO->set($propObj->name, $row[$propName]);
					}
				}
			}
		}catch (IllegalArguementException $e) {
			self::$logger->warn('Bad data stored in the table ['.$this->BO->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
		}catch (PHPException $e) {
			// it is possible that the load failed due to the table not being up-to-date
			if($this->BO->checkTableNeedsUpdate()) {
				$missingFields = $this->BO->findMissingFields();

				$count = count($missingFields);

				for($i = 0; $i < $count; $i++)
					$this->BO->addProperty($missingFields[$i]);

				throw new BONotFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->BO->getTableName().'] was out of sync with the database so had to be updated!');
				self::$logger->warn('<<load');
				return;
			}
		}

		self::$logger->debug('<<load ['.$OID.']');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadByAttribute()
	 */
	public function loadByAttribute($attribute, $value, $ignoreClassType=false, $loadAttributes=array()) {
		self::$logger->debug('>>loadByAttribute(attribute=['.$attribute.'], value=['.$value.'], ignoreClassType=['.$ignoreClassType.'],
			loadAttributes=['.var_export($loadAttributes, true).'])');

		if(count($loadAttributes) == 0)
			$attributes = $this->BO->getPersistentAttributes();
		else
			$attributes = $loadAttributes;

		$fields = '';
		foreach($attributes as $att)
			$fields .= $att.',';
		$fields = mb_substr($fields, 0, -1);

		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = ? AND classname = ? LIMIT 1;';
		else
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = ? LIMIT 1;';

		self::$logger->debug('Query=['.$sqlQuery.']');

		$this->BO->setLastQuery($sqlQuery);
		$stmt = self::getConnection()->stmt_init();

		$row = array();

		if($stmt->prepare($sqlQuery)) {
			if($this->BO->getPropObject($attribute) instanceof Integer) {
				if(!$ignoreClassType && $this->BO->isTableOverloaded()) {
					$stmt->bind_param('is', $value, get_class($this->BO));
				}else{
					$stmt->bind_param('i', $value);
				}
			}else{
				if(!$ignoreClassType && $this->BO->isTableOverloaded()) {
					$stmt->bind_param('ss', $value, get_class($this->BO));
				}else{
					$stmt->bind_param('s', $value);
				}
			}

			$stmt->execute();

			$result = $this->bindResult($stmt);

			if(isset($result[0]))
				$row = $result[0];

			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();

				throw new BONotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
			}
			return;
		}

		if(!isset($row['OID']) || $row['OID'] < 1) {
			throw new BONotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], not found in database.');
			self::$logger->debug('<<loadByAttribute');
			return;
		}

		$this->OID = $row['OID'];

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		try {
			foreach($properties as $propObj) {
				$propName = $propObj->name;

				if(isset($row[$propName])) {
					// filter transient attributes
					if(!in_array($propName, $this->BO->getTransientAttributes())) {
						$this->BO->set($propName, $row[$propName]);
					}elseif(!$propObj->isPrivate() && $this->BO->get($propName) != '' && $this->BO->getPropObject($propName) instanceof Relation) {
						$prop = $this->BO->getPropObject($propName);

						// handle the setting of ONE-TO-MANY relation values
						if($prop->getRelationType() == 'ONE-TO-MANY') {
							$this->BO->set($propObj->name, $this->BO->getOID());
						}
					}
				}
			}
		}catch (IllegalArguementException $e) {
			self::$logger->warn('Bad data stored in the table ['.$this->BO->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
		}catch (PHPException $e) {
			// it is possible that the load failed due to the table not being up-to-date
			if($this->BO->checkTableNeedsUpdate()) {
				$missingFields = $this->BO->findMissingFields();

				$count = count($missingFields);

				for($i = 0; $i < $count; $i++)
					$this->BO->addProperty($missingFields[$i]);

				throw new BONotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table ['.$this->BO->getTableName().'] was out of sync with the database so had to be updated!');
				self::$logger->debug('<<loadByAttribute');
				return;
			}
		}

		self::$logger->debug('<<loadByAttribute');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAll()
	 */
	public function loadAll($start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false) {
		self::$logger->debug('>>loadAll(start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

		// ensure that the field name provided in the orderBy param is legit
		try {
			$field = $this->BO->get($orderBy);
		}catch(AlphaException $e) {
			throw new AlphaException('The field name ['.$orderBy.'] provided in the param orderBy does not exist on the class ['.get_class($this->BO).']');
		}

		if(!$ignoreClassType && $this->BO->isTableOverloaded()) {
			if($limit == 0) {
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE classname=\''.get_class($this->BO).'\' ORDER BY '.$orderBy.' '.$order.';';
			}else{
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE classname=\''.get_class($this->BO).'\' ORDER BY '.$orderBy.' '.$order.' LIMIT '.
					$start.', '.$limit.';';
			}
		}else{
			if($limit == 0)
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
			else
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$start.', '.$limit.';';
		}

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.AlphaDAOProviderMySQL::getConnection()->error.'], query ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<loadAll [0]');
			return array();
		}

		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			try {
				$obj = new $BO_Class();
				$obj->load($row['OID']);
				$objects[$count] = $obj;
				$count++;
			}catch(ResourceNotAllowedException $e) {
				// the resource not allowed will be absent from the list
			}
		}

		self::$logger->debug('<<loadAll ['.count($objects).']');
		return $objects;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByAttribute()
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false, $constructorArgs=array()) {
		self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.'], constructorArgs=['.print_r($constructorArgs, true).']');

		if ($limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';

		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE $attribute = ? AND classname = ? ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE $attribute = ? ORDER BY ".$orderBy." ".$order.$limit;

		$this->BO->setLastQuery($sqlQuery);
		self::$logger->debug($sqlQuery);

		$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

		$row = array();

		if($stmt->prepare($sqlQuery)) {
			if($this->BO->getPropObject($attribute) instanceof Integer) {
				if($this->BO->isTableOverloaded()) {
					$stmt->bind_param('is', $value, get_class($this->BO));
				}else{
					$stmt->bind_param('i', $value);
				}
			}else{
				if($this->BO->isTableOverloaded()) {
					$stmt->bind_param('ss', $value, get_class($this->BO));
				}else{
					$stmt->bind_param('s', $value);
				}
			}

			$stmt->execute();

			$result = $this->bindResult($stmt);

			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();

				throw new BONotFoundException('Failed to load objects by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
			}
			self::$logger->debug('<<loadAllByAttribute []');
			return array();
		}

		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		foreach($result as $row) {
			try {
				$argsCount = count($constructorArgs);

				if($argsCount < 1) {
					$obj = new $BO_Class();
				}else{
					switch ($argsCount) {
						case 1:
							$obj = new $BO_Class($constructorArgs[0]);
						break;
						case 2:
							$obj = new $BO_Class($constructorArgs[0],$constructorArgs[1]);
						break;
						case 3:
							$obj = new $BO_Class($constructorArgs[0],$constructorArgs[1],$constructorArgs[2]);
						break;
						case 4:
							$obj = new $BO_Class($constructorArgs[0],$constructorArgs[1],$constructorArgs[2],$constructorArgs[3]);
						break;
						case 5:
							$obj = new $BO_Class($constructorArgs[0],$constructorArgs[1],$constructorArgs[2],$constructorArgs[3],$constructorArgs[4]);
						break;
						default:
							throw new IllegalArguementException('Too many elements in the $constructorArgs array passed to the loadAllByAttribute method!');
						break;
					}
				}

				$obj->load($row['OID']);
				$objects[$count] = $obj;
				$count++;
			}catch(ResourceNotAllowedException $e) {
				// the resource not allowed will be absent from the list
			}
		}

		self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');
		return $objects;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByAttributes()
	 */
	public function loadAllByAttributes($attributes=array(), $values=array(), $start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false) {
		self::$logger->debug('>>loadAllByAttributes(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'], start=['.
			$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

		$whereClause = ' WHERE';

		$count = count($attributes);

		for($i = 0; $i < $count; $i++) {
			$whereClause .= ' '.$attributes[$i].' = ? AND';
			self::$logger->debug($whereClause);
		}

		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$whereClause .= ' classname = ? AND';

		// remove the last " AND"
		$whereClause = mb_substr($whereClause, 0, -4);

		if ($limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';

		$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName().$whereClause." ORDER BY ".$orderBy." ".$order.$limit;

		$this->BO->setLastQuery($sqlQuery);

		$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

		if($stmt->prepare($sqlQuery)) {
			// bind params where required attributes are provided
			if(count($attributes) > 0 && count($attributes) == count($values)) {
				$stmt = $this->bindParams($stmt, $attributes, $values);
			}else{
				// we'll still need to bind the "classname" for overloaded BOs...
				if($this->BO->isTableOverloaded())
					$stmt->bind_param('s', get_class($this->BO));
			}
			$stmt->execute();

			$result = $this->bindResult($stmt);

			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');

			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();

				throw new BONotFoundException('Failed to load objects by attributes ['.var_export($attributes, true).'] and values ['.
					var_export($values, true).'], table did not exist so had to create!');
			}

			self::$logger->debug('<<loadAllByAttributes []');
			return array();
		}

		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		foreach($result as $row) {
			try {
				$obj = new $BO_Class();
				$obj->load($row['OID']);
				$objects[$count] = $obj;
				$count++;
			}catch(ResourceNotAllowedException $e) {
				// the resource not allowed will be absent from the list
			}
		}

		self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');
		return $objects;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByDayUpdated()
	 */
	public function loadAllByDayUpdated($date, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false) {
		self::$logger->debug('>>loadAllByDayUpdated(date=['.$date.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

		if ($start != 0 && $limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';

		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' AND classname='".get_class($this->BO)."' ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' ORDER BY ".$orderBy." ".$order.$limit;

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.self::getConnection()->error.'], query ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<loadAllByDayUpdated []');
			return array();
		}

		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$obj = new $BO_Class();
			$obj->load($row['OID']);
			$objects[$count] = $obj;
			$count++;
		}

		self::$logger->debug('<<loadAllByDayUpdated ['.count($objects).']');
		return $objects;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllFieldValuesByAttribute()
	 */
	public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order='ASC', $ignoreClassType=false) {
		self::$logger->debug('>>loadAllFieldValuesByAttribute(attribute=['.$attribute.'], value=['.$value.'], returnAttribute=['.$returnAttribute.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');

		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = "SELECT ".$returnAttribute." FROM ".$this->BO->getTableName()." WHERE $attribute = '$value' AND classname='".get_class($this->BO)."' ORDER BY OID ".$order.";";
		else
			$sqlQuery = "SELECT ".$returnAttribute." FROM ".$this->BO->getTableName()." WHERE $attribute = '$value' ORDER BY OID ".$order.";";

		$this->BO->setLastQuery($sqlQuery);

		self::$logger->debug('lastQuery ['.$sqlQuery.']');

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load field ['.$returnAttribute.'] values, MySql error is ['.AlphaDAOProviderMySQL::getConnection()->error.'], query ['.$this->getLastQuery().']');
			self::$logger->debug('<<loadAllFieldValuesByAttribute []');
			return array();
		}

		// now build an array of attribute values to be returned
		$values = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$values[$count] = $row[$returnAttribute];
			$count++;
		}

		self::$logger->debug('<<loadAllFieldValuesByAttribute ['.count($values).']');
		return $values;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::save()
	 */
	public function save() {
		self::$logger->debug('>>save()');

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		$sqlQuery = '';
		$stmt = null;

		if($this->BO->getVersion() != $this->BO->getVersionNumber()->getValue()){
			throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
			return;
		}

		// set the "updated by" fields, we can only set the user id if someone is logged in
		if(isset($_SESSION['currentUser']))
			$this->BO->set('updated_by', $_SESSION['currentUser']->getOID());

		$this->BO->set('updated_ts', new Timestamp(date("Y-m-d H:i:s")));

		// check to see if it is a transient object that needs to be inserted
		if($this->BO->isTransient()) {
			$savedFieldsCount = 0;
			$sqlQuery = 'INSERT INTO '.$this->BO->getTableName().' (';

			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->BO->getTransientAttributes())) {
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {
						$sqlQuery .= "$propName,";
						$savedFieldsCount++;
					}

					if($propName == 'version_num') {
						$sqlQuery .= 'version_num,';
						$savedFieldsCount++;
					}
				}
			}
			if($this->BO->isTableOverloaded())
				$sqlQuery .= 'classname,';

			$sqlQuery = rtrim($sqlQuery, ",");

			$sqlQuery .= ') VALUES (';

			for($i = 0; $i < $savedFieldsCount; $i++)
				$sqlQuery.= '?,';

			if($this->BO->isTableOverloaded())
				$sqlQuery.= '?,';

			$sqlQuery = rtrim($sqlQuery, ',').')';

			$this->BO->setLastQuery($sqlQuery);
			self::$logger->debug('Query ['.$sqlQuery.']');

			$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

			if($stmt->prepare($sqlQuery)) {
				$stmt = $this->bindParams($stmt);
				$stmt->execute();
			}else{
				throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
			}
		}else{
			// assume that it is a persistent object that needs to be updated
			$savedFieldsCount = 0;
			$sqlQuery = 'UPDATE '.$this->BO->getTableName().' SET ';

			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->BO->getTransientAttributes())) {
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {
						$sqlQuery .= "$propName = ?,";
						$savedFieldsCount++;
					}

					if($propName == 'version_num') {
						$sqlQuery .= 'version_num = ?,';
						$savedFieldsCount++;
					}
				}
			}

			if($this->BO->isTableOverloaded())
				$sqlQuery .= 'classname = ?,';

			$sqlQuery = rtrim($sqlQuery, ",");

			$sqlQuery .= " WHERE OID=?;";

			$this->BO->setLastQuery($sqlQuery);
			$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

			if($stmt->prepare($sqlQuery)) {
				$this->bindParams($stmt);
				$stmt->execute();
			}else{
				throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
			}
		}

		if ($stmt != null && $stmt->error == '') {
			// populate the updated OID in case we just done an insert
			if($this->BO->isTransient())
				$this->BO->setOID(AlphaDAOProviderMySQL::getConnection()->insert_id);

			try {
				foreach($properties as $propObj) {
					$propName = $propObj->name;

					if($this->BO->getPropObject($propName) instanceof Relation) {
						$prop = $this->BO->getPropObject($propName);

						// handle the saving of MANY-TO-MANY relation values
						if($prop->getRelationType() == 'MANY-TO-MANY') {
							try {
								try{
									// check to see if the rel is on this class
									$side = $prop->getSide(get_class($this->BO));
								}catch (IllegalArguementException $iae) {
									$side = $prop->getSide(ucfirst($this->BO->getTableName()).'Object');
								}

								$lookUp = $prop->getLookup();

								// first delete all of the old RelationLookup objects for this rel
								try {
									if($side == 'left')
										$lookUp->deleteAllByAttribute('leftID', $this->BO->getOID());
									else
										$lookUp->deleteAllByAttribute('rightID', $this->BO->getOID());
								}catch (Exception $e) {
									throw new FailedSaveException('Failed to delete old RelationLookup objects on the table ['.$prop->getLookup()->getTableName().'], error is ['.$e->getMessage().']');
								}

								if(isset($_POST[$propName]) && $_POST[$propName] != '00000000000')
									$OIDs = explode(',', $_POST[$propName]);

								if(isset($OIDs) && !empty($OIDs[0])) {
									// now for each posted OID, create a new RelationLookup record and save
									foreach ($OIDs as $oid) {
										$newLookUp = new RelationLookup($lookUp->get('leftClassName'), $lookUp->get('rightClassName'));
										if($side == 'left') {
											$newLookUp->set('leftID', $this->BO->getOID());
											$newLookUp->set('rightID', $oid);
										}else{
											$newLookUp->set('rightID', $this->BO->getOID());
											$newLookUp->set('leftID', $oid);
										}
										$newLookUp->save();
									}
								}
							}catch (Exception $e) {
								throw new FailedSaveException('Failed to update a MANY-TO-MANY relation on the object, error is ['.$e->getMessage().']');
								return;
							}
						}

						// handle the saving of ONE-TO-MANY relation values
						if($prop->getRelationType() == 'ONE-TO-MANY') {
							$prop->setValue($this->BO->getOID());
						}
					}
				}
			}catch (Exception $e) {
				throw new FailedSaveException('Failed to save object, error is ['.$e->getMessage().']');
				return;
			}

			$stmt->close();
		}else{
			// there has been an error, so decrement the version number back
			$temp = $this->BO->getVersionNumber()->getValue();
			$this->BO->set('version_num', $temp-1);

			// check for unique violations
			if(AlphaDAOProviderMySQL::getConnection()->errno == '1062') {
				throw new ValidationException('Failed to save, the value '.$this->findOffendingValue(AlphaDAOProviderMySQL::getConnection()->error).' is already in use!');
				return;
			}else{
				throw new FailedSaveException('Failed to save object, MySql error is ['.AlphaDAOProviderMySQL::getConnection()->error.'], query ['.$this->BO->getLastQuery().']');
			}
		}

		if($this->BO->getMaintainHistory())
        	$this->BO->saveHistory();
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::saveAttribute()
	 */
	public function saveAttribute($attribute, $value) {
		self::$logger->debug('>>saveAttribute(attribute=['.$attribute.'], value=['.$value.'])');

		// assume that it is a persistent object that needs to be updated
		$sqlQuery = 'UPDATE '.$this->BO->getTableName().' SET '.$attribute.'=?, version_num = ? WHERE OID=?;';

		$this->BO->setLastQuery($sqlQuery);
		$stmt = self::getConnection()->stmt_init();

		$newVersionNumber = $this->BO->getVersionNumber()->getValue()+1;

		if($stmt->prepare($sqlQuery)) {
			if($this->BO->getPropObject($attribute) instanceof Integer)
				$bindingsType = 'i';
			else
				$bindingsType = 's';
			$stmt->bind_param($bindingsType.'ii', $value, $newVersionNumber, $this->BO->getOID());
			self::$logger->debug('Binding params ['.$bindingsType.'i, '.$value.', '.$this->BO->getOID().']');
			$stmt->execute();
		}else{
			throw new FailedSaveException('Failed to save attribute, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
		}

		$stmt->close();

		$this->BO->set($attribute, $value);
		$this->BO->set('version_num', $newVersionNumber);

		if($this->BO->getMaintainHistory())
			$this->BO->saveHistory();

		self::$logger->debug('<<saveAttribute');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::saveHistory()
	 */
	public function saveHistory() {
		self::$logger->debug('>>saveHistory()');

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		$sqlQuery = '';
		$stmt = null;

		$savedFieldsCount = 0;
		$attributeNames = array();
		$attributeValues = array();

		$sqlQuery = 'INSERT INTO '.$this->BO->getTableName().'_history (';

		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if (!in_array($propName, $this->BO->getTransientAttributes())) {
				$sqlQuery .= "$propName,";
				$attributeNames[] = $propName;
				$attributeValues[] = $this->BO->get($propName);
				$savedFieldsCount++;
			}
		}

		if($this->BO->isTableOverloaded())
			$sqlQuery .= 'classname,';

		$sqlQuery = rtrim($sqlQuery, ",");

		$sqlQuery .= ') VALUES (';

		for($i = 0; $i < $savedFieldsCount; $i++)
			$sqlQuery.= '?,';

		if($this->BO->isTableOverloaded())
			$sqlQuery.= '?,';

		$sqlQuery = rtrim($sqlQuery, ',').')';

		$this->BO->setLastQuery($sqlQuery);
		self::$logger->debug('Query ['.$sqlQuery.']');

		$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

		if($stmt->prepare($sqlQuery)) {
			$stmt = $this->bindParams($stmt, $attributeNames, $attributeValues);
			$stmt->execute();
		}else{
			throw new FailedSaveException('Failed to save object history, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::delete()
	 */
	public function delete() {
		self::$logger->debug('>>delete()');

		$sqlQuery = "DELETE FROM ".$this->BO->getTableName()." WHERE OID = ?;";

		$this->BO->setLastQuery($sqlQuery);

		$stmt = self::getConnection()->stmt_init();

		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $this->BO->getOID());
			$stmt->execute();
			self::$logger->debug('Deleted the object ['.$this->BO->getOID().'] of class ['.get_class($this->BO).']');
		}else{
			throw new FailedDeleteException('Failed to delete object ['.$this->BO->getOID().'], error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');
		}

		$stmt->close();

		self::$logger->debug('<<delete');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getVersion()
	 */
	public function getVersion() {
		self::$logger->debug('>>getVersion()');

		$sqlQuery = 'SELECT version_num FROM '.$this->BO->getTableName().' WHERE OID = ?;';
		$this->BO->setLastQuery($sqlQuery);

		$stmt = AlphaDAOProviderMySQL::getConnection()->stmt_init();

		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $this->BO->getOID());

			$stmt->execute();

			$result = $this->bindResult($stmt);
			if(isset($result[0]))
				$row = $result[0];

			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();

				throw new BONotFoundException('Failed to get the version number, table did not exist so had to create!');
			}
			return;
		}

		if(!isset($row['version_num']) || $row['version_num'] < 1) {
			self::$logger->debug('<<getVersion [0]');
			return 0;
		}else{
			$version_num = $row['version_num'];

			self::$logger->debug('<<getVersion ['.$version_num.']');
			return $version_num;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::makeTable()
	 */
	public function makeTable() {
		self::$logger->debug('>>makeTable()');

		$sqlQuery = "CREATE TABLE ".$this->BO->getTableName()." (OID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT,";

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;

			if(!in_array($propName, $this->BO->getTransientAttributes()) && $propName != "OID") {
				$propClass = get_class($this->BO->getPropObject($propName));

				switch (strtoupper($propClass)) {
					case "INTEGER":
						// special properties for RelationLookup OIDs
						if($this->BO instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID'))
							$sqlQuery .= "$propName INT(".$this->BO->getPropObject($propName)->getSize().") ZEROFILL NOT NULL,";
						else
							$sqlQuery .= "$propName INT(".$this->BO->getPropObject($propName)->getSize()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$propName DOUBLE(".$this->BO->getPropObject($propName)->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$propName VARCHAR(".$this->BO->getPropObject($propName)->getSize()."),";
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
						$enumVals = $this->BO->getPropObject($propName)->getOptions();
						foreach($enumVals as $val) {
							$sqlQuery .= "'".$val."',";
						}
						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this->BO).'::'.$propName);
						$sqlQuery .= "$propName INT(11) ZEROFILL,";
					break;
					case "RELATION":
						$sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED,";
					break;
					default:
						$sqlQuery .= "";
					break;
				}
			}
		}
		if($this->BO->isTableOverloaded())
			$sqlQuery .= "classname VARCHAR(100),";

		$sqlQuery .= "PRIMARY KEY (OID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;";

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to create the table ['.$this->BO->getTableName().'] for the class ['.get_class($this->BO).'], database error is ['.AlphaDAOProviderMySQL::getConnection()->error.']');
			self::$logger->debug('<<makeTable');
		}

		// check the table indexes if any additional ones required
		$this->checkIndexes();

		if($this->BO->getMaintainHistory())
			$this->BO->makeHistoryTable();

		self::$logger->debug('<<makeTable');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::makeHistoryTable()
	 */
	public function makeHistoryTable() {
		self::$logger->debug('>>makeHistoryTable()');

		$sqlQuery = "CREATE TABLE ".$this->BO->getTableName()."_history (OID INT(11) ZEROFILL NOT NULL,";

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;

			if(!in_array($propName, $this->BO->getTransientAttributes()) && $propName != "OID") {
				$propClass = get_class($this->BO->getPropObject($propName));

				switch (strtoupper($propClass)) {
					case "INTEGER":
						// special properties for RelationLookup OIDs
						if($this->BO instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID'))
							$sqlQuery .= "$propName INT(".$this->BO->getPropObject($propName)->getSize().") ZEROFILL NOT NULL,";
						else
							$sqlQuery .= "$propName INT(".$this->BO->getPropObject($propName)->getSize()."),";
					break;
					case "DOUBLE":
						$sqlQuery .= "$propName DOUBLE(".$this->BO->getPropObject($propName)->getSize(true)."),";
					break;
					case "STRING":
						$sqlQuery .= "$propName VARCHAR(".$this->BO->getPropObject($propName)->getSize()."),";
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

						$enumVals = $this->BO->getPropObject($propName)->getOptions();

						foreach($enumVals as $val) {
							$sqlQuery .= "'".$val."',";
						}

						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= "),";
					break;
					case "DENUM":
						$tmp = new DEnum(get_class($this->BO).'::'.$propName);
						$sqlQuery .= "$propName INT(11) ZEROFILL,";
					break;
					case "RELATION":
						$sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED,";
					break;
					default:
						$sqlQuery .= "";
					break;
				}
			}
		}

		if($this->BO->isTableOverloaded())
			$sqlQuery .= "classname VARCHAR(100),";

		$sqlQuery .= "PRIMARY KEY (OID, version_num)) ENGINE=MyISAM;";

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to create the table ['.$this->BO->getTableName().'_history] for the class ['.get_class($this->BO).'], database error is ['.AlphaDAOProviderMySQL::getConnection()->error.']');
			self::$logger->debug('<<makeHistoryTable');
		}

		self::$logger->debug('<<makeHistoryTable');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::rebuildTable()
	 */
	public function rebuildTable() {
		self::$logger->debug('>>rebuildTable()');

		$sqlQuery = 'DROP TABLE IF EXISTS '.$this->BO->getTableName().';';

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to drop the table ['.$this->BO->getTableName().'] for the class ['.get_class($this->BO).'], database error is ['.AlphaDAOProviderMySQL::getConnection()->error.']');
			self::$logger->debug('<<rebuildTable');
		}

		$this->BO->makeTable();

		self::$logger->debug('<<rebuildTable');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::dropTable()
	 */
	public function dropTable($tableName=null) {
		self::$logger->debug('>>dropTable()');

		if($tableName == null)
			$tableName = $this->BO->getTableName();

		$sqlQuery = 'DROP TABLE IF EXISTS '.$tableName.';';

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to drop the table ['.$tableName.'] for the class ['.get_class($this->BO).'], query is ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<dropTable');
		}

		self::$logger->debug('<<dropTable');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::addProperty()
	 */
	public function addProperty($propName) {
		self::$logger->debug('>>addProperty(propName=['.$propName.'])');

		$sqlQuery = 'ALTER TABLE '.$this->BO->getTableName().' ADD ';

		if($this->isTableOverloaded() && $propName == 'classname') {
			$sqlQuery .= 'classname VARCHAR(100)';
		}else{
			if(!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$propClass = get_class($this->BO->getPropObject($propName));

				switch (strtoupper($propClass)) {
					case 'INTEGER':
						$sqlQuery .= "$propName INT(".$this->BO->getPropObject($propName)->getSize().")";
					break;
					case 'DOUBLE':
						$sqlQuery .= "$propName DOUBLE(".$this->BO->getPropObject($propName)->getSize(true).")";
					break;
					case 'STRING':
						$sqlQuery .= "$propName VARCHAR(".$this->BO->getPropObject($propName)->getSize().")";
					break;
					case 'SEQUENCE':
						$sqlQuery .= "$propName VARCHAR(".$this->BO->getPropObject($propName)->getSize().")";
					break;
					case 'TEXT':
						$sqlQuery .= "$propName TEXT";
					break;
					case 'BOOLEAN':
						$sqlQuery .= "$propName CHAR(1) DEFAULT '0'";
					break;
					case 'DATE':
						$sqlQuery .= "$propName DATE";
					break;
					case 'TIMESTAMP':
						$sqlQuery .= "$propName DATETIME";
					break;
					case 'ENUM':
						$sqlQuery .= "$propName ENUM(";
						$enumVals = $this->BO->getPropObject($propName)->getOptions();
						foreach($enumVals as $val) {
							$sqlQuery .= "'".$val."',";
						}
						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= ')';
					break;
					case 'DENUM':
						$tmp = new DEnum(get_class($this->BO).'::'.$propName);
						$tmp->save();
						$sqlQuery .= "$propName INT(11) ZEROFILL";
					break;
					case 'RELATION':
						$sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED";
					break;
					default:
						$sqlQuery .= '';
					break;
				}
			}
		}

		$this->BO->setLastQuery($sqlQuery);

		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<addProperty');
		}else{
			self::$logger->info('Successfully added the ['.$propName.'] column onto the ['.$this->BO->getTableName().'] table for the class ['.get_class($this->BO).']');
		}

		self::$logger->debug('<<addProperty');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getMAX()
	 */
	public function getMAX() {
		self::$logger->debug('>>getMAX()');

		$sqlQuery = 'SELECT MAX(OID) AS max_OID FROM '.$this->BO->getTableName();

		$this->BO->setLastQuery($sqlQuery);

		try {
			$result = $this->BO->query($sqlQuery);

			$row = $result[0];

			if (isset($row['max_OID'])) {
				self::$logger->debug('<<getMAX ['.$row['max_OID'].']');
				return $row['max_OID'];
			}else{
				throw new AlphaException('Failed to get the MAX ID for the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
			}
		}catch (Exception $e) {
			throw new AlphaException($e->getMessage());
			self::$logger->debug('<<getMAX [0]');
			return 0;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getCount()
	 */
	public function getCount($attributes=array(), $values=array()) {
		self::$logger->debug('>>getCount(attributes=['.var_export($attributes, true).'], values=['.var_export($values, true).'])');

		if($this->BO->isTableOverloaded())
			$whereClause = ' WHERE classname = \''.get_class($this->BO).'\' AND';
		else
			$whereClause = ' WHERE';

		$count = count($attributes);

		for($i = 0; $i < $count; $i++) {
			$whereClause .= ' '.$attributes[$i].' = \''.$values[$i].'\' AND';
			self::$logger->debug($whereClause);
		}
		// remove the last " AND"
		$whereClause = mb_substr($whereClause, 0, -4);

		if($whereClause != ' WHERE')
			$sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->BO->getTableName().$whereClause;
		else
			$sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->BO->getTableName();

		$this->BO->setLastQuery($sqlQuery);

		$result = self::getConnection()->query($sqlQuery);

		if ($result) {
			$row = $result->fetch_array(MYSQLI_ASSOC);

			self::$logger->debug('<<getCount ['.$row['class_count'].']');
			return $row['class_count'];
		}else{
			throw new AlphaException('Failed to get the count for the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<getCount [0]');
			return 0;
		}
	}

	/**
         * (non-PHPdoc)
         * @see alpha/model/AlphaDAOProviderInterface::getHistoryCount()
         */
	public function getHistoryCount() {
		self::$logger->debug('>>getHistoryCount()');

		if(!$this->BO->getMaintainHistory())
			throw new AlphaException('getHistoryCount method called on a DAO where no history is maintained!');

		$sqlQuery = 'SELECT COUNT(OID) AS object_count FROM '.$this->BO->getTableName().'_history WHERE OID='.$this->BO->getOID();

               	$this->BO->setLastQuery($sqlQuery);

               	$result = self::getConnection()->query($sqlQuery);

               	if ($result) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);

                    self::$logger->debug('<<getHistoryCount ['.$row['object_count'].']');
                    return $row['object_count'];
                }else{
                    throw new AlphaException('Failed to get the history count for the business object ['.$this->BO->getOID().'] from the table ['.$this->BO->getTableName().'_history], query is ['.$this->BO->getLastQuery().']');
                    self::$logger->debug('<<getHistoryCount [0]');
                    return 0;
               	}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::setEnumOptions()
	 * @since 1.1
	 */
	public function setEnumOptions() {
		self::$logger->debug('>>setEnumOptions()');

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		// flag for any database errors
		$dbError = false;

		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if(!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$propClass = get_class($this->BO->getPropObject($propName));
				if ($propClass == 'Enum') {
					$sqlQuery = "SHOW COLUMNS FROM ".$this->BO->getTableName()." LIKE '$propName'";

					$this->BO->setLastQuery($sqlQuery);

					$result = AlphaDAOProviderMySQL::getConnection()->query($sqlQuery);

					if ($result) {
						$row = $result->fetch_array(MYSQLI_NUM);
						$options = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2",$row[1]));

						$this->BO->getPropObject($propName)->setOptions($options);
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
		self::$logger->debug('<<setEnumOptions');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkTableExists()
	 */
	public function checkTableExists($checkHistoryTable = false) {
		self::$logger->debug('>>checkTableExists(checkHistoryTable=['.$checkHistoryTable.'])');

		global $config;

		$tableExists = false;

		$sqlQuery = 'SHOW TABLES;';
		$this->BO->setLastQuery($sqlQuery);

		$result = self::getConnection()->query($sqlQuery);

		if ($result) {
			$tableName = ($checkHistoryTable ? $this->BO->getTableName().'_history' : $this->BO->getTableName());

			while ($row = $result->fetch_array(MYSQLI_NUM)) {
		    	if (strtolower($row[0]) == strtolower($tableName))
	    			$tableExists = true;
			}

			self::$logger->debug('<<checkTableExists ['.$tableExists.']');
			return $tableExists;
		}else{
			throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
			self::$logger->debug('<<checkTableExists [false]');
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkBOTableExists()
	 */
	public static function checkBOTableExists($BOClassName, $checkHistoryTable = false) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAOProvidermySQL');
		self::$logger->debug('>>checkBOTableExists(BOClassName=['.$BOClassName.'], checkHistoryTable=['.$checkHistoryTable.'])');

		eval('$tableName = '.$BOClassName.'::TABLE_NAME;');

		if(empty($tableName))
			$tableName = mb_substr($BOClassName, 0, mb_strpos($BOClassName, '_'));

		if($checkHistoryTable)
            $tableName .= '_history';

		$tableExists = false;

		$sqlQuery = 'SHOW TABLES;';

		$result = self::getConnection()->query($sqlQuery);

		while ($row = $result->fetch_array(MYSQLI_NUM)) {
    			if ($row[0] == $tableName)
    				$tableExists = true;
		}

		if ($result) {
			self::$logger->debug('<<checkBOTableExists ['.($tableExists ? 'true' : 'false').']');
			return $tableExists;
		}else{
			throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
			self::$logger->debug('<<checkBOTableExists [false]');
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkTableNeedsUpdate()
	 */
	public function checkTableNeedsUpdate() {
		self::$logger->debug('>>checkTableNeedsUpdate()');

		$updateRequired = false;

		$matchCount = 0;

		$query = 'SHOW COLUMNS FROM '.$this->BO->getTableName();
		$result = self::getConnection()->query($query);
		$this->BO->setLastQuery($query);

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if (!in_array($propName, $this->BO->getTransientAttributes())) {

				$foundMatch = false;

				while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			    	if ($propName == $row['Field']) {
			    		$foundMatch = true;
			    		break;
			    	}
				}

				if(!$foundMatch)
					$matchCount--;

				$result->data_seek(0);
			}
		}

		// check for the "classname" field in overloaded tables
		if($this->BO->isTableOverloaded()) {
			$foundMatch = false;

			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				if ('classname' == $row['Field']) {
					$foundMatch = true;
			    	break;
				}
			}
			if(!$foundMatch)
				$matchCount--;
		}

		if ($matchCount != 0)
			$updateRequired = true;

		if ($result) {
			// check the table indexes
			try {
				$this->checkIndexes();
			}catch (AlphaException $ae) {
				self::$logger->warn("Error while checking database indexes:\n\n".$ae->getMessage());
			}

			self::$logger->debug('<<checkTableNeedsUpdate ['.$updateRequired.']');
			return $updateRequired;
		}else{
			throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
			self::$logger->debug('<<checkTableNeedsUpdate [false]');
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::findMissingFields()
	 */
	public function findMissingFields() {
		self::$logger->debug('>>findMissingFields()');

		$missingFields = array();
		$matchCount = 0;

		$sqlQuery = 'SHOW COLUMNS FROM '.$this->BO->getTableName();

		$result = self::getConnection()->query($sqlQuery);

		$this->BO->setLastQuery($sqlQuery);

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if (!in_array($propName, $this->BO->getTransientAttributes())) {
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
		    		if ($propName == $row['Field']) {
		    			$matchCount++;
		    			break;
		    		}
				}
				$result->data_seek(0);
			}else{
				$matchCount++;
			}

			if($matchCount==0) {
				array_push($missingFields, $propName);
			}else{
				$matchCount = 0;
			}
		}

		// check for the "classname" field in overloaded tables
		if($this->BO->isTableOverloaded()) {
			$foundMatch = false;

			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				if ('classname' == $row['Field']) {
					$foundMatch = true;
		    		break;
				}
			}
			if(!$foundMatch)
				array_push($missingFields, 'classname');
		}

		if (!$result) {
			throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
		}

		self::$logger->debug('<<findMissingFields ['.var_export($missingFields, true).']');
		return $missingFields;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getIndexes()
	 */
	public function getIndexes() {
		self::$logger->debug('>>getIndexes()');

		$query = 'SHOW INDEX FROM '.$this->BO->getTableName();

		$result = self::getConnection()->query($query);

		$this->BO->setLastQuery($query);

		$indexNames = array();

		if (!$result) {
			throw new AlphaException('Failed to access the system database correctly, error is ['.self::getConnection()->error.']');
		}else{
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				array_push($indexNames, $row['Key_name']);
			}
		}

		self::$logger->debug('<<getIndexes');
		return $indexNames;
	}

	/**
	 * Checks to see if all of the indexes are in place for the BO's table, creates those that are missing.
	 *
	 * @since 1.1
	 */
	private function checkIndexes() {
		self::$logger->debug('>>checkIndexes()');

		$indexNames = $this->getIndexes();

		// process unique keys
		foreach($this->BO->getUniqueAttributes() as $prop) {
			// check for composite indexes
			if(mb_strpos($prop, '+')) {
				$attributes = explode('+', $prop);

				$index_exists = false;
				foreach ($indexNames as $index) {
					if ($attributes[0].'_'.$attributes[1].'_unq_idx' == $index) {
				    	$index_exists = true;
					}
					if(count($attributes) == 3) {
						if ($attributes[0].'_'.$attributes[1].'_'.$attributes[2].'_unq_idx' == $index) {
					    	$index_exists = true;
						}
					}
				}

				if(!$index_exists) {
					if(count($attributes) == 3)
						$this->BO->createUniqueIndex($attributes[0], $attributes[1], $attributes[2]);
					else
						$this->BO->createUniqueIndex($attributes[0], $attributes[1]);
				}
			}else{
				$index_exists = false;
				foreach ($indexNames as $index) {
					if ($prop.'_unq_idx' == $index) {
				    	$index_exists = true;
					}
				}

				if(!$index_exists)
					$this->createUniqueIndex($prop);
			}
		}

		// process foreign-key indexes
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$prop = $this->BO->getPropObject($propName);
			if($prop instanceof Relation) {

				if($prop->getRelationType() == 'MANY-TO-ONE') {
					$indexExists = false;
					foreach ($indexNames as $index) {
						if ($propName.'_fk_idx' == $index) {
					    	$indexExists = true;
						}
					}

					if(!$indexExists) {
						$this->createForeignIndex($propName, $prop->getRelatedClass(), $prop->getRelatedClassField());
					}
				}

				if($prop->getRelationType() == 'MANY-TO-MANY') {
					$lookup = $prop->getLookup();

					if($lookup != null) {
						try {
							$lookupIndexNames = $lookup->getIndexes();

							// handle index check/creation on left side of Relation
							$indexExists = false;
							foreach ($lookupIndexNames as $index) {
								if ('leftID_fk_idx' == $index) {
							    	$indexExists = true;
								}
							}

							if(!$indexExists) {
								$lookup->createForeignIndex('leftID', $prop->getRelatedClass('left'), 'OID');
							}

							// handle index check/creation on right side of Relation
							$indexExists = false;
							foreach ($lookupIndexNames as $index) {
								if ('rightID_fk_idx' == $index) {
							    	$indexExists = true;
								}
							}

							if(!$indexExists) {
								$lookup->createForeignIndex('rightID', $prop->getRelatedClass('right'), 'OID');
							}
						}catch(AlphaException $e) {
							self::$logger->error($e->getMessage());
						}
					}
				}

			}
		}

		self::$logger->debug('<<checkIndexes');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::createForeignIndex()
	 */
	public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute) {
		self::$logger->debug('>>createForeignIndex(attributeName=['.$attributeName.'], relatedClass=['.$relatedClass.'], relatedClassAttribute=['.$relatedClassAttribute.']');

		AlphaDAO::loadClassDef($relatedClass);
		$relatedBO = new $relatedClass;
		$tableName = $relatedBO->getTableName();

		$result = false;

		if(self::checkBOTableExists(ucfirst($tableName).'Object')) {
			$sqlQuery = '';

			if($attributeName == 'leftID')
				$sqlQuery = 'ALTER TABLE '.$this->BO->getTableName().' ADD INDEX leftID_fk_idx (leftID);';
			if($attributeName == 'rightID')
				$sqlQuery = 'ALTER TABLE '.$this->BO->getTableName().' ADD INDEX rightID_fk_idx (rightID);';

			if(!empty($sqlQuery)) {
				$this->BO->setLastQuery($sqlQuery);

				$result = self::getConnection()->query($sqlQuery);

				if (!$result) {
					throw new FailedIndexCreateException('Failed to create an index on ['.$this->BO->getTableName().'], error is ['.self::getConnection()->error.'], query ['.$this->BO->getLastQuery().']');
				}
			}

			$sqlQuery = 'ALTER TABLE '.$this->BO->getTableName().' ADD FOREIGN KEY '.$attributeName.'_fk_idx ('.$attributeName.') REFERENCES '.$tableName.' ('.$relatedClassAttribute.') ON DELETE SET NULL;';

			$this->BO->setLastQuery($sqlQuery);
			$result = self::getConnection()->query($sqlQuery);
		}

		if ($result) {
			self::$logger->debug('Successfully created the foreign key index ['.$attributeName.'_fk_idx]');
		}else{
			throw new FailedIndexCreateException('Failed to create the index ['.$attributeName.'_fk_idx] on ['.$this->BO->getTableName().'], error is ['.self::getConnection()->error.'], query ['.$this->BO->getLastQuery().']');
		}

		self::$logger->debug('<<createForeignIndex');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::createUniqueIndex()
	 */
	public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '') {
		self::$logger->debug('>>createUniqueIndex(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'], attribute3Name=['.$attribute3Name.'])');

		if($attribute2Name != '' && $attribute3Name != '')
			$sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_'.$attribute2Name.'_'.$attribute3Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.','.$attribute2Name.','.$attribute3Name.');';

		if($attribute2Name != '' && $attribute3Name == '')
			$sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_'.$attribute2Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.','.$attribute2Name.');';

		if($attribute2Name == '' && $attribute3Name == '')
			$sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_unq_idx ON '.$this->BO->getTableName().' ('.$attribute1Name.');';

		$this->BO->setLastQuery($sqlQuery);

		$result = self::getConnection()->query($sqlQuery);

		if ($result) {
			self::$logger->debug('Successfully created the unique index on ['.$this->BO->getTableName().']');
		}else{
			throw new FailedIndexCreateException('Failed to create the unique index on ['.$this->BO->getTableName().'], error is ['.self::getConnection()->error.']');
		}

		self::$logger->debug('<<createUniqueIndex');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::reload()
	 */
	public function reload() {
		self::$logger->debug('>>reload()');

		if(!$this->isTransient()) {
			$this->load($this->getOID());
		}else{
			throw new AlphaException('Cannot reload transient object from database!');
		}
		self::$logger->debug('<<reload');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkRecordExists()
	 */
	public function checkRecordExists($OID) {
		self::$logger->debug('>>checkRecordExists(OID=['.$OID.'])');

		$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' WHERE OID = ?;';

		$this->BO->setLastQuery($sqlQuery);

		$stmt = self::getConnection()->stmt_init();

		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $OID);

			$stmt->execute();

			$result = $this->bindResult($stmt);

			$stmt->close();

			if ($result) {
				if(count($result) > 0) {
					self::$logger->debug('<<checkRecordExists [true]');
					return true;
				}else{
					self::$logger->debug('<<checkRecordExists [false]');
					return false;
				}
			}else{
				throw new AlphaException('Failed to check for the record ['.$OID.'] on the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
				self::$logger->debug('<<checkRecordExists [false]');
				return false;
			}
		}else{
			throw new AlphaException('Failed to check for the record ['.$OID.'] on the class ['.get_class($this->BO).'] from the table ['.$this->BO->getTableName().'], query is ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<checkRecordExists [false]');
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::isTableOverloaded()
	 */
	public function isTableOverloaded() {
		self::$logger->debug('>>isTableOverloaded()');

		$classname = get_class($this->BO);
		$tablename = ucfirst($this->BO->getTableName()).'Object';

		// use reflection to check to see if we are dealing with a persistent type (e.g. DEnum) which are never overloaded
		$reflection = new ReflectionClass($classname);
		$implementedInterfaces = $reflection->getInterfaces();

		foreach ($implementedInterfaces as $interface) {
			if ($interface->name == 'AlphaTypeInterface') {
				self::$logger->debug('<<isTableOverloaded [false]');
				return false;
			}
		}

		if($classname != $tablename) {
			// loop over all BOs to see if there is one using the same table as this BO

			$BOclasses = AlphaDAO::getBOClassNames();

			foreach($BOclasses as $BOclassName) {
				if($tablename == $BOclassName) {
					self::$logger->debug('<<isTableOverloaded [true]');
					return true;
				}
			}
			throw new BadBOTableNameException('The table name ['.$tablename.'] for the class ['.$classname.'] is invalid as it does not match a BO definition in the system!');
			self::$logger->debug('<<isTableOverloaded [false]');
			return false;
		}else{
			// check to see if there is already a "classname" column in the database for this BO

			$query = 'SHOW COLUMNS FROM '.$this->BO->getTableName();

			$result = self::getConnection()->query($query);

			if($result) {
				while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
					if ('classname' == $row['Field']) {
						self::$logger->debug('<<isTableOverloaded [true]');
						return true;
					}
				}
			}else{
				self::$logger->warn('Error during show columns ['.self::getConnection()->error.']');
			}

			self::$logger->debug('<<isTableOverloaded [false]');
			return false;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::begin()
	 */
	public static function begin() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAOProviderMySQL');
		self::$logger->debug('>>begin()');

     	if (!self::getConnection()->autocommit(false))
     		throw new AlphaException('Error beginning a new transaction, error is ['.self::getConnection()->error.']');

     	self::$logger->debug('<<begin');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::commit()
	 */
	public static function commit() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAOProviderMySQL');
		self::$logger->debug('>>commit()');

    	if (!self::getConnection()->commit())
     		throw new FailedSaveException('Error commiting a transaction, error is ['.self::getConnection()->error.']');

    	self::$logger->debug('<<commit');
  	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::rollback()
	 */
	public static function rollback() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAOProviderMySQL');
		self::$logger->debug('>>rollback()');

    	if (!self::getConnection()->rollback())
     		throw new AlphaException('Error rolling back a transaction, error is ['.self::getConnection()->error.']');

    	self::$logger->debug('<<rollback');
  	}

  	/**
  	 * (non-PHPdoc)
  	 * @see alpha/model/AlphaDAOProviderInterface::setBO()
  	 */
	public function setBO($BO) {
		$this->BO = $BO;
	}

	/**
  	 * Dynamically binds all of the attributes for the current BO to the supplied prepared statement
  	 * parameters.  If arrays of attribute names and values are provided, only those will be bound to
  	 * the supplied statement.
  	 *
  	 * @param mysqli_stmt $stmt The SQL statement to bind to.
  	 * @param array Optional array of BO attributes.
  	 * @param array Optional array of BO values.
  	 * @return mysqli_stmt
  	 * @since 1.1
  	 */
	private function bindParams($stmt, $attributes=array(), $values=array()) {
		self::$logger->debug('>>bindParams(stmt=['.var_export($stmt, true).'])');

		$bindingsTypes = '';
		$params = array();

		// here we are only binding the supplied attributes
		if(count($attributes) > 0 && count($attributes) == count($values)) {

			$count = count($values);

			for($i = 0; $i < $count; $i++) {
				if (AlphaValidator::isInteger($values[$i]))
					$bindingsTypes .= 'i';
				else
					$bindingsTypes .= 's';
				array_push($params, $values[$i]);
			}

			if($this->BO->isTableOverloaded()) {
				if(isset($this->classname)) {
					$bindingsTypes .= 's';
					array_push($params, $this->classname);
				}else{
					$bindingsTypes .= 's';
					array_push($params, get_class($this->BO));
				}
			}
		}else{ // bind all attributes on the business object

			// get the class attributes
			$reflection = new ReflectionClass(get_class($this->BO));
			$properties = $reflection->getProperties();

			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->BO->getTransientAttributes())) {
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {
						if($this->BO->getPropObject($propName) instanceof Integer)
							$bindingsTypes .= 'i';
						else
							$bindingsTypes .= 's';
						array_push($params, $this->BO->get($propName));
					}

					if($propName == 'version_num') {
						$temp = $this->BO->getVersionNumber()->getValue();
						$this->BO->set('version_num', $temp+1);
						$bindingsTypes .= 'i';
						array_push($params, $this->BO->getVersionNumber()->getValue());
					}
				}
			}

			if($this->BO->isTableOverloaded()) {
				if(isset($this->classname)) {
					$bindingsTypes .= 's';
					array_push($params, $this->classname);
				}else{
					$bindingsTypes .= 's';
					array_push($params, get_class($this->BO));
				}
			}

			// the OID may be on the WHERE clause for UPDATEs and DELETEs
			if(!$this->BO->isTransient()) {
				$bindingsTypes .= 'i';
				array_push($params, $this->BO->getOID());
			}
		}

		self::$logger->debug('bindingsTypes=['.$bindingsTypes.'], count: ['.mb_strlen($bindingsTypes).']');
		self::$logger->debug('params ['.var_export($params, true).']');

	    if ($params != null) {
			$bind_names[] = $bindingsTypes;

			$count = count($params);

		    for ($i = 0; $i < $count; $i++) {
		    	$bind_name = 'bind'.$i;
		        $$bind_name = $params[$i];
		        $bind_names[] = &$$bind_name;
		    }

		    call_user_func_array(array($stmt,'bind_param'), $bind_names);
	   	}

	   	self::$logger->debug('<<bindParams ['.var_export($stmt, true).']');
	    return $stmt;
	}

	/**
	 * Dynamically binds the result of the supplied prepared statement to a 2d array, where each element in the array is another array
	 * representing a database row.
	 *
	 * @param mysqli_stmt $stmt
	 * @return array A 2D array containing the query result.
	 * @since 1.1
	 */
	private function bindResult($stmt) {
		$result = array();

		$metadata = $stmt->result_metadata();
      	$fields = $metadata->fetch_fields();

      	while(true) {
        	$pointers = array();
        	$row = array();

        	$pointers[] = $stmt;
        	foreach ($fields as $field) {
          		$fieldname = $field->name;
          		$pointers[] = &$row[$fieldname];
        	}

        	call_user_func_array('mysqli_stmt_bind_result', $pointers);

        	if (!$stmt->fetch())
          		break;

       		$result[] = $row;
      	}

      	$metadata->free();

      	return $result;
    }

	/**
	 * Parses a MySQL error for the value that violated a unique constraint.
	 *
	 * @param string $error The MySQL error string.
	 * @since 1.1
	 */
	private function findOffendingValue($error) {
		self::$logger->debug('>>findOffendingValue(error=['.$error.'])');

		$singleQuote1 = mb_strpos($error,"'");
		$singleQuote2 = mb_strrpos($error,"'");

		$value = mb_substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
		self::$logger->debug('<<findOffendingValue ['.$value.'])');
		return $value;
	}
}

?>