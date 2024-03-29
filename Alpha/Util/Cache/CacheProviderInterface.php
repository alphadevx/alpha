<?php

namespace Alpha\Util\Cache;

/**
 * An interface that contains the methods for a cache implementation for storing business
 * objects and other less complex values.
 *
 * @since 1.1
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
interface CacheProviderInterface
{
    /**
     * Attempt to get the value from the cache for the given $key.
     *
     * @param $key
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     *
     * @since 1.1
     */
    public function get($key): mixed;

    /**
     * Attempt to set the value in the cache for the given $key.  Old values on the same
     * key will be overwritten.
     *
     * @param $key
     * @param $value
     * @param $expiry Optional, some cache implementations will support an expiry value in seconds.
     *
     * @since 1.1
     */
    public function set($key, $value, $expiry = 0): void;

    /**
     * Attempt to delete the value from the cache for the given $key.
     *
     * @param $key
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     *
     * @since 1.1
     */
    public function delete($key): void;

    /**
     * Check the cache for the existance of the given $key.  Returns true if the $key is set, false otherwise.
     *
     * @param $key
     *
     * @since 4.0
     */
    public function check($key): bool;
}
