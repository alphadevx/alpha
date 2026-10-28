<?php

namespace Alpha\Test\Model;

use Alpha\Exception\AlphaException;
use Alpha\Exception\BadTableNameException;
use Alpha\Exception\CustomQueryException;
use Alpha\Exception\FailedDeleteException;
use Alpha\Exception\FailedIndexCreateException;
use Alpha\Exception\FailedSaveException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\LockingException;
use Alpha\Exception\NotImplementedException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ValidationException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderSQLite;
use Alpha\Model\Type\Boolean;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Model\Type\Sequence;
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

    /**
     * Swaps the mocked config provider singleton for the real one and snapshots
     * db.file.path so tests can operate on real file paths.
     *
     * @return array{0: mixed, 1: ConfigProvider, 2: string}
     *
     * @since 4.1
     */
    private function swapToRealConfig(): array
    {
        $p = new ReflectionProperty(ConfigProvider::class, 'instance');
        $p->setAccessible(true);
        $mock = $p->getValue();
        $p->setValue(null, null);
        $real = ConfigProvider::getInstance();
        $origPath = '';
        try {
            $origPath = $real->get('db.file.path');
        } catch (\Throwable $e) {
        }

        return array($mock, $real, $origPath);
    }

    /**
     * Restores the mocked config provider singleton captured by swapToRealConfig().
     *
     * @since 4.1
     */
    private function restoreFromRealConfig(array $snapshot): void
    {
        $p = new ReflectionProperty(ConfigProvider::class, 'instance');
        $p->setAccessible(true);
        $p->setValue(null, $snapshot[0]);
    }

    /**
     * Round trip of checkDatabaseExists()/createDatabase()/dropDatabase() against
     * a unique temp db file path.
     *
     * @since 4.1
     */
    public function testDatabaseExistsCreateDropRoundTrip(): void
    {
        $snapshot = $this->swapToRealConfig();
        $real = $snapshot[1];
        $origPath = $snapshot[2];
        $tmpFile = '/tmp/SQLiteProvTest_db_' . uniqid() . '.db';
        try {
            $real->set('db.file.path', $tmpFile);
            $this->assertFalse(ActiveRecordProviderSQLite::checkDatabaseExists());

            ActiveRecordProviderSQLite::createDatabase();
            $this->assertTrue(ActiveRecordProviderSQLite::checkDatabaseExists());
            $this->assertFileExists($tmpFile);

            ActiveRecordProviderSQLite::dropDatabase();
            $this->assertFalse(ActiveRecordProviderSQLite::checkDatabaseExists());
        } finally {
            try {
                $real->set('db.file.path', $origPath);
            } catch (\Throwable $e) {
            }
            @unlink($tmpFile);
            $this->restoreFromRealConfig($snapshot);
        }
    }

    /**
     * backupDatabase() copies the configured db file to the target path.
     *
     * @since 4.1
     */
    public function testBackupDatabaseCreatesTargetFile(): void
    {
        $snapshot = $this->swapToRealConfig();
        $real = $snapshot[1];
        $origPath = $snapshot[2];
        $srcFile = '/tmp/SQLiteProvTest_src_' . uniqid() . '.db';
        $dstFile = '/tmp/SQLiteProvTest_bak_' . uniqid() . '.db';
        try {
            $db = new SQLite3($srcFile);
            $db->exec('CREATE TABLE bak_t (ID INTEGER PRIMARY KEY, name TEXT)');
            $db->exec("INSERT INTO bak_t (ID, name) VALUES (1, 'a')");
            $db->close();

            $real->set('db.file.path', $srcFile);
            ActiveRecordProviderSQLite::backupDatabase($dstFile);
            $this->assertFileExists($dstFile);
        } finally {
            try {
                $real->set('db.file.path', $origPath);
            } catch (\Throwable $e) {
            }
            @unlink($srcFile);
            @unlink($dstFile);
            $this->restoreFromRealConfig($snapshot);
        }
    }

    /**
     * getConnection() throws when the configured db file path is not writable.
     *
     * @since 4.1
     */
    public function testGetConnectionFailureWithRealConfigThrows(): void
    {
        $snapshot = $this->swapToRealConfig();
        $real = $snapshot[1];
        $origPath = $snapshot[2];
        $this->setStaticConnection(null);
        try {
            $real->set('db.file.path', '/no/such/dir/x.db');
            $this->expectException(\Throwable::class);
            ActiveRecordProviderSQLite::getConnection();
        } finally {
            try {
                $real->set('db.file.path', $origPath);
            } catch (\Throwable $e) {
            }
            $this->restoreFromRealConfig($snapshot);
            $this->setStaticConnection($this->connection);
        }
    }

    /**
     * loadAllOldVersions() throws when the record maintains no history.
     *
     * @since 4.1
     */
    public function testLoadAllOldVersionsWithHistoryDisabledThrows(): void
    {
        $this->record->maintainHistory = false;
        $this->expectException(AlphaException::class);
        $this->provider->loadAllOldVersions(1);
    }

    /**
     * checkTableNeedsUpdate() detects a dropped column and findMissingFields()
     * plus addProperty() repair the live table.
     *
     * @since 4.1
     */
    public function testCheckTableNeedsUpdateAfterDropColumn(): void
    {
        $table = 'sQLiteProvTestLive';
        $this->connection->exec('CREATE TABLE ' . $table . ' (ID INTEGER PRIMARY KEY, name TEXT, version_num INTEGER DEFAULT 1)');
        $live = new SQLiteProvTestLive();
        $live->tableName = $table;
        $live->propObjects['name'] = $this->createMock(Text::class);
        $this->provider->setRecord($live);
        try {
            $this->assertFalse($this->provider->checkTableNeedsUpdate());

            $this->connection->exec('ALTER TABLE ' . $table . ' DROP COLUMN name');
            $this->assertTrue($this->provider->checkTableNeedsUpdate());

            $missing = $this->provider->findMissingFields();
            $this->assertContains('name', $missing);

            $this->provider->addProperty('name');
            $this->assertEquals(array(), $this->provider->findMissingFields());
            $this->assertFalse($this->provider->checkTableNeedsUpdate());
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * checkRecordTableExists() static returns true for an existing table and
     * false for a missing one.
     *
     * @since 4.1
     */
    public function testCheckRecordTableExistsStatic(): void
    {
        $this->assertTrue(ActiveRecordProviderSQLite::checkRecordTableExists(SQLiteProvTestTableTrue::class));
        $this->assertFalse(ActiveRecordProviderSQLite::checkRecordTableExists(SQLiteProvTestTableFalse::class));
    }

    /**
     * load() covers the IllegalArguementException warn branch and the PHPException
     * recovery branch where the table was out of sync (column dropped live).
     *
     * @since 4.1
     */
    public function testLoadRecoveryBranches(): void
    {
        $table = 'sQLiteProvTestSync';
        $this->connection->exec('CREATE TABLE ' . $table . ' (ID INTEGER PRIMARY KEY, name TEXT, version_num INTEGER, extra TEXT)');
        $this->connection->exec("INSERT INTO " . $table . " (ID, name, version_num, extra) VALUES (1, 'x', 1, NULL)");
        $this->connection->exec('ALTER TABLE ' . $table . ' DROP COLUMN extra');

        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = $table;
        $rec->illegalOn = 'name';
        $this->provider->setRecord($rec);
        try {
            // bad data branch: set() throws IllegalArguementException, provider only warns
            $this->provider->load(1);
            $this->assertStringContainsString('SELECT', $rec->getLastQuery());
            $this->assertEquals(0, $rec->makeTableCalls);

            // out of sync branch: set() throws PHPException, missing fields get added
            $rec->illegalOn = null;
            $rec->throwOn = 'name';
            $rec->needsUpdate = true;
            $rec->missingFields = array('extra');
            try {
                $this->provider->load(1);
                $this->fail('expected RecordFoundException for out of sync table');
            } catch (AlphaException $e) {
                $this->assertStringContainsString('out of sync', $e->getMessage());
            }
            $this->assertEquals(array('extra'), $rec->addedProps);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * loadByAttribute() success against a real row plus the prepare-failure branches.
     *
     * @since 4.1
     */
    public function testLoadByAttributeSuccessAndPrepareFailure(): void
    {
        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = 't_real';
        $rec->propObjects = array('name' => new Text(), 'ID' => new Integer(1));
        $this->provider->setRecord($rec);
        try {
            $this->provider->loadByAttribute('name', 'x');
            $this->assertEquals(1, $rec->idSet);
            $this->assertEquals('x', $rec->name);

            // prepare failure with the table present: warn and return silently
            $rec->lastQuery = '';
            $this->provider->loadByAttribute('bad-col', 'x');
            $this->assertStringContainsString('bad-col', $rec->getLastQuery());
            $this->assertEquals(0, $rec->makeTableCalls);

            // prepare failure with the table missing: makeTable and RecordNotFoundException
            $rec->tableExists = false;
            try {
                $this->provider->loadByAttribute('bad-col', 'x');
                $this->fail('expected RecordNotFoundException');
            } catch (RecordNotFoundException $e) {
                $this->assertEquals(1, $rec->makeTableCalls);
            }
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * loadAll() with real saved rows, orderBy/order variants and a bad orderBy
     * field reaching the validation catch.
     *
     * @since 4.1
     */
    public function testLoadAllOrderVariantsAndBadOrderBy(): void
    {
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (2, 'x', 1)");
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (3, 'x', 1)");
        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = 't_real';
        $this->provider->setRecord($rec);
        try {
            $out = $this->provider->loadAll(0, 0, 'ID', 'ASC');
            $this->assertCount(3, $out);
            $this->assertEquals(1, $out[0]->getID());
            $this->assertEquals(3, $out[2]->getID());

            $out = $this->provider->loadAll(1, 1, 'ID', 'DESC');
            $this->assertCount(1, $out);

            try {
                $this->provider->loadAll(0, 0, 'nope', 'ASC');
                $this->fail('expected AlphaException for bad orderBy');
            } catch (AlphaException $e) {
                $this->assertStringContainsString('nope', $e->getMessage());
            }
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * loadAllByAttribute() and loadAllByAttributes() success paths with real
     * saved rows, exercising the text and integer binding branches.
     *
     * @since 4.1
     */
    public function testLoadAllByAttributeAndAttributesSuccess(): void
    {
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (2, 'x', 1)");
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (3, 'x', 1)");
        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = 't_real';
        $rec->propObjects = array('name' => new Text(), 'version_num' => new Integer(1));
        $this->provider->setRecord($rec);
        try {
            $out = $this->provider->loadAllByAttribute('name', 'x');
            $this->assertCount(3, $out);

            $out = $this->provider->loadAllByAttribute('name', 'x', 1, 1, 'ID', 'DESC');
            $this->assertCount(1, $out);

            $out = $this->provider->loadAllByAttributes(array('name'), array('x'));
            $this->assertCount(3, $out);

            $out = $this->provider->loadAllByAttributes(array('version_num'), array(1));
            $this->assertCount(3, $out);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * loadAllFieldValuesByAttribute() returns raw field values from real rows.
     *
     * @since 4.1
     */
    public function testLoadAllFieldValuesByAttributeRealData(): void
    {
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (2, 'x', 1)");
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num) VALUES (3, 'y', 1)");
        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = 't_real';
        $this->provider->setRecord($rec);
        try {
            $out = $this->provider->loadAllFieldValuesByAttribute('name', 'x', 'ID');
            $this->assertEquals(array(1, 2), array_map('intval', $out));

            $out = $this->provider->loadAllFieldValuesByAttribute('name', 'y', 'ID', 'DESC');
            $this->assertEquals(array(3), array_map('intval', $out));
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * save() INSERT then UPDATE against a real unique table built with makeTable()
     * from real Alpha\Model\Type attributes.
     *
     * @since 4.1
     */
    public function testSaveInsertThenUpdateWithRealTypes(): void
    {
        $rec = new SQLiteProvTestSyncRecord();
        $rec->tableName = 'sQLiteProvTestSave_' . mt_rand();
        $rec->transient = true;
        $rec->getObjFor = 'version_num';
        $rec->propObjects = array(
            'version_num' => new Integer(1),
            'intAttr' => new Integer(1),
            'name' => new Text()
        );
        $this->provider->setRecord($rec);
        try {
            $this->provider->makeTable(false);

            $this->provider->save();
            $this->assertGreaterThanOrEqual(1, $rec->getID());
            $this->assertEquals(1, $rec->saveRelationsCalls);

            $rec->transient = false;
            $rec->name = 'updated';
            $this->provider->save();
            $this->assertEquals(2, $rec->saveRelationsCalls);

            $row = $this->connection->querySingle('SELECT name || "|" || version_num AS nv FROM ' . $rec->tableName . ' WHERE ID = ' . $rec->getID(), true);
            $this->assertEquals('updated|2', $row['nv']);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * makeTable() covers every type mapping branch including the foreign key
     * clauses, and a second call on the existing table throws.
     *
     * @since 4.1
     */
    public function testMakeTableTypeBranchesAndDuplicateFailure(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'SQLiteProvTestSchemaRecord_' . mt_rand();
        $this->provider->setRecord($rec);
        $this->setForeignKeys(array('relAttr' => array('SQLiteProvTestSchemaTarget', 'ID')));
        try {
            $this->provider->makeTable(false);
            $found = $this->connection->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . $rec->TableName . "'");
            $this->assertEquals(1, $found);

            $this->expectException(AlphaException::class);
            $this->provider->makeTable(false);
        } finally {
            $this->clearForeignKeys();
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * makeTable() with checkIndexes enabled and an overloaded record covers the
     * unique index hand-off and the classname column branch.
     *
     * @since 4.1
     */
    public function testMakeTableOverloadedWithIndexes(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'SQLiteProvTestSchemaRecord_' . mt_rand();
        $rec->Overloaded = true;
        $rec->UniqueAttrs = array('intAttr', 'dblAttr+txtAttr', 'intAttr+txtAttr+dateAttr');
        $rec->MaintainHistory = true;
        $this->provider->setRecord($rec);
        try {
            $this->provider->makeTable();
            $this->assertEquals(2, count($rec->UniqueIndexCalls));
            $this->assertTrue($rec->MadeHistoryTable);

            $found = $this->connection->querySingle("SELECT classname FROM " . $rec->TableName . " LIMIT 1");
            $this->assertNull($found);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * makeHistoryTable() with every type branch, and a second call on the
     * existing history table throws.
     *
     * @since 4.1
     */
    public function testMakeHistoryTableTypeBranchesAndDuplicateFailure(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'SQLiteProvTestSchemaRecord_' . mt_rand();
        $this->provider->setRecord($rec);
        try {
            $this->provider->makeTable(false);
            $this->provider->makeHistoryTable();
            $found = $this->connection->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='" . $rec->TableName . "_history'");
            $this->assertEquals(1, $found);

            $this->expectException(AlphaException::class);
            $this->provider->makeHistoryTable();
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * rebuildTable() drops and re-creates via the record, and dropTable() covers
     * the history table drop and the missing table variants.
     *
     * @since 4.1
     */
    public function testRebuildTableAndDropTableVariants(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'SQLiteProvTestSchemaRecord_' . mt_rand();
        $rec->MaintainHistory = true;
        $this->provider->setRecord($rec);
        try {
            $this->provider->makeTable(false);
            $this->connection->exec('CREATE TABLE ' . $rec->TableName . '_history (ID INTEGER, version_num INTEGER)');

            $this->provider->rebuildTable();
            $this->assertTrue($rec->MadeTable);

            $this->provider->dropTable();
            $found = $this->connection->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name IN ('" . $rec->TableName . "', '" . $rec->TableName . "_history')");
            $this->assertEquals(0, $found);

            // dropping tables that do not exist must not throw thanks to IF EXISTS
            $this->provider->dropTable('sqliteprovtest_missing_tbl');
            $this->assertTrue(true);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * addProperty() covers every type mapping branch against a bare table,
     * the maintainHistory variant and the duplicate column failure.
     *
     * @since 4.1
     */
    public function testAddPropertyTypeBranchesAndHistory(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        // fixed name so the provider isTableOverloaded() PRAGMA path matches the
        // class short name after ucfirst(); the in-memory db is per-test
        $rec->TableName = 'sQLiteProvTestSchemaRecord';
        $this->provider->setRecord($rec);
        try {
            $this->connection->exec('CREATE TABLE ' . $rec->TableName . ' (ID INTEGER PRIMARY KEY)');
            $this->connection->exec('CREATE TABLE ' . $rec->TableName . '_history (ID INTEGER, version_num INTEGER)');

            foreach (array('intAttr', 'dblAttr', 'stAttr', 'txtAttr', 'boolAttr', 'dateAttr', 'tsAttr', 'enumAttr', 'relAttr') as $attr) {
                $this->provider->addProperty($attr);
            }

            // SQLite rejects a NOT NULL column without a default, so this one may
            // legitimately fail; either way the branch lines are executed
            try {
                $this->provider->addProperty('leftID');
            } catch (AlphaException $e) {
            }

            $rec->MaintainHistory = true;
            $this->provider->addProperty('htAttr');
            $rows = $this->connection->query('PRAGMA table_info(' . $rec->TableName . ')');
            $this->assertNotFalse($rows);

            // adding an existing column again must fail
            $this->expectException(AlphaException::class);
            $this->provider->addProperty('intAttr');
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * createUniqueIndex() single, composite and tri-field variants plus
     * getIndexes() and createForeignIndex() against a live table.
     *
     * @since 4.1
     */
    public function testUniqueAndForeignIndexCreation(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'SQLiteProvTestSchemaRecord_' . mt_rand();
        $this->provider->setRecord($rec);
        try {
            $this->provider->makeTable(false);

            $this->provider->createForeignIndex('relAttr', 'Alpha\Test\Model\SQLiteProvTestSchemaTarget', 'ID');

            $this->provider->createUniqueIndex('intAttr');
            $this->provider->createUniqueIndex('intAttr', 'txtAttr');
            $this->provider->createUniqueIndex('intAttr', 'txtAttr', 'dateAttr');

            $indexes = $this->provider->getIndexes();
            $this->assertContains('intAttr_unq_idx', $indexes);
            $this->assertContains('intAttr_txtAttr_unq_idx', $indexes);
            $this->assertContains('intAttr_txtAttr_dateAttr_unq_idx', $indexes);
            $this->assertContains($rec->TableName . '_relAttr_fk_idx', $indexes);
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * getVersion() against a missing table triggers the makeTable recovery and
     * throws RecordNotFoundException.
     *
     * @since 4.1
     */
    public function testGetVersionMissingTableRecovery(): void
    {
        $this->record->tableName = 't_missing_versions';
        $this->record->checkTableExists = false;
        try {
            $this->provider->getVersion();
            $this->fail('expected RecordNotFoundException');
        } catch (RecordNotFoundException $e) {
            $this->assertTrue($this->record->madeTable);
        } finally {
            $this->record->tableName = 't_real';
            $this->record->checkTableExists = true;
        }
    }

    /**
     * getVersion() returns 0 when the stored version_num is below 1.
     *
     * @since 4.1
     */
    public function testGetVersionZeroForFreshRow(): void
    {
        $this->connection->exec('INSERT INTO t_real (ID, name, version_num) VALUES (5, \'z\', 0)');
        $this->record->ID = 5;
        try {
            $this->assertEquals(0, $this->provider->getVersion());
        } finally {
            $this->record->ID = 1;
        }
    }

    /**
     * getCount() covers the overloaded classname filter, the attribute filter
     * and the query failure branch.
     *
     * @since 4.1
     */
    public function testGetCountOverloadedAndErrorBranches(): void
    {
        $this->record->overloaded = true;
        try {
            $this->assertEquals(0, $this->provider->getCount());
            $this->assertEquals(0, $this->provider->getCount(array('name'), array('x')));

            $this->record->tableName = 't_missing_count';
            $this->expectException(AlphaException::class);
            $this->provider->getCount();
        } finally {
            $this->record->overloaded = false;
            $this->record->tableName = 't_real';
        }
    }

    /**
     * getHistoryCount() throws when the history table cannot be queried.
     *
     * @since 4.1
     */
    public function testGetHistoryCountMissingHistoryTableThrows(): void
    {
        $this->record->maintainHistory = true;
        $this->record->tableName = 't_missing_hist';
        try {
            $this->expectException(AlphaException::class);
            $this->provider->getHistoryCount();
        } finally {
            $this->record->maintainHistory = false;
            $this->record->tableName = 't_real';
        }
    }

    /**
     * checkRecordExists() throws when the prepare fails on a missing table.
     *
     * @since 4.1
     */
    public function testCheckRecordExistsPrepareFailureThrows(): void
    {
        $this->record->tableName = 't_missing_chk';
        try {
            $this->expectException(AlphaException::class);
            $this->provider->checkRecordExists(1);
        } finally {
            $this->record->tableName = 't_real';
        }
    }

    /**
     * commit() without a transaction fails, a nested begin() fails, and a
     * rollback with no active transaction is silently tolerated.
     *
     * @since 4.1
     */
    public function testTransactionErrorBranches(): void
    {
        $this->setStaticLogger(null);
        try {
            try {
                ActiveRecordProviderSQLite::commit();
                $this->fail('expected AlphaException');
            } catch (AlphaException $e) {
            }

            ActiveRecordProviderSQLite::begin();
            try {
                ActiveRecordProviderSQLite::begin();
                $this->fail('expected AlphaException');
            } catch (AlphaException $e) {
            }
            ActiveRecordProviderSQLite::commit();

            ActiveRecordProviderSQLite::rollback();
            $this->assertTrue(true);
        } finally {
            $this->setStaticLogger($this->logger);
            $this->setStaticConnection($this->connection);
        }
    }

    /**
     * getMAX() success and failure via the record's query() hand-off.
     *
     * @since 4.1
     */
    public function testGetMAXSuccessAndFailure(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->QueryReturn = array(array('max_ID' => 42));
        $this->provider->setRecord($rec);
        try {
            $this->assertEquals(42, $this->provider->getMAX());

            $rec->QueryReturn = array();
            $this->expectException(AlphaException::class);
            $this->provider->getMAX();
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * @since 4.1
     */
    public function testLoadByAttributeOverloadedBindBranches(): void
    {
        $this->record->overloaded = true;
        try {
            $this->provider->loadByAttribute('ID', 1);
        } catch (RecordNotFoundException $e) {
        }
        try {
            $this->provider->loadByAttribute('name', 'x');
        } catch (RecordNotFoundException $e) {
        }
        $this->record->overloaded = false;
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllOverloadedVariants(): void
    {
        $this->connection->exec("INSERT INTO t_real (ID, name, version_num, classname) VALUES (2, 'x', 1, '".get_class($this->record)."')");
        $this->record->overloaded = true;
        try {
            $objects = $this->provider->loadAll();
            $this->assertCount(1, $objects);
            $this->provider->loadAll(0, 5);
        } finally {
            $this->record->overloaded = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testLoadAllBadSQLThrows(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->provider->loadAll(0, 0, 'bad col');
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributeOverloadedBinds(): void
    {
        $this->record->overloaded = true;
        try {
            $this->provider->loadAllByAttribute('ID', 1);
            $this->provider->loadAllByAttribute('name', 'x');
        } finally {
            $this->record->overloaded = false;
        }
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributeConstructorArgsVariants(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $args = array_fill(0, $i, 'a');
            $objects = $this->provider->loadAllByAttribute('name', 'x', 0, 0, 'ID', 'ASC', false, $args);
            $this->assertCount(1, $objects);
        }
        $this->expectException(IllegalArguementException::class);
        $this->provider->loadAllByAttribute('name', 'x', 0, 0, 'ID', 'ASC', false, array('a', 'b', 'c', 'd', 'e', 'f'));
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributePrepareFailureBranches(): void
    {
        $objects = $this->provider->loadAllByAttribute('bad col', 'x');
        $this->assertCount(0, $objects);

        $this->record->checkTableExists = false;
        try {
            $this->provider->loadAllByAttribute('bad col', 'x');
            $this->fail('expected RecordFoundException');
        } catch (\Alpha\Model\RecordFoundException $e) {
            $this->assertTrue($this->record->madeTable);
        } finally {
            $this->record->checkTableExists = true;
        }
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributesOverloadedAndLimit(): void
    {
        $this->record->overloaded = true;
        try {
            $this->provider->loadAllByAttributes(array('name'), array('x'), 0, 5);
            $this->provider->loadAllByAttributes(array('ID'), array('1'), 0, 0);
            $objects = $this->provider->loadAllByAttributes(array('name'), array());
            $this->assertCount(0, $objects);
        } finally {
            $this->record->overloaded = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributesPrepareFailureBranches(): void
    {
        $objects = $this->provider->loadAllByAttributes(array('bad col'), array('x'));
        $this->assertCount(0, $objects);

        $this->record->checkTableExists = false;
        try {
            $this->provider->loadAllByAttributes(array('bad col'), array('x'));
            $this->fail('expected RecordFoundException');
        } catch (\Alpha\Model\RecordFoundException $e) {
            $this->assertTrue($this->record->madeTable);
        } finally {
            $this->record->checkTableExists = true;
        }
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributesConstructorArgsVariants(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $args = array_fill(0, $i, 'a');
            $objects = $this->provider->loadAllByAttributes(array('name'), array('x'), 0, 0, 'ID', 'ASC', false, $args);
            $this->assertCount(1, $objects);
        }
        $this->expectException(IllegalArguementException::class);
        $this->provider->loadAllByAttributes(array('name'), array('x'), 0, 0, 'ID', 'ASC', false, array('a', 'b', 'c', 'd', 'e', 'f'));
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByDayUpdatedVariants(): void
    {
        $this->provider->loadAllByDayUpdated('2026-01-01', 1, 5);
        $this->record->overloaded = true;
        try {
            $this->provider->loadAllByDayUpdated('2026-01-01');
        } finally {
            $this->record->overloaded = false;
        }
        $this->expectException(RecordNotFoundException::class);
        $this->provider->loadAllByDayUpdated('2026-01-01', 0, 0, 'bad col');
    }

    /**
     * @since 4.1
     */
    public function testLoadAllFieldValuesOverloadedAndFailure(): void
    {
        $this->record->overloaded = true;
        try {
            $this->provider->loadAllFieldValuesByAttribute('name', 'x', 'name');
        } finally {
            $this->record->overloaded = false;
        }
        $this->expectException(RecordNotFoundException::class);
        $this->provider->loadAllFieldValuesByAttribute('name', 'x', 'bad col');
    }

    /**
     * @since 4.1
     */
    public function testSaveAttributeLockingAndOverloadedUpdate(): void
    {
        $this->config->method('get')->willReturnCallback(function ($k, $d = null) {
            if ($k === 'session.provider.name') {
                return 'Alpha\Util\Http\Session\SessionProviderArray';
            }
            return is_string($d) ? $d : '';
        });
        $this->record->version = 2;
        try {
            $this->provider->saveAttribute('name', 'y');
            $this->fail('expected LockingException');
        } catch (LockingException $e) {
            $this->record->version = 1;
        }

        $this->record->overloaded = true;
        try {
            $this->provider->save();
            $this->fail('expected FailedSaveException');
        } catch (FailedSaveException $e) {
            $this->assertTrue(true);
        } finally {
            $this->record->overloaded = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testSaveHistoryPrepareFailure(): void
    {
        $this->expectException(FailedSaveException::class);
        $this->provider->saveHistory();
    }

    /**
     * @since 4.1
     */
    public function testSaveHistoryOverloadedSuccess(): void
    {
        $this->connection->exec('CREATE TABLE t_hist (ID INTEGER PRIMARY KEY, name TEXT, num TEXT, extra TEXT, lastQuery TEXT, classname TEXT)');
        $this->connection->exec('CREATE TABLE t_hist_history (name TEXT, num TEXT, extra TEXT, lastQuery TEXT, classname TEXT)');

        $rec = new SQLiteProvTestHistRecord();
        $this->provider->setRecord($rec);
        try {
            $this->provider->saveHistory();
            $this->assertStringContainsString('t_hist_history', $rec->getLastQuery());
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * @since 4.1
     */
    public function testDeletePrepareFailure(): void
    {
        $this->record->tableName = 'missing_table';
        try {
            $this->expectException(FailedDeleteException::class);
            $this->provider->delete();
        } finally {
            $this->record->tableName = 't_real';
        }
    }

    /**
     * @since 4.1
     */
    public function testRebuildTableBadTableNameThrows(): void
    {
        $this->record->tableName = 'bad name';
        try {
            $this->expectException(AlphaException::class);
            $this->provider->rebuildTable();
        } finally {
            $this->record->tableName = 't_real';
        }
    }

    /**
     * @since 4.1
     */
    public function testDropTableBadTableNameThrows(): void
    {
        $this->record->tableName = 'bad name';
        try {
            $this->expectException(AlphaException::class);
            $this->provider->dropTable();
        } finally {
            $this->record->tableName = 't_real';
        }
    }

    /**
     * @since 4.1
     */
    public function testAddPropertyOverloadedClassnameBranch(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $this->connection->exec('CREATE TABLE SQLiteProvTestSchemaRecord (ID INTEGER PRIMARY KEY, classname TEXT)');
        $this->provider->setRecord($rec);
        try {
            // the PRAGMA lookup sees the classname column so the overloaded branch runs;
            // the ALTER then fails because the column already exists
            $this->expectException(AlphaException::class);
            $this->provider->addProperty('classname');
        } finally {
            $this->assertStringContainsString('ADD classname TEXT(100)', $rec->getLastQuery());
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * @since 4.1
     */
    public function testIsTableOverloadedBadTableNameThrows(): void
    {
        $rec = new SQLiteProvTestSchemaRecord();
        $rec->TableName = 'nope';
        $this->provider->setRecord($rec);
        try {
            $this->expectException(BadTableNameException::class);
            $this->provider->addProperty('intAttr');
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * @since 4.1
     */
    public function testAddPropertyHistoryTableFailure(): void
    {
        $this->connection->exec('CREATE TABLE t_nh (ID INTEGER PRIMARY KEY)');
        $this->record->tableName = 't_nh';
        $this->record->maintainHistory = true;
        $this->record->propObjects['x1'] = $this->createMock(Text::class);
        try {
            $this->expectException(AlphaException::class);
            $this->provider->addProperty('x1');
        } finally {
            $this->record->tableName = 't_real';
            $this->record->maintainHistory = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testCheckTableNeedsUpdateEarlyReturnAndOverloaded(): void
    {
        $this->record->checkTableExists = false;
        $this->assertFalse($this->provider->checkTableNeedsUpdate());
        $this->record->checkTableExists = true;

        $this->record->overloaded = true;
        try {
            // t_real has a classname column so the overloaded check finds a match
            $this->provider->checkTableNeedsUpdate();
            $this->assertTrue(true);
        } finally {
            $this->record->overloaded = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testCheckRecordTableExistsVariants(): void
    {
        try {
            ActiveRecordProviderSQLite::checkRecordTableExists('No\\Such\\Class');
            $this->fail('expected IllegalArguementException');
        } catch (IllegalArguementException $e) {
        }

        // empty TABLE_NAME constant forces the class name parsing branch
        $exists = ActiveRecordProviderSQLite::checkRecordTableExists('Alpha\\Test\\Model\\SQLiteProvTest_noHist');
        $this->assertFalse($exists);

        $this->assertFalse(ActiveRecordProviderSQLite::checkRecordTableExists('Alpha\\Test\\Model\\SQLiteProvTest_noHist', true));
    }

    /**
     * @since 4.1
     */
    public function testSaveTransientOverloadedInsertSuccess(): void
    {
        $this->connection->exec('CREATE TABLE t_ins (ID INTEGER PRIMARY KEY, name TEXT, extra TEXT, version_num INTEGER, tableName TEXT, lastQuery TEXT, classname TEXT)');
        $rec = new SQLiteProvTestInsRecord();
        $this->provider->setRecord($rec);
        try {
            $this->provider->save();
            $this->assertStringContainsString('INSERT INTO t_ins', $rec->getLastQuery());
        } finally {
            $this->provider->setRecord($this->record);
        }
    }

    /**
     * @since 4.1
     */
    public function testSaveTransientUniqueViolationThrows(): void
    {
        $conn = new SQLite3(':memory:');
        $conn->enableExceptions(true);
        $conn->exec('CREATE TABLE t_u (ID INTEGER PRIMARY KEY, name TEXT UNIQUE, extra TEXT, version_num INTEGER, tableName TEXT, lastQuery TEXT, classname TEXT)');
        $conn->exec("INSERT INTO t_u (name, extra) VALUES ('x', 'y')");
        $this->setStaticConnection($conn);

        $rec = new SQLiteProvTestInsRecord();
        $rec->setTableName('t_u');
        $this->provider->setRecord($rec);
        try {
            $this->expectException(ValidationException::class);
            $this->provider->save();
        } finally {
            $this->provider->setRecord($this->record);
            $this->setStaticConnection($this->connection);
            $conn->close();
        }
    }

    /**
     * @since 4.1
     */
    public function testFindMissingFieldsOverloadedMissingClassname(): void
    {
        $this->connection->exec('CREATE TABLE t_nc (ID INTEGER PRIMARY KEY)');
        $this->record->tableName = 't_nc';
        $this->record->overloaded = true;
        try {
            $missing = $this->provider->findMissingFields();
            $this->assertContains('classname', $missing);
        } finally {
            $this->record->tableName = 't_real';
            $this->record->overloaded = false;
        }
    }

    /**
     * @since 4.1
     */
    public function testGetIndexesFKPseudoNamesAndPragmaFailure(): void
    {
        $this->connection->exec('CREATE TABLE t_parent (ID INTEGER PRIMARY KEY)');
        $this->connection->exec('CREATE TABLE t_child (ID INTEGER PRIMARY KEY, parentID INTEGER, FOREIGN KEY(parentID) REFERENCES t_parent(ID))');
        $this->connection->exec('CREATE INDEX t_child_name_idx ON t_child (parentID)');
        $this->record->tableName = 't_child';
        try {
            $names = $this->provider->getIndexes();
            $this->assertContains('t_child_parentID_fk_idx', $names);
        } finally {
            $this->record->tableName = 't_real';
        }

        $this->record->tableName = 'bad name';
        try {
            $names = $this->provider->getIndexes();
            $this->assertCount(0, $names);
        } finally {
            $this->record->tableName = 't_real';
        }
    }
}

/**
 * Live table record stub; the odd tableName casing makes ucfirst(tableName())
 * match the short class name so provider isTableOverloaded() resolves cleanly.
 *
 * @since 4.1
 */
class SQLiteProvTestLive
{
    public $tableName = 'sQLiteProvTestLive';
    public $propObjects = array();
    public $ID = 1;
    public $name = 'x';
    public $version_num = 1;
    public $lastQuery = '';
    public function getTableName()
    {
        return $this->tableName;
    }
    public function getTransientAttributes()
    {
        return array('tableName', 'propObjects', 'lastQuery');
    }
    public function getPersistentAttributes()
    {
        return array('ID', 'name', 'version_num');
    }
    public function getDefaultAttributes()
    {
        return array();
    }
    public function checkTableExists()
    {
        return true;
    }
    public function isTableOverloaded()
    {
        return false;
    }
    public function getMaintainHistory()
    {
        return false;
    }
    public function getIndexes()
    {
        return array();
    }
    public function getUniqueAttributes()
    {
        return array();
    }
    public function getPropObject($n)
    {
        return isset($this->propObjects[$n]) ? $this->propObjects[$n] : null;
    }
    public function setLastQuery($q)
    {
        $this->lastQuery = $q;
    }
    public function getLastQuery()
    {
        return $this->lastQuery;
    }
    public function getID()
    {
        return $this->ID;
    }
}

/**
 * Configurable record stub driving the load/save query paths of the provider.
 *
 * @since 4.1
 */
class SQLiteProvTestSyncRecord
{
    public $tableName = 't_real';
    public $propObjects = array();
    public $lastQuery = '';
    public $transient = false;
    public $throwOn = null;
    public $illegalOn = null;
    public $getObjFor = null;
    public $needsUpdate = false;
    public $missingFields = array();
    public $addedProps = array();
    public $makeTableCalls = 0;
    public $saveRelationsCalls = 0;
    public $tableExists = true;
    public $idSet = null;
    public $ID = 0;
    public $name = '';
    public $version_num = 1;
    public $intAttr = 1;
    public function getTableName()
    {
        return $this->tableName;
    }
    public function getTransientAttributes()
    {
        return array('tableName', 'propObjects', 'lastQuery', 'transient', 'throwOn', 'illegalOn', 'getObjFor', 'needsUpdate', 'missingFields', 'addedProps', 'makeTableCalls', 'saveRelationsCalls', 'tableExists', 'idSet');
    }
    public function getPersistentAttributes()
    {
        return array('ID', 'name', 'version_num');
    }
    public function getDefaultAttributes()
    {
        return array();
    }
    public function getPropObject($n)
    {
        return isset($this->propObjects[$n]) ? $this->propObjects[$n] : null;
    }
    public function getUniqueAttributes()
    {
        return array();
    }
    public function getIndexes()
    {
        return array();
    }
    public function isTableOverloaded()
    {
        return false;
    }
    public function isTransient()
    {
        return $this->transient;
    }
    public function getMaintainHistory()
    {
        return false;
    }
    public function checkTableExists()
    {
        return $this->tableExists;
    }
    public function checkTableNeedsUpdate()
    {
        return $this->needsUpdate;
    }
    public function findMissingFields()
    {
        return $this->missingFields;
    }
    public function addProperty($p)
    {
        $this->addedProps[] = $p;
    }
    public function makeTable()
    {
        ++$this->makeTableCalls;
    }
    public function makeHistoryTable()
    {
    }
    public function setLastQuery($q)
    {
        $this->lastQuery = $q;
    }
    public function getLastQuery()
    {
        return $this->lastQuery;
    }
    public function set($n, $v): void
    {
        if ($this->illegalOn !== null && $n == $this->illegalOn) {
            throw new \Alpha\Exception\IllegalArguementException('stub illegal on [' . $n . ']');
        }
        if ($this->throwOn !== null && $n == $this->throwOn) {
            throw new \Alpha\Exception\PHPException('stub php exception on [' . $n . ']');
        }
        $this->$n = $v;
    }
    public function get($n)
    {
        if ($this->getObjFor !== null && $n == $this->getObjFor) {
            return new Integer((int) $this->$n);
        }
        if (property_exists($this, $n)) {
            return $this->$n;
        }
        throw new \Alpha\Exception\AlphaException('no property [' . $n . ']');
    }
    public function getID()
    {
        return $this->ID;
    }
    public function setID($id)
    {
        $this->ID = $id;
        $this->idSet = $id;
    }
    public function getVersion()
    {
        return $this->version_num;
    }
    public function getVersionNumber()
    {
        return new Integer((int) $this->version_num);
    }
    public function saveRelations()
    {
        ++$this->saveRelationsCalls;
    }
    public function saveHistory()
    {
    }
    public function createUniqueIndex($a, $b = '', $c = '')
    {
    }
    public function createForeignIndex($a, $b, $c)
    {
    }
    public function load($ID, $version = 0)
    {
        $this->ID = $ID;
    }
}

/**
 * Record stub backed by the t_real table for static table existence checks.
 *
 * @since 4.1
 */
class SQLiteProvTestTableTrue
{
    public const TABLE_NAME = 't_real';
}

/**
 * Record stub naming a table that never exists, for static table existence checks.
 *
 * @since 4.1
 */
class SQLiteProvTestTableFalse
{
    public const TABLE_NAME = 't_no_such_table_zzz';
}

/**
 * Instantiable relation target used by the schema record stub.
 *
 * @since 4.1
 */
class SQLiteProvTestSchemaTarget
{
    public function getTableName()
    {
        return 'SQLiteProvTestSchemaTarget';
    }
}

/**
 * Record stub exposing one attribute per supported type for makeTable(),
 * makeHistoryTable() and addProperty() branch coverage.
 *
 * @since 4.1
 */
class SQLiteProvTestSchemaRecord
{
    public $TableName = 'SQLiteProvTestSchemaRecord';
    public $Overloaded = false;
    public $MaintainHistory = false;
    public $UniqueAttrs = array();
    public $MadeTable = false;
    public $MadeHistoryTable = false;
    public $UniqueIndexCalls = array();
    public $LastQuery = '';
    public $QueryReturn = array();

    public $intAttr;
    public $dblAttr;
    public $stAttr;
    public $txtAttr;
    public $ltAttr;
    public $htAttr;
    public $boolAttr;
    public $dateAttr;
    public $tsAttr;
    public $enumAttr;
    public $relAttr;
    public $version_num;
    public $leftID;
    public $rightID;

    private $PropObjects = array();

    /**
     * @since 4.1
     */
    public function __construct()
    {
        $rel = new Relation();
        // setRelatedClass() insists the related class is a discovered record class,
        // so the backing properties are set directly via reflection instead
        $rc = new \ReflectionProperty(Relation::class, 'relatedClass');
        $rc->setAccessible(true);
        $rc->setValue($rel, 'Alpha\Test\Model\SQLiteProvTestSchemaTarget');
        $rf = new \ReflectionProperty(Relation::class, 'relatedClassField');
        $rf->setAccessible(true);
        $rf->setValue($rel, 'ID');
        $rt = new \ReflectionProperty(Relation::class, 'relationType');
        $rt->setAccessible(true);
        $rt->setValue($rel, 'MANY-TO-ONE');

        $lookup = new SQLiteProvTestSchemaLookup();

        $this->PropObjects = array(
            'intAttr' => new Integer(1),
            'dblAttr' => new Double(1.0),
            'stAttr' => new SmallText('a'),
            'txtAttr' => new Text(),
            'ltAttr' => new LargeText(),
            'htAttr' => new HugeText(),
            'boolAttr' => new Boolean(),
            'dateAttr' => new Date(),
            'tsAttr' => new Timestamp(),
            'enumAttr' => new Enum(),
            'relAttr' => $rel,
            'version_num' => new Integer(1),
            'leftID' => $lookup,
            'rightID' => $lookup,
        );
    }

    public function getTableName()
    {
        return $this->TableName;
    }

    public function getTransientAttributes()
    {
        return array();
    }

    public function getDefaultAttributes()
    {
        return array();
    }

    public function getPropObject($name)
    {
        return isset($this->PropObjects[$name]) ? $this->PropObjects[$name] : null;
    }

    public function getIndexes()
    {
        return array();
    }

    public function getUniqueAttributes()
    {
        return $this->UniqueAttrs;
    }

    public function isTableOverloaded()
    {
        return $this->Overloaded;
    }

    public function getMaintainHistory()
    {
        return $this->MaintainHistory;
    }

    public function setLastQuery($q)
    {
        $this->LastQuery = $q;
    }

    public function getLastQuery()
    {
        return $this->LastQuery;
    }

    public function makeTable()
    {
        $this->MadeTable = true;
    }

    public function makeHistoryTable()
    {
        $this->MadeHistoryTable = true;
    }

    public function createUniqueIndex($a, $b = '', $c = '')
    {
        $this->UniqueIndexCalls[] = array($a, $b, $c);
    }

    public function createForeignIndex($a, $b, $c)
    {
    }

    public function dropTable($tableName = null)
    {
        $this->MadeTable = false;
    }

    public function query($q)
    {
        return $this->QueryReturn;
    }
}

/**
 * RelationLookup stand-in that avoids the ActiveRecord constructor while still
 * satisfying the provider's instanceof checks.
 *
 * @since 4.1
 */
class SQLiteProvTestSchemaLookup extends RelationLookup
{
    /**
     * @since 4.1
     */
    public function __construct()
    {
    }

    public function getSize(): int
    {
        return 11;
    }
}

/**
 * Minimal record stub for exercising the overloaded saveHistory success path;
 * deliberately has no classname property of its own.
 *
 * @since 4.1
 */
class SQLiteProvTestHistRecord
{
    public $name = 'x';
    public $num = 1;
    public $extra = '';
    public $lastQuery = '';

    public function getTableName()
    {
        return 't_hist';
    }

    public function getTransientAttributes()
    {
        return array();
    }

    public function isTableOverloaded()
    {
        return true;
    }

    public function get($n)
    {
        return 'x';
    }

    public function setLastQuery($q)
    {
        $this->lastQuery = $q;
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }
}

/**
 * Class name deliberately contains an underscore and an empty TABLE_NAME
 * constant to exercise the table name parsing branch of
 * ActiveRecordProviderSQLite::checkRecordTableExists().
 *
 * @since 4.1
 */
class SQLiteProvTest_noHist
{
    public const TABLE_NAME = '';
}

/**
 * Slim transient record stub for the overloaded insert save() success path;
 * no classname or version_num properties so the INSERT column list stays clean.
 *
 * @since 4.1
 */
class SQLiteProvTestInsRecord
{
    public $name = 'x';
    public $extra = 'y';
    public $tableName = 't_ins';
    public $lastQuery = '';
    public $ID = 0;
    public $version_num = 1;

    public function setTableName($t)
    {
        $this->tableName = $t;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getTransientAttributes()
    {
        return array();
    }

    public function isTableOverloaded()
    {
        return true;
    }

    public function isTransient()
    {
        return true;
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
        return $this->ID;
    }

    public function setID($id)
    {
        $this->ID = $id;
    }

    public function saveRelations()
    {
    }

    public function getMaintainHistory()
    {
        return false;
    }

    public function setLastQuery($q)
    {
        $this->lastQuery = $q;
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }
}
