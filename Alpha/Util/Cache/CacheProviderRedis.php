<?php

namespace Alpha\Util\Cache;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Redis;

/**
 * An implementation of the CacheProviderInterface interface that uses Redis as the
 * target store.
 *
 * @since 1.2.4
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
class CacheProviderRedis implements CacheProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.2.4
     */
    private static $logger = null;

    /**
     * Connection to the cache server.
     *
     * @var Redis
     *
     * @since 1.2.4
     */
    private $connection;

    /**
     * Cache key prefix to use, based on the application title, to prevent key clashes between different apps
     * using the same cache provider.
     *
     * @var string
     *
     * @since 3.0.0
     */
    private $appPrefix;

    /**
     * Constructor.
     *
     * @since 1.2.4
     */
    public function __construct()
    {
        self::$logger = new Logger('CacheProviderRedis');

        $config = ConfigProvider::getInstance();

        $this->appPrefix = preg_replace("/[^a-zA-Z0-9]+/", "", $config->get('app.title'));

        try {
            $this->connection = new Redis();
            $this->connection->connect($config->get('cache.redis.host'), $config->get('cache.redis.port'));
            $this->connection->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            $this->connection->select($config->get('cache.redis.db'));
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to connect to Redis cache: ['.$e->getMessage().']');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($key): mixed
    {
        self::$logger->debug('>>get(key=['.$key.'])');

        $value = $this->connection->get($this->appPrefix.'-'.$key);

        if ($value === false) {
            if ($this->connection->type($key) === Redis::REDIS_NOT_FOUND) {
                throw new ResourceNotFoundException('Unable to get a cache value on the key ['.$key.']');
            } else {
                self::$logger->error('Error while attempting to load a business object from Redis instance: ['.$e->getMessage().']');
                self::$logger->debug('<<get');

                throw new ResourceNotFoundException('Unable to get a cache value on the key ['.$key.']');
            }
        }

        self::$logger->debug('<<get: ['.print_r($value, true).'])');

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expiry = 0): void
    {
        try {
            if ($expiry > 0) {
                $this->connection->setex($this->appPrefix.'-'.$key, $expiry, $value);
            } else {
                $this->connection->set($this->appPrefix.'-'.$key, $value);
            }
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to store a value to Redis instance: ['.$e->getMessage().']');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): void
    {
        $count = $this->connection->del($this->appPrefix.'-'.$key);

        if ($count === 0) {
            if ($this->connection->type($key) === Redis::REDIS_NOT_FOUND) {
                throw new ResourceNotFoundException('Unable to delete a cache value on the key ['.$key.']');
            } else {
                self::$logger->error('Error while attempting to remove a value from Redis instance: ['.$e->getMessage().']');

                throw new ResourceNotFoundException('Unable to delete a cache value on the key ['.$key.']');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function check($key): bool
    {
        if ($this->connection->type($key) === Redis::REDIS_NOT_FOUND) {
            return false;
        } else {
            return true;
        }
    }
}
