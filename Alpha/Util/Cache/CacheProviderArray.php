<?php

namespace Alpha\Util\Cache;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;

/**
 * An implementation of the CacheProviderInterface interface that uses an array as the
 * target store.  Only useful for unit tests.
 *
 * @since 1.2.1
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
class CacheProviderArray implements CacheProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.2.1
     */
    private static $logger = null;

    /**
     * The hash array containing the cached items.
     *
     * @var array
     *
     * @since 1.2.1
     */
    public static $cacheArray = array();

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
     * @since 1.2.1
     */
    public function __construct()
    {
        self::$logger = new Logger('CacheProviderArray');

        $config = ConfigProvider::getInstance();

        $this->appPrefix = preg_replace("/[^a-zA-Z0-9]+/", "", $config->get('app.title'));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key): mixed
    {
        self::$logger->debug('>>get(key=['.$key.'])');

        self::$logger->debug('Getting value for key ['.$key.']');

        if (array_key_exists($this->appPrefix.'-'.$key, self::$cacheArray)) {
            return self::$cacheArray[$this->appPrefix.'-'.$key];
        } else {
            throw new ResourceNotFoundException('Unable to get a cache value on the key ['.$key.']');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expiry = 0): void
    {
        self::$logger->debug('Setting value for key ['.$key.']');

        self::$cacheArray[$this->appPrefix.'-'.$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): void
    {
        self::$logger->debug('Removing value for key ['.$key.']');

        if (array_key_exists($this->appPrefix.'-'.$key, self::$cacheArray)) {
            unset(self::$cacheArray[$this->appPrefix.'-'.$key]);
        } else {
            throw new ResourceNotFoundException('Unable to delete a cache value on the key ['.$key.']');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function check($key): bool
    {
        return array_key_exists($this->appPrefix.'-'.$key, self::$cacheArray);
    }
}
