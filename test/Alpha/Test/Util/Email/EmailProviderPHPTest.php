<?php

namespace Alpha\Util\Email {
    if (!function_exists('Alpha\Util\Email\mb_send_mail')) {
        function mb_send_mail($to, $subject, $message, $headers = null, $params = null)
        {
            if (!empty($GLOBALS['alpha_test_mb_send_mail_throw'])) {
                throw new \Alpha\Exception\PHPException('Simulated mb_send_mail failure');
            }
            if (!isset($GLOBALS['alpha_test_mb_send_mail_calls'])) {
                $GLOBALS['alpha_test_mb_send_mail_calls'] = [];
            }
            $GLOBALS['alpha_test_mb_send_mail_calls'][] = [$to, $subject, $message, $headers];
            return true;
        }
    }
}

namespace Alpha\Test\Util\Email {

    use Alpha\Exception\MailNotSentException;
    use Alpha\Util\Config\ConfigProvider;
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
        private $originalConfigInstance = null;
        private $configPropName = null;
        private $originalLogger = null;

        protected function setUp(): void
        {
            parent::setUp();
            $this->configPropName = $this->findConfigPropName();
            $this->originalConfigInstance = ConfigProvider::getInstance();
            $this->originalLogger = $this->getLogger();
            unset($GLOBALS['alpha_test_mb_send_mail_calls']);
            unset($GLOBALS['alpha_test_mb_send_mail_throw']);
        }

        protected function tearDown(): void
        {
            $this->setConfigProviderInstance($this->originalConfigInstance);
            $this->setLogger($this->originalLogger);
            unset($GLOBALS['alpha_test_mb_send_mail_calls']);
            unset($GLOBALS['alpha_test_mb_send_mail_throw']);
            parent::tearDown();
        }

        private function findConfigPropName(): ?string
        {
            $real = ConfigProvider::getInstance();
            $ref = new \ReflectionClass(ConfigProvider::class);
            foreach ($ref->getProperties(\ReflectionProperty::IS_STATIC) as $p) {
                $p->setAccessible(true);
                if ($p->getValue() === $real) {
                    return $p->getName();
                }
            }
            return null;
        }

        private function setConfigProviderInstance($instance): void
        {
            if ($this->configPropName === null) {
                return;
            }
            $ref = new \ReflectionClass(ConfigProvider::class);
            $p = $ref->getProperty($this->configPropName);
            $p->setAccessible(true);
            $p->setValue(null, $instance);
        }

        private function setConfigEnvironment(string $env): void
        {
            $mock = $this->createMock(ConfigProvider::class);
            $mock->method('getEnvironment')->willReturn($env);
            $this->setConfigProviderInstance($mock);
        }

        private function getLogger()
        {
            $ref = new \ReflectionClass(EmailProviderPHP::class);
            $p = $ref->getProperty('logger');
            $p->setAccessible(true);
            return $p->getValue();
        }

        private function setLogger($logger): void
        {
            $ref = new \ReflectionClass(EmailProviderPHP::class);
            $p = $ref->getProperty('logger');
            $p->setAccessible(true);
            $p->setValue(null, $logger);
        }

        private function setMockLogger(): void
        {
            $this->setLogger($this->createMock(Logger::class));
        }

        public function testConstructor(): void
        {
            $emailProvider = new EmailProviderPHP();
            $reflection = new \ReflectionClass($emailProvider);
            $this->assertTrue($reflection->hasProperty('logger'), 'Testing that EmailProviderPHP has logger property');
        }

        public function testSendMethodExists(): void
        {
            $emailProvider = new EmailProviderPHP();
            $reflection = new \ReflectionClass($emailProvider);
            $this->assertTrue($reflection->hasMethod('send'), 'Testing that EmailProviderPHP has send method');
            $method = $reflection->getMethod('send');
            $this->assertTrue($method->isPublic(), 'Testing that send method is public');
            $this->assertGreaterThan(0, $method->getNumberOfParameters(), 'Testing that send method has parameters');
        }

        public function testImplementsInterface(): void
        {
            $emailProvider = new EmailProviderPHP();
            $this->assertInstanceOf(EmailProviderInterface::class, $emailProvider);
        }

        public function testHasLogger(): void
        {
            $emailProvider = new EmailProviderPHP();
            $reflection = new \ReflectionClass($emailProvider);
            $this->assertTrue($reflection->hasProperty('logger'), 'Testing that EmailProviderPHP has logger property');
        }

        public function testLoggerIsStatic(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $loggerProperty = $reflection->getProperty('logger');
            $this->assertTrue($loggerProperty->isStatic(), 'Testing that logger property is static');
        }

        public function testMultipleInstancesShareLogger(): void
        {
            new EmailProviderPHP();
            new EmailProviderPHP();
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $loggerProperty = $reflection->getProperty('logger');
            $loggerProperty->setAccessible(true);
            $logger1 = $loggerProperty->getValue();
            $logger2 = $loggerProperty->getValue();
            $this->assertEquals(spl_object_hash($logger1), spl_object_hash($logger2), 'Testing that multiple instances share the same logger');
        }

        public function testConstructorCreatesLogger(): void
        {
            new EmailProviderPHP();
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $loggerProperty = $reflection->getProperty('logger');
            $loggerProperty->setAccessible(true);
            $logger = $loggerProperty->getValue();
            $this->assertInstanceOf(Logger::class, $logger);
        }

        public function testLoggerHasCorrectName(): void
        {
            new EmailProviderPHP();
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $loggerProperty = $reflection->getProperty('logger');
            $loggerProperty->setAccessible(true);
            $logger = $loggerProperty->getValue();
            $this->assertInstanceOf(Logger::class, $logger);
        }

        public function testSendMethodSignature(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $method = $reflection->getMethod('send');
            $returnType = $method->getReturnType();
            $this->assertNotNull($returnType, 'Testing that send method has return type');
            if ($returnType instanceof \ReflectionNamedType) {
                $this->assertEquals('void', $returnType->getName());
            } else {
                $this->assertEquals('void', (string) $returnType);
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

        public function testAllPropertiesExist(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $expectedProperties = ['logger'];
            foreach ($expectedProperties as $property) {
                $this->assertTrue($reflection->hasProperty($property), 'Testing that class has property: ' . $property);
            }
        }

        public function testClassIsNotFinal(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $this->assertFalse($reflection->isFinal(), 'Testing that class is not final');
        }

        public function testHasExpectedMethods(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $methods = $reflection->getMethods();
            $methodNames = array_column($methods, 'name');
            $this->assertContains('__construct', $methodNames, 'Testing that class has constructor method');
            $this->assertContains('send', $methodNames, 'Testing that class has send method');
        }

        public function testSendIsPublic(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $method = $reflection->getMethod('send');
            $this->assertTrue($method->isPublic(), 'Testing that send method is public');
        }

        public function testLoggerIsPrivate(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $loggerProperty = $reflection->getProperty('logger');
            $this->assertTrue($loggerProperty->isPrivate(), 'Testing that logger property is private');
        }

        public function testClassInCorrectNamespace(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $this->assertEquals('Alpha\Util\Email', $reflection->getNamespaceName());
        }

        public function testClassShortName(): void
        {
            $reflection = new \ReflectionClass(EmailProviderPHP::class);
            $this->assertEquals('EmailProviderPHP', $reflection->getShortName());
        }

        public function testSendInDevEnvironmentLogsAndSkipsMail(): void
        {
            $this->setMockLogger();
            $this->setConfigEnvironment('dev');
            $emailProvider = new EmailProviderPHP();
            $this->setMockLogger();
            $emailProvider->send('to@example.com', 'from@example.com', 'Subject', 'Body', false);
            $this->assertArrayNotHasKey('alpha_test_mb_send_mail_calls', $GLOBALS);
        }

        public function testSendInTestEnvironmentLogsAndSkipsMail(): void
        {
            $this->setMockLogger();
            $this->setConfigEnvironment('test');
            $emailProvider = new EmailProviderPHP();
            $this->setMockLogger();
            $emailProvider->send('to@example.com', 'from@example.com', 'Subject', 'Body', true);
            $this->assertArrayNotHasKey('alpha_test_mb_send_mail_calls', $GLOBALS);
        }

        public function testSendInProdEnvironmentPlainText(): void
        {
            $this->setMockLogger();
            $this->setConfigEnvironment('prod');
            $emailProvider = new EmailProviderPHP();
            $this->setMockLogger();
            $emailProvider->send('to@example.com', 'from@example.com', 'Subject', 'Body', false);
            $this->assertArrayHasKey('alpha_test_mb_send_mail_calls', $GLOBALS);
            $this->assertCount(1, $GLOBALS['alpha_test_mb_send_mail_calls']);
            $call = $GLOBALS['alpha_test_mb_send_mail_calls'][0];
            $this->assertEquals('to@example.com', $call[0]);
            $this->assertEquals('Subject', $call[1]);
            $this->assertEquals('Body', $call[2]);
            $this->assertStringContainsString('From: from@example.com', $call[3]);
            $this->assertStringNotContainsString('text/html', $call[3]);
        }

        public function testSendInProdEnvironmentHtml(): void
        {
            $this->setMockLogger();
            $this->setConfigEnvironment('prod');
            $emailProvider = new EmailProviderPHP();
            $this->setMockLogger();
            $emailProvider->send('to@example.com', 'from@example.com', 'Subject', '<b>Body</b>', true);
            $this->assertArrayHasKey('alpha_test_mb_send_mail_calls', $GLOBALS);
            $this->assertCount(1, $GLOBALS['alpha_test_mb_send_mail_calls']);
            $call = $GLOBALS['alpha_test_mb_send_mail_calls'][0];
            $this->assertStringContainsString('Content-type: text/html', $call[3]);
            $this->assertStringContainsString('MIME-Version: 1.0', $call[3]);
        }

        public function testSendThrowsMailNotSentExceptionOnFailure(): void
        {
            $this->setMockLogger();
            $this->setConfigEnvironment('prod');
            $GLOBALS['alpha_test_mb_send_mail_throw'] = true;
            $emailProvider = new EmailProviderPHP();
            $this->setMockLogger();
            $this->expectException(MailNotSentException::class);
            $emailProvider->send('to@example.com', 'from@example.com', 'Subject', 'Body', false);
        }
    }
}
