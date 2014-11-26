<?php

namespace Alpha\Util\Cache;

use Alpha\Util\Logging\Logger;

/**
 *
 * An implementation of the CacheProviderInterface interface that uses APC/APCu as the
 * target store.
 *
 * @package alpha::util::cache
 * @since 1.2.4
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class CacheProviderAPC implements CacheProviderInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.2.4
     */
    private static $logger = null;


    /**
     * Constructor
     *
     * @since 1.2.4
     */
    public function __construct()
    {
        self::$logger = new Logger('AlphaCacheProviderAPC');
    }

    /**
     * (non-PHPdoc)
     * @see alpha/util/cache/AlphaCacheProviderInterface::get()
     * @since 1.2.4
     */
    public function get($key)
    {
        self::$logger->debug('>>get(key=['.$key.'])');

        try {
            $value = apc_fetch($key);

            self::$logger->debug('<<get: ['.print_r($value, true).'])');

            return $value;
        } catch(\Exception $e) {
            self::$logger->error('Error while attempting to load a business object from APC cache: ['.$e->getMessage().']');
            self::$logger->debug('<<get: [false])');
            return false;
        }
    }

    /**
     * (non-PHPdoc)
     * @see alpha/util/cache/AlphaCacheProviderInterface::set()
     * @since 1.2.4
     */
    public function set($key, $value, $expiry=0)
    {
        try {
            if($expiry > 0)
                apc_store($key, $value, $expiry);
            else
                apc_store($key, $value);

          } catch(\Exception $e) {
              self::$logger->error('Error while attempting to store a value to APC cache: ['.$e->getMessage().']');
          }
    }

    /**
     * (non-PHPdoc)
     * @see alpha/util/cache/AlphaCacheProviderInterface::delete()
     * @since 1.2.4
     */
    public function delete($key)
    {
        try {
            apc_delete($key);
        } catch(\Exception $e) {
            self::$logger->error('Error while attempting to remove a value from APC cache: ['.$e->getMessage().']');
        }
    }
}

?>