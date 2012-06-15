<?php

/**
 * SQLite DAO provider (uses the SQLite3 native API in PHP).
 * 
 * @package alpha::model
 * @since 1.2
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
class AlphaDAOProviderSQLite implements AlphaDAOProviderInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.2
	 */
	private static $logger = null;

	/**
	 * Database connection
	 *
	 * @var SQLite3
	 * @since 1.2
	 */
	private static $connection;
	
	/**
	 * The business object that we are mapping back to
	 * 
	 * @var AlphaDAO
	 * @since 1.2
	 */
	private $BO;
	
	/**
	 * The constructor
	 * 
	 * @since 1.2
	 */
	public function __construct() {
		self::$logger = new Logger('AlphaDAOProviderSQLite');
		self::$logger->debug('>>__construct()');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getConnection()
	 */
	public static function getConnection() {
		global $config;

		if(!isset(self::$connection)) {
			try {
				self::$connection = new SQLite3($config->get('sysDBFilePath'));
			} catch(Exeception $e) {
				self::$logger->fatal('Could not open SQLite database: ['.$e->getMessage().']');
			}
		}

		return self::$connection;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::disconnect()
	 */
	public static function disconnect() {
		if(isset(self::$connection)) {
			self::$connection->close();
			self::$connection = null;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getLastDatabaseError()
	 */
	public static function getLastDatabaseError() {
		self::$connection->lastErrorMsg();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::query()
	 */
	public function query($sqlQuery) {
		$this->BO->setLastQuery($sqlQuery);
		
		$resultArray = array();
		
		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new CustomQueryException('Failed to run the custom query, SQLite error is ['.self::getLastDatabaseError().'], query ['.$sqlQuery.']');
			return array();
		}else{
			while($row = $result->fetchArray(SQLITE3_ASSOC)) {
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
		self::$logger->debug('>>load(OID=['.$OID.'])');

	        global $config;
		
		$attributes = $this->BO->getPersistentAttributes();
		$fields = '';
		foreach($attributes as $att)
			$fields .= $att.',';
		$fields = substr($fields, 0, -1);
			
		$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE OID = :OID LIMIT 1;';
		$this->BO->setLastQuery($sqlQuery);
		$stmt = self::getConnection()->prepare($sqlQuery);

		$row = array();
			
		if($stmt instanceof SQLite3Stmt) {
			$stmt->bindValue(':OID', $OID, SQLITE3_INTEGER);
				
			$result = $stmt->execute();

			// there should only ever be one (or none)
			$row = $result->fetchArray(SQLITE3_ASSOC);
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();
				if($this->BO->getMaintainHistory())
					$this->BO->makeHistoryTable();
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
				self::$logger->debug('<<load');
				return;
			}
		}
		
		self::$logger->debug('<<load');
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
		$fields = substr($fields, 0, -1);
		
		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = :attribute AND classname = :classname LIMIT 1;';
		else
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->BO->getTableName().' WHERE '.$attribute.' = :attribute LIMIT 1;';
		
		self::$logger->debug('Query=['.$sqlQuery.']');
		
		$this->BO->setLastQuery($sqlQuery);
		$stmt = self::getConnection()->prepare($sqlQuery);

		$row = array();
		
		if($stmt instanceof SQLite3Stmt) {
			if($this->BO->getPropObject($attribute) instanceof Integer) {
				if(!$ignoreClassType && $this->BO->isTableOverloaded()) {
					$stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
					$stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
				}else{
					$stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
				}
			}else{
				if(!$ignoreClassType && $this->BO->isTableOverloaded()) {
					$stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                                        $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
				}else{
					$stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
				}
			}
			
			$result = $stmt->execute();
			
			// there should only ever be one (or none)
                        $row = $result->fetchArray(SQLITE3_ASSOC);
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();
				if($this->BO->getMaintainHistory())
                                        $this->BO->makeHistoryTable();
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
					$limit.' OFFSET '.$start.';';
			}
		}else{				
			if($limit == 0)
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
			else
				$sqlQuery = 'SELECT OID FROM '.$this->BO->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$limit.' OFFSET '.$start.';';
		}
		
		$this->BO->setLastQuery($sqlQuery);
		
		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<loadAll [0]');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);
		
		while($row = $result->fetchArray()) {
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
		
		if ($start != 0 && $limit != 0)
			$limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
		else
			$limit = ';';
		
		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE $attribute = :attribute AND classname = :classname ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE $attribute = :attribute ORDER BY ".$orderBy." ".$order.$limit;			
			
		$this->BO->setLastQuery($sqlQuery);
		self::$logger->debug($sqlQuery);
		
		$stmt = self::getConnection()->prepare($sqlQuery);
		
		if($stmt instanceof SQLite3Stmt) {
			if($this->BO->getPropObject($attribute) instanceof Integer) {
				if($this->BO->isTableOverloaded()) {
					$stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
					$stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
				}else{
					$stmt->bindValue(':attribute', $value, SQLITE3_INTEGER);
				}
			}else{
				if($this->BO->isTableOverloaded()) {
					$stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
                                        $stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
				}else{
					$stmt->bindValue(':attribute', $value, SQLITE3_TEXT);
				}
			}
			
			$result = $stmt->execute();
			
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();
				if($this->BO->getMaintainHistory())
                                        $this->BO->makeHistoryTable();
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
			$whereClause .= ' '.$attributes[$i].' = :'.$attributes[$i].' AND';
			self::$logger->debug($whereClause);
		}
		
		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$whereClause .= ' classname = :classname AND';
		
		// remove the last " AND"
		$whereClause = substr($whereClause, 0, -4);
		
		if ($limit != 0)
			$limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
		else
			$limit = ';';
		
		$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName().$whereClause." ORDER BY ".$orderBy." ".$order.$limit;
			
		$this->BO->setLastQuery($sqlQuery);
		
		$stmt = self::getConnection()->prepare($sqlQuery);
		
		if($stmt instanceof SQLite3Stmt) {
			// bind params where required attributes are provided
			if(count($attributes) > 0 && count($attributes) == count($values)) {
				for($i = 0; $i < count($attributes); $i++) {
					$stmt->bindValue(':'.$attributes[$i], $values[$i], SQLITE3_TEXT);
				}
			}else{
				// we'll still need to bind the "classname" for overloaded BOs...
				if($this->BO->isTableOverloaded())
					$stmt->bindValue(':classname', get_class($this->BO), SQLITE3_TEXT);
			}
			
			$result = $stmt->execute();
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			
			if(!$this->BO->checkTableExists()) {
				$this->BO->makeTable();
				if($this->BO->getMaintainHistory())
                                        $this->BO->makeHistoryTable();
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
			$limit = ' LIMIT '.$limit.' OFFSET '.$start.';';
		else
			$limit = ';';
		
		if(!$ignoreClassType && $this->BO->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' AND classname='".get_class($this->BO)."' ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->BO->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' ORDER BY ".$orderBy." ".$order.$limit;
			
		$this->BO->setLastQuery($sqlQuery);

		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<loadAllByDayUpdated []');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this->BO);
		
		while($row = $result->fetchArray()) {
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

		if(!$result = self::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load field ['.$returnAttribute.'] values, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
			self::$logger->debug('<<loadAllFieldValuesByAttribute []');
			return array();
		}

		// now build an array of attribute values to be returned
		$values = array();
		$count = 0;
		$BO_Class = get_class($this->BO);

		while($row = $result->fetchArray()) {
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
			$savedFields = array();
			$sqlQuery = 'INSERT INTO '.$this->BO->getTableName().' (';
	
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->BO->getTransientAttributes())) {
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {
						$sqlQuery .= "$propName,";
						$savedFields[] = $propName;
					}
					
					if($propName == 'version_num') {
						$sqlQuery .= 'version_num,';
						$savedFields[] = 'version_num';
					}
				}
			}
			if($this->BO->isTableOverloaded())
				$sqlQuery .= 'classname,';
	
			$sqlQuery = rtrim($sqlQuery, ",");
	
			$sqlQuery .= ') VALUES (';
				
			foreach($savedFields as $savedField)
				$sqlQuery.= ':'.$savedField.',';
				
			if($this->BO->isTableOverloaded())
				$sqlQuery.= ':classname,';
				
			$sqlQuery = rtrim($sqlQuery, ',').')';
				
			$this->BO->setLastQuery($sqlQuery);
			self::$logger->debug('Query ['.$sqlQuery.']');
			
			$stmt = self::getConnection()->prepare($sqlQuery);
		
			if($stmt instanceof SQLite3Stmt) {

				foreach($savedFields as $savedField) {
					if($this->BO->get($savedField) instanceof Integer)
						$stmt->bindParam(':'.$savedField, $savedField, SQLITE3_INTEGER);
					else
						$stmt->bindParam(':'.$savedField, $savedField, SQLITE3_TEXT);
				}

				if($this->BO->isTableOverloaded())
					$stmt->bindParam(':classname', get_class($this->BO), SQLITE3_TEXT);

				$stmt->execute();
			}else{
				throw new FailedSaveException('Failed to save object, error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
			}
		}else{
			// assume that it is a persistent object that needs to be updated
			$savedFields = array();
			$sqlQuery = 'UPDATE '.$this->BO->getTableName().' SET ';
	
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->BO->getTransientAttributes())) {
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {			
						$sqlQuery .= "$propName = :$propName,";
						$savedFields[] = $propName;
					}

					if($propName == 'version_num') {
						$sqlQuery .= 'version_num = :version_num,';
						$savedFields[] = 'version_num';
					}
				}
			}

			if($this->BO->isTableOverloaded())
				$sqlQuery .= 'classname = :classname,';
	
			$sqlQuery = rtrim($sqlQuery, ",");
	
			$sqlQuery .= " WHERE OID=:OID;";

			$this->BO->setLastQuery($sqlQuery);
			$stmt = self::getConnection()->prepare($sqlQuery);
				
			if($stmt instanceof SQLite3Stmt) {				
				
				foreach($savedFields as $savedField) {
                                       	if($this->BO->get($savedField) instanceof Integer)
                                                $stmt->bindParam(':'.$savedField, $savedField, SQLITE3_INTEGER);
                                        else
                                                $stmt->bindParam(':'.$savedField, $savedField, SQLITE3_TEXT);
                                }

                                if($this->BO->isTableOverloaded())
                                       	$stmt->bindParam(':classname', get_class($this->BO), SQLITE3_TEXT);

				$stmt->bindValue(':OID', $OID, SQLITE3_INTEGER);

				$stmt->execute();	
			}else{
				throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->BO->getLastQuery().']');				
			}	
		}

		if ($stmt != null && $stmt != false) {
			// populate the updated OID in case we just done an insert				
			if($this->BO->isTransient())
				$this->BO->setOID(self::getConnection()->lastInsertRowID());

			try {
				foreach($properties as $propObj) {
					$propName = $propObj->name;

					if(!$propObj->isPrivate() && $this->BO->get($propName) != '' && $this->BO->getPropObject($propName) instanceof Relation) {				
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

			throw new FailedSaveException('Failed to save object, SQLite error is ['.self::getLastDatabaseError().'], query ['.$this->BO->getLastQuery().']');
		}

		if($this->BO->getMaintainHistory())
                        $this->BO->saveHistory();
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::saveAttribute()
	 * @todo
	 */
	public function saveAttribute($attribute, $value) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::saveAttribute() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::saveHistory()
	 * @todo
	 */
	public function saveHistory() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::saveHistory() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::delete()
	 * @todo
	 */
	public function delete() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::delete() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getVersion()
	 * @todo
	 */
	public function getVersion() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::getVersion() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::makeTable()
	 * @todo
	 */
	public function makeTable() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::makeTable() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::makeHistoryTable()
	 * @todo
	 */
	public function makeHistoryTable() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::makeHistoryTable() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::rebuildTable()
	 * @todo
	 */
	public function rebuildTable() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::rebuildTable() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::dropTable()
	 * @todo
	 */
	public function dropTable($tableName=null) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::dropTable() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::addProperty()
	 * @todo
	 */
	public function addProperty($propName) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::addProperty() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getMAX()
	 * @todo
	 */
	public function getMAX() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::getMAX() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getCount()
	 * @todo
	 */
	public function getCount($attributes=array(), $values=array()) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::getCount() not implemented by this provider');
	}

	/**
         * (non-PHPdoc)
         * @see alpha/model/AlphaDAOProviderInterface::getHistoryCount()
         * @todo
         */
	public function getHistoryCount() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::getHistoryCount() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::setEnumOptions()
	 * @todo
	 */
	public function setEnumOptions() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::setEnumOptions() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkTableExists()
	 * @todo
	 */
	public function checkTableExists($checkHistoryTable = false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::checkTableExists() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkBOTableExists()
	 * @todo
	 */
	public static function checkBOTableExists($BOClassName, $checkHistoryTable = false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::checkBOTableExists() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkTableNeedsUpdate()
	 * @todo
	 */
	public function checkTableNeedsUpdate() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::checkTableNeedsUpdate() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::findMissingFields()
	 * @todo
	 */
	public function findMissingFields() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::findMissingFields() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::getIndexes()
	 * @todo
	 */
	public function getIndexes() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::getIndexes() not implemented by this provider');
	}
	
	/**
         * (non-PHPdoc)
         * @see alpha/model/AlphaDAOProviderInterface::checkIndexes()
         * @todo
         */
	private function checkIndexes() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::checkIndexes() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::createForeignIndex()
	 * @todo
	 */
	public function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::createForeignIndex() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::createUniqueIndex()
	 * @todo
	 */
	public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '') {
                throw new NotImplementedException('AlphaDAOProviderInterface::createUniqueIndex() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::reload()
	 * @todo
	 */
	public function reload() {
                throw new NotImplementedException('AlphaDAOProviderInterface::reload() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::checkRecordExists()
	 * @todo
	 */
	public function checkRecordExists($OID) {
                throw new NotImplementedException('AlphaDAOProviderInterface::checkRecordExists() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::isTableOverloaded()
	 * @todo
	 */
	public function isTableOverloaded() {
                throw new NotImplementedException('AlphaDAOProviderInterface::isTableOverloaded() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::begin()
	 * @todo
	 */
	public static function begin() {
                throw new NotImplementedException('AlphaDAOProviderInterface::begin() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::commit()
	 * @todo
	 */
	public static function commit() {
                throw new NotImplementedException('AlphaDAOProviderInterface::commit() not implemented by this provider');
  	}
  	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::rollback()
	 * @todo
	 */
	public static function rollback() {
                throw new NotImplementedException('AlphaDAOProviderInterface::createForeignIndex() not implemented by this provider');
  	}
	
  	/**
  	 * (non-PHPdoc)
  	 * @see alpha/model/AlphaDAOProviderInterface::setBO()
	 * @todo
  	 */
	public function setBO($BO) {
                throw new NotImplementedException('AlphaDAOProviderInterface::setBO() not implemented by this provider');
	}
}

?>
