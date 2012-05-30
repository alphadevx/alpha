<?php

/**
 * SQLite DAO provider (uses the SQLite3 native API in PHP).
 * 
 * @package alpha::model
 * @since 1.2
 * @author John Collins <dev@alphaframework.org>
 * @version $Id: AlphaDAOProviderMySQL.inc 1524 2012-05-10 12:58:19Z alphadev $
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
	 * @todo
	 */
	public function load($OID) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::load() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadByAttribute()
	 * @todo
	 */
	public function loadByAttribute($attribute, $value, $ignoreClassType=false, $loadAttributes=array()) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadByAttribute() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAll()
	 * @todo
	 */
	public function loadAll($start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadAll() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByAttribute()
	 * @todo
	 */
	public function loadAllByAttribute($attribute, $value, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false, $constructorArgs=array()) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadAllByAttribute() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByAttributes()
	 * @todo
	 */
	public function loadAllByAttributes($attributes=array(), $values=array(), $start=0, $limit=0, $orderBy='OID', $order='ASC', $ignoreClassType=false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadAllByAttributes() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllByDayUpdated()
	 * @todo
	 */
	public function loadAllByDayUpdated($date, $start=0, $limit=0, $orderBy="OID", $order="ASC", $ignoreClassType=false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadAllByDayUpdated() not implemented by this provider');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::loadAllFieldValuesByAttribute()
	 * @todo
	 */
	public function loadAllFieldValuesByAttribute($attribute, $value, $returnAttribute, $order='ASC', $ignoreClassType=false) {
               	throw new NotImplementedException('AlphaDAOProviderInterface::loadAllFieldValuesByAttribute() not implemented by this provider');
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/model/AlphaDAOProviderInterface::save()
	 * @todo
	 */
	public function save() {
               	throw new NotImplementedException('AlphaDAOProviderInterface::save() not implemented by this provider');
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
