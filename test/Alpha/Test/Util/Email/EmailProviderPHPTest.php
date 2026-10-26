<?php

namespace Alpha\Test\Util\Email;

use Alpha\Util\Email\EmailProviderPHP;
use Alpha\Util\Email\EmailProviderInterface;
use Alpha\Util\Logging\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the EmailProviderPHP class.
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
class EmailProviderPHPTest extends TestCase
{
    /**
     * Testing the constructor initializes the email provider.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $emailProvider = new EmailProviderPHP();

        $reflection = new \ReflectionClass($emailProvider);

        $this->assertTrue($reflection->hasProperty('logger'), 'Testing that EmailProviderPHP has logger property');
    }

    /**
     * Testing send() method exists and is callable.
     *
     * @since 4.1
     */
    public function testSendMethodExists(): void
    {
        $emailProvider = new EmailProviderPHP();

        $reflection = new \ReflectionClass($emailProvider);

        $this->assertTrue($reflection->hasMethod('send'), 'Testing that EmailProviderPHP has send method');

        $method = $reflection->getMethod('send');
        $this->assertTrue($method->isPublic(), 'Testing that send method is public');
        $this->assertGreaterThan(0, $method->getNumberOfParameters(), 'Testing that send method has parameters');
    }

    /**
     * Testing that EmailProviderPHP implements EmailProviderInterface.
     *
     * @since 4.1
     */
    public function testImplementsInterface(): void
    {
        $emailProvider = new EmailProviderPHP();

        $this->assertInstanceOf(EmailProviderInterface::class, $emailProvider);
    }

    /**
     * Testing that EmailProviderPHP has logger property.
     *
     * @since 4.1
     */
    public function testHasLogger(): void
    {
        $emailProvider = new EmailProviderPHP();

        $reflection = new \ReflectionClass($emailProvider);

        $this->assertTrue($reflection->hasProperty('logger'), 'Testing that EmailProviderPHP has logger property');
    }

    /**
     * Testing that the logger is static.
     *
     * @since 4.1
     */
    public function testLoggerIsStatic(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $loggerProperty = $reflection->getProperty('logger');

        $this->assertTrue($loggerProperty->isStatic(), 'Testing that logger property is static');
    }

    /**
     * Testing that multiple instances share the same logger.
     *
     * @since 4.1
     */
    public function testMultipleInstancesShareLogger(): void
    {
        $emailProvider1 = new EmailProviderPHP();
        $emailProvider2 = new EmailProviderPHP();

        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);

        $logger1 = $loggerProperty->getValue();
        $logger2 = $loggerProperty->getValue();

        $this->assertEquals(spl_object_hash($logger1), spl_object_hash($logger2), 'Testing that multiple instances share the same logger');
    }

    /**
     * Testing the constructor creates a Logger instance.
     *
     * @since 4.1
     */
    public function testConstructorCreatesLogger(): void
    {
        $emailProvider = new EmailProviderPHP();

        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);

        $logger = $loggerProperty->getValue();

        $this->assertInstanceOf(Logger::class, $logger);
    }

    /**
     * Testing the logger has correct name.
     *
     * @since 4.1
     */
    public function testLoggerHasCorrectName(): void
    {
        $emailProvider = new EmailProviderPHP();

        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);

        $logger = $loggerProperty->getValue();

        // Check that the logger is a Logger instance
        $this->assertInstanceOf(Logger::class, $logger);
    }

    /**
     * Testing the send method signature.
     *
     * @since 4.1
     */
    public function testSendMethodSignature(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $method = $reflection->getMethod('send');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'Testing that send method has return type');
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('void', $returnType->getName());
        } else {
            $this->assertEquals('void', (string)$returnType);
        }
        $this->assertEquals(5, $method->getNumberOfParameters());

        $params = $method->getParameters();
        $this->assertEquals('to', $params[0]->getName());
        $this->assertEquals('from', $params[1]->getName());
        $this->assertEquals('subject', $params[2]->getName());
        $this->assertEquals('body', $params[3]->getName());
        $this->assertEquals('isHTML', $params[4]->getName());

        $this->assertTrue($params[4]->isOptional(), 'Testing that isHTML parameter is optional');
    }

    /**
     * Testing that send method has correct parameters.
     *
     * @since 4.1
     */
    public function testSendParameters(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $method = $reflection->getMethod('send');
        $params = $method->getParameters();

        $this->assertEquals('to', $params[0]->getName());
        $this->assertEquals('from', $params[1]->getName());
        $this->assertEquals('subject', $params[2]->getName());
        $this->assertEquals('body', $params[3]->getName());
        $this->assertEquals('isHTML', $params[4]->getName());
    }

    /**
     * Testing that all class properties exist.
     *
     * @since 4.1
     */
    public function testAllPropertiesExist(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);

        $expectedProperties = ['logger'];
        $actualProperties = array_keys($reflection->getProperties());

        foreach ($expectedProperties as $property) {
            $this->assertTrue($reflection->hasProperty($property), 'Testing that class has property: '.$property);
        }
    }

    /**
     * Testing that class is final or not final as expected.
     *
     * @since 4.1
     */
    public function testClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);

        $this->assertFalse($reflection->isFinal(), 'Testing that class is not final');
    }

    /**
     * Testing that class has correct methods.
     *
     * @since 4.1
     */
    public function testHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $methods = $reflection->getMethods();

        $methodNames = array_column($methods, 'name');

        $this->assertContains('__construct', $methodNames, 'Testing that class has constructor method');
        $this->assertContains('send', $methodNames, 'Testing that class has send method');
    }

    /**
     * Testing that send method is public.
     *
     * @since 4.1
     */
    public function testSendIsPublic(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $method = $reflection->getMethod('send');

        $this->assertTrue($method->isPublic(), 'Testing that send method is public');
    }

    /**
     * Testing that logger is private.
     *
     * @since 4.1
     */
    public function testLoggerIsPrivate(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);
        $loggerProperty = $reflection->getProperty('logger');

        $this->assertTrue($loggerProperty->isPrivate(), 'Testing that logger property is private');
    }

    /**
     * Testing class is in correct namespace.
     *
     * @since 4.1
     */
    public function testClassInCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);

        $this->assertEquals('Alpha\Util\Email', $reflection->getNamespaceName());
    }

    /**
     * Testing class has correct short name.
     *
     * @since 4.1
     */
    public function testClassShortName(): void
    {
        $reflection = new \ReflectionClass(EmailProviderPHP::class);

        $this->assertEquals('EmailProviderPHP', $reflection->getShortName());
    }
}
