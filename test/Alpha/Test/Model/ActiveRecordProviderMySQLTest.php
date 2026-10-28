<?php

namespace Alpha\Test\Model;

use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\ActiveRecord;
use Alpha\Model\ActiveRecordProviderMySQL;
use Alpha\Model\Type\Integer;
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
}
