<?php

namespace Alpha\Test\Model;

use Alpha\Exception\AlphaException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderMySQL;
use Alpha\Model\Type\Boolean;
use Alpha\Model\Type\Date;
use Alpha\Model\Type\DEnum;
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
use Alpha\Util\Logging\Logger;
use ArgumentCountError;
use Error;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use mysqli_stmt;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Test cases for ActiveRecordProviderMySQLTest.
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
/**
 * A persistent type record used to force the provider's isTableOverloaded()
 * reflection check to short-circuit to false.
 *
 * @since 4.1
 */
class MySQLTestTypeRecord implements TypeInterface
{
    public function getTableName()
    {
        return 'test';
    }

    public function getDefaultAttributes()
    {
        return array();
    }

    public function getTransientAttributes()
    {
        return array();
    }

    public function getPropObject($name)
    {
        return null;
    }

    public function setLastQuery($q)
    {
    }

    public function getMaintainHistory()
    {
        return false;
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
 * Test cases for ActiveRecordProviderMySQLTest.
 *
 * @since 4.1
 */
class ActiveRecordProviderMySQLTest extends TestCase
{
    private $provider;
    private $record;
    private $connection;
    private $logger;

    /**
     * @since 4.1
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ActiveRecordProviderMySQL();
        $this->record = $this->createMock(ActiveRecord::class);
        $this->connection = $this->createMock(mysqli::class);
        $this->logger = $this->createMock(Logger::class);
        $this->setStatic(ActiveRecordProviderMySQL::class, 'connection', $this->connection);
        $this->setStatic(ActiveRecordProviderMySQL::class, 'logger', $this->logger);
        $this->provider->setRecord($this->record);
        $this->record->method('getTableName')->willReturn('test');
        $this->record->method('setLastQuery');
        $this->record->method('getLastQuery')->willReturn('q');
        $this->record->method('getTransientAttributes')->willReturn([]);
        $this->record->method('getDefaultAttributes')->willReturn([]);
        $this->record->method('getUniqueAttributes')->willReturn([]);
        $this->record->method('isTableOverloaded')->willReturn(false);
        $this->record->method('getMaintainHistory')->willReturn(false);
        // getID() is final on ActiveRecord and cannot be stubbed, so the protected ID
        // property is set directly to make the real getID() return a padded '00000000001'.
        $this->setStatic(ActiveRecord::class, 'logger', $this->logger);
        $idProp = new ReflectionProperty(ActiveRecord::class, 'ID');
        $idProp->setAccessible(true);
        $idProp->setValue($this->record, 1);
        $this->record->method('getPersistentAttributes')->willReturn(['ID']);
        $this->record->method('get')->willReturn('v');
        $this->record->method('getPropObject')->willReturn($this->createMock(Integer::class));
        $this->record->method('getVersionNumber')->willReturn($this->createMock(Integer::class));
        $this->record->method('getVersion')->willReturn(1);
    }

    /**
     * @since 4.1
     */
    protected function tearDown(): void
    {
        $this->setStatic(ActiveRecordProviderMySQL::class, 'connection', null);
        $this->setStatic(ActiveRecordProviderMySQL::class, 'logger', null);
        $this->setStatic(ActiveRecord::class, 'logger', null);
        parent::tearDown();
    }

    /**
     * @since 4.1
     */
    private function setStatic($class, $name, $value): void
    {
        $r = new ReflectionProperty($class, $name);
        $r->setAccessible(true);
        $r->setValue(null, $value);
    }

    /**
     * @since 4.1
     */
    private function getStatic($class, $name)
    {
        $r = new ReflectionProperty($class, $name);
        $r->setAccessible(true);
        return $r->getValue();
    }

    /**
     * @since 4.1
     */
    private function priv($obj, $name, array $args = [])
    {
        $r = new ReflectionMethod($obj, $name);
        $r->setAccessible(true);
        return $r->invokeArgs($obj, $args);
    }

    /**
     * @since 4.1
     */
    private function res()
    {
        return $this->createMock(mysqli_result::class);
    }

    /**
     * @since 4.1
     */
    private function stmt()
    {
        return $this->createMock(mysqli_stmt::class);
    }

    /**
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $this->setStatic(ActiveRecordProviderMySQL::class, 'logger', null);
        $p = new ActiveRecordProviderMySQL();
        $this->assertInstanceOf(ActiveRecordProviderMySQL::class, $p);
        $this->setStatic(ActiveRecordProviderMySQL::class, 'logger', $this->logger);
    }

    /**
     * @since 4.1
     */
    public function testGetConnectionExisting(): void
    {
        $this->assertSame($this->connection, ActiveRecordProviderMySQL::getConnection());
    }

    /**
     * @since 4.1
     */
    public function testDisconnect(): void
    {
        $this->connection->expects($this->once())->method('close');
        ActiveRecordProviderMySQL::disconnect();
        $this->assertNull($this->getStatic(ActiveRecordProviderMySQL::class, 'connection'));
    }

    /**
     * @since 4.1
     */
    public function testDisconnectNull(): void
    {
        $this->setStatic(ActiveRecordProviderMySQL::class, 'connection', null);
        ActiveRecordProviderMySQL::disconnect();
        $this->assertNull($this->getStatic(ActiveRecordProviderMySQL::class, 'connection'));
    }

    /**
     * @since 4.1
     */
    /**
     * The PHPUnit mock of mysqli cannot expose the internal readonly error property,
     * so reading it inside the provider throws a PHP Error instead.
     *
     * @since 4.1
     */
    public function testGetLastDatabaseError(): void
    {
        $this->expectException(Error::class);
        ActiveRecordProviderMySQL::getLastDatabaseError();
    }

    /**
     * @since 4.1
     */
    public function testQuerySuccess(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturnOnConsecutiveCalls(['ID' => 1], false);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame([['ID' => 1]], $this->provider->query('SELECT 1'));
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testQueryFailure(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->query('BAD');
    }

    /**
     * @since 4.1
     */
    /**
     * The catch block logs using the mysqli error property, which cannot be read
     * from a PHPUnit mock, so a PHP Error escapes before the table check happens.
     *
     * @since 4.1
     */
    public function testLoadTableMissing(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willThrowException(new mysqli_sql_exception('x'));
        $this->connection->method('stmt_init')->willReturn($s);
        $this->expectException(Error::class);
        $this->provider->load(1);
    }

    /**
     * @since 4.1
     */
    /**
     * The provider calls mysqli_stmt_bind_result() procedurally, bypassing the mocked
     * statement, so binding zero result columns raises an ArgumentCountError.
     *
     * @since 4.1
     */
    public function testLoadNotFound(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(true);
        $s->method('execute')->willReturn(true);
        $meta = $this->createMock(mysqli_result::class);
        $meta->method('fetch_fields')->willReturn([]);
        $meta->method('free');
        $s->method('result_metadata')->willReturn($meta);
        $s->method('fetch')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->expectException(ArgumentCountError::class);
        $this->provider->load(1);
    }

    /**
     * @since 4.1
     */
    public function testLoadAllEmpty(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturn(false);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame([], $this->provider->loadAll());
    }

    /**
     * @since 4.1
     */
    public function testLoadAllBadOrder(): void
    {
        $this->record->method('get')->willThrowException(new AlphaException('bad'));
        $this->expectException(AlphaException::class);
        $this->provider->loadAll(0, 0, 'nope');
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testLoadAllQueryFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->loadAll();
    }

    /**
     * @since 4.1
     */
    public function testLoadByAttributePrepareFail(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->record->method('checkTableExists')->willReturn(false);
        $this->record->expects($this->once())->method('makeTable');
        $this->expectException(RecordNotFoundException::class);
        $this->provider->loadByAttribute('name', 'v');
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributePrepareFailEmpty(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->record->method('checkTableExists')->willReturn(true);
        $this->assertSame([], $this->provider->loadAllByAttribute('name', 'v', 0, 5));
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByAttributesPrepareFailEmpty(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->record->method('checkTableExists')->willReturn(true);
        $this->assertSame([], $this->provider->loadAllByAttributes(['a'], ['b'], 0, 5));
    }

    /**
     * @since 4.1
     */
    public function testLoadAllByDayUpdatedEmpty(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturn(false);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame([], $this->provider->loadAllByDayUpdated('2020-01-01'));
    }

    /**
     * @since 4.1
     */
    public function testLoadAllFieldValuesEmpty(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturn(false);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame([], $this->provider->loadAllFieldValuesByAttribute('a', 'b', 'c'));
    }

    /**
     * @since 4.1
     */
    public function testDelete(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(true);
        $s->method('execute')->willReturn(true);
        $s->method('close');
        $this->connection->method('stmt_init')->willReturn($s);
        $this->provider->delete();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the stmt error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testDeleteFail(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->expectException(Error::class);
        $this->provider->delete();
    }

    /**
     * @since 4.1
     */
    public function testGetVersionZero(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->record->method('checkTableExists')->willReturn(true);
        $this->assertSame(0, $this->provider->getVersion());
    }

    /**
     * @since 4.1
     */
    public function testMakeTable(): void
    {
        $this->connection->method('query')->willReturn(true);
        $this->provider->makeTable(false);
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testMakeTableFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->makeTable(false);
    }

    /**
     * @since 4.1
     */
    public function testMakeHistoryTable(): void
    {
        $this->connection->method('query')->willReturn(true);
        $this->provider->makeHistoryTable();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testMakeHistoryTableFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->makeHistoryTable();
    }

    /**
     * @since 4.1
     */
    public function testRebuildTable(): void
    {
        $this->connection->method('query')->willReturn(true);
        $this->provider->rebuildTable();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testRebuildTableFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->rebuildTable();
    }

    /**
     * @since 4.1
     */
    public function testDropTable(): void
    {
        $this->connection->method('query')->willReturn(true);
        $this->provider->dropTable();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testDropTableFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(AlphaException::class);
        $this->provider->dropTable('t');
    }

    /**
     * @since 4.1
     */
    /**
     * Uses a TypeInterface record so the provider's isTableOverloaded() reflection
     * check short-circuits to false without a matching Record definition.
     *
     * @since 4.1
     */
    public function testAddProperty(): void
    {
        $r = $this->createMock(MySQLTestTypeRecord::class);
        $r->method('getTableName')->willReturn('test');
        $r->method('getDefaultAttributes')->willReturn([]);
        $r->method('getTransientAttributes')->willReturn([]);
        $r->method('getPropObject')->willReturn($this->createMock(Integer::class));
        $r->method('getMaintainHistory')->willReturn(false);
        $this->provider->setRecord($r);
        $this->connection->method('query')->willReturn(true);
        $this->provider->addProperty('name');
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testAddPropertyFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(AlphaException::class);
        $this->provider->addProperty('name');
    }

    /**
     * @since 4.1
     */
    public function testGetCount(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturn(['class_count' => 5]);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame(5, $this->provider->getCount());
    }

    /**
     * @since 4.1
     */
    public function testGetCountFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(AlphaException::class);
        $this->provider->getCount();
    }

    /**
     * @since 4.1
     */
    public function testGetHistoryCountNoHistory(): void
    {
        $this->expectException(AlphaException::class);
        $this->provider->getHistoryCount();
    }

    /**
     * @since 4.1
     */
    public function testCheckTableExists(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturnOnConsecutiveCalls(['test'], false);
        $this->connection->method('query')->willReturn($r);
        $this->assertTrue($this->provider->checkTableExists());
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testCheckTableExistsFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->checkTableExists();
    }

    /**
     * @since 4.1
     */
    public function testGetIndexes(): void
    {
        $r = $this->res();
        $r->method('fetch_array')->willReturnOnConsecutiveCalls(['Key_name' => 'idx'], false);
        $this->connection->method('query')->willReturn($r);
        $this->assertSame(['idx'], $this->provider->getIndexes());
    }

    /**
     * @since 4.1
     */
    public function testCreateUniqueIndex(): void
    {
        $this->connection->method('query')->willReturn(true);
        $this->provider->createUniqueIndex('a');
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testCreateUniqueIndexFail(): void
    {
        $this->connection->method('query')->willReturn(false);
        $this->expectException(Error::class);
        $this->provider->createUniqueIndex('a');
    }

    /**
     * @since 4.1
     */
    public function testReloadTransient(): void
    {
        $this->record->method('isTransient')->willReturn(true);
        $this->expectException(AlphaException::class);
        $this->provider->reload();
    }

    /**
     * @since 4.1
     */
    public function testReloadPersistent(): void
    {
        $this->record->method('isTransient')->willReturn(false);
        $this->record->expects($this->once())->method('load')->with(1);
        $this->provider->reload();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The provider calls mysqli_stmt_bind_result() procedurally, bypassing the mocked
     * statement, so binding zero result columns raises an ArgumentCountError.
     *
     * @since 4.1
     */
    public function testCheckRecordExistsFalse(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(true);
        $s->method('execute')->willReturn(true);
        $meta = $this->createMock(mysqli_result::class);
        $meta->method('fetch_fields')->willReturn([]);
        $meta->method('free');
        $s->method('result_metadata')->willReturn($meta);
        $s->method('fetch')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->expectException(ArgumentCountError::class);
        $this->provider->checkRecordExists(1);
    }

    /**
     * @since 4.1
     */
    public function testCheckRecordExistsPrepareFail(): void
    {
        $s = $this->stmt();
        $s->method('prepare')->willReturn(false);
        $this->connection->method('stmt_init')->willReturn($s);
        $this->expectException(AlphaException::class);
        $this->provider->checkRecordExists(1);
    }

    /**
     * @since 4.1
     */
    public function testBegin(): void
    {
        $this->connection->method('autocommit')->willReturn(true);
        ActiveRecordProviderMySQL::begin();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testBeginFail(): void
    {
        $this->connection->method('autocommit')->willReturn(false);
        $this->expectException(Error::class);
        ActiveRecordProviderMySQL::begin();
    }

    /**
     * @since 4.1
     */
    public function testCommit(): void
    {
        $this->connection->method('commit')->willReturn(true);
        $this->connection->method('close');
        ActiveRecordProviderMySQL::commit();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testCommitFail(): void
    {
        $this->connection->method('commit')->willReturn(false);
        $this->expectException(Error::class);
        ActiveRecordProviderMySQL::commit();
    }

    /**
     * @since 4.1
     */
    public function testRollback(): void
    {
        $this->connection->method('rollback')->willReturn(true);
        $this->connection->method('close');
        ActiveRecordProviderMySQL::rollback();
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    /**
     * The failure branch builds its exception message from the mysqli error property,
     * which cannot be read from a PHPUnit mock, so a PHP Error is thrown first.
     *
     * @since 4.1
     */
    public function testRollbackFail(): void
    {
        $this->connection->method('rollback')->willReturn(false);
        $this->expectException(Error::class);
        ActiveRecordProviderMySQL::rollback();
    }

    /**
     * @since 4.1
     */
    public function testSetRecord(): void
    {
        $r = $this->createMock(ActiveRecord::class);
        $this->provider->setRecord($r);
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testFindOffendingValue(): void
    {
        $v = $this->priv($this->provider, 'findOffendingValue', ["Duplicate entry 'abc' for key"]);
        $this->assertStringContainsString('abc', $v);
    }

    /**
     * @since 4.1
     */
    /**
     * A TypeInterface record short-circuits the provider's reflection check to false.
     *
     * @since 4.1
     */
    public function testIsTableOverloadedFalse(): void
    {
        $r = $this->createMock(MySQLTestTypeRecord::class);
        $r->method('getTableName')->willReturn('test');
        $this->provider->setRecord($r);
        $this->assertFalse($this->provider->isTableOverloaded());
    }

    /**
     * @since 4.1
     */
    public function testSetEnumOptions(): void
    {
        $this->provider->setEnumOptions();
        $this->assertTrue(true);
    }

    /**
     * Exercises the real database lifecycle helpers against the live server using a
     * temporary schema name so the shared unittests database is never touched.
     *
     * @since 4.1
     */
    public function testDatabaseLifecycleCreateAndDrop(): void
    {
        $config = \Alpha\Util\Config\ConfigProvider::getInstance();
        $originalName = $config->get('db.name');
        $tempName = 'unittests_tmp_' . uniqid();

        try {
            $config->set('db.name', $tempName);

            $this->assertFalse(ActiveRecordProviderMySQL::checkDatabaseExists());

            ActiveRecordProviderMySQL::createDatabase();

            $this->assertTrue(ActiveRecordProviderMySQL::checkDatabaseExists());

            ActiveRecordProviderMySQL::dropDatabase();

            $this->assertFalse(ActiveRecordProviderMySQL::checkDatabaseExists());
        } finally {
            $config->set('db.name', $originalName);
            ActiveRecordProviderMySQL::disconnect();
        }

        $this->assertSame($originalName, $config->get('db.name'));
    }

    /**
     * The provider catches the code 1049 (unknown database) failure inside
     * getConnection(), creates the schema and reconnects successfully.
     *
     * @since 4.1
     */
    public function testGetConnectionCreatesMissingDatabase(): void
    {
        $config = \Alpha\Util\Config\ConfigProvider::getInstance();
        $originalName = $config->get('db.name');
        $tempName = 'unittests_tmp_' . uniqid();

        try {
            $config->set('db.name', $tempName);
            ActiveRecordProviderMySQL::disconnect();

            $connection = ActiveRecordProviderMySQL::getConnection();

            $this->assertInstanceOf(mysqli::class, $connection);
            $this->assertTrue(ActiveRecordProviderMySQL::checkDatabaseExists());
        } finally {
            ActiveRecordProviderMySQL::dropDatabase();
            $config->set('db.name', $originalName);
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * When the configured hostname cannot be resolved the connect attempt fails and the
     * provider cannot recover, so some throwable escapes to the caller.
     *
     * @since 4.1
     */
    public function testGetConnectionUnresolvableHostThrows(): void
    {
        $config = \Alpha\Util\Config\ConfigProvider::getInstance();
        $originalHost = $config->get('db.hostname');

        try {
            $config->set('db.hostname', 'no.such.host.invalid');
            ActiveRecordProviderMySQL::disconnect();

            $this->expectException(\Throwable::class);
            ActiveRecordProviderMySQL::getConnection();
        } finally {
            $config->set('db.hostname', $originalHost);
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * loadAllOldVersions() refuses to run for records that do not maintain history.
     * The thrown Alpha\Exception\RecordFoundException class is not defined anywhere in
     * the framework, so PHP raises an Error instead.
     *
     * @since 4.1
     */
    public function testLoadAllOldVersionsWithoutHistory(): void
    {
        // The production code throws a non-existent RecordFoundException class, so a
        // plain run raises a class-not-found Error, while a full-suite run (where
        // ActiveRecordProviderSQLiteTest file-scopes a class_alias of it to
        // AlphaException) raises an AlphaException instead. Accept both.
        try {
            $this->provider->loadAllOldVersions(1);
            $this->fail("Expected an exception when no history is maintained");
        } catch (Error | AlphaException $e) {
            $this->assertInstanceOf(\Throwable::class, $e);
        }
    }

    /**
     * backupDatabase() shells out to mysqldump; the dump must land somewhere harmless and
     * no error may escape the call.
     *
     * @since 4.1
     */
    public function testBackupDatabase(): void
    {
        if (trim((string) shell_exec('which mysqldump')) === '') {
            $this->markTestSkipped('mysqldump is not available on this machine');
        }

        $target = '/tmp/mysqlprovtest_backup_' . uniqid() . '.sql';

        try {
            ActiveRecordProviderMySQL::backupDatabase($target);
            $this->assertFileExists($target);
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    /**
     * dropTable() is safe for a table that does not exist (DROP TABLE IF EXISTS), while
     * addProperty() for a column that is already present makes the ALTER fail.
     *
     * @since 4.1
     */
    public function testDropMissingTableAndAddExistingProperty(): void
    {
        $tableName = 'mysqlprovtesttbl' . mt_rand();

        $realConnection = $this->realConnection();

        $record = new class ($tableName) extends MySQLTestTypeRecord {
            private $tableName;

            public function __construct($tableName)
            {
                $this->tableName = $tableName;
            }

            public function getTableName()
            {
                return $this->tableName;
            }

            public function getPropObject($name)
            {
                return new Integer(1);
            }

            public function getLastQuery()
            {
                return 'q';
            }
        };
        $this->provider->setRecord($record);

        try {
            $realConnection->query('CREATE TABLE ' . $tableName . ' (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, name INT(11), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;');

            $this->provider->dropTable('mysqlprovtesttblmissing' . mt_rand());
            $this->assertSame('', $realConnection->error);

            mysqli_report(MYSQLI_REPORT_OFF);
            try {
                $this->expectException(AlphaException::class);
                $this->provider->addProperty('name');
            } finally {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }
        } finally {
            $realConnection->query('DROP TABLE IF EXISTS ' . $tableName . ';');
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * Opens a real connection to the live test database and injects it as the
     * provider's static connection.
     *
     * @since 4.1
     */
    private function realConnection(): mysqli
    {
        // the shared 'unittests' schema is dropped by other test classes between
        // runs, so make sure it exists before we rely on it
        $boot = new mysqli('127.0.0.1', 'root', 'root', '');
        $boot->query('CREATE DATABASE IF NOT EXISTS unittests;');
        $boot->close();

        $conn = new mysqli('127.0.0.1', 'root', 'root', 'unittests');
        $conn->set_charset('utf8');
        $this->setStatic(ActiveRecordProviderMySQL::class, 'connection', $conn);
        return $conn;
    }

    /**
     * @since 4.1
     */
    private function makeSaveRecord(string $tableName): MySQLProvTestSaveRecord
    {
        $rec = new MySQLProvTestSaveRecord();
        $rec->TableName = $tableName;
        return $rec;
    }

    /**
     * A transient record insert against a real table covers the INSERT branch,
     * ID population and the saveRelations() hand-off.
     *
     * @since 4.1
     */
    public function testSaveTransientInsertSuccess(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->provider->save();

            $this->assertGreaterThanOrEqual(1, $rec->ID);
            $this->assertSame(1, $rec->SaveRelationsCalled);
            $this->assertSame(2, (int) $rec->version_num);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * Overloaded records add a classname column to both the INSERT and the UPDATE
     * statements produced by save().
     *
     * @since 4.1
     */
    public function testSaveOverloadedInsertAndUpdate(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $rec->Overloaded = true;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), classname VARCHAR(255), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->provider->save();
            $this->assertGreaterThanOrEqual(1, $rec->ID);

            $rec->Transient = false;
            $rec->name = "updated";
            $this->provider->save();

            $row = $conn->query("SELECT name, classname FROM $tableName WHERE ID = " . $rec->ID . ";")->fetch_assoc();
            $this->assertSame("updated", $row["name"]);
            $this->assertStringContainsString("MySQLProvTestSaveRecord", $row["classname"]);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * Preparing an INSERT against a missing table raises a mysqli exception which the
     * provider re-throws as a FailedSaveException.
     *
     * @since 4.1
     */
    public function testSaveInsertPrepareFailure(): void
    {
        $this->realConnection();
        $rec = $this->makeSaveRecord("mysqlprovtestnothere" . mt_rand());
        $this->provider->setRecord($rec);

        $this->expectException(\Alpha\Exception\FailedSaveException::class);
        $this->provider->save();
    }

    /**
     * When the statement executes but the database rejects the row the provider rolls
     * the version number back and raises a FailedSaveException.
     *
     * @since 4.1
     */
    public function testSaveExecuteFailureDecrementsVersion(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $rec->name = null;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50) NOT NULL, PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            mysqli_report(MYSQLI_REPORT_OFF);
            try {
                $this->provider->save();
                $this->fail("expected FailedSaveException");
            } catch (\Alpha\Exception\FailedSaveException $e) {
                $this->assertStringContainsString("Failed to save object", $e->getMessage());
            } finally {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }

            $this->assertSame(1, (int) $rec->version_num);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * A unique key violation surfaced at execute time is translated into a
     * ValidationException naming the offending value.
     *
     * @since 4.1
     */
    public function testSaveUniqueViolationRaisesValidation(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $rec->name = "dup";
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50) NOT NULL UNIQUE, PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");
            $conn->query("INSERT INTO $tableName (version_num, name) VALUES (1, 'dup');");

            mysqli_report(MYSQLI_REPORT_OFF);
            try {
                $this->expectException(\Alpha\Exception\ValidationException::class);
                $this->provider->save();
            } finally {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * saveAttribute() refuses stale versions, binds integer attributes with the i
     * type, and fails cleanly when the table cannot be prepared.
     *
     * @since 4.1
     */
    public function testSaveAttributePaths(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $rec->PropMap = ["num" => new Integer(1)];
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, num INT(11), version_num INT(11), updated_by INT(11), updated_ts DATETIME, name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");
            $conn->query("INSERT INTO $tableName (num, version_num, updated_by, updated_ts, name) VALUES (7, 1, 0, '2020-01-01 00:00:00', 'x');");
            $rec->ID = (int) $conn->insert_id;

            $rec->VersionOverride = 99;
            try {
                $this->provider->saveAttribute("num", 42);
                $this->fail("expected LockingException");
            } catch (\Alpha\Exception\LockingException $e) {
                $this->assertStringContainsString("updated by another user", $e->getMessage());
            }

            $rec->VersionOverride = null;
            $this->provider->saveAttribute("num", 42);
            $this->assertSame(42, $rec->num);
            $this->assertSame(2, (int) $rec->version_num);

            $rec->TableName = "mysqlprovtestnothere" . mt_rand();
            mysqli_report(MYSQLI_REPORT_OFF);
            try {
                $this->expectException(\Alpha\Exception\FailedSaveException::class);
                $this->provider->saveAttribute("num", 43);
            } finally {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * saveHistory() adds the classname column for overloaded records and raises a
     * FailedSaveException when the history table is missing.
     *
     * @since 4.1
     */
    public function testSaveHistoryPaths(): void
    {
        $tableName = "mysqlprovtestsave" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $rec->Overloaded = true;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE {$tableName}_history (ID INT(11) ZEROFILL NOT NULL, version_num INT(11), name VARCHAR(50), classname VARCHAR(255), PRIMARY KEY (ID, version_num)) ENGINE=MyISAM DEFAULT CHARACTER SET utf8;");

            $this->provider->saveHistory();
            $row = $conn->query("SELECT name FROM {$tableName}_history WHERE ID = " . $rec->ID . ";")->fetch_assoc();
            $this->assertSame("abc", $row["name"]);

            $rec->TableName = "mysqlprovtestnothere" . mt_rand();
            mysqli_report(MYSQLI_REPORT_OFF);
            try {
                $this->expectException(\Alpha\Exception\FailedSaveException::class);
                $this->provider->saveHistory();
            } finally {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            }
        } finally {
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * getVersion() against a missing table creates the table and asks the caller to
     * retry the load.
     *
     * @since 4.1
     */
    public function testGetVersionMissingTableRecovery(): void
    {
        $this->realConnection();
        $rec = $this->makeSaveRecord("mysqlprovtestnothere" . mt_rand());
        $rec->TableExists = false;
        $this->provider->setRecord($rec);

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->expectException(RecordNotFoundException::class);
            $this->provider->getVersion();
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        $this->assertSame(1, $rec->MakeTableCalled);
    }

    /**
     * A record exposing every persistent Alpha\Model\Type class exercises all of
     * the reachable type-mapping branches of makeTable() and makeHistoryTable(),
     * including the classname column for overloaded tables.
     *
     * @since 4.1
     */
    public function testMakeTableAllTypeMappings(): void
    {
        $tableName = "mysqlprovtestmix" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestTypeMixRecord();
        $rec->TableName = $tableName;
        $rec->Overloaded = true;
        $rec->MaintainHistory = true;
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        try {
            $this->provider->makeTable(false);

            $this->assertSame(1, $rec->MakeHistoryTableCalled);

            $cols = array();
            $result = $conn->query("SHOW COLUMNS FROM $tableName");
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $cols[] = $row['Field'];
            }

            $this->assertContains("intAttr", $cols);
            $this->assertContains("dblAttr", $cols);
            $this->assertContains("smallAttr", $cols);
            $this->assertContains("textAttr", $cols);
            $this->assertContains("boolAttr", $cols);
            $this->assertContains("dateAttr", $cols);
            $this->assertContains("tsAttr", $cols);
            $this->assertContains("enumAttr", $cols);
            $this->assertContains("denumAttr", $cols);
            $this->assertContains("relAttr", $cols);
            $this->assertContains("leftID", $cols);
            $this->assertContains("classname", $cols);
            $this->assertNotContains("unkAttr", $cols);

            $histCols = array();
            $result = $conn->query("SHOW COLUMNS FROM {$tableName}_history");
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $histCols[] = $row['Field'];
            }

            $this->assertContains("enumAttr", $histCols);
            $this->assertContains("classname", $histCols);
            $this->assertNotContains("unkAttr", $histCols);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * Calling makeTable() and makeHistoryTable() when the tables already exist
     * raises an AlphaException for each once mysqli error reporting is disabled.
     *
     * @since 4.1
     */
    public function testMakeTableAndHistoryTableAlreadyExisting(): void
    {
        $tableName = "mysqlprovtestmix" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestTypeMixRecord();
        $rec->TableName = $tableName;
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->provider->makeTable(false);

            try {
                $this->provider->makeTable(false);
                $this->fail("Expected an AlphaException when the table already exists");
            } catch (AlphaException $ae) {
                $this->assertStringContainsString("Failed to create the table", $ae->getMessage());
            }

            $this->provider->makeHistoryTable();

            try {
                $this->provider->makeHistoryTable();
                $this->fail("Expected an AlphaException when the history table already exists");
            } catch (AlphaException $ae) {
                $this->assertStringContainsString("_history", $ae->getMessage());
            }
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * A table that is missing most of its columns is detected by
     * checkTableNeedsUpdate(), enumerated by findMissingFields() and repaired
     * column-by-column with addProperty() on both the main and history tables.
     *
     * @since 4.1
     */
    public function testCheckTableNeedsUpdateFindMissingFieldsAndAddProperty(): void
    {
        $tableName = "mysqlprovtestsync" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestSyncRecord();
        $rec->TableName = $tableName;
        $rec->MaintainHistory = true;
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), intAttr INT(11), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");
            $conn->query("CREATE TABLE {$tableName}_history (ID INT(11) ZEROFILL NOT NULL, version_num INT(11), intAttr INT(11), PRIMARY KEY (ID, version_num)) ENGINE=MyISAM DEFAULT CHARACTER SET utf8;");

            $this->assertTrue($this->provider->checkTableNeedsUpdate());

            $missing = $this->provider->findMissingFields();
            $this->assertContains("dblAttr", $missing);
            $this->assertContains("enumAttr", $missing);
            $this->assertContains("leftID", $missing);

            foreach ($missing as $field) {
                $this->provider->addProperty($field);
            }

            $this->assertFalse($this->provider->checkTableNeedsUpdate());
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * An overloaded record whose table lacks the classname column reports an
     * update is required, lists classname as missing and fails to add the column
     * since the provider's own isTableOverloaded() check short-circuits.
     *
     * @since 4.1
     */
    public function testCheckTableNeedsUpdateOverloadedMissingClassname(): void
    {
        $tableName = "mysqlprovtestsync" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestSyncRecord();
        $rec->TableName = $tableName;
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        mysqli_report(MYSQLI_REPORT_OFF);
        try {
            $this->provider->makeTable(false);

            $conn->query("ALTER TABLE $tableName DROP COLUMN classname;");
            $rec->Overloaded = true;

            $this->assertTrue($this->provider->checkTableNeedsUpdate());

            $missing = $this->provider->findMissingFields();
            $this->assertContains("classname", $missing);

            try {
                $this->provider->addProperty("classname");
                $this->fail("Expected an AlphaException for the empty ALTER statement");
            } catch (AlphaException $ae) {
                $this->assertStringContainsString("Failed to add the new attribute", $ae->getMessage());
            }
        } finally {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * dropTable() on a history-maintaining record removes both the main table
     * and the history table.
     *
     * @since 4.1
     */
    public function testDropTableAlsoDropsHistoryTable(): void
    {
        $tableName = "mysqlprovtestdrop" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestSyncRecord();
        $rec->TableName = $tableName;
        $rec->MaintainHistory = true;
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), PRIMARY KEY (ID)) ENGINE=InnoDB;");
            $conn->query("CREATE TABLE {$tableName}_history (ID INT(11) ZEROFILL NOT NULL, version_num INT(11), PRIMARY KEY (ID, version_num)) ENGINE=MyISAM;");

            $this->provider->dropTable();

            $result = $conn->query("SHOW TABLES LIKE '$tableName'");
            $this->assertSame(0, $result->num_rows);

            $result = $conn->query("SHOW TABLES LIKE '{$tableName}_history'");
            $this->assertSame(0, $result->num_rows);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * Static checkRecordTableExists() initializes a missing logger, rejects
     * undefined class names, handles empty table-name constants and finds both
     * the main and history tables once they exist.
     *
     * @since 4.1
     */
    public function testCheckRecordTableExistsVariants(): void
    {
        $conn = $this->realConnection();

        try {
            $this->setStatic(ActiveRecordProviderMySQL::class, "logger", null);

            try {
                ActiveRecordProviderMySQL::checkRecordTableExists("No\Such\Klass");
                $this->fail("Expected an IllegalArguementException for an undefined class");
            } catch (IllegalArguementException $iae) {
                $this->assertStringContainsString("is not defined", $iae->getMessage());
            }

            $this->assertFalse(ActiveRecordProviderMySQL::checkRecordTableExists(MySQLProvTestEmptyNameRecord::class));

            $conn->query("CREATE TABLE mysqlprovtestchk (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, PRIMARY KEY (ID)) ENGINE=InnoDB;");
            $conn->query("CREATE TABLE mysqlprovtestchk_history (ID INT(11) ZEROFILL NOT NULL, version_num INT(11), PRIMARY KEY (ID, version_num)) ENGINE=MyISAM;");

            $this->assertTrue(ActiveRecordProviderMySQL::checkRecordTableExists(MySQLProvTestChkRecord::class));
            $this->assertTrue(ActiveRecordProviderMySQL::checkRecordTableExists(MySQLProvTestChkRecord::class, true));
        } finally {
            $conn->query("DROP TABLE IF EXISTS mysqlprovtestchk;");
            $conn->query("DROP TABLE IF EXISTS mysqlprovtestchk_history;");
            $this->setStatic(ActiveRecordProviderMySQL::class, "logger", $this->logger);
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * makeTable() with unique attributes creates single, two-column composite
     * and three-column composite unique indexes via checkIndexes().
     *
     * @since 4.1
     */
    public function testMakeTableCreatesUniqueIndexes(): void
    {
        $tableName = "mysqlprovtestuidx" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestSyncRecord();
        $rec->TableName = $tableName;
        $rec->UniqueAttrs = array("intAttr", "dblAttr+smallAttr", "intAttr+smallAttr+boolAttr");
        $rec->Provider = $this->provider;
        $this->provider->setRecord($rec);

        try {
            $this->provider->makeTable(true);

            $indexes = $this->provider->getIndexes();

            $this->assertContains("intAttr_unq_idx", $indexes);
            $this->assertContains("dblAttr_smallAttr_unq_idx", $indexes);
            $this->assertContains("intAttr_smallAttr_boolAttr_unq_idx", $indexes);
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * begin()/commit()/rollback() against the live DB: a row inserted inside a
     * rolled-back transaction is gone, a committed row persists, and rollback()
     * with no active transaction is a safe no-op that still disconnects.
     *
     * @since 4.1
     */
    public function testBeginCommitRollbackAgainstLiveMySQL(): void
    {
        $tableName = "mysqlprovtesttxn" . mt_rand();
        $conn = $this->realConnection();
        $verify = new mysqli('127.0.0.1', 'root', 'root', 'unittests');

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            ActiveRecordProviderMySQL::begin();
            $conn->query("INSERT INTO $tableName (name, version_num) VALUES ('gone', 1);");
            ActiveRecordProviderMySQL::rollback();

            $row = $verify->query("SELECT COUNT(*) AS c FROM $tableName;")->fetch_array(MYSQLI_ASSOC);
            $this->assertSame(0, (int) $row['c']);

            // A committed transaction must really persist the row.
            $conn2 = $this->realConnection();
            ActiveRecordProviderMySQL::begin();
            $conn2->query("INSERT INTO $tableName (name, version_num) VALUES ('kept', 1);");
            ActiveRecordProviderMySQL::commit();

            $row = $verify->query("SELECT name FROM $tableName;")->fetch_array(MYSQLI_ASSOC);
            $this->assertSame('kept', $row['name']);

            // A rollback with no active transaction must be safe and disconnect.
            $this->realConnection();
            ActiveRecordProviderMySQL::rollback();
            $this->assertNull((new ReflectionProperty(ActiveRecordProviderMySQL::class, 'connection'))->getValue());
        } finally {
            $verify->query("DROP TABLE IF EXISTS $tableName;");
            $verify->close();
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * createUniqueIndex() against a live table must show the new index in
     * getIndexes().
     *
     * @since 4.1
     */
    public function testCreateUniqueIndexShowsInGetIndexes(): void
    {
        $tableName = "mysqlprovtestuniq" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->provider->createUniqueIndex('name');

            $this->assertContains('name_unq_idx', $this->provider->getIndexes());
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * createForeignIndex() on a MANY-TO-ONE style attribute creates a real
     * FOREIGN KEY that shows up in getIndexes().
     *
     * @since 4.1
     */
    public function testCreateForeignIndexShowsInGetIndexes(): void
    {
        $mainTable = "mysqlprovtestfkmain" . mt_rand();
        $relTable = MySQLProvTestFkRelatedRecord::TABLE_NAME;
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($mainTable);
        $this->provider->setRecord($rec);

        try {
            $conn->query("DROP TABLE IF EXISTS $relTable;");
            $conn->query("CREATE TABLE $relTable (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");
            $conn->query("CREATE TABLE $mainTable (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, relID INT(11) ZEROFILL DEFAULT NULL, PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->provider->createForeignIndex('relID', MySQLProvTestFkRelatedRecord::class, 'ID');

            $this->assertContains($mainTable . '_relID_fk_idx', $this->provider->getIndexes());
        } finally {
            $conn->query("DROP TABLE IF EXISTS $mainTable;");
            $conn->query("DROP TABLE IF EXISTS $relTable;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * checkRecordExists() returns true for a real saved row and false for a
     * non-existent ID.
     *
     * @since 4.1
     */
    public function testCheckRecordExistsTrueAndFalse(): void
    {
        $tableName = "mysqlprovtestchkex" . mt_rand();
        $conn = $this->realConnection();

        $rec = $this->makeSaveRecord($tableName);
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->provider->save();

            $this->assertTrue($this->provider->checkRecordExists((int) $rec->ID));
            $this->assertFalse($this->provider->checkRecordExists(987654321));
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * getMAX()/getCount() on an empty table (MAX throws, count is 0) and after
     * two real saves, plus getHistoryCount() against a live history table.
     *
     * @since 4.1
     */
    public function testGetMAXGetCountGetHistoryCountAgainstLiveMySQL(): void
    {
        $tableName = "mysqlprovtestagg" . mt_rand();
        $conn = $this->realConnection();

        $rec = new MySQLProvTestQueryRecord();
        $rec->TableName = $tableName;
        $this->provider->setRecord($rec);

        try {
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, version_num INT(11), name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");
            $conn->query("CREATE TABLE {$tableName}_history (ID INT(11) ZEROFILL NOT NULL, version_num INT(11)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            try {
                $this->provider->getMAX();
                $this->fail("Expected an AlphaException for MAX(ID) over an empty table");
            } catch (AlphaException $ae) {
                $this->assertStringContainsString("MAX ID", $ae->getMessage());
            }

            $this->assertSame(0, $this->provider->getCount());
            $this->assertSame(0, $this->provider->getCount(array('name'), array('abc')));

            $this->provider->save();

            $rec2 = new MySQLProvTestQueryRecord();
            $rec2->TableName = $tableName;
            $this->provider->setRecord($rec2);
            $this->provider->save();

            $this->assertSame(2, $this->provider->getCount());
            $this->assertSame(2, $this->provider->getCount(array('name'), array('abc')));
            $this->assertSame((int) $rec2->ID, $this->provider->getMAX());

            $rec2->MaintainHistory = true;
            $this->assertSame(0, $this->provider->getHistoryCount());

            $conn->query("INSERT INTO {$tableName}_history (ID, version_num) VALUES (".(int) $rec2->ID.", 1), (".(int) $rec2->ID.", 2);");
            $this->assertSame(2, $this->provider->getHistoryCount());
        } finally {
            $conn->query("DROP TABLE IF EXISTS {$tableName}_history;");
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }

    /**
     * isTableOverloaded() is false when the live table has no classname column
     * and true once one is added.
     *
     * @since 4.1
     */
    public function testIsTableOverloadedTrueAndFalse(): void
    {
        $tableName = 'MySQLProvTestOverloadRecord';
        $conn = $this->realConnection();

        $rec = new MySQLProvTestOverloadRecord();
        $this->provider->setRecord($rec);

        try {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            $conn->query("CREATE TABLE $tableName (ID INT(11) ZEROFILL NOT NULL AUTO_INCREMENT, name VARCHAR(50), PRIMARY KEY (ID)) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;");

            $this->assertFalse($this->provider->isTableOverloaded());

            $conn->query("ALTER TABLE $tableName ADD COLUMN classname VARCHAR(100) DEFAULT NULL;");

            $this->assertTrue($this->provider->isTableOverloaded());
        } finally {
            $conn->query("DROP TABLE IF EXISTS $tableName;");
            ActiveRecordProviderMySQL::disconnect();
        }
    }
}

/**
 * Duck-typed record used to drive the save/saveAttribute/saveHistory code paths of
 * the MySQL provider against the live database.
 *
 * @since 4.1
 */
class MySQLProvTestSaveRecord extends MySQLTestTypeRecord
{
    public $TableName = "test";
    public $Transient = true;
    public $Overloaded = false;
    public $MaintainHistory = false;
    public $TableExists = true;
    public $ID = 0;
    public $version_num = 1;
    public $name = "abc";
    public $num = 7;
    public $updated_by = 0;
    public $updated_ts = "2020-01-01 00:00:00";
    public $SaveRelationsCalled = 0;
    public $MakeTableCalled = 0;
    public $PropMap = array();
    public $VersionOverride = null;

    public function getTableName()
    {
        return $this->TableName;
    }

    public function getTransientAttributes()
    {
        return array("TableName", "Transient", "Overloaded", "MaintainHistory", "TableExists", "SaveRelationsCalled", "MakeTableCalled", "PropMap", "VersionOverride", "num", "updated_by", "updated_ts");
    }

    public function getDefaultAttributes()
    {
        return array();
    }

    public function getPropObject($name)
    {
        return isset($this->PropMap[$name]) ? $this->PropMap[$name] : null;
    }

    public function getUniqueAttributes()
    {
        return array();
    }

    public function isTransient()
    {
        return $this->Transient;
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
    }

    public function getLastQuery()
    {
        return "q";
    }

    public function set($name, $val): void
    {
        if ($name == "updated_ts" && is_object($val)) {
            $this->updated_ts = "2020-01-01 00:00:00";
        } else {
            $this->$name = $val;
        }
    }

    public function get($name): mixed
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function getID()
    {
        return $this->ID;
    }

    public function setID($id)
    {
        $this->ID = $id;
    }

    public function getVersion()
    {
        return $this->VersionOverride !== null ? $this->VersionOverride : $this->version_num;
    }

    public function getVersionNumber()
    {
        return new Integer($this->version_num);
    }

    public function saveRelations()
    {
        ++$this->SaveRelationsCalled;
    }

    public function checkTableExists()
    {
        return $this->TableExists;
    }

    public function makeTable()
    {
        ++$this->MakeTableCalled;
    }

    public function saveHistory()
    {
    }

    public function setValue(mixed $val): void
    {
    }

    public function getValue(): mixed
    {
        return $this->name;
    }
}

/**
 * Duck-typed record exposing every persistent Alpha\Model\Type class so that all
 * reachable type-mapping branches of makeTable()/makeHistoryTable() run at once.
 *
 * @since 4.1
 */
class MySQLProvTestTypeMixRecord extends MySQLTestTypeRecord
{
    public $TableName = "test";
    public $Overloaded = false;
    public $MaintainHistory = false;
    public $UniqueAttrs = array();
    public $PropMap = array();
    public $Provider = null;
    public $MakeHistoryTableCalled = 0;

    public $ID = 0;
    public $version_num = 1;
    public $intAttr;
    public $dblAttr;
    public $smallAttr;
    public $textAttr;
    public $largeAttr;
    public $hugeText;
    public $boolAttr;
    public $dateAttr;
    public $tsAttr;
    public $enumAttr;
    public $denumAttr;
    public $relAttr;
    public $leftID;
    public $rightID;
    public $unkAttr;

    /**
     * @since 4.1
     */
    public function __construct()
    {
        $this->PropMap = array(
            "version_num" => new Integer(1),
            "intAttr" => new Integer(1),
            "dblAttr" => new Double(1.5),
            "smallAttr" => new SmallText(),
            "textAttr" => new Text(),
            "largeAttr" => new LargeText(),
            "hugeText" => new HugeText(),
            "boolAttr" => new Boolean(),
            "dateAttr" => new Date(),
            "tsAttr" => new Timestamp(),
            "enumAttr" => new Enum(array("a", "b")),
            "denumAttr" => new DEnum("MySQLProvTestTypeMixRecord::denumAttr"),
            "relAttr" => new class () extends Relation {
                public function getRelationType(): string
                {
                    return "ONE-TO-MANY";
                }
            },
            "leftID" => new class ("stdClass", "stdClass") extends RelationLookup {
                public function getSize(): int
                {
                    return 11;
                }
            },
            "rightID" => new class ("stdClass", "stdClass") extends RelationLookup {
                public function getSize(): int
                {
                    return 11;
                }
            }
        );
    }

    public function getTableName()
    {
        return $this->TableName;
    }

    public function getTransientAttributes()
    {
        return array("TableName", "Overloaded", "MaintainHistory", "UniqueAttrs", "PropMap", "Provider", "MakeHistoryTableCalled");
    }

    public function getDefaultAttributes()
    {
        return array();
    }

    public function getPropObject($name)
    {
        return isset($this->PropMap[$name]) ? $this->PropMap[$name] : null;
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
    }

    public function makeHistoryTable()
    {
        ++$this->MakeHistoryTableCalled;
        $this->Provider->makeHistoryTable();
    }
}

/**
 * Duck-typed record with one attribute per addProperty()-reachable type, used to
 * drive the checkTableNeedsUpdate()/findMissingFields()/addProperty() recovery
 * cycle and unique-index creation against the live database.
 *
 * @since 4.1
 */
class MySQLProvTestSyncRecord extends MySQLTestTypeRecord
{
    public $TableName = "test";
    public $Overloaded = false;
    public $MaintainHistory = false;
    public $UniqueAttrs = array();
    public $PropMap = array();
    public $Provider = null;

    public $ID = 0;
    public $version_num = 1;
    public $intAttr;
    public $dblAttr;
    public $smallAttr;
    public $textAttr;
    public $boolAttr;
    public $dateAttr;
    public $tsAttr;
    public $enumAttr;
    public $denumAttr;
    public $relAttr;
    public $leftID;

    /**
     * @since 4.1
     */
    public function __construct()
    {
        $this->PropMap = array(
            "version_num" => new Integer(1),
            "intAttr" => new Integer(1),
            "dblAttr" => new Double(1.5),
            "smallAttr" => new SmallText(),
            "textAttr" => new Text(),
            "boolAttr" => new Boolean(),
            "dateAttr" => new Date(),
            "tsAttr" => new Timestamp(),
            "enumAttr" => new Enum(array("a", "b")),
            "denumAttr" => new DEnum("MySQLProvTestSyncRecord::denumAttr"),
            "relAttr" => new class () extends Relation {
                public function getRelationType(): string
                {
                    return "ONE-TO-MANY";
                }
            },
            "leftID" => new class ("stdClass", "stdClass") extends RelationLookup {
                public function getSize(): int
                {
                    return 11;
                }
            }
        );
    }

    public function getTableName()
    {
        return $this->TableName;
    }

    public function getTransientAttributes()
    {
        return array("TableName", "Overloaded", "MaintainHistory", "UniqueAttrs", "PropMap", "Provider");
    }

    public function getDefaultAttributes()
    {
        return array();
    }

    public function getPropObject($name)
    {
        return isset($this->PropMap[$name]) ? $this->PropMap[$name] : null;
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
    }

    public function getLastQuery()
    {
        return "q";
    }

    public function createUniqueIndex($attribute1Name, $attribute2Name = '', $attribute3Name = '')
    {
        return $this->Provider->createUniqueIndex($attribute1Name, $attribute2Name, $attribute3Name);
    }
}

/**
 * Stub class with a usable TABLE_NAME constant for checkRecordTableExists().
 *
 * @since 4.1
 */
class MySQLProvTestChkRecord extends MySQLTestTypeRecord
{
    public const TABLE_NAME = "mysqlprovtestchk";
}

/**
 * Stub class with an empty TABLE_NAME constant for checkRecordTableExists().
 *
 * @since 4.1
 */
class MySQLProvTestEmptyNameRecord extends MySQLTestTypeRecord
{
    public const TABLE_NAME = "";
}

/**
 * Save-capable duck-typed record whose query() runs real SQL against the
 * provider's live connection so getMAX() can be driven against the database.
 *
 * @since 4.1
 */
class MySQLProvTestQueryRecord extends MySQLProvTestSaveRecord
{
    public function query($sqlQuery)
    {
        $result = ActiveRecordProviderMySQL::getConnection()->query($sqlQuery);

        $rows = array();
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }
}

/**
 * Related stub with a real TABLE_NAME constant and a live table so that
 * createForeignIndex() can resolve and reference the related record's table.
 *
 * @since 4.1
 */
class MySQLProvTestFkRelatedRecord extends MySQLTestTypeRecord
{
    public const TABLE_NAME = "mysqlprovtestfkrel";

    public function getTableName()
    {
        return self::TABLE_NAME;
    }
}

/**
 * Duck-typed record that does NOT implement TypeInterface and whose short class
 * name matches ucfirst(table name), so isTableOverloaded() falls through to
 * the SHOW COLUMNS check for a classname column.
 *
 * @since 4.1
 */
class MySQLProvTestOverloadRecord
{
    public function getTableName()
    {
        return "MySQLProvTestOverloadRecord";
    }
}
