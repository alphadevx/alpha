<?php

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/util/InputFilter.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedSaveException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedDeleteException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/LockingException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ValidationException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/MailNotSentException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BadBOTableNameException.inc';
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
require_once $config->get('sysRoot').'alpha/model/types/Sequence.inc';

/**
 * Base business object class definition
 * 
 * @package alpha::model
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
abstract class AlphaDAO {
	/**
	 * The object ID
	 * 
	 * @var integer
	 * @since 1.0
	 */
	protected $OID;
	
	/**
	 * The last database query run by this object.  Useful for tracing an error.
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $lastQuery;
	
	/**
	 * The version number of the object, used for locking mechanism
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	protected $version_num;
	
	/**
	 * The timestamp of creation
	 * 
	 * @var Timestamp
	 * @since 1.0
	 */
	protected $created_ts;
	
	/**
	 * The OID of the person who created this BO
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	protected $created_by;
	
	/**
	 * The timestamp of the last update
	 * 
	 * @var Timestamp
	 * @since 1.0
	 */
	protected $updated_ts;
	
	/**
	 * The OID of the person who last updated this BO
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	protected $updated_by;
	
	/**
	 * An array of the names of all of the default attributes of a persistent BO defined in this class
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $defaultAttributes = array("OID", "lastQuery", "version_num", "dataLabels", "created_ts", "created_by", "updated_ts", "updated_by", "defaultAttributes", "transientAttributes", "uniqueAttributes", "TABLE_NAME", "logger");
	
	/**
	 * An array of the names of all of the transient attributes of a persistent BO which are not saved to the DB
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $transientAttributes = array("lastQuery", "dataLabels", "defaultAttributes", "transientAttributes", "uniqueAttributes", "TABLE_NAME", "logger");
	
	/**
	 * An array of the uniquely-constained attributes of this persistent BO
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $uniqueAttributes = array();
	
	/**
	 * An array of the data labels used for displaying class attributes
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array();
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Datebase connection
	 * 
	 * @var mysqli
	 * @since 1.0
	 */
	private static $connection;
	
	/**
	 * The constructor which sets up some housekeeping attributes
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>__construct()');
		
		$this->version_num = new Integer(0);
		$this->created_ts = new Timestamp(date("Y-m-d H:i:s"));
		$person_ID = (isset($_SESSION['currentUser'])? $_SESSION['currentUser']->getOID(): 0);
		$this->created_by = new Integer($person_ID);
		$this->updated_ts = new Timestamp(date("Y-m-d H:i:s"));
		$this->updated_by = new Integer($person_ID);
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Gets the current connection singleton, or creates a new one if none exists
	 *  
	 * @return mysqli
	 * @since 1.0
	 */
	public static function getConnection() {
		global $config;
		
		if (!isset(self::$connection)) {
			self::$connection = new mysqli($config->get('sysDBHost'), $config->get('sysDBUsername'), $config->get('sysDBPassword'), $config->get('sysDB'));
				
			if (mysqli_connect_error()) {
			    self::$logger->fatal('Could not connect to database: ['.mysqli_connect_errno().'] '.mysqli_connect_error());
			}
		}
		
		return self::$connection;
	}
	
	/**
	 * Populates the child object with the properties retrived from the database for the object $OID.
	 * 
	 * @param integer $OID The object ID of the business object to load.
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function load($OID) {
		self::$logger->debug('>>load(OID=['.$OID.'])');
		
		if(method_exists($this, 'before_load_callback'))
			$this->before_load_callback();
		
		$this->OID = $OID;
		
		$attributes = $this->getPersistentAttributes();
		$fields = '';
		foreach($attributes as $att)
			$fields .= $att.',';
		$fields = substr($fields, 0, -1);
		
		$sqlQuery = 'SELECT '.$fields.' FROM '.$this->getTableName().' WHERE OID = ?;';					
		$this->lastQuery = $sqlQuery;
		$stmt = AlphaDAO::getConnection()->stmt_init();

		$row = array();
		
		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $OID);
			$stmt->execute();
			
			$result = $this->bindResult($stmt);
			if(isset($result[0]))
				$row = $result[0];
			
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->checkTableExists()) {
				$this->makeTable();
				throw new BONotFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->getTableName().'] did not exist so had to create!');
			}
			return;
		}
		
		if(!isset($row['OID']) || $row['OID'] < 1) {
			throw new BONotFoundException('Failed to load object of OID ['.$OID.'] not found in database.');			
			self::$logger->debug('<<load');
			return;
		}
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();

		try {
			foreach($properties as $propObj) {
				$propName = $propObj->name;
											
				// filter transient attributes
				if(!in_array($propName, $this->transientAttributes)) {
					$this->set($propName, $row[$propName]);
				}elseif(!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {
					$prop = $this->getPropObject($propName);
					
					// handle the setting of ONE-TO-MANY relation values
					if($prop->getRelationType() == 'ONE-TO-MANY') {
						$this->set($propObj->name, $this->getOID());
					}
				}
			}
		}catch (IllegalArguementException $e) {
			self::$logger->warn('Bad data stored in the table ['.$this->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
		}catch (PHPException $e) {
			// it is possible that the load failed due to the table not being up-to-date
			if($this->checkTableNeedsUpdate()) {				
				$missingFields = $this->findMissingFields();
	    	
				for($i = 0; $i < count($missingFields); $i++)
					$this->addProperty($missingFields[$i]);
					
				throw new BONotFoundException('Failed to load object of OID ['.$OID.'], table ['.$this->getTableName().'] was out of sync with the database so had to be updated!');
				self::$logger->debug('<<load');
				return;
			}
		}
		
		$this->setEnumOptions();	
		if(method_exists($this, 'after_load_callback'))
			$this->after_load_callback();
		
		self::$logger->debug('<<load');
	}
	
	/**
	 * Populates the child object from the database table by the given attribute value.
	 * 
	 * @param string $atribute The name of the attribute to load the object by.
	 * @param string $value The value of the attribute to load the object by.
	 * @param boolean $ignoreClassType Default is false, set to true if you want to load from overloaded tables and ignore the class type
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function loadByAttribute($attribute, $value, $ignoreClassType=false)	{
		self::$logger->debug('>>loadByAttribute(attribute=['.$attribute.'], value=['.$value.'], ignoreClassType=['.$ignoreClassType.'])');
		
		if(method_exists($this, 'before_loadByAttribute_callback'))
				$this->before_loadByAttribute_callback();
		
		$attributes = $this->getPersistentAttributes();
		$fields = '';
		foreach($attributes as $att)
			$fields .= $att.',';
		$fields = substr($fields, 0, -1);
		
		if(!$ignoreClassType && $this->isTableOverloaded())
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->getTableName().' WHERE '.$attribute.' = ? AND classname=?;';
		else
			$sqlQuery = 'SELECT '.$fields.' FROM '.$this->getTableName().' WHERE '.$attribute.' = ?;';
		
		self::$logger->debug('Query=['.$sqlQuery.']');	
		
		$this->lastQuery = $sqlQuery;
		$stmt = AlphaDAO::getConnection()->stmt_init();

		$row = array();
		
		if($stmt->prepare($sqlQuery)) {
			if($this->$attribute instanceof Integer) {
				if(!$ignoreClassType && $this->isTableOverloaded()) {
					$stmt->bind_param('is', $value, get_class($this));
				}else{
					$stmt->bind_param('i', $value);
				}
			}else{
				if(!$ignoreClassType && $this->isTableOverloaded()) {
					$stmt->bind_param('ss', $value, get_class($this));
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
			if(!$this->checkTableExists()) {
				$this->makeTable();
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
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();

		try {
			foreach($properties as $propObj) {
				$propName = $propObj->name;
											
				// filter transient attributes
				if(!in_array($propName, $this->transientAttributes)) {
					$this->set($propName, $row[$propName]);
				}elseif(!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {
					$prop = $this->getPropObject($propName);
					
					// handle the setting of ONE-TO-MANY relation values
					if($prop->getRelationType() == 'ONE-TO-MANY') {
						$this->set($propObj->name, $this->getOID());
					}
				}
			}
		}catch (IllegalArguementException $e) {
			self::$logger->warn('Bad data stored in the table ['.$this->getTableName().'], field ['.$propObj->name.'] bad value['.$row[$propObj->name].'], exception ['.$e->getMessage().']');
		}catch (PHPException $e) {
			// it is possible that the load failed due to the table not being up-to-date
			if($this->checkTableNeedsUpdate()) {				
				$missingFields = $this->findMissingFields();
	    	
				for($i = 0; $i < count($missingFields); $i++)
					$this->addProperty($missingFields[$i]);
					
				throw new BONotFoundException('Failed to load object by attribute ['.$attribute.'] and value ['.$value.'], table ['.$this->getTableName().'] was out of sync with the database so had to be updated!');
				self::$logger->debug('<<loadByAttribute');
				return;
			}
		}
		
		$this->setEnumOptions();	
		if(method_exists($this, 'after_loadByAttribute_callback'))
			$this->after_loadByAttribute_callback();
		
		self::$logger->debug('<<loadByAttribute');
	}
	
	/**
	 * Loads all of the objects of this class into an array which is returned.
	 * 
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @return array An array containing objects of this type of business object.
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function loadAll($start=0, $limit=0, $orderBy='OID', $order='ASC') {
		self::$logger->debug('>>loadAll(start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.']');
		
		if(method_exists($this, 'before_loadAll_callback'))
			$this->before_loadAll_callback();
		
		global $config;
		
		// ensure that the field name provided in the orderBy param is legit
		try {
			$field = $this->get($orderBy);
		}catch(AlphaException $e) {
			throw new AlphaException('The field name ['.$orderBy.'] provided in the param orderBy does not exist on the class ['.get_class($this).']');
		}
		
		if(!$this->isTableOverloaded()) {
			if($limit == 0)
				$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' ORDER BY '.$orderBy.' '.$order.';';
			else
				$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$start.', '.$limit.';';
		}else{
			if($limit == 0)
				$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' WHERE classname=\''.get_class($this).'\' ORDER BY '.$orderBy.' '.$order.';';
			else
				$sqlQuery = 'SELECT OID FROM '.$this->getTableName().' WHERE classname=\''.get_class($this).'\' ORDER BY '.$orderBy.' '.$order.' LIMIT '.$start.', '.$limit.';';
		}
		
		$this->lastQuery = $sqlQuery;
		
		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.AlphaDAO::getConnection()->error.'], query ['.$this->lastQuery.']');
			self::$logger->debug('<<loadAll [0]');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
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
		
		if(method_exists($this, 'after_loadAll_callback'))
			$this->after_loadAll_callback();
		
		self::$logger->debug('<<loadAll ['.count($objects).']');
		return $objects;
	}
	
	/**
	 * Loads all of the objects of this class by the specified attribute into an array which is returned.
	 * 
	 * @param string $atribute The attribute to load the objects by.
	 * @param string $value The value of the attribute to load the objects by.
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @return array An array containing objects of this type of business object.
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC") {
		self::$logger->debug('>>loadAllByAttribute(attribute=['.$attribute.'], value=['.$value.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.']');
		
		if(method_exists($this, 'before_loadAllByAttribute_callback'))
			$this->before_loadAllByAttribute_callback();
		
		global $config;
		
		if ($start != 0 && $limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';
		
		if(!$this->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE $attribute = ? ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE $attribute = ? AND classname = ? ORDER BY ".$orderBy." ".$order.$limit;
			
		$this->lastQuery = $sqlQuery;
		
		$stmt = AlphaDAO::getConnection()->stmt_init();

		$row = array();
		
		if($stmt->prepare($sqlQuery)) {
			if($this->$attribute instanceof Integer) {
				if($this->isTableOverloaded()) {
					$stmt->bind_param('is', $value, get_class($this));
				}else{
					$stmt->bind_param('i', $value);
				}
			}else{
				if($this->isTableOverloaded()) {
					$stmt->bind_param('ss', $value, get_class($this));
				}else{
					$stmt->bind_param('s', $value);
				}
			}
			
			$stmt->execute();
			
			$result = $this->bindResult($stmt);
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->checkTableExists()) {
				$this->makeTable();
				throw new BONotFoundException('Failed to load objects by attribute ['.$attribute.'] and value ['.$value.'], table did not exist so had to create!');
			}
			self::$logger->debug('<<loadAllByAttribute []');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
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
		
		if(method_exists($this, 'after_loadAllByAttribute_callback'))
			$this->after_loadAllByAttribute_callback();
		
		self::$logger->debug('<<loadAllByAttribute ['.count($objects).']');
		return $objects;	
	}
	
	/**
	 * Loads all of the objects of this class by the specified attributes into an array which is returned.
	 * 
	 * @param array $atributes The attributes to load the objects by.
	 * @param array $values The values of the attributes to load the objects by.
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @return array An array containing objects of this type of business object.
	 * @since 1.0
	 * @throws BONotFoundException
	 * @throws IllegalArguementException
	 */
	public function loadAllByAttributes($attributes=array(), $values=array(), $start=0, $limit=0, $orderBy='OID', $order='ASC') {
		self::$logger->debug('>>loadAllByAttributes(attributes=['.print_r($attributes, true).'], values=['.print_r($values, true).'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.']');
		
		if(method_exists($this, 'before_loadAllByAttributes_callback'))
			$this->before_loadAllByAttributes_callback();
		
		global $config;
		
		if(!is_array($attributes) || !is_array($values)) {
			throw new IllegalArguementException('Illegal arrays attributes=['.print_r($attributes, true).'] and values=['.print_r($values, true).'] provided to loadAllByAttributes');
		}
		
		$whereClause = ' WHERE';
		
		for($i = 0; $i < count($attributes); $i++) {
			$whereClause .= ' '.$attributes[$i].' = ? AND';
			self::$logger->debug($whereClause);
		}
		
		if($this->isTableOverloaded())
			$whereClause .= ' classname = ? AND';
		
		// remove the last " AND"
		$whereClause = substr($whereClause, 0, -4);
		
		if ($limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';
		
		$sqlQuery = "SELECT OID FROM ".$this->getTableName().$whereClause." ORDER BY ".$orderBy." ".$order.$limit;
			
		$this->lastQuery = $sqlQuery;
		self::$logger->debug($sqlQuery);
		
		$stmt = AlphaDAO::getConnection()->stmt_init();
		
		if($stmt->prepare($sqlQuery)) {			
			$stmt = $this->bindParams($stmt, $attributes, $values);
			$stmt->execute();
			
			$result = $this->bindResult($stmt);
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->checkTableExists()) {
				$this->makeTable();
				throw new BONotFoundException('Failed to load objects by attributes ['.print_r($attributes, true).'] and values ['.print_r($values, true).'], table did not exist so had to create!');
			}
			self::$logger->debug('<<loadAllByAttributes []');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
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
		
		if(method_exists($this, 'after_loadAllByAttributes_callback'))
			$this->after_loadAllByAttributes_callback();
		
		self::$logger->debug('<<loadAllByAttributes ['.count($objects).']');
		return $objects;	
	}
	
	/**
	 * Loads all of the objects of this class that where updated (updated_ts value) on the date indicated.
	 * 
	 * @param string $date The date for which to load the objects updated on, in the format 'YYYY-MM-DD'.	 
	 * @param integer $start The start of the SQL LIMIT clause, useful for pagination.
	 * @param integer $limit The amount (limit) of objects to load, useful for pagination.
	 * @param string $orderBy The name of the field to sort the objects by.
	 * @param string $order The order to sort the objects by.
	 * @return array An array containing objects of this type of business object.
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function loadAllByDayUpdated($date, $start=0, $limit=0, $orderBy="OID", $order="ASC") {
		self::$logger->debug('>>loadAllByDayUpdated(date=['.$date.'], start=['.$start.'], limit=['.$limit.'], orderBy=['.$orderBy.'], order=['.$order.']');
		
		if(method_exists($this, 'before_loadAllByDayUpdated_callback'))
			$this->before_loadAllByDayUpdated_callback();
		
		global $config;
		
		if ($start != 0 && $limit != 0)
			$limit = ' LIMIT '.$start.', '.$limit.';';
		else
			$limit = ';';
		
		if(!$this->isTableOverloaded())
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' ORDER BY ".$orderBy." ".$order.$limit;
		else
			$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE updated_ts >= '".$date." 00:00:00' AND updated_ts <= '".$date." 23:59:59' AND classname='".get_class($this)."' ORDER BY ".$orderBy." ".$order.$limit;
			
		$this->lastQuery = $sqlQuery;

		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load object OIDs, MySql error is ['.AlphaDAO::getConnection()->error.'], query ['.$this->lastQuery.']');
			self::$logger->debug('<<loadAllByDayUpdated []');
			return array();
		}
		
		// now build an array of objects to be returned
		$objects = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$obj = new $BO_Class();
			$obj->load($row['OID']);
			$objects[$count] = $obj;
			$count++;
		}
		
		if(method_exists($this, 'after_loadAllByDayUpdated_callback'))
			$this->after_loadAllByDayUpdated_callback();
		
		self::$logger->debug('<<loadAllByDayUpdated ['.count($objects).']');
		return $objects;	
	}
	
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
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order='ASC', $ignoreClassType=false) {
		self::$logger->debug('>>loadAllFieldValuesByAttribute(attribute=['.$attribute.'], value=['.$value.'], returnAttribute=['.$returnAttribute.'], order=['.$order.'], ignoreClassType=['.$ignoreClassType.']');
		
		global $config;
		
		if(!$ignoreClassType && $this->isTableOverloaded())
			$sqlQuery = "SELECT ".$returnAttribute." FROM ".$this->getTableName()." WHERE $attribute = '$value' AND classname='".get_class($this)."' ORDER BY OID ".$order.";";
		else
			$sqlQuery = "SELECT ".$returnAttribute." FROM ".$this->getTableName()." WHERE $attribute = '$value' ORDER BY OID ".$order.";";
			
			
		$this->lastQuery = $sqlQuery;
		self::$logger->debug('lastQuery ['.$sqlQuery.']');

		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new BONotFoundException('Failed to load field ['.$returnAttribute.'] values, MySql error is ['.AlphaDAO::getConnection()->error.'], query ['.$this->lastQuery.']');
			self::$logger->debug('<<loadAllFieldValuesByAttribute []');
			return array();
		}
		
		// now build an array of attribute values to be returned
		$values = array();
		$count = 0;
		$BO_Class = get_class($this);
		
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$values[$count] = $row[$returnAttribute];
			$count++;
		}
		
		self::$logger->debug('<<loadAllFieldValuesByAttribute ['.count($values).']');
		return $values;
	}

	/**
	 * Saves the object.  If $this->OID is empty or null it will INSERT, otherwise UPDATE.
	 * 
	 * @since 1.0
	 * @throws FailedSaveException
	 * @throws LockingException
	 * @throws ValidationException
	 */
	public function save() {
		self::$logger->debug('>>save()');
			
		if(method_exists($this, 'before_save_callback'))
			$this->before_save_callback();
		
		// firstly we will validate the object before we try to save it
		if(!$this->validate()) {
			throw new FailedSaveException('Could not save due to a validation error.');
			return;
		}else{		
			// get the class attributes
			$reflection = new ReflectionClass(get_class($this));
			$properties = $reflection->getProperties();
			$sqlQuery = '';
			$stmt = null;

			if($this->getVersion() != $this->version_num->getValue()){
				throw new LockingException('Could not save the object as it has been updated by another user.  Please try saving again.');
				return;
			}
			
			// set the "updated by" fields, we can only set the user id if someone is logged in
			if(isset($_SESSION['currentUser']))
				$this->updated_by->setValue($_SESSION['currentUser']->getOID());
			
			$this->updated_ts = new Timestamp(date("Y-m-d H:i:s"));
			
			// check to see if it is a transient object that needs to be inserted
			if($this->isTransient()) {
				$savedFieldsCount = 0;
				$sqlQuery = 'INSERT INTO '.$this->getTableName().' (';
	
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					if (!in_array($propName, $this->transientAttributes)) {
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
				if($this->isTableOverloaded())
					$sqlQuery .= 'classname,';
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= ') VALUES (';
				
				for($i = 0; $i < $savedFieldsCount; $i++)
					$sqlQuery.= '?,';
				
				if($this->isTableOverloaded())
					$sqlQuery.= '?,';
				
				$sqlQuery = rtrim($sqlQuery, ',').')';
				
				$this->lastQuery = $sqlQuery;
				self::$logger->debug('Query ['.$sqlQuery.']');
				$stmt = AlphaDAO::getConnection()->stmt_init();
			
				if($stmt->prepare($sqlQuery)) {			
					$stmt = $this->bindParams($stmt);					
					$stmt->execute();					
				}else{
					throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->lastQuery.']');				
				}
			}else{
				// assume that it is a persistent object that needs to be updated
				$savedFieldsCount = 0;
				$sqlQuery = 'UPDATE '.$this->getTableName().' SET ';
	
				foreach($properties as $propObj) {
					$propName = $propObj->name;
					if (!in_array($propName, $this->transientAttributes)) {
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
				if($this->isTableOverloaded())
					$sqlQuery .= 'classname = ?,';
	
				$sqlQuery = rtrim($sqlQuery, ",");
	
				$sqlQuery .= " WHERE OID=?;";
				
				$this->lastQuery = $sqlQuery;
				$stmt = AlphaDAO::getConnection()->stmt_init();
				
				if($stmt->prepare($sqlQuery)) {							
					$this->bindParams($stmt);					
					$stmt->execute();					
				}else{
					throw new FailedSaveException('Failed to save object, error is ['.$stmt->error.'], query ['.$this->lastQuery.']');				
				}				
			}
	
			if ($stmt != null && $stmt->error == '') {
				// populate the updated OID in case we just done an insert				
				if($this->isTransient())
					$this->setOID(AlphaDAO::getConnection()->insert_id);

				try {
					foreach($properties as $propObj) {
						$propName = $propObj->name;
						
						if(!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {				
							$prop = $this->getPropObject($propName);
							
							// handle the saving of MANY-TO-MANY relation values
							if($prop->getRelationType() == 'MANY-TO-MANY') {
								try {
									try{
										// check to see if the rel is on this class
										$side = $prop->getSide(get_class($this));											
									}catch (IllegalArguementException $iae) {
										$side = $prop->getSide($this->getTableName().'_object');
									}
										
									$lookUp = $prop->getLookup();									
										
									// first delete all of the old RelationLookup objects for this rel
									try {
										if($side == 'left')
											$lookUp->deleteAllByAttribute('leftID', $this->getOID());
										else
											$lookUp->deleteAllByAttribute('rightID', $this->getOID());							
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
												$newLookUp->set('leftID', $this->getOID());
												$newLookUp->set('rightID', $oid);
											}else{
												$newLookUp->set('rightID', $this->getOID());
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
								$prop->setValue($this->getOID());
							}
						}						
					}
				}catch (Exception $e) {
					throw new FailedSaveException('Failed to save object, error is ['.$e->getMessage().']');
					return;
				}
				
				$stmt->close();
				
				if(method_exists($this, 'after_save_callback'))
					$this->after_save_callback();
			}else{
				// there has been an error, so decrement the version number back
				$temp = $this->version_num->getValue();					
				$this->version_num->setValue($temp-1);	
				
				// check for unique violations			
				if(AlphaDAO::getConnection()->errno == '1062') {					
					throw new ValidationException('Failed to save, the value '.$this->findOffendingValue(mysql_error()).' is already in use!');
					return;
				}else{					
					throw new FailedSaveException('Failed to save object, MySql error is ['.AlphaDAO::getConnection()->error.'], query ['.$this->lastQuery.']');
				}
			}
		}
	}
	
	/**
	 * Saves the field specified with the value supplied.  Only works for persistent BOs.  Note that no Alpha type
	 * validation is performed with this method!
	 * 
	 * @param string $attribute The name of the attribute to save.
	 * @param mixed $value The value of the attribute to save.
	 * @since 1.0
	 * @throws IllegalArguementException
	 * @throws FailedSaveException
	 */
	public function saveAttribute($attribute, $value) {
		self::$logger->debug('>>saveAttribute(attribute=['.$attribute.'], value=['.$value.'])');
		
		if(method_exists($this, 'before_saveAttribute_callback'))
			$this->before_saveAttribute_callback();
		
		if(!isset($this->$attribute))
			throw new IllegalArguementException('Could not perform save, as the attribute ['.$attribute.'] is not present on the class['.get_class($this).']');
		
		if($this->isTransient())
			throw new FailedSaveException('Cannot perform saveAttribute method on transient BO!');

		// assume that it is a persistent object that needs to be updated
		$sqlQuery = 'UPDATE '.$this->getTableName().' SET '.$attribute.'=? WHERE OID=?;';
				
		$this->lastQuery = $sqlQuery;
		$stmt = AlphaDAO::getConnection()->stmt_init();
				
		if($stmt->prepare($sqlQuery)) {
			if($this->$attribute instanceof Integer)
				$bindingsType = 'i';
			else
				$bindingsType = 's';
			$stmt->bind_param($bindingsType.'i', $value, $this->getOID());
			self::$logger->debug('Binding params ['.$bindingsType.'i, '.$value.', '.$this->getOID().']');
			$stmt->execute();					
		}else{
			throw new FailedSaveException('Failed to save attribute, error is ['.$stmt->error.'], query ['.$this->lastQuery.']');				
		}
				
		$stmt->close();
		
		$this->set($attribute, $value);
				
		if(method_exists($this, 'after_saveAttribute_callback'))
			$this->after_saveAttribute_callback();
			
		self::$logger->debug('<<saveAttribute');
	}
	
	/**
	 * Validates the object to be saved.
	 * 
	 * @return boolean
	 * @since 1.0
	 * @throws ValidationException
	 */
	protected function validate() {
		self::$logger->debug('>>validate()');
		
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
					if ($this->getPropObject($propName) != false && !preg_match($this->getPropObject($propName)->getRule(), $this->getPropObject($propName)->getValue())) {
						throw new ValidationException('Failed to save, validation error is: '.$this->getPropObject($propName)->getHelper());
						$valid = false;
					}
				}
			}
		}
		
		if(method_exists($this, 'after_validate_callback'))
			$this->after_validate_callback();
			
		self::$logger->debug('<<validate ['.$valid.']');		
		return $valid;
	}
	
	/**
	 * Deletes the current object from the database.
	 * 
	 * @since 1.0
	 * @throws FailedDeleteException
	 */
	public function delete() {
		self::$logger->debug('>>delete()');
				
		if(method_exists($this, 'before_delete_callback'))
				$this->before_delete_callback();
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
			
		// check for any relations on this object, then remove them to prevent orphaned data
		foreach($properties as $propObj) {
			$propName = $propObj->name;
						
			if(!$propObj->isPrivate() && isset($this->$propName) && $this->$propName instanceof Relation) {				
				$prop = $this->getPropObject($propName);
					
				// Handle MANY-TO-MANY rels
				if($prop->getRelationType() == 'MANY-TO-MANY') {
					self::$logger->debug('Deleting MANY-TO-MANY lookup objects...');
					
					try{
						// check to see if the rel is on this class
						$side = $prop->getSide(get_class($this));											
					}catch (IllegalArguementException $iae) {
						$side = $prop->getSide($this->getTableName().'_object');
					}
																
					self::$logger->debug('Side is ['.$side.']'.$this->getOID());
						
					$lookUp = $prop->getLookup();
					self::$logger->debug('Lookup object['.print_r($lookUp, true).']');
										
					// delete all of the old RelationLookup objects for this rel
					if($side == 'left')
						$lookUp->deleteAllByAttribute('leftID', $this->getOID());
					else
						$lookUp->deleteAllByAttribute('rightID', $this->getOID());
					self::$logger->debug('...done deleting!');
				}
					
				// should set related field values to null (MySQL is doing this for us as-is)
				if($prop->getRelationType() == 'ONE-TO-MANY' && !$prop->getRelatedClass() == 'tag_object') {
					$relatedObjects = $prop->getRelatedObjects();
					
					foreach($relatedObjects as $object) {
						$object->set($prop->getRelatedClassField(), null);
						$object->save();
					}
				}
				
				// in the case of tags, we will always remove the related tags once the BO is deleted
				if($prop->getRelationType() == 'ONE-TO-MANY' && $prop->getRelatedClass() == 'tag_object') {
					// just making sure that the Relation is set to current OID as its transient
					$prop->setValue($this->getOID());
					$relatedObjects = $prop->getRelatedObjects();					
					
					foreach($relatedObjects as $object) {						
						$object->delete();
					}
				}
			}
		}
		
		$sqlQuery = "DELETE FROM ".$this->getTableName()." WHERE OID = ?;";
			
		$this->lastQuery = $sqlQuery;
		
		$stmt = AlphaDAO::getConnection()->stmt_init();
				
		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $this->getOID());			
			$stmt->execute();					
		}else{
			throw new FailedDeleteException('Failed to delete object ['.$this->getOID().'], error is ['.$stmt->error.'], query ['.$this->lastQuery.']');				
		}
				
		$stmt->close();
		
		if(method_exists($this, 'after_delete_callback'))
			$this->after_delete_callback();
			
		$this->clear();
		self::$logger->debug('<<delete');
	}
	
	/**
	 * Delete all object instances from the database by the specified attribute matching the value provided.
	 *
	 * @param string $attribute The name of the field to delete the objects by.
	 * @param mixed $value The value of the field to delete the objects by.
	 * @return integer The number of rows deleted.
	 * @since 1.0
	 * @throws FailedDeleteException
	 */
	public function deleteAllByAttribute($attribute, $value) {
		self::$logger->debug('>>deleteAllByAttribute(attribute=['.$attribute.'], value=['.$value.'])');
				
		if(method_exists($this, 'before_deleteAllByAttribute_callback'))
			$this->before_deleteAllByAttribute_callback();
		
		try {
			$doomedObjects = $this->loadAllByAttribute($attribute, $value);
			$deletedRowCount = 0;
			
			foreach ($doomedObjects as $object) {
				$object->delete();
				$deletedRowCount++;
			}
		}catch (BONotFoundException $bonf) {
			// nothing found to delete
			self::$logger->warn($bonf->getMessage());
			return 0;
		}catch (AlphaException $e) {
			throw new FailedDeleteException('Failed to delete objects, error is ['.$e->getMessage().']');
			self::$logger->debug('<<deleteAllByAttribute [0]');
			return 0;
		}
		
		if(method_exists($this, 'after_deleteAllByAttribute_callback'))
			$this->after_deleteAllByAttribute_callback();
			
		self::$logger->debug('<<deleteAllByAttribute ['.$deletedRowCount.']');
		return $deletedRowCount;
	}
	
	/**
	 * Gets the version_num of the object from the database (returns 0 if the BO is not saved yet).
	 * 
	 * @return integer
	 * @since 1.0
	 * @throws BONotFoundException
	 */
	public function getVersion() {
		self::$logger->debug('>>getVersion()');
		
		if(method_exists($this, 'before_getVersion_callback'))
			$this->before_getVersion_callback();
		
		$sqlQuery = 'SELECT version_num FROM '.$this->getTableName().' WHERE OID = ?;';
		$this->lastQuery = $sqlQuery;
		
		$stmt = AlphaDAO::getConnection()->stmt_init();
		
		if($stmt->prepare($sqlQuery)) {
			$stmt->bind_param('i', $this->OID);
			
			$stmt->execute();
			
			$result = $this->bindResult($stmt);
			if(isset($result[0]))
				$row = $result[0];
				
			$stmt->close();
		}else{
			self::$logger->warn('The following query caused an unexpected result ['.$sqlQuery.']');
			if(!$this->checkTableExists()) {
				$this->makeTable();
				throw new BONotFoundException('Failed to get the version number, table did not exist so had to create!');
			}
			return;
		}
		
		if(!isset($row['version_num']) || $row['version_num'] < 1) {
			if(method_exists($this, 'after_getVersion_callback'))
				$this->after_getVersion_callback();
				
			self::$logger->debug('<<getVersion [0]');
			return 0;
		}else{
			if(method_exists($this, 'after_getVersion_callback'))
				$this->after_getVersion_callback();
			
			$version_num = $row['version_num'];
			
			self::$logger->debug('<<getVersion ['.$version_num.']');
			return $version_num;
		}
	}

	/**
	 * Builds a new database table for the BO class.
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */	
	public function makeTable() {
		self::$logger->debug('>>makeTable()');
		
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
						// special properties for RelationLookup OIDs
						if($this instanceof RelationLookup && ($propName == 'leftID' || $propName == 'rightID'))
							$sqlQuery .= "$propName INT(".$this->getPropObject($propName)->getSize().") ZEROFILL NOT NULL,";
						else
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
					case "RELATION":						
						$sqlQuery .= "$propName INT(11) ZEROFILL UNSIGNED,";
					break;
					default:
						$sqlQuery .= "";
					break;
				}
			}			
		}
		if($this->isTableOverloaded())
			$sqlQuery .= "classname VARCHAR(100),";
		
		$sqlQuery .= "PRIMARY KEY (OID)) TYPE=InnoDB;";
		
		$this->lastQuery = $sqlQuery;
		
		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to create the table ['.$this->getTableName().'] for the class ['.get_class($this).'], query is ['.$this->lastQuery.']');
			self::$logger->debug('<<makeTable');
		}
				
		// check the table indexes if any additional ones required
		$this->checkIndexes();
		
		if(method_exists($this, 'after_makeTable_callback'))
			$this->after_makeTable_callback();
		
		self::$logger->debug('<<makeTable');
	}

	/**
	 * Re-builds the table if the model requirements have changed.  All data is lost!
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */	
	public function rebuildTable() {
		self::$logger->debug('>>rebuildTable()');
		
		if(method_exists($this, 'before_rebuildTable_callback'))
			$this->before_rebuildTable_callback();
		
		$sqlQuery = 'DROP TABLE IF EXISTS '.$this->getTableName().';';

		$this->lastQuery = $sqlQuery;

		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to drop the table ['.$this->getTableName().'] for the class ['.get_class($this).'], query is ['.$this->lastQuery.']');
			self::$logger->debug('<<rebuildTable');
		}

		$this->makeTable();
		
		if(method_exists($this, 'after_rebuildTable_callback'))
			$this->after_rebuildTable_callback();			
		
		self::$logger->debug('<<rebuildTable');		
	}

	/**
	 * Adds in a new class property without loosing existing data (does an ALTER TABLE query on the
	 * database).
	 * 
	 * @param string $propName The name of the new field to add to the database table.
	 * @since 1.0
	 * @throws AlphaException	 
	 */
	public function addProperty($propName) {
		self::$logger->debug('>>addProperty(propName=['.$propName.'])');
		
		if(method_exists($this, 'before_addProperty_callback'))
			$this->before_addProperty_callback();
			
		$sqlQuery = 'ALTER TABLE '.$this->getTableName().' ADD ';
		
		if($this->isTableOverloaded() && $propName == 'classname') {
			$sqlQuery .= 'classname VARCHAR(100)';
		}else{
			if(!in_array($propName, $this->defaultAttributes) && !in_array($propName, $this->transientAttributes)) {
				$propClass = get_class($this->getPropObject($propName));
	
				switch (strtoupper($propClass)) {
					case 'INTEGER':
						$sqlQuery .= "$propName INT(".$this->getPropObject($propName)->getSize().")";
					break;
					case 'DOUBLE':
						$sqlQuery .= "$propName DOUBLE(".$this->getPropObject($propName)->getSize(true).")";
					break;
					case 'STRING':
						$sqlQuery .= "$propName VARCHAR(".$this->getPropObject($propName)->getSize().")";
					break;
					case 'SEQUENCE':
						$sqlQuery .= "$propName VARCHAR(".$this->getPropObject($propName)->getSize().")";
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
						$enumVals = $this->getPropObject($propName)->getOptions();
						foreach($enumVals as $val) {
							$sqlQuery .= "'".$val."',";
						}
						$sqlQuery = rtrim($sqlQuery, ",");
						$sqlQuery .= ')';
					break;
					case 'DENUM':
						$tmp = new DEnum(get_class($this).'::'.$propName);						
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

		$this->lastQuery = $sqlQuery;

		if(!$result = AlphaDAO::getConnection()->query($sqlQuery)) {
			throw new AlphaException('Failed to add the new attribute ['.$propName.'] to the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');
			self::$logger->debug('<<addProperty');
		}

		if(method_exists($this, 'after_addProperty_callback'))
			$this->after_addProperty_callback();			

		self::$logger->debug('<<addProperty');
	}

	/**
	 * Populates the current business object from global POST data.
	 * 
	 * @since 1.0
	 */	
	public function populateFromPost() {
		self::$logger->debug('>>populateFromPost()');
		
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
					if (strtoupper($propClass) != 'DATE' && strtoupper($propClass) != 'TIMESTAMP') {
						if(strtoupper($propClass) == 'TEXT' && !$this->getPropObject($propName)->getAllowHTML())
							$this->getPropObject($propName)->setValue(InputFilter::encode($_POST[$propName], false));
						else
							$this->getPropObject($propName)->setValue(InputFilter::encode($_POST[$propName], true));
					}else{						
						$this->getPropObject($propName)->populateFromString(InputFilter::encode($_POST[$propName], true));
					}					
				}
			}
			if ($propName == 'version_num' && isset($_POST['version_num']))
				$this->version_num->setValue($_POST['version_num']);
		}
		if(method_exists($this, 'after_populateFromPost_callback'))
			$this->after_populateFromPost_callback();
		
		self::$logger->debug('<<populateFromPost');	
	}

	/**
	 * Gets the maximum OID value from the database for this class type.
	 * 
	 * @return integer The maximum OID value in the class table.
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getMAX() {
		self::$logger->debug('>>getMAX()');
		
		if(method_exists($this, 'before_getMAX_callback'))
			$this->before_getMAX_callback();
		
		$sqlQuery = 'SELECT MAX(OID) AS max_OID FROM '.$this->getTableName();

		$this->lastQuery = $sqlQuery;

		$result = AlphaDAO::getConnection()->query($sqlQuery);	

		$row = $result->fetch_array(MYSQLI_ASSOC);

		if (isset($row['max_OID'])) {
			if(method_exists($this, 'after_getMAX_callback'))
				$this->after_getMAX_callback();

			self::$logger->debug('<<getMAX ['.$row['max_OID'].']');
			return $row['max_OID'];
		}else{			
			throw new AlphaException('Failed to get the MAX ID for the class ['.get_class($this).'] from the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');
			self::$logger->debug('<<getMAX [0]');
			return 0;
		}
	}
	
	/**
	 * Gets the count from the database for the amount of objects of this class.
	 * 
	 * @param array $atributes The attributes to count the objects by (optional).
	 * @param array $values The values of the attributes to count the objects by (optional).
	 * @return integer
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getCount($attributes=array(), $values=array()) {
		self::$logger->debug('>>getCount(attributes=['.print_r($attributes, true).'], values=['.print_r($values, true).'])');
		
		if(method_exists($this, 'before_getCount_callback'))
			$this->before_getCount_callback();
			
		if(!is_array($attributes) || !is_array($values)) {
			throw new IllegalArguementException('Illegal arrays attributes=['.print_r($attributes, true).'] and values=['.print_r($values, true).'] provided to loadAllByAttributes');
		}
		
		if($this->isTableOverloaded())
			$whereClause = ' WHERE classname = \''.get_class($this).'\' AND';
		else
			$whereClause = ' WHERE';
		for($i = 0; $i < count($attributes); $i++) {
			$whereClause .= ' '.$attributes[$i].' = \''.$values[$i].'\' AND';
			self::$logger->debug($whereClause);
		}
		// remove the last " AND"
		$whereClause = substr($whereClause, 0, -4);
		
		if($whereClause != ' WHERE')
			$sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->getTableName().$whereClause;
		else
			$sqlQuery = 'SELECT COUNT(OID) AS class_count FROM '.$this->getTableName();

		$this->lastQuery = $sqlQuery;
		
		$result = AlphaDAO::getConnection()->query($sqlQuery);

		if ($result) {
			if(method_exists($this, 'after_getCount_callback'))
				$this->after_getCount_callback();
				
			$row = $result->fetch_array(MYSQLI_ASSOC);

			self::$logger->debug('<<getCount ['.$row['class_count'].']');
			return $row['class_count'];
		}else{
			throw new AlphaException('Failed to get the count for the class ['.get_class($this).'] from the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');			
			self::$logger->debug('<<getCount [0]');
			return 0;
		}
	}

	/**
	 * Gets the OID for the object in zero-padded format (same as getOID()).  This version is designed
	 * to be overridden in case you want to do something different with the ID of your objects outside
	 * of the standard 11 digit OID sequence used internally in Alpha.
	 * 
	 * @return integer 11 digit zero-padded OID value.
	 * @since 1.0
	 */
	public function getID() {
		self::$logger->debug('>>getID()');
		$oid = str_pad($this->OID, 11, '0', STR_PAD_LEFT);
		self::$logger->debug('<<getID ['.$oid.']');
		return $oid;
	}
	
	/**
	 * Gets the OID for the object in zero-padded format (same as getID()).  This version is final so cannot
	 * be overridden.
	 * 
	 * @return integer 11 digit zero-padded OID value.
	 * @since 1.0
	 */
	public final function getOID() {
		self::$logger->debug('>>getOID()');
		$oid = str_pad($this->OID, 11, '0', STR_PAD_LEFT);
		self::$logger->debug('<<getOID ['.$oid.']');
		return $oid;
	}
	
	/**
	 * Method for getting version number of the object.
	 * 
	 * @return Integer The object version number.
	 * @since 1.0
	 */
	public function getVersionNumber() {
		self::$logger->debug('>>getVersionNumber()');
		self::$logger->debug('<<getVersionNumber ['.$this->version_num.']');
		return $this->version_num;
	}
	
	/**
	 * Populate all of the enum options for this object from the database.
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */
	protected function setEnumOptions() {
		self::$logger->debug('>>setEnumOptions()');
		
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
					$sqlQuery = "SHOW COLUMNS FROM ".$this->getTableName()." LIKE '$propName'";
					
					$this->lastQuery = $sqlQuery;
					
					$result = AlphaDAO::getConnection()->query($sqlQuery);
					
					if ($result) {							
						$row = $result->fetch_array(MYSQLI_NUM);
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
		self::$logger->debug('<<setEnumOptions');
	}
	
	/**
	 * Generic getter method for accessing class properties.  Will use the method get.ucfirst($prop) instead if that
	 * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use any 
	 * get.ucfirst($prop) method even if it exists, false otherwise (default).
	 * 
	 * @param string $prop The name of the object property to get.
	 * @param boolean $noChildMethods Set to true if you do not want to use getters in the child object, defaults
	 * to false.
	 * @return mixed The property value.
	 * @since 1.0
	 * @throws IllegalArguementException
	 * @throws AlphaException
	 */
	public function get($prop, $noChildMethods = false) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
			
		self::$logger->debug('>>get(prop=['.$prop.'], noChildMethods=['.$noChildMethods.'])');
		
		if(method_exists($this, 'before_get_callback'))
			$this->before_get_callback();
			
		if(empty($prop))
			throw new IllegalArguementException('Cannot call get with empty $prop arguement!');
		
		// handle attributes with a get.ucfirst($prop) method
		if(!$noChildMethods && method_exists($this, 'get'.ucfirst($prop))) {
			if(method_exists($this, 'after_get_callback'))
				$this->after_get_callback();

			self::$logger->debug('<<get ['.eval('return $this->get'.ucfirst($prop).'();').'])');
			return eval('return $this->get'.ucfirst($prop).'();');
		}else{
			// handle attributes with no dedicated child get.ucfirst($prop) method
			if(isset($this->$prop) && method_exists($this->$prop, 'getValue')) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();

				// complex types will have a getValue() method, return the value of that
				self::$logger->debug('<<get ['.$this->$prop->getValue().'])');
				return $this->$prop->getValue();
			}elseif(isset($this->$prop)) {
				if(method_exists($this, 'after_get_callback'))
					$this->after_get_callback();

				// simple types returned as-is
				self::$logger->debug('<<get ['.$this->$prop.'])');
				return $this->$prop;
			}else{
				throw new AlphaException('Could not access the property ['.$prop.'] on the object of class ['.get_class($this).']');
				self::$logger->debug('<<get [false])');
				return false;
			}
		}
	}
	
	/**
	 * Generic setter method for setting class properties.  Will use the method set.ucfirst($prop) instead if that
	 * method exists at a child level (by default).  Set $noChildMethods to true if you don't want to use 
	 * any get.ucfirst($prop) method even if it exists, false otherwise (default).
	 * 
	 * @param string $prop The name of the property to set.
	 * @param mixed $value The value of the property to set.
	 * @param boolean $noChildMethods Set to true if you do not want to use setters in the child object, defaults
	 * to false.
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function set($prop, $value, $noChildMethods = false) {
		self::$logger->debug('>>set(prop=['.$prop.'], $value=['.$value.'], noChildMethods=['.$noChildMethods.'])');
		
		if(method_exists($this, 'before_set_callback'))
			$this->before_set_callback();
		
		// handle attributes with a set.ucfirst($prop) method
		if(!$noChildMethods && method_exists($this, 'set'.ucfirst($prop))) {
			if(method_exists($this, 'after_set_callback'))
				$this->after_set_callback();
			
			eval('$this->set'.ucfirst($prop)."('$value');");
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
		self::$logger->debug('<<set');
	}
	
	/**
	 * Gets the property object rather than the value for complex attributes.  Returns false if 
	 * the property exists but is private.
	 * 
	 * @param string $prop The name of the property we are getting.
	 * @return AlphaType The complex type object found.
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function getPropObject($prop) {
		self::$logger->debug('>>getPropObject(prop=['.$prop.'])');
		
		if(method_exists($this, 'before_getPropObject_callback'))
			$this->before_getPropObject_callback();
			
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		// firstly, check for private
		$attribute = new ReflectionProperty(get_class($this), $prop);
		
		if($attribute->isPrivate()) {
			if(method_exists($this, 'after_getPropObject_callback'))
				$this->after_getPropObject_callback();
				
			self::$logger->debug('<<getPropObject [false]');
			return false;
		}
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
		
			if($prop == $propName) {
				if(method_exists($this, 'after_getPropObject_callback'))
					$this->after_getPropObject_callback();
				
				self::$logger->debug('<<getPropObject ['.print_r($this->$prop, true).']');
				return $this->$prop;				
			}
		}		
		throw new IllegalArguementException('Could not access the property object ['.$prop.'] on the object of class ['.get_class($this).']');
		self::$logger->debug('<<getPropObject [false]');
		return false;
	}
	
	/**
	 * Checks to see if the table exists in the database for the current business class.
	 * 
	 * @return boolean
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function checkTableExists() {
		self::$logger->debug('>>checkTableExists()');
		
		if(method_exists($this, 'before_checkTableExists_callback'))
			$this->before_checkTableExists_callback();
			
		global $config;
				
		$tableExists = false;
		
		$sqlQuery = 'SHOW TABLES;';
		$this->lastQuery = $sqlQuery;
		
		$result = AlphaDAO::getConnection()->query($sqlQuery);
		
		while ($row = $result->fetch_array(MYSQLI_NUM)) {    		
    		if (strtolower($row[0]) == strtolower($this->getTableName()))
    			$tableExists = true;
		}		
		
		if ($result) {
			if(method_exists($this, 'after_checkTableExists_callback'))
				$this->after_checkTableExists_callback();
			
			self::$logger->debug('<<checkTableExists ['.$tableExists.']');
			return $tableExists;
		}else{			
			throw new AlphaException('Failed to access the system database correctly, error is ['.AlphaDAO::getConnection()->error.']');
			self::$logger->debug('<<checkTableExists [false]');
			return false;
		}
	}
	
	/**
	 * Static method to check the database and see if the table for the indicated BO class name
	 * exists (assumes table name will be $BOClassName less "_object").
	 * 
	 * @param string $BOClassName The name of the business object class we are checking.
	 * @return boolean
	 * @since 1.0
	 * @throws AlphaException
	 */
	public static function checkBOTableExists($BOClassName) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>checkBOTableExists(BOClassName=['.$BOClassName.'])');
		
		global $config;
		
		eval('$tableName = '.$BOClassName.'::TABLE_NAME;');
		
		if(empty($tableName))
			$tableName = substr($BOClassName, 0, strpos($BOClassName, '_'));		
				
		$tableExists = false;
		
		$sqlQuery = 'SHOW TABLES;';
		
		$result = AlphaDAO::getConnection()->query($sqlQuery);
		
		while ($row = $result->fetch_array(MYSQLI_NUM)) {    		
    		if ($row[0] == $tableName)
    			$tableExists = true;
		}		
		
		if ($result) {			
			self::$logger->debug('<<checkBOTableExists ['.($tableExists ? 'true' : 'false').']');
			return $tableExists;
		}else{			
			throw new AlphaException('Failed to access the system database correctly, error is ['.AlphaDAO::getConnection()->error.']');
			self::$logger->debug('<<checkBOTableExists [false]');
			return false;
		}
	}
	
	/**
	 * Checks to see if the table in the database matches (for fields) the business class definition, i.e. if the
	 * database table is in sync with the class definition.
	 * 
	 * @return boolean
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function checkTableNeedsUpdate() {
		self::$logger->debug('>>checkTableNeedsUpdate()');
				
		if(method_exists($this, 'before_checkTableNeedsUpdate_callback'))
			$this->before_checkTableNeedsUpdate_callback();
		
		$tableExists = $this->checkTableExists();
		
		if (!$tableExists) {
			self::$logger->debug('<<checkTableNeedsUpdate [true]');
			return true;
		}else{
			$updateRequired = false;
			
			$matchCount = 0;
			
			$query = 'SHOW COLUMNS FROM '.$this->getTableName();
			$result = AlphaDAO::getConnection()->query($query);
			$this->lastQuery = $query;
			
			// get the class attributes
			$reflection = new ReflectionClass(get_class($this));
			$properties = $reflection->getProperties();
			
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->transientAttributes)) {
				
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
			if($this->isTableOverloaded()) {
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
				if(method_exists($this, 'before_checkTableNeedsUpdate_callback'))
					$this->before_checkTableNeedsUpdate_callback();
				
				// check the table indexes
				try {
					$this->checkIndexes();
				}catch (AlphaException $ae) {
					self::$logger->warn("Error while checking database indexes:\n\n".$ae->getMessage());
				}
				
				self::$logger->debug('<<checkTableNeedsUpdate ['.$updateRequired.']');
				return $updateRequired;
			}else{				
				throw new AlphaException('Failed to access the system database correctly, error is ['.AlphaDAO::getConnection()->error.']');
				self::$logger->debug('<<checkTableNeedsUpdate [false]');
				return false;
			}
		}
	}
	
	/**
	 * Returns an array containing any properties on the class which have not been created on the database 
	 * table yet.
	 * 
	 * @return array An array of missing fields in the database table.
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function findMissingFields() {
		self::$logger->debug('>>findMissingFields()');
		
		if(method_exists($this, 'before_findMissingFields_callback'))
			$this->before_findMissingFields_callback();
		
		$missingFields = array();			
		$matchCount = 0;
			
		$sqlQuery = 'SHOW COLUMNS FROM '.$this->getTableName();
		
		$result = AlphaDAO::getConnection()->query($sqlQuery);
		
		$this->lastQuery = $sqlQuery;
			
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if (!in_array($propName, $this->transientAttributes)) {
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
		if($this->isTableOverloaded()) {
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
			throw new AlphaException('Failed to access the system database correctly, error is ['.AlphaDAO::getConnection()->error.']');
		}
		
		if(method_exists($this, 'after_findMissingFields_callback'))
			$this->after_findMissingFields_callback();
		
		self::$logger->debug('<<findMissingFields ['.print_r($missingFields, true).']');
		return $missingFields;	
	}
	
	/**
	 * Getter for the TABLE_NAME, which should be set by a child of this class.
	 * 
	 * @return string The table name in the database.
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function getTableName() {
		self::$logger->debug('>>getTableName()');
		
		eval('$TABLE_NAME = '.get_class($this).'::TABLE_NAME;');
		
		if(!empty($TABLE_NAME)) {
			self::$logger->debug('<<getTableName ['.$TABLE_NAME.']');
    		return $TABLE_NAME;        
    	}else{
    		throw new AlphaException('Error: no TABLE_NAME constant set for the class '.get_class($this));
    		self::$logger->debug('<<getTableName []');		
    		return '';
    	}
	}
	
	/**
	 * Method for getting the OID of the person who created this BO.
	 * 
	 * @return Integer The OID of the creator.
	 * @since 1.0
	 */
	public function getCreatorId() {
		self::$logger->debug('>>getCreatorId()');
		self::$logger->debug('<<getCreatorId ['.$this->created_by.']');
		return $this->created_by;
	}
	
	/**
	 * Method for getting the OID of the person who updated this BO.
	 * 
	 * @return Integer The OID of the updator.
	 * @since 1.0
	 */
	public function getUpdatorId() {
		self::$logger->debug('>>getUpdatorId()');
		self::$logger->debug('<<getUpdatorId ['.$this->updated_by.']');
		return $this->updated_by;
	}
	
	/**
	 * Method for getting the date/time of when the BO was created.
	 * 
	 * @return Timestamp
	 * @since 1.0
	 */
	public function getCreateTS() {
		self::$logger->debug('>>getCreateTS()');
		self::$logger->debug('<<getCreateTS ['.$this->created_ts.']');
		return $this->created_ts;
	}
	
	/**
	 * Method for getting the date/time of when the BO was last updated.
	 * 
	 * @return Timestamp
	 * @since 1.0
	 */
	public function getUpdateTS() {
		self::$logger->debug('>>getUpdateTS()');
		self::$logger->debug('<<getUpdateTS ['.$this->updated_ts.']');
		return $this->updated_ts;
	}
	
	/**
	 * Adds the name of the attribute provided to the list of transient (non-saved) attributes for this BO.
	 * 
	 * @param string $attributeName The name of the attribute to not save.
	 * @since 1.0
	 */
	public function markTransient($attributeName) {
		self::$logger->debug('>>markTransient(attributeName=['.$attributeName.'])');
		self::$logger->debug('<<markTransient');
		array_push($this->transientAttributes, $attributeName);		
	}
	
	/**
	 * Removes the name of the attribute provided from the list of transient (non-saved) attributes for this BO, 
	 * ensuring that it will be saved on the next attempt.
	 * 
	 * @param string $attributeName The name of the attribute to save.
	 * @since 1.0
	 */
	public function markPersistent($attributeName) {
		self::$logger->debug('>>markPersistent(attributeName=['.$attributeName.'])');
		self::$logger->debug('<<markPersistent');
		$this->transientAttributes = array_diff($this->transientAttributes, array($attributeName));
	}
	
	/**
	 * Adds the name of the attribute(s) provided to the list of unique (constrained) attributes for this BO.
	 * 
	 * @param string $attribute1Name The first attribute to mark unique in the database.
	 * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
	 * @since 1.0
	 */
	protected function markUnique($attribute1Name, $attribute2Name='') {
		self::$logger->debug('>>markUnique(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'])');
		if(empty($attribute2Name)) {
			array_push($this->uniqueAttributes, $attribute1Name);
		}else{
			// Process composite unique keys: add them seperated by a + sign
			$attributes = $attribute1Name.'+'.$attribute2Name;
			array_push($this->uniqueAttributes, $attributes);
		}
		self::$logger->debug('<<markUnique');
	}
	
	/**
	 * Gets an array of all of the names of the active database indexes for this class.
	 *
	 * @return array An array of database indexes on this table.
	 * @since 1.0
	 * @throws AlphaException
	 */
	protected function getIndexes() {
		self::$logger->debug('>>getIndexes()');
		
		$query = 'SHOW INDEX FROM '.$this->getTableName();
		
		$result = AlphaDAO::getConnection()->query($query);
		
		$this->lastQuery = $query;
		
		$indexNames = array();
		
		if (!$result) {
			throw new AlphaException('Failed to access the system database correctly, error is ['.AlphaDAO::getConnection()->error.']');
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
	 * @since 1.0
	 */
	protected function checkIndexes() {
		self::$logger->debug('>>checkIndexes()');		
		
		if(method_exists($this, 'before_checkIndexes_callback'))
			$this->before_checkIndexes_callback();
		
		$indexNames = $this->getIndexes();		
		
		// process unique keys
		foreach($this->uniqueAttributes as $prop) {
			// check for composite indexes
			if(strpos($prop, '+')) {
				$attributes = explode('+', $prop);
				
				$index_exists = false;
				foreach ($indexNames as $index) {
					if ($attributes[0].'_'.$attributes[1].'_unq_idx' == $index) {
				    	$index_exists = true;
					}
				}
	
				if(!$index_exists)
					$this->createUniqueIndex($attributes[0], $attributes[1]);
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
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;			
			$prop = $this->getPropObject($propName);
			if(get_class($prop) == 'Relation') {
								
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
		
		if(method_exists($this, 'after_checkIndexes_callback'))
			$this->after_checkIndexes_callback();
		
		self::$logger->debug('<<checkIndexes');
	}
	
	/**
	 * Creates a foreign key constraint (index) in the database on the given attribute.
	 * 
	 * @param string $attributeName The name of the attribute to apply the index on.
	 * @param string $relatedClass The name of the related class in the format "name_object".
	 * @param string $relatedClassAttribute The name of the field to relate to on the related class.
	 * @param bool $allowNullValues For foreign key indexes that don't allow null values, set this to false (default is true).
	 * @since 1.0
	 * @throws AlphaException
	 * @todo convert to MySQLi
	 */
	protected function createForeignIndex($attributeName, $relatedClass, $relatedClassAttribute) {
		self::$logger->debug('>>createForeignIndex(attributeName=['.$attributeName.'], relatedClass=['.$relatedClass.'], relatedClassAttribute=['.$relatedClassAttribute.']');
		
		if(method_exists($this, 'before_createForeignIndex_callback'))
			$this->before_createForeignIndex_callback();
		
		AlphaDAO::loadClassDef($relatedClass);		
		$relatedBO = new $relatedClass;		
		$tableName = $relatedBO->getTableName();

		// if the relation is on itself (table-wise), exist without attempting to create the foreign keys
		if($this->getTableName() == $tableName) {
			self::$logger->debug('<<createForeignIndex');
			return;
		}

		if(AlphaDAO::checkBOTableExists($tableName.'_object')) {
			$sqlQuery = '';
			if($attributeName == 'leftID')
				$sqlQuery = 'ALTER TABLE '.$this->getTableName().' ADD INDEX leftID_fk_idx (leftID);';
			if($attributeName == 'rightID')
				$sqlQuery = 'ALTER TABLE '.$this->getTableName().' ADD INDEX rightID_fk_idx (rightID);';
				
			if(!empty($sqlQuery)) {
				$this->lastQuery = $sqlQuery;
	
				$result = mysql_query($sqlQuery);
	
				if (mysql_error() != '') {					
					throw new AlphaException('Failed to create an index on ['.$this->getTableName().'], error is ['.mysql_error().'], query ['.$this->lastQuery.']');
				}
			}

			$sqlQuery = 'ALTER TABLE '.$this->getTableName().' ADD FOREIGN KEY '.$attributeName.'_fk_idx ('.$attributeName.') REFERENCES '.$tableName.' ('.$relatedClassAttribute.') ON DELETE SET NULL;';
			
			$this->lastQuery = $sqlQuery;	
			$result = mysql_query($sqlQuery);
		}
	
		if (mysql_error() == '') {
			if(method_exists($this, 'after_createForeignIndex_callback'))
				$this->after_createForeignIndex_callback();
				self::$logger->debug('Successfully created the foreign key index ['.$attributeName.'_fk_idx]');
		}else{			
			throw new AlphaException('Failed to create the index ['.$attributeName.'_fk_idx] on ['.$this->getTableName().'], error is ['.mysql_error().'], query ['.$this->lastQuery.']');
		}		
		
		self::$logger->debug('<<createForeignIndex');
	}
	
	/**
	 * Creates a unique index in the database on the given attribute(s).
	 * 
	 * @param string $attribute1Name The first attribute to mark unique in the database.
	 * @param string $attribute2Name The second attribute to mark unique in the databse (optional, use only for composite keys).
	 * @since 1.0
	 * @throws AlphaException
	 * @todo convert to MySQLi
	 */
	protected function createUniqueIndex($attribute1Name, $attribute2Name = '') {
		self::$logger->debug('>>createUniqueIndex(attribute1Name=['.$attribute1Name.'], attribute2Name=['.$attribute2Name.'])');
		
		if(method_exists($this, 'before_createUniqueIndex_callback'))
			$this->before_createUniqueIndex_callback();
		
		if(empty($attribute2Name)) {
			$sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_unq_idx ON '.$this->getTableName().' ('.$attribute1Name.');';
			
			$this->lastQuery = $sqlQuery;
	
			$result = mysql_query($sqlQuery);
	
			if (mysql_error() == '') {
				if(method_exists($this, 'after_createUniqueIndex_callback'))
					$this->after_createUniqueIndex_callback();
			}else{			
				throw new AlphaException('Failed to create the index ['.$attribute1Name.'_unq_idx] on ['.$this->getTableName().'], error is ['.mysql_error().']');
			}
		}else{
			// process composite unique keys
			$sqlQuery = 'CREATE UNIQUE INDEX '.$attribute1Name.'_'.$attribute2Name.'_unq_idx ON '.$this->getTableName().' ('.$attribute1Name.','.$attribute2Name.');';
			
			$this->lastQuery = $sqlQuery;
	
			$result = mysql_query($sqlQuery);
	
			if (mysql_error() == '') {
				if(method_exists($this, 'after_create_unique_index_callback'))
					$this->after_createUniqueIndex_callback();
			}else{			
				throw new AlphaException('Failed to create the index ['.$attribute1Name.'_'.$attribute2Name.'_unq_idx] on ['.$this->getTableName().'], error is ['.mysql_error().']');
			}
		}
		self::$logger->debug('<<createUniqueIndex');
	}
	
	/**
	 * Parses a MySQL error for the value that violated a unique constraint.
	 * 
	 * @param string $error The MySQL error string.
	 * @since 1.0
	 */
	protected function findOffendingValue($error) {
		self::$logger->debug('>>findOffendingValue(error=['.$error.'])');
				
		$singleQuote1 = strpos($error,"'");
		$singleQuote2 = strrpos($error,"'");
		
		$value = substr($error, $singleQuote1, ($singleQuote2-$singleQuote1)+1);
		self::$logger->debug('<<findOffendingValue ['.$value.'])');
		return $value;
	}
	
	/**
	 * Gets the data labels array.
	 * 
	 * @return array An array of attribute labels.
	 * @since 1.0
	 */
	public function getDataLabels() {
		self::$logger->debug('>>getDataLabels()');
		self::$logger->debug('<<getDataLabels() ['.print_r($this->dataLabels, true).'])');		
		return $this->dataLabels;
	}
	
	/**
	 * Gets the data label for the given attribute name.
	 * 
	 * @param $att The attribute name to get the label for.
	 * @return string
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function getDataLabel($att) {
		self::$logger->debug('>>getDataLabel(att=['.$att.'])');
		
		if(in_array($att, array_keys($this->dataLabels))) {
			self::$logger->debug('<<getDataLabel ['.$this->dataLabels[$att].'])');		
			return $this->dataLabels[$att];
		}else{
			throw new IllegalArguementException('No data label found on the class ['.get_class($this).'] for the attribute ['.$att.']');
			self::$logger->debug('<<getDataLabel [])');
			return '';
		}
	}
	
	/**
	 * Loops over the core and custom BO directories and builds an array of all of the BO class names in the system.
	 *
	 * @return array An array of business object class names.
	 * @since 1.0
	 */
	public static function getBOClassNames() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>getBOClassNames()');
		
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

	    asort($classNameArray);
	    self::$logger->debug('<<getBOClassNames ['.print_r($classNameArray, true).']');
	    return $classNameArray;
	}
	
	/**
	 * Get the array of default attribute names.
	 *
	 * @return array An array of attribute names.
	 * @since 1.0
	 */
	public function getDefaultAttributes() {
		self::$logger->debug('>>getDefaultAttributes()');
		self::$logger->debug('<<getDefaultAttributes ['.print_r($this->defaultAttributes, true).']');
		return $this->defaultAttributes;
	}
	
	/**
	 * Get the array of transient attribute names.
	 *
	 * @return array An array of attribute names.
	 * @since 1.0
	 */
	public function getTransientAttributes() {
		self::$logger->debug('>>getTransientAttributes()');
		self::$logger->debug('<<getTransientAttributes ['.print_r($this->transientAttributes, true).']');
		return $this->transientAttributes;
	}
	
	/**
	 * Get the array of persistent attribute names, i.e. those that are saved in the database.
	 * 
	 * @return array An array of attribute names.
	 * @since 1.0
	 */
	public function getPersistentAttributes() {
		self::$logger->debug('>>getPersistentAttributes()');
		
		$attributes = array();
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
	
		foreach($properties as $propObj) {
			$propName = $propObj->name;
											
			// filter transient attributes
			if(!in_array($propName, $this->transientAttributes)) {
				array_push($attributes, $propName);
			}
		}
		
		self::$logger->debug('<<getPersistentAttributes ['.print_r($attributes, true).']');
		return $attributes;
	}
	
	/**
	 * Private setter for the object ID, used from load methods.
	 *
	 * @param integer $OID The Object ID.
	 * @since 1.0
	 */
	private function setOID($OID) {
		self::$logger->debug('>>setOID(OID=['.$OID.'])');
		self::$logger->debug('<<setOID');
		$this->OID = $OID;
	}
	
	/**
	 * Inspector to see if the business object is transient (not presently stored in the database).
	 * 
	 * @return boolean
	 * @since 1.0
	 */
	public function isTransient() {
		self::$logger->debug('>>isTransient()');
		
		if(empty($this->OID) || !isset($this->OID) || $this->OID == '00000000000') {
			self::$logger->debug('<<isTransient [true]');
			return true;
		}else{
			self::$logger->debug('<<isTransient [false]');
			return false;
		}
	}
	
	/**
	 * Get the last database query run on this object.
	 *
	 * @return string An SQL query string.
	 * @since 1.0
	 */
	public function getLastQuery() {
		self::$logger->debug('>>getLastQuery()');
		self::$logger->debug('<<getLastQuery ['.$this->lastQuery.']');
		return $this->lastQuery;
	}
	
	/**
	 * Unsets all of the attributes of this object to null.
	 * 
	 * @since 1.0
	 */
	private function clear() {
		self::$logger->debug('>>clear()');
		
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			if(!$propObj->isPrivate())
				unset($this->$propName);
		}
		
		self::$logger->debug('<<clear');
	}
	
	/**
	 * Reloads the object from the database, overwritting any attribute values in memory.
	 * 
	 * @since 1.0
	 * @throws AlphaException
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
	 * Loads the definition from the file system for the BO class name provided.
	 * 
	 * @param string $classname The name of the business object class name.
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public static function loadClassDef($classname) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>loadClassDef(classname=['.$classname.'])');
		
		global $config;
		
		if(file_exists($config->get('sysRoot').'model/'.$classname.'.inc'))
			require_once $config->get('sysRoot').'model/'.$classname.'.inc';
		elseif(file_exists($config->get('sysRoot').'alpha/model/'.$classname.'.inc'))
			require_once $config->get('sysRoot').'alpha/model/'.$classname.'.inc';
		else
			throw new IllegalArguementException('The class ['.$classname.'] is not defined anywhere!');
		
		self::$logger->debug('<<loadClassDef');
	}
	
	/**
	 * Checks that a record exists for the BO in the database.
	 * 
	 * @param int $OID The Object ID of the object we want to see whether it exists or not.
	 * @return boolean
	 * @since 1.0
	 * @throws AlphaException
	 * @todo convert to MySQLi
	 */
	public function checkRecordExists($OID) {
		self::$logger->debug('>>checkRecordExists(OID=['.$OID.'])');
		
		if(method_exists($this, 'before_checkRecordExists_callback'))
			$this->before_checkRecordExists_callback();
		
		$sqlQuery = "SELECT OID FROM ".$this->getTableName()." WHERE OID = '$OID';";

		$this->lastQuery = $sqlQuery;

		$result = mysql_query($sqlQuery);		

		if (mysql_error() == '') {
			if(method_exists($this, 'after_checkRecordExists_callback'))
				$this->after_checkRecordExists_callback();
				
			if(mysql_num_rows($result) > 0) {
				self::$logger->debug('<<checkRecordExists [true]');
				return true;
			}else{
				self::$logger->debug('<<checkRecordExists [false]');
				return false;
			}
		}else{
			throw new AlphaException('Failed to check for the record ['.$OID.'] on the class ['.get_class($this).'] from the table ['.$this->getTableName().'], query is ['.$this->lastQuery.']');			
			self::$logger->debug('<<checkRecordExists [false]');
			return false;
		}
	}
	
	/**
	 * Checks to see if the table name matches the classname, and if not if the table
	 * name matches the classname name of another BO, i.e. the table is used to store 
	 * multiple types of BOs.
	 * 
	 * @return bool
	 * @since 1.0
	 * @throws BadBOTableNameException
	 * @todo convert to MySQLi
	 */
	public function isTableOverloaded() {
		self::$logger->debug('>>isTableOverloaded()');
		
		$classname = get_class($this);
		$tablename = $this->getTableName().'_object';
		
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
			
			$BOclasses = self::getBOClassNames();
			
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
			
			$query = 'SHOW COLUMNS FROM '.$this->getTableName();
			$result = mysql_query($query);
			
			if(mysql_error() == '') {
				while ($row = mysql_fetch_assoc($result)) {						
					if ('classname' == $row['Field']) {
						self::$logger->debug('<<isTableOverloaded [true]');
						return true;
					}
				}
			}else{
				self::$logger->warn('Error during show columns ['.mysql_error().']');
			}
			
			self::$logger->debug('<<isTableOverloaded [false]');
			return false;
		}
	}
	
	/**
	 * Starts a new database transaction.
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */
	public static function begin() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>begin()');     	
     	
     	if (!AlphaDAO::getConnection()->autocommit(false))
     		throw new AlphaException('Error beginning a new transaction, error is ['.AlphaDAO::getConnection()->get_warnings().']');
     	
     	self::$logger->debug('<<begin');
	}
	
	/**
	 * Commits the current database transaction.
	 * 
	 * @since 1.0
	 * @throws FailedSaveException
	 * @todo convert to MySQLi
	 */
	public static function commit() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>commit()');
		
    	if (!AlphaDAO::getConnection()->commit())
     		throw new FailedSaveException('Error commiting a transaction, error is ['.AlphaDAO::getConnection()->get_warnings().']');
    	
    	self::$logger->debug('<<commit');
  	}
  	
	/**
	 * Aborts the current database transaction.
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 * @todo convert to MySQLi
	 */
	public static function rollback() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>rollback()');
    	mysql_query('ROLLBACK');
    	
    	if (!AlphaDAO::getConnection()->rollback())
     		throw new AlphaException('Error aborting a transaction, error is ['.AlphaDAO::getConnection()->get_warnings().']');
    	
    	self::$logger->debug('<<rollback');
  	}

  	/**
  	 * Static method that tries to determine if the system database has been installed or not.
  	 *
  	 * @return boolean
  	 * @since 1.0
  	 * @throws AlphaException
  	 * @todo convert to MySQLi
  	 */
  	public static function isInstalled() {
  		if(self::$logger == null)
			self::$logger = new Logger('AlphaDAO');
		self::$logger->debug('>>isInstalled()');
		
		global $config;
		
  		/*
  		 * Install conditions are:
  		 * 
  		 * 1. person table exists
  		 * 2. rights table exists
  		 */
  		$tableExistsCount = 0;
		
		$result = mysql_list_tables($config->get('sysDB'));
		
		while ($row = mysql_fetch_row($result)) {    		
    		if ($row[0] == person_object::TABLE_NAME || $row[0] == rights_object::TABLE_NAME)
    			$tableExistsCount++;
		}		
		
		if (mysql_error() == '') {			
			if($tableExistsCount == 2) {
				self::$logger->debug('<<isInstalled [true]');
				return true;
			}else{
				self::$logger->debug('<<isInstalled [false]');
				return false;
			}
		}else{			
			throw new AlphaException('Failed to access the system database correctly, error is ['.mysql_error().']');
			self::$logger->debug('<<isInstalled [false]');
			return false;
		}
  	}
  	
  	/**
  	 * Returns true if the BO has a Relation property called tags, false otherwise.
  	 * 
  	 * @return boolean
  	 * @since 1.0
  	 */
  	public function isTagged() {
  		if(isset($this->taggedAttributes) && isset($this->tags) && $this->tags instanceof Relation)
  			return true;
  		else
  			return false;
  	}

  	/**
  	 * Setter for the BO version number.
  	 * 
  	 * @param integer $versionNumber The version number.
  	 * @since 1.0
  	 */
  	private function setVersion($versionNumber) {
  		$this->version_num->setValue($versionNumber);
  	}
  	
  	/**
  	 * Cast a BO to another type of BO.  A new BO will be returned with the same OID and
  	 * version_num as the old BO, so this is NOT a true cast but is a copy.  All attribute
  	 * values will be copied accross.
  	 * 
  	 * @param string $targetClassName The name of the target BO class.
  	 * @param AlphaDAO $originalBO The original business object.
  	 * @return AlphaDAO The new business object resulting from the cast.
  	 * @since 1.0
  	 */
  	public function cast($targetClassName, $originalBO) {  		
  		AlphaDAO::loadClassDef($targetClassName);
  		
  		$BO = new $targetClassName;
  		$BO->setOID($originalBO->getOID());
  		$BO->setVersion($originalBO->getVersion());
  		
  		// get the class attributes
		$originalBOreflection = new ReflectionClass(get_class($originalBO));
		$originalBOproperties = $originalBOreflection->getProperties();
		$newBOreflection = new ReflectionClass($targetClassName);
		$newBOproperties = $newBOreflection->getProperties();
		
		// copy the property values from the old BO to the new BO
		
		if(count($originalBOproperties) < count($newBOproperties)) {
			// the original BO is smaller, so loop over its properties
			foreach($originalBOproperties as $propObj) {
				$propName = $propObj->name;
				if(!in_array($propName, $this->transientAttributes))
					$BO->set($propName, $originalBO->get($propName));
			}
		}else{
			// the new BO is smaller, so loop over its properties
			foreach($newBOproperties as $propObj) {
				$propName = $propObj->name;
				if(!in_array($propName, $this->transientAttributes))
					$BO->set($propName, $originalBO->get($propName));
			}
		}		
  		
  		return $BO;
  	}
  	
  	/**
  	 * Converts "business_object" to "Business" and returns.
  	 *  
  	 * @return string
  	 * @since 1.0
  	 */
  	public function getFriendlyClassName() {
  		$name = explode('_', get_class($this));
  		return ucfirst($name[0]);
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
  	 * @since 1.0
  	 */
	protected function bindParams($stmt, $attributes=array(), $values=array()) {
		self::$logger->debug('>>bindParams(stmt=['.print_r($stmt, true).'])');
		
		$bindingsTypes = '';
		$params = array();
		
		// here we are only binding the supplied attributes
		if(count($attributes) > 0 && count($attributes) == count($values)) {
	
			for($i = 0; $i < count($values); $i++) {
				if (Validator::isInteger($values[$i]))
					$bindingsTypes .= 'i';
				else
					$bindingsTypes .= 's';
				array_push($params, $values[$i]);
			}
				
			if($this->isTableOverloaded()) {
				if(isset($this->classname)) {
					$bindingsTypes .= 's';
					array_push($params, $this->classname);					
				}else{					
					$bindingsTypes .= 's';					
					array_push($params, get_class($this));					
				}
			}
		}else{ // bind all attributes on the business object
		
			// get the class attributes
			$reflection = new ReflectionClass(get_class($this));
			$properties = $reflection->getProperties();
	
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				if (!in_array($propName, $this->transientAttributes)) {				
					// Skip the OID, database auto number takes care of this.
					if($propName != 'OID' && $propName != 'version_num') {
						if($this->$propName instanceof Integer)
							$bindingsTypes .= 'i';
						else
							$bindingsTypes .= 's';
						array_push($params, $this->get($propName));
					}
								
					if($propName == 'version_num') {
						$temp = $this->version_num->getValue();
						$this->version_num->setValue($temp+1);
						$bindingsTypes .= 'i';
						array_push($params, $this->version_num->getValue());
					}
				}
			}
				
			if($this->isTableOverloaded()) {
				if(isset($this->classname)) {
					$bindingsTypes .= 's';
					array_push($params, $this->classname);					
				}else{					
					$bindingsTypes .= 's';					
					array_push($params, get_class($this));					
				}
			}
			
			// the OID may be on the WHERE clause for UPDATEs and DELETEs
			if(!$this->isTransient()) {					
				$bindingsTypes .= 'i';
				array_push($params, $this->OID);
			}
		}
		
		self::$logger->debug('bindingsTypes=['.$bindingsTypes.']');
		self::$logger->debug('params ['.print_r($params, true).']');
					
	    if ($params != null) {		 
			$bind_names[] = $bindingsTypes;
		 
		    for ($i=0; $i<count($params); $i++) {
		    	$bind_name = 'bind'.$i;
		        $$bind_name = $params[$i];
		        $bind_names[] = &$$bind_name;
		    }		 

		    call_user_func_array(array($stmt,'bind_param'), $bind_names);
	   	}
	    
	   	self::$logger->debug('<<bindParams ['.print_r($stmt, true).']');
	    return $stmt;
	}
	
	/**
	 * Dynamically binds the result of the supplied prepared statement to a 2d array, where each element in the array is another array
	 * representing a database row.
	 * 
	 * @param mysqli_stmt $stmt
	 * @return array A 2D array containing the query result.
	 * @since 1.0
	 */
	protected function bindResult($stmt) {
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
     * Check to see if an attribute exists on the BO.
     * 
     * @param $attribute The attribute name.
     * @return boolean
     * @since 1.0
     */
    public function hasAttribute($attribute) {
    	return property_exists(get_class($this), $attribute);
    }
}

?>