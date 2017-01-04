<?php

namespace Alpha\Test\Util\Config;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\IllegalArguementException;

/**
 * Test cases for the AlphaConfig class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A copy of the global config singleton that we will use for testing.
     *
     * @var Alpha\Util\Config\ConfigProvider
     */
    private $configCopy;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp()
    {
        $config = ConfigProvider::getInstance();

        $this->configCopy = clone $config;
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function tearDown()
    {
        unset($this->configCopy);
    }

    /**
     * Testing that the ConfigProvider getInstance method is returning the same instance object each time.
     *
     * @since 1.0
     */
    public function testGetInstance()
    {
        $config1 = ConfigProvider::getInstance();
        $config2 = ConfigProvider::getInstance();

        $config1->set('testkey', 'somevalue');

        $this->assertEquals('somevalue', $config2->get('testkey'), 'testing that the ConfigProvider getInstance method is returning the same instance object each time');
    }

    /**
     * Testing that attempting to access a config value that is not set will cause an exception.
     *
     * @since 1.0
     */
    public function testGetBad()
    {
        try {
            $this->configCopy->get('keyDoesNotExist');
            $this->fail('Testing that attempting to access a config value that is not set will cause an exception');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The config property [keyDoesNotExist] is not set in the .ini config file', $e->getMessage(), 'Testing that attempting to access a config value that is not set will cause an exception');
        }
    }

    /**
     * Testing that the ConfigProvider reloadConfig method reloads the config from storage
     *
     * @since 2.0.1
     */
    public function testReloadConfig()
    {
        $this->configCopy->set('app.title', 'Testing');

        $this->assertEquals('Testing', $this->configCopy->get('app.title'), 'testing that the ConfigProvider reloadConfig method reloads the config from storage');

        $this->configCopy->reloadConfig();

        $this->assertEquals('Alpha Unit Tests', $this->configCopy->get('app.title'), 'testing that the ConfigProvider reloadConfig method reloads the config from storage');
    }
}
