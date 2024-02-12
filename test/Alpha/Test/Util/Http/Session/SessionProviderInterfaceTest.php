<?php

namespace Alpha\Test\Util\Http\Session;

use Alpha\Util\Service\ServiceFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the session providers.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2024, John Collins (founder of Alpha Framework).
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
class SessionProviderInterfaceTest extends TestCase
{
    #[DataProvider('getProviders')]
    public function testSetAndGet(\Alpha\Util\Http\Session\SessionProviderInterface $provider)
    {
        $provider->set('somekey', 'somevalue');

        $this->assertEquals('somevalue', $provider->get('somekey'), 'Testing setting and getting a value from the session');
    }

    #[DataProvider('getProviders')]
    public function testDelete(\Alpha\Util\Http\Session\SessionProviderInterface $provider)
    {
        $provider->set('somekey', 'somevalue');

        $this->assertEquals('somevalue', $provider->get('somekey'), 'Testing setting and getting a value from the session');

        $provider->delete('somekey');

        $this->assertFalse($provider->get('somekey'), 'Testing deleting a value from the session');
    }

    #[DataProvider('getProviders')]
    public function testDestroy(\Alpha\Util\Http\Session\SessionProviderInterface $provider)
    {
        $provider->set('somekey', 'somevalue');
        $provider->set('someotherkey', 'someothervalue');

        $this->assertEquals('somevalue', $provider->get('somekey'), 'Testing setting and getting a value from the session');
        $this->assertEquals('someothervalue', $provider->get('someotherkey'), 'Testing setting and getting a value from the session');

        $provider->destroy('somekey');

        $this->assertFalse($provider->get('somekey'), 'Testing destroying session');
        $this->assertFalse($provider->get('someotherkey'), 'Testing destroying session');
    }

    #[DataProvider('getProviders')]
    public function testInit(\Alpha\Util\Http\Session\SessionProviderInterface $provider)
    {
        $provider->set('itisstillthere', 'stillhere');

        $this->assertEquals('stillhere', $provider->get('itisstillthere'), 'Testing values survive re-initialization of session');

        $provider = ServiceFactory::getInstance(get_class($provider), 'Alpha\Util\Http\Session\SessionProviderInterface');

        $this->assertEquals('stillhere', $provider->get('itisstillthere'), 'Testing values survive re-initialization of session');
    }

    public static function getProviders(): array
    {
        $arrayProvider = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        //$PHPSessionProvider = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderPHP', 'Alpha\Util\Http\Session\SessionProviderInterface');

        return array(
            array($arrayProvider)
            //array($PHPSessionProvider),
        );
    }
}
