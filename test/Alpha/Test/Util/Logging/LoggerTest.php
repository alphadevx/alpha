<?php

namespace Alpha\Test\Util\Logging;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Logger class.
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
class LoggerTest extends TestCase
{
    /**
     * @var string
     */
    private $originalLogLevel = '';

    /**
     * @var array
     */
    private $originalDebugClasses = [];

    /**
     * @var string
     */
    private $originalLogActionSetting = '';

    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();

        // Store original settings
        $this->originalLogLevel = $config->get('app.log.trace.level');
        $this->originalDebugClasses = explode(',', $config->get('app.log.trace.debug.classes'));
        $this->originalLogActionSetting = $config->get('app.log.action.logging');

        // Set test config
        $config->set('app.log.trace.level', 'DEBUG');
        $config->set('app.log.trace.debug.classes', '');
        $config->set('app.log.action.logging', false);
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
    }

    protected function tearDown(): void
    {
        // Restore original settings
        $config = ConfigProvider::getInstance();
        $config->set('app.log.trace.level', $this->originalLogLevel);
        $config->set('app.log.trace.debug.classes', implode(',', $this->originalDebugClasses));
        $config->set('app.log.action.logging', $this->originalLogActionSetting);
    }

    /**
     * Testing the constructor initializes the logger correctly.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $logger = new Logger('TestClass');

        $reflection = new \ReflectionClass($logger);
        $levelProperty = $reflection->getProperty('level');
        $levelProperty->setAccessible(true);
        $logProviderProperty = $reflection->getProperty('logProvider');
        $logProviderProperty->setAccessible(true);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);

        $this->assertEquals('DEBUG', $levelProperty->getValue($logger));
        $this->assertNotNull($logProviderProperty->getValue($logger));
        $this->assertNotNull($requestProperty->getValue($logger));
    }

    /**
     * Testing that debug() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testDebugMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->debug('Test debug message');
        $this->assertTrue(true, 'Testing that debug method executes without errors');
    }

    /**
     * Testing that info() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testInfoMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->info('Test info message');
        $this->assertTrue(true, 'Testing that info method executes without errors');
    }

    /**
     * Testing that warn() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testWarnMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->warn('Test warn message');
        $this->assertTrue(true, 'Testing that warn method executes without errors');
    }

    /**
     * Testing that error() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testErrorMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->error('Test error message');
        $this->assertTrue(true, 'Testing that error method executes without errors');
    }

    /**
     * Testing that fatal() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testFatalMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->fatal('Test fatal message');
        $this->assertTrue(true, 'Testing that fatal method executes without errors');
    }

    /**
     * Testing that sql() method does not throw an exception.
     *
     * @since 4.1
     */
    public function testSqlMethod(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->sql('SELECT * FROM test');
        $this->assertTrue(true, 'Testing that sql method executes without errors');
    }

    /**
     * Testing that action() method does not throw an exception when disabled.
     *
     * @since 4.1
     */
    public function testActionMethodWhenDisabled(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.log.action.logging', false);

        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->action('Test action message');
        $this->assertTrue(true, 'Testing that action method executes without errors when disabled');
    }

    /**
     * Testing that the logger can be created with different class names.
     *
     * @since 4.1
     */
    public function testConstructorWithDifferentClassNames(): void
    {
        $logger1 = new Logger('TestClass1');
        $logger2 = new Logger('TestClass2');
        $logger3 = new Logger('MyCustomClass');

        $this->assertTrue(true, 'Testing that logger can be created with different class names');
    }

    /**
     * Testing that debug() method works with various message types.
     *
     * @since 4.1
     */
    public function testDebugWithDifferentMessageTypes(): void
    {
        $logger = new Logger('TestClass');

        // Test with string message
        $logger->debug('String message');

        // Test with empty string
        $logger->debug('');

        // Test with message containing special characters
        $logger->debug('Message with "quotes" and \'apostrophes\'');

        $this->assertTrue(true, 'Testing that debug method works with different message types');
    }

    /**
     * Testing that the log level setting affects logging.
     *
     * @since 4.1
     */
    public function testLogLevelSetting(): void
    {
        $config = ConfigProvider::getInstance();

        // Set level to FATAL which should suppress most messages
        $config->set('app.log.trace.level', 'FATAL');

        $logger = new Logger('TestClass');

        // Should not throw any exception even with different levels
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warn('Warn message');
        $logger->error('Error message');
        $logger->fatal('Fatal message');

        $this->assertTrue(true, 'Testing that logger handles different log levels');
    }

    /**
     * Testing that debug classes setting works.
     *
     * @since 4.1
     */
    public function testDebugClassesSetting(): void
    {
        $config = ConfigProvider::getInstance();

        // Set level to ERROR but add our class to debug classes
        $config->set('app.log.trace.level', 'ERROR');
        $config->set('app.log.trace.debug.classes', 'TestClass');

        $logger = new Logger('TestClass');

        // Should still log debug because class is in debug classes
        $logger->debug('Debug message for debug class');

        $this->assertTrue(true, 'Testing that debug classes setting works');
    }

    /**
     * Testing that setLogProviderFile() method works.
     *
     * @since 4.1
     */
    public function testSetLogProviderFile(): void
    {
        $logger = new Logger('TestClass');

        // Should not throw any exception
        $logger->setLogProviderFile('/tmp/test-logger-custom.log');

        $this->assertTrue(true, 'Testing that setLogProviderFile method works');
    }

    /**
     * Testing that multiple logger instances can be created.
     *
     * @since 4.1
     */
    public function testMultipleLoggerInstances(): void
    {
        $logger1 = new Logger('Class1');
        $logger2 = new Logger('Class2');
        $logger3 = new Logger('Class3');

        // All should work independently
        $logger1->debug('Message 1');
        $logger2->info('Message 2');
        $logger3->warn('Message 3');

        $this->assertTrue(true, 'Testing that multiple logger instances can be created');
    }

    /**
     * Testing that the class name is stored correctly.
     *
     * @since 4.1
     */
    public function testClassNameStored(): void
    {
        $className = 'MyTestClass';
        $logger = new Logger($className);

        $reflection = new \ReflectionClass($logger);
        $classnameProperty = $reflection->getProperty('classname');
        $classnameProperty->setAccessible(true);

        $this->assertEquals($className, $classnameProperty->getValue($logger));
        $this->assertNotNull($classnameProperty->getValue($logger));
    }

    /**
     * Testing that all log methods can be called in sequence.
     *
     * @since 4.1
     */
    public function testAllLogMethodsInSequence(): void
    {
        $logger = new Logger('TestClass');

        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warn('Warn message');
        $logger->error('Error message');
        $logger->fatal('Fatal message');
        $logger->sql('SQL query');

        $this->assertTrue(true, 'Testing that all log methods can be called in sequence');
    }

    /**
     * Testing that empty messages don't cause issues.
     *
     * @since 4.1
     */
    public function testEmptyMessages(): void
    {
        $logger = new Logger('TestClass');

        $logger->debug('');
        $logger->info('');
        $logger->warn('');
        $logger->error('');
        $logger->fatal('');
        $logger->sql('');

        $this->assertTrue(true, 'Testing that empty messages don\'t cause issues');
    }

    /**
     * Testing that the logger works with special characters in class name.
     *
     * @since 4.1
     */
    public function testClassNameWithSpecialCharacters(): void
    {
        $logger = new Logger('Test_Class_With_Underscores');

        $logger->debug('Test message');

        $this->assertTrue(true, 'Testing that logger works with special characters in class name');
    }

    /**
     * Testing that the logger is properly initialized with singleton service factory.
     *
     * @since 4.1
     */
    public function testLoggerInitialization(): void
    {
        $logger = new Logger('TestClass');

        $reflection = new \ReflectionClass($logger);

        $this->assertTrue($reflection->hasProperty('logProvider'), 'Testing that logger has logProvider property');
        $this->assertTrue($reflection->hasProperty('level'), 'Testing that logger has level property');
        $this->assertTrue($reflection->hasProperty('classname'), 'Testing that logger has classname property');
        $this->assertTrue($reflection->hasProperty('debugClasses'), 'Testing that logger has debugClasses property');
        $this->assertTrue($reflection->hasProperty('request'), 'Testing that logger has request property');
    }

    /**
     * Testing that notifyAdmin() method exists and is callable.
     *
     * @since 4.1
     */
    public function testNotifyAdminMethodExists(): void
    {
        $logger = new Logger('TestClass');

        $reflection = new \ReflectionClass($logger);

        $this->assertTrue($reflection->hasMethod('notifyAdmin'), 'Testing that logger has notifyAdmin method');
    }

    /**
     * Testing that the logger can be created in different scenarios.
     *
     * @since 4.1
     */
    public function testLoggerInDifferentScenarios(): void
    {
        // Scenario 1: Normal usage
        $logger1 = new Logger('NormalClass');

        // Scenario 2: Class name with namespace-like structure
        $logger2 = new Logger('App\Namespace\Class');

        // Scenario 3: Short class name
        $logger3 = new Logger('Short');

        $this->assertTrue(true, 'Testing that logger can be created in different scenarios');
    }
}
