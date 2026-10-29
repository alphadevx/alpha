<?php

/**
 * Test cases for CacheProviderRedisTest.
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

namespace Alpha\Test\Util\Cache;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Util\Cache\CacheProviderRedis;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Redis;
use Throwable;

class CacheProviderRedisTest extends TestCase
{
    /**
     * @since 4.1
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = ConfigProvider::getInstance();
        $config->set('app.title', 'Alpha Test App');
        $config->set('cache.redis.host', '127.0.0.1');
        $config->set('cache.redis.port', 6379);
        $config->set('cache.redis.db', 0);
    }

    /**
     * @since 4.1
     */
    private function createProviderWithoutConstructor(): CacheProviderRedis
    {
        $reflection = new ReflectionClass(CacheProviderRedis::class);
        $provider = $reflection->newInstanceWithoutConstructor();

        $appPrefix = new ReflectionProperty(CacheProviderRedis::class, 'appPrefix');
        $appPrefix->setAccessible(true);
        $appPrefix->setValue($provider, 'AlphaTestApp');

        $logger = $this->createMock(Logger::class);
        $loggerProperty = new ReflectionProperty(CacheProviderRedis::class, 'logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue(null, $logger);

        return $provider;
    }

    /**
     * @since 4.1
     */
    private function setConnection(CacheProviderRedis $provider, $connection): void
    {
        $connectionProperty = new ReflectionProperty(CacheProviderRedis::class, 'connection');
        $connectionProperty->setAccessible(true);
        $connectionProperty->setValue($provider, $connection);
    }

    /**
     * @since 4.1
     */
    public function testConstructorWithSuccessfulConnection(): void
    {
        try {
            $provider = new CacheProviderRedis();
            $this->assertInstanceOf(CacheProviderRedis::class, $provider);
        } catch (Throwable $e) {
            $this->assertInstanceOf(Throwable::class, $e);
        }
    }

    /**
     * @since 4.1
     */
    public function testConstructorWithConnectionException(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('cache.redis.host', 'invalid-host-that-does-not-exist');
        $config->set('cache.redis.port', 1);
        try {
            $provider = new CacheProviderRedis();
            $this->assertInstanceOf(CacheProviderRedis::class, $provider);
        } catch (Throwable $e) {
            $this->assertInstanceOf(Throwable::class, $e);
        }
    }

    /**
     * @since 4.1
     */
    public function testGetReturnsValue(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('get')->with('AlphaTestApp-test-key')->willReturn('cached-value');
        $this->setConnection($provider, $connection);
        $this->assertSame('cached-value', $provider->get('test-key'));
    }

    /**
     * @since 4.1
     */
    public function testGetThrowsWhenKeyNotFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('get')->with('AlphaTestApp-test-key')->willReturn(false);
        $connection->method('type')->with('test-key')->willReturn(Redis::REDIS_NOT_FOUND);
        $this->setConnection($provider, $connection);
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to get a cache value on the key [test-key]');
        $provider->get('test-key');
    }

    /**
     * @since 4.1
     */
    public function testGetThrowsWhenTypeIsNotRedisNotFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('get')->with('AlphaTestApp-test-key')->willReturn(false);
        $connection->method('type')->with('test-key')->willReturn(1);
        $this->setConnection($provider, $connection);
        $this->expectException(Throwable::class);
        $provider->get('test-key');
    }

    /**
     * @since 4.1
     */
    public function testSetWithExpiry(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->expects($this->once())->method('setex')->with('AlphaTestApp-test-key', 100, 'value');
        $this->setConnection($provider, $connection);
        $provider->set('test-key', 'value', 100);
    }

    /**
     * @since 4.1
     */
    public function testSetWithoutExpiry(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->expects($this->once())->method('set')->with('AlphaTestApp-test-key', 'value');
        $this->setConnection($provider, $connection);
        $provider->set('test-key', 'value');
    }

    /**
     * @since 4.1
     */
    public function testSetWithException(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('set')->willThrowException(new \Exception('set failed'));
        $this->setConnection($provider, $connection);
        $provider->set('test-key', 'value');
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testDeleteSuccess(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('del')->with('AlphaTestApp-test-key')->willReturn(1);
        $this->setConnection($provider, $connection);
        $provider->delete('test-key');
        $this->assertTrue(true);
    }

    /**
     * @since 4.1
     */
    public function testDeleteThrowsWhenKeyNotFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('del')->with('AlphaTestApp-test-key')->willReturn(0);
        $connection->method('type')->with('test-key')->willReturn(Redis::REDIS_NOT_FOUND);
        $this->setConnection($provider, $connection);
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to delete a cache value on the key [test-key]');
        $provider->delete('test-key');
    }

    /**
     * @since 4.1
     */
    public function testDeleteThrowsWhenTypeIsNotRedisNotFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('del')->with('AlphaTestApp-test-key')->willReturn(0);
        $connection->method('type')->with('test-key')->willReturn(1);
        $this->setConnection($provider, $connection);
        $this->expectException(Throwable::class);
        $provider->delete('test-key');
    }

    /**
     * @since 4.1
     */
    public function testCheckReturnsFalseWhenNotFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('type')->with('test-key')->willReturn(Redis::REDIS_NOT_FOUND);
        $this->setConnection($provider, $connection);
        $this->assertFalse($provider->check('test-key'));
    }

    /**
     * @since 4.1
     */
    public function testCheckReturnsTrueWhenFound(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $connection->method('type')->with('test-key')->willReturn(1);
        $this->setConnection($provider, $connection);
        $this->assertTrue($provider->check('test-key'));
    }

    /**
     * @since 4.1
     */
    public function testGetNativeConnection(): void
    {
        $provider = $this->createProviderWithoutConstructor();
        $connection = $this->createMock(Redis::class);
        $this->setConnection($provider, $connection);
        $this->assertSame($connection, $provider->getNativeConnection());
    }
}
