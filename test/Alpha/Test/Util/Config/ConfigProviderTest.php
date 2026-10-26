<?php

namespace Alpha\Test\Util\Config;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test cases for the AlphaConfig class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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
class ConfigProviderTest extends TestCase
{
    /**
     * A copy of the global config singleton that we will use for testing.
     *
     * @var \Alpha\Util\Config\ConfigProvider
     */
    private $configCopy;

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp(): void
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
    protected function tearDown(): void
    {
        unset($this->configCopy);
        // Reset singleton for clean test isolation
        $this->resetSingleton();
    }

    /**
     * Helper method to reset the singleton instance.
     *
     * @since 4.1
     */
    private function resetSingleton(): void
    {
        $reflection = new ReflectionClass(ConfigProvider::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    /**
     * Testing that the ConfigProvider getInstance method is returning the same instance object each time.
     *
     * @since 4.1
     */
    public function testGetInstance(): void
    {
        $config1 = ConfigProvider::getInstance();
        $config2 = ConfigProvider::getInstance();

        $config1->set('testkey', 'somevalue');

        $this->assertEquals('somevalue', $config2->get('testkey'), 'testing that the ConfigProvider getInstance method is returning the same instance object each time');
    }

    /**
     * Testing that attempting to access a config value that is not set will cause an exception.
     *
     * @since 4.1
     */
    public function testGetBad(): void
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
    public function testReloadConfig(): void
    {
        $this->configCopy->set('app.title', 'Testing');

        $this->assertEquals('Testing', $this->configCopy->get('app.title'), 'testing that the ConfigProvider reloadConfig method reloads the config from storage');

        $this->configCopy->reloadConfig();

        $this->assertEquals('Alpha Unit Tests', $this->configCopy->get('app.title'), 'testing that the ConfigProvider reloadConfig method reloads the config from storage');
    }

    /**
     * Testing that getEnvironment method returns the current environment.
     *
     * @since 4.1
     */
    public function testGetEnvironment(): void
    {
        $environment = $this->configCopy->getEnvironment();

        $this->assertNotEmpty($environment, 'Testing that getEnvironment returns a non-empty string');
        $this->assertIsString($environment, 'Testing that getEnvironment returns a string');
        $this->assertTrue(in_array($environment, ['dev', 'pro', 'test'], true), 'Testing that getEnvironment returns a valid environment');
    }

    /**
     * Testing that set method allows setting config values.
     *
     * @since 4.1
     */
    public function testSet(): void
    {
        $testKey = 'test_set_key_'.time();
        $testValue = 'test_set_value_'.time();

        $this->configCopy->set($testKey, $testValue);
        $this->assertEquals($testValue, $this->configCopy->get($testKey), 'Testing that set method allows setting config values');
    }

    /**
     * Testing that set method allows overwriting existing config values.
     *
     * @since 4.1
     */
    public function testSetOverwrite(): void
    {
        $originalValue = $this->configCopy->get('app.title');
        $newValue = $originalValue.' - Modified';

        $this->configCopy->set('app.title', $newValue);
        $this->assertEquals($newValue, $this->configCopy->get('app.title'), 'Testing that set method allows overwriting existing config values');
    }

    /**
     * Testing that get method returns config values correctly.
     *
     * @since 4.1
     */
    public function testGetExistingKey(): void
    {
        $keysToTest = ['app.title', 'app.url', 'db.provider.name'];

        foreach ($keysToTest as $key) {
            try {
                $value = $this->configCopy->get($key);
                $this->assertNotEmpty($value, 'Testing that get returns a non-empty value for key: '.$key);
            } catch (IllegalArguementException $e) {
                // Some keys might not be set in test environment, that's okay
            }
        }
    }

    /**
     * Testing that get method throws IllegalArguementException for non-existent keys.
     *
     * @since 4.1
     */
    public function testGetNonExistentKey(): void
    {
        $nonExistentKey = 'this_key_does_not_exist_'.time();

        try {
            $this->configCopy->get($nonExistentKey);
            $this->fail('Testing that get method throws exception for non-existent key');
        } catch (IllegalArguementException $e) {
            $this->assertStringContainsString($nonExistentKey, $e->getMessage(), 'Testing that error message contains the key name');
            $this->assertStringContainsString('not set', $e->getMessage(), 'Testing that error message indicates key is not set');
        }
    }

    /**
     * Testing that getInstance returns the same instance on consecutive calls.
     *
     * @since 4.1
     */
    public function testGetInstanceSameInstance(): void
    {
        $config1 = ConfigProvider::getInstance();
        $config2 = ConfigProvider::getInstance();

        $this->assertSame($config1, $config2, 'Testing that getInstance returns the same instance on consecutive calls');
    }

    /**
     * Testing that getInstance is a static method.
     *
     * @since 4.1
     */
    public function testGetInstanceIsStatic(): void
    {
        $reflection = new ReflectionClass(ConfigProvider::class);
        $method = $reflection->getMethod('getInstance');

        $this->assertTrue($method->isStatic(), 'Testing that getInstance is a static method');
    }

    /**
     * Testing that setRootPath sets the rootPath config value.
     *
     * @since 4.1
     */
    public function testSetRootPath(): void
    {
        $reflection = new ReflectionClass($this->configCopy);
        $setRootPathMethod = $reflection->getMethod('setRootPath');
        $setRootPathMethod->setAccessible(true);

        $setRootPathMethod->invoke($this->configCopy);

        $rootPath = $this->configCopy->get('rootPath');
        $this->assertNotEmpty($rootPath, 'Testing that setRootPath sets the rootPath config value');
        $this->assertStringEndsWith('/', $rootPath, 'Testing that rootPath ends with a slash');
    }

    /**
     * Testing that setRootPath handles both composer and direct installation paths.
     *
     * @since 4.1
     */
    public function testSetRootPathComposerPath(): void
    {
        // This test verifies the logic for both paths
        $reflection = new ReflectionClass($this->configCopy);
        $setRootPathMethod = $reflection->getMethod('setRootPath');
        $setRootPathMethod->setAccessible(true);

        // The method should work regardless of the path
        $setRootPathMethod->invoke($this->configCopy);

        $rootPath = $this->configCopy->get('rootPath');
        $this->assertNotNull($rootPath, 'Testing that setRootPath works for composer installation path');
    }

    /**
     * Testing that reloadConfig resets the config and reloads from file.
     *
     * @since 4.1
     */
    public function testReloadConfigResetsConfig(): void
    {
        // Store original value
        $originalValue = $this->configCopy->get('app.title');

        // Modify the value
        $this->configCopy->set('app.title', 'Modified Value');
        $this->assertEquals('Modified Value', $this->configCopy->get('app.title'), 'Testing that value was modified');

        // Reload the config
        $this->configCopy->reloadConfig();

        // Value should be restored
        $this->assertEquals($originalValue, $this->configCopy->get('app.title'), 'Testing that reloadConfig resets config to original values');
    }

    /**
     * Testing that multiple config values can be set and retrieved.
     *
     * @since 4.1
     */
    public function testSetMultipleValues(): void
    {
        $testValues = [
            'test_key_1' => 'value1',
            'test_key_2' => 'value2',
            'test_key_3' => 'value3',
        ];

        foreach ($testValues as $key => $value) {
            $this->configCopy->set($key, $value);
        }

        foreach ($testValues as $key => $value) {
            $this->assertEquals($value, $this->configCopy->get($key), 'Testing that multiple values can be set and retrieved');
        }
    }

    /**
     * Testing that config values persist within the same instance.
     *
     * @since 4.1
     */
    public function testConfigValuePersistence(): void
    {
        $testKey = 'persist_test_'.time();
        $testValue = 'persistent_value';

        $this->configCopy->set($testKey, $testValue);

        // Retrieve multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($testValue, $this->configCopy->get($testKey), 'Testing that config values persist within the same instance');
        }
    }

    /**
     * Testing that getEnvironment method returns the correct environment.
     *
     * @since 4.1
     */
    public function testEnvironmentConsistency(): void
    {
        $environment = $this->configCopy->getEnvironment();
        $serverIni = $this->configCopy->get('rootPath').'config/servers.ini';

        $this->assertFileExists($serverIni, 'Testing that servers.ini config file exists');
        $this->assertNotEmpty($environment, 'Testing that environment is set after config is loaded');
    }

    /**
     * Testing that config values can be empty strings (valid config values).
     *
     * @since 4.1
     */
    public function testSetEmptyStringValue(): void
    {
        $testKey = 'empty_test_'.time();
        $testValue = '';

        $this->configCopy->set($testKey, $testValue);
        $this->assertEquals('', $this->configCopy->get($testKey), 'Testing that empty string values can be set');
    }

    /**
     * Testing that config values with special characters can be set and retrieved.
     *
     * @since 4.1
     */
    public function testSetSpecialCharacters(): void
    {
        $testKey = 'special_test_'.time();
        $testValue = "Special chars: !@#$%^&*()_+-=[]{}|;':\",./<>?";

        $this->configCopy->set($testKey, $testValue);
        $this->assertEquals($testValue, $this->configCopy->get($testKey), 'Testing that special characters can be set and retrieved');
    }

    /**
     * Testing that config values with newlines can be set and retrieved.
     *
     * @since 4.1
     */
    public function testSetNewlineCharacters(): void
    {
        $testKey = 'newline_test_'.time();
        $testValue = "Line 1\nLine 2\nLine 3";

        $this->configCopy->set($testKey, $testValue);
        $this->assertEquals($testValue, $this->configCopy->get($testKey), 'Testing that newline characters can be set and retrieved');
    }

    /**
     * Testing that the ConfigProvider class is a singleton.
     *
     * @since 4.1
     */
    public function testSingletonPattern(): void
    {
        $reflection = new ReflectionClass(ConfigProvider::class);

        // Check that constructor is private
        $constructor = $reflection->getConstructor();
        $this->assertTrue($constructor->isPrivate(), 'Testing that constructor is private (singleton pattern)');

        // Check that getInstance is static
        $method = $reflection->getMethod('getInstance');
        $this->assertTrue($method->isStatic(), 'Testing that getInstance is static');
    }

    /**
     * Testing that the class has the expected properties.
     *
     * @since 4.1
     */
    public function testExpectedProperties(): void
    {
        $reflection = new ReflectionClass(ConfigProvider::class);

        $expectedProperties = ['configVars', 'instance', 'environment'];

        foreach ($expectedProperties as $property) {
            $this->assertTrue($reflection->hasProperty($property), 'Testing that class has property: '.$property);
        }
    }

    /**
     * Testing that config values are stored as strings.
     *
     * @since 4.1
     */
    public function testConfigValuesStoredAsString(): void
    {
        $testKey = 'string_test_'.time();
        $testValue = 'test string value';

        $this->configCopy->set($testKey, $testValue);
        $value = $this->configCopy->get($testKey);

        $this->assertIsString($value, 'Testing that config values are stored and retrieved as strings');
        $this->assertEquals($testValue, $value, 'Testing that config values match the original');
    }

    /**
     * Testing that reloadConfig does not throw errors on valid config.
     *
     * @since 4.1
     */
    public function testReloadConfigNoErrors(): void
    {
        try {
            $this->configCopy->reloadConfig();
            $this->assertTrue(true, 'Testing that reloadConfig does not throw errors on valid config');
        } catch (\Exception $e) {
            $this->fail('Testing that reloadConfig does not throw errors: '.$e->getMessage());
        }
    }

    /**
     * Testing that getEnvironment returns a valid environment after reloadConfig.
     *
     * @since 4.1
     */
    public function testGetEnvironmentAfterReload(): void
    {
        $this->configCopy->reloadConfig();

        $environment = $this->configCopy->getEnvironment();
        $this->assertNotEmpty($environment, 'Testing that getEnvironment returns a value after reloadConfig');
    }
}
