<?php

namespace Alpha\Test\Model;

use Alpha\Exception\AlphaException;
use Alpha\Exception\CustomQueryException;
use Alpha\Exception\FailedDeleteException;
use Alpha\Exception\FailedIndexCreateException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\LockingException;
use Alpha\Exception\NotImplementedException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderSQLite;
use Alpha\Model\Type\Boolean;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\Double;
use Alpha\Model\Type\Enum;
use Alpha\Model\Type\HugeText;
use Alpha\Model\Type\Integer;
use Alpha\Model\Type\LargeText;
use Alpha\Model\Type\Relation;
use Alpha\Model\Type\RelationLookup;
use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Text;
use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\TypeInterface;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Service\ServiceFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SQLite3;

if (!class_exists('Alpha\Model\RecordFoundException', false)) {
    class_alias('Alpha\Exception\AlphaException', 'Alpha\Model\RecordFoundException');
}

/**
 * @since 4.1
 */
class TestTypeRecord implements TypeInterface
{
    public $tableName = 't_type';
    public $propObjects = array();
    public $ID = 1;
    public $name = 'x';
    public $version_num = 1;
    public function getTableName()
    {
        return $this->tableName;
    }
    public function getTransientAttributes()
    {
        return array();
    }
    public function getPersistentAttributes()
    {
        return array('ID', 'name');
    }
    public function isTableOverloaded()
    {
        return false;
    }
    public function getMaintainHistory()
    {
        return false;
    }
    public function setLastQuery($q)
    {
    }
    public function getLastQuery()
    {
        return 'q';
    }
    public function getPropObject($n)
    {
        return isset($this->propObjects[$n]) ? $this->propObjects[$n] : null;
    }
    public function get($n)
    {
        return 'x';
    }
    public function set($n, $v)
    {
    }
    public function getID()
    {
        return 1;
    }
    public function isTransient()
    {
        return false;
    }
    public function getVersionNumber()
    {
        return new class () {
            public function getValue()
            {
                return 1;
            }
        };
    }
    public function setValue(mixed $val): void
    {
    }
    public function getValue(): mixed
    {
        return 'x';
    }
    public function setHelper(string $helper): void
    {
    }
    public function getHelper(): string
    {
        return '';
    }
    public function __toString(): string
    {
        return 'x';
    }
}

/**
 * @since 4.1
 */
class TestRecord
{
    public $tableName = 't_real';
    public $propObjects = array();
    public $uniqueAttributes = array();
    public $indexes = array();
    public $transient = false;
    public $overloaded = false;
    public $maintainHistory = false;
    public $checkTableExists = true;
    public $checkNeedsUpdate = false;
    public $ID = 1;
    public $name = 'x';
    public $version_num = 1;
    public $updated_by = 0;
    public $updated_ts = '';
    public $created_by = 0;
    public $created_ts = '';
    public $classname = '';
    public $lastQuery = '';
    public $idSet = null;
    public $savedRelations = false;
    public $savedHistory = false;
    public $madeTable = false;
    public $madeHistoryTable = false;
    public $addedProperty = null;
    public $version = 1;
    public function getTableName()
    {
        return $this->tableName;
    }
    public function getTransientAttributes()
    {
        return array();
    }
    public function getPersistentAttributes()
    {
        return array('ID', 'name');
    }
    public function isTableOverloaded()
    {
        return $this->overloaded;
    }
    public function getMaintainHistory()
    {
        return $this->maintainHistory;
    }
    public function setLastQuery($q)
    {
        $this->lastQuery = $q;
    }
    public function getLastQuery()
    {
        return $this->lastQuery;
    }
    public function getIndexes()
    {
        return $this->indexes;
    }
    public function getUniqueAttributes()
    {
        return $this->uniqueAttributes;
    }
    public function getPropObject($n)
    {
        return isset($this->propObjects[$n]) ? $this->propObjects[$n] : null;
    }
    public function getDefaultAttributes()
    {
        return array();
    }
    public function set($n, $v)
    {
    }
    public function setID($id)
    {
        $this->idSet = $id;
    }
    public function saveRelations()
    {
        $this->savedRelations = true;
    }
    public function saveHistory()
    {
        $this->savedHistory = true;
    }
    public function makeTable()
    {
        $this->madeTable = true;
    }
    public function makeHistoryTable()
    {
        $this->madeHistoryTable = true;
    }
    public function checkTableExists()
    {
        return $this->checkTableExists;
    }
    public function checkTableNeedsUpdate()
    {
        return $this->checkNeedsUpdate;
    }
    public function findMissingFields()
    {
        return array();
    }
    public function addProperty($p)
    {
        $this->addedProperty = $p;
    }
    public function getID()
    {
        return $this->ID;
    }
    public function get($n)
    {
        return 'x';
    }
    public function isTransient()
    {
        return $this->transient;
    }
    public function getVersion()
    {
        return $this->version;
    }
    public function getVersionNumber()
    {
        return new class () {
            public function getValue()
            {
                return 1;
            }
        };
    }
    public function createUniqueIndex($a, $b = '', $c = '')
    {
    }
    public function createForeignIndex($a, $b, $c)
    {
    }
    public function query($q)
    {
        return array();
    }
    public function load($id, $v = 0)
    {
    }
}

/**
 * Test cases for ActiveRecordProviderSQLiteTest.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2026, John Collins (founder of Alpha Framework).
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
class ActiveRecordProviderSQLiteTest extends TestCase
{
    private $provider;
    private $record;
    private $connection;
    private $config;
    private $session;
    private $logger;
    private $tempFiles = array();

    /**
     * @since 4.1
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->createMock(ConfigProvider::class);
        $this->setConfigProvider($this->config);
        $this->connection = new SQLite3(':memory:');
        $this->connection->enableExceptions(false);
        $this->connection->exec('CREATE TABLE t_real (ID INTEGER PRIMARY KEY, name TEXT, version_num INTEGER DEFAULT 1, updated_by INTEGER, updated_ts TEXT, created_by INTEGER, created_ts TEXT, classname TEXT)');
        $this->connection->exec('CREATE TABLE t_real_history (ID INTEGER, version_num INTEGER, name TEXT, updated_by INTEGER, updated_ts TEXT, created_by INTEGER, created_ts TEXT, classname TEXT, PRIMARY KEY (ID, version_num))');
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (1, 'x', 1)");
        $this->connection->exec("INSERT INTO t_real_history (ID, version_num, name) VALUES (1, 1, 'x')");
        $this->provider = new ActiveRecordProviderSQLite();
        $this->record = new TestRecord();
        $this->record->propObjects = array(
            'ID' => $this->createMock(Integer::class),
            'name' => $this->createMock(Text::class),
            'version_num' => $this->createMock(Integer::class),
            'updated_by' => $this->createMock(Integer::class),
            'updated_ts' => $this->createMock(Timestamp::class),
            'created_by' => $this->createMock(Integer::class),
            'created_ts' => $this->createMock(Timestamp::class),
            'classname' => $this->createMock(Text::class),
            'extra' => $this->createMock(Text::class),
        );
        $this->record->propObjects['ID']->method('getSize')->willReturn(11);
        $this->record->propObjects['version_num']->method('getSize')->willReturn(11);
        $this->record->propObjects['updated_by']->method('getSize')->willReturn(11);
        $this->record->propObjects['created_by']->method('getSize')->willReturn(11);
        $this->session = $this->createMock('Alpha\Util\Http\Session\SessionProviderInterface');
        $this->logger = $this->createMock(Logger::class);
        $this->provider->setRecord($this->record);
        $this->setStaticConnection($this->connection);
        $this->setStaticLogger($this->logger);
        $this->setServiceFactoryInstance('mocksession', $this->session);
    }

    /**
     * @since 4.1
     */
    protected function tearDown(): void
    {
        $this->setStaticConnection(null);
        $this->setStaticLogger(null);
        $this->setConfigProvider(null);
        $this->clearServiceFactoryInstances();
        $this->clearForeignKeys();
        foreach ($this->tempFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
        parent::tearDown();
    }

    /**
     * @since 4.1
     */
    private function setStaticConnection($c): void
    {
        $p = new ReflectionProperty(ActiveRecordProviderSQLite::class, 'connection');
        $p->setAccessible(true);
        $p->setValue(null, $c);
    }

    /**
     * @since 4.1
     */
    private function getStaticConnection()
    {
        $p = new ReflectionProperty(ActiveRecordProviderSQLite::class, 'connection');
        $p->setAccessible(true);
        return $p->getValue();
    }

    /**
     * @since 4.1
     */
    private function setStaticLogger($l): void
    {
        $p = new ReflectionProperty(ActiveRecordProviderSQLite::class, 'logger');
        $p->setAccessible(true);
        $p->setValue(null, $l);
    }

    /**
     * @since 4.1
     */
    private function setConfigProvider($c): void
    {
        try {
            $p = new ReflectionProperty(ConfigProvider::class, 'instance');
            $p->setAccessible(true);
            $p->setValue(null, $c);
        } catch (\ReflectionException $e) {
        }
    }

    /**
     * @since 4.1
     */
    private function setServiceFactoryInstance($k, $i): void
    {
        try {
            $p = new ReflectionProperty(ServiceFactory::class, 'instances');
            $p->setAccessible(true);
            $v = $p->getValue();
            if (!is_array($v)) {
                $v = array();
            }
            $v[$k] = $i;
            $p->setValue(null, $v);
        } catch (\ReflectionException $e) {
        }
    }

    /**
     * @since 4.1
     */
    private function clearServiceFactoryInstances(): void
    {
        try {
            $p = new ReflectionProperty(ServiceFactory::class, 'instances');
            $p->setAccessible(true);
            $p->setValue(null, array());
        } catch (\ReflectionException $e) {
        }
    }

    /**
     * @since 4.1
     */
    private function clearForeignKeys(): void
    {
        $p = new ReflectionProperty(ActiveRecordProviderSQLite::class, 'foreignKeys');
        $p->setAccessible(true);
        $p->setValue($this->provider, array());
    }

    /**
     * @since 4.1
     */
    private function setForeignKeys(array $fk): void
    {
        $p = new ReflectionProperty(ActiveRecordProviderSQLite::class, 'foreignKeys');
        $p->setAccessible(true);
        $p->setValue($this->provider, $fk);
    }

    /**
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $p = new ActiveRecordProviderSQLite();
        $this->assertInstanceOf(ActiveRecordProviderSQLite::class, $p);
    }

    /**
     * @since 4.1
     */
    public function testGetConnectionExisting(): void
    {
        $this->assertSame($this->connection, ActiveRecordProviderSQLite::getConnection());
    }

    /**
     * @since 4.1
     */
    public function testGetConnectionNew(): void
    {
        $this->setStaticConnection(null);
        $this->config->method('get')->willReturn(':memory:');
        $c = ActiveRecordProviderSQLite::getConnection();
        $this->assertInstanceOf(SQLite3::class, $c);
        $this->setStaticConnection($this->connection);
    }

    /**
     * @since 4.1
     */
    public function testGetConnectionFailure(): void
    {
        $this->setStaticConnection(null);
        $this->config->method('get')->willReturn('/no/such/path/xyz.sqlite');
        // getConnection() logs a fatal and returns null on failure, violating its SQLite3 return type
        $this->expectException(\TypeError::class);
        ActiveRecordProviderSQLite::getConnection();
    }

    /**
     * @since 4.1
     */
    public function testDisconnect(): void
    {
        ActiveRecordProviderSQLite::disconnect();
        $this->assertNull($this->getStaticConnection());
        $this->setStaticConnection($this->connection);
    }

    /**
     * @since 4.1
     */
    public function testDisconnectNull(): void
    {
        $this->setStaticConnection(null);
        ActiveRecordProviderSQLite::disconnect();
        $this->assertNull($this->getStaticConnection());
        $this->setStaticConnection($this->connection);
    }

    /**
     * @since 4.1
     */
    public function testGetLastDatabaseError(): void
    {
        $this->assertIsString(ActiveRecordProviderSQLite::getLastDatabaseError());
    }

    /**
     * @since 4.1
     */
    public function testQuerySuccess(): void
    {
        $out = $this->provider->query('SELECT ID FROM t_real');
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testQueryFailure(): void
    {
        $this->expectException(CustomQueryException::class);
        $this->provider->query('BAD SQL HERE');
    }

    /**
     * @since 4.1
     */
    public function testLoadSuccess(): void
    {
        $this->provider->load(1);
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testLoadWithVersion(): void
    {
        $this->provider->load(1, 1);
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testLoadNotFound(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->provider->load(999);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllOldVersionsNoHistory(): void
    {
        $this->record->maintainHistory = false;
        $this->expectException(AlphaException::class);
        $this->provider->loadAllOldVersions(1);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllOldVersionsEmpty(): void
    {
        $this->record->maintainHistory = true;
        $out = $this->provider->loadAllOldVersions(999);
        $this->assertEquals(array(), $out);
    }

    /**
     * @since 4.1
     */
    public function testLoadByAttributeSuccess(): void
    {
        $this->provider->loadByAttribute('name', 'x');
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testLoadByAttributeNotFound(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->provider->loadByAttribute('name', 'zzz');
    }

    /**
     * @since 4.1
     */
    public function testLoadAllSuccess(): void
    {
        $out = $this->provider->loadAll();
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributeSuccess(): void
    {
        $out = $this->provider->loadAllByAttribute('name', 'x');
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributeEmpty(): void
    {
        $out = $this->provider->loadAllByAttribute('name', 'zzz');
        $this->assertIsArray($out);
        $this->assertEquals(array(), $out);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributesSuccess(): void
    {
        $out = $this->provider->loadAllByAttributes(array('name'), array('x'));
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByDayUpdated(): void
    {
        $out = $this->provider->loadAllByDayUpdated('2026-01-01');
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllFieldValuesByAttribute(): void
    {
        $out = $this->provider->loadAllFieldValuesByAttribute('name', 'x', 'name');
        $this->assertIsArray($out);
    }

    /**
     * @since 4.1
     */
    public function testGetVersion(): void
    {
        $this->assertEquals(1, $this->provider->getVersion());
    }

    /**
     * @since 4.1
     */
    public function testGetCount(): void
    {
        $this->assertEquals(1, $this->provider->getCount());
    }

    /**
     * @since 4.1
     */
    public function testGetHistoryCount(): void
    {
        $this->record->maintainHistory = true;
        $this->assertEquals(1, $this->provider->getHistoryCount());
    }

    /**
     * @since 4.1
     */
    public function testGetMAX(): void
    {
        $record = new class () extends TestRecord {
            public function query($q)
            {
                return array(array('max_ID' => 1));
            }
        };
        $this->provider->setRecord($record);
        $this->assertIsInt($this->provider->getMAX());
        $this->provider->setRecord($this->record);
    }

    /**
     * @since 4.1
     */
    public function testCheckRecordExistsTrue(): void
    {
        $this->assertTrue($this->provider->checkRecordExists(1));
    }

    /**
     * @since 4.1
     */
    public function testCheckRecordExistsFalse(): void
    {
        $this->assertFalse($this->provider->checkRecordExists(999));
    }

    /**
     * @since 4.1
     */
    public function testCheckTableExists(): void
    {
        $this->assertTrue($this->provider->checkTableExists());
        $this->assertTrue($this->provider->checkTableExists(true));
    }

    /**
     * @since 4.1
     */
    public function testSetEnumOptionsThrows(): void
    {
        $this->expectException(NotImplementedException::class);
        $this->provider->setEnumOptions();
    }

    /**
     * @since 4.1
     */
    public function testBeginCommitRollback(): void
    {
        ActiveRecordProviderSQLite::begin();
        ActiveRecordProviderSQLite::commit();
        $this->assertTrue(true);
        ActiveRecordProviderSQLite::begin();
        ActiveRecordProviderSQLite::rollback();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testIsTableOverloaded(): void
    {
        $provider = new ActiveRecordProviderSQLite();
        $provider->setRecord(new TestTypeRecord());
        $this->assertFalse($provider->isTableOverloaded());
    }

    /**
     * @since 4.1
     */
    public function testReload(): void
    {
        $this->provider->reload();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testFindMissingFields(): void
    {
        $out = $this->provider->findMissingFields();
        $this->assertIsArray($out);
        $this->assertFalse(in_array('name', $out, true));
    }

    /**
     * @since 4.1
     */
    public function testDropTable(): void
    {
        $this->provider->dropTable();
        $this->assertFalse($this->provider->checkTableExists());
    }
}
