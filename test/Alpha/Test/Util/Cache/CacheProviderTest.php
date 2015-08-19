<?php

namespace Alpha\Test\Util\Cache;

use Alpha\Util\Cache\CacheProviderFactory;

/**
 *
 * Test cases for the CacheProviderInterface implementations
 *
 * @since 2.0
 * @author John Collins <dev@alphaframework.org>
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
class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns an array of cache providers
     *
     * @return array
     * @since 2.0
     */
    public function getCacheProviders()
    {
        return array(
            array('Alpha\Util\Cache\CacheProviderArray'),
            array('Alpha\Util\Cache\CacheProviderMemcache')
        );
    }

    /**
     * Testing the set()/get()/delete() methods
     *
     * @since 2.0
     * @dataProvider getCacheProviders
     */
    public function testSetGetDelete($provider)
    {
        $cache = CacheProviderFactory::getInstance($provider);

        $cached = array('value' => 5);

        $cache->set('cached', $cached);

        $this->assertEquals(5, $cache->get('cached')['value'], 'Testing the set/get methods');

        $cached = array('value' => 'five');

        $cache->set('cached', $cached);

        $this->assertEquals('five', $cache->get('cached')['value'], 'Testing the set/get methods');

        $cache->delete('cached');

        $this->assertFalse($cache->get('cached'), 'Testing the delete method');
    }
}

?>