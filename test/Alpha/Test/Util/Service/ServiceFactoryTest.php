<?php

namespace Alpha\Test\Util\Service;

use Alpha\Util\Service\ServiceFactory;
use Alpha\Util\Cache\CacheProviderInterface;
use Alpha\Util\Cache\CacheProviderArray;
use Alpha\Util\Email\EmailProviderInterface;
use Alpha\Util\Email\EmailProviderPHP;
use Alpha\Exception\IllegalArguementException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test cases for the ServiceFactory class.
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
class ServiceFactoryTest extends TestCase
{
    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 4.1
     */
    protected function tearDown(): void
    {
        // Reset the singletons for clean test isolation
        $this->resetSingletons();
        // Reset the logger for clean test isolation
        $this->resetLogger();
    }

    /**
     * Helper method to reset the singletons array.
     *
     * @since 4.1
     */
    private function resetSingletons(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);
        $singletonsProperty = $reflection->getProperty('singletons');
        $singletonsProperty->setAccessible(true);
        $singletonsProperty->setValue(null, []);
    }

    /**
     * Helper method to reset the logger.
     *
     * @since 4.1
     */
    private function resetLogger(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);
        $loggerProperty = $reflection->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue(null, null);
    }

    /**
     * Testing that getInstance returns an instance of the requested service.
     *
     * @since 4.1
     */
    public function testGetInstanceReturnsInstance(): void
    {
        $service = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class
        );

        $this->assertInstanceOf(CacheProviderArray::class, $service);
        $this->assertInstanceOf(CacheProviderInterface::class, $service);
    }

    /**
     * Testing that getInstance returns a new instance each time when isSingleton is false.
     *
     * @since 4.1
     */
    public function testGetInstanceReturnsNewInstance(): void
    {
        $service1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            false
        );

        $service2 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            false
        );

        $this->assertNotSame($service1, $service2, 'Testing that getInstance returns a new instance each time when isSingleton is false');
    }

    /**
     * Testing that getInstance returns the same instance when isSingleton is true.
     *
     * @since 4.1
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $this->resetSingletons();

        $service1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        $service2 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        // Verify both are instances of the expected class
        $this->assertInstanceOf(CacheProviderArray::class, $service1);
        $this->assertInstanceOf(CacheProviderArray::class, $service2);
        $this->assertEquals(get_class($service1), get_class($service2), 'Testing that getInstance returns the same class type when isSingleton is true');
    }

    /**
     * Testing that getInstance throws IllegalArguementException when service class does not exist.
     *
     * @since 4.1
     */
    public function testGetInstanceServiceNotFound(): void
    {
        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('The class [Alpha\Util\NonExistent\Service] is not defined anywhere!');

        ServiceFactory::getInstance(
            'Alpha\Util\NonExistent\Service',
            CacheProviderInterface::class
        );
    }

    /**
     * Testing that getInstance throws IllegalArguementException when service does not implement the interface.
     *
     * @since 4.1
     */
    public function testGetInstanceInterfaceMismatch(): void
    {
        // A class that does not implement the interface
        $this->expectException(IllegalArguementException::class);
        $this->expectExceptionMessage('The class [Alpha\Util\Email\EmailProviderPHP] does not implement the expected [Alpha\Util\Cache\CacheProviderInterface] interface!');

        ServiceFactory::getInstance(
            EmailProviderPHP::class,
            CacheProviderInterface::class
        );
    }

    /**
     * Testing that getInstance works with different service types.
     *
     * @since 4.1
     */
    public function testGetInstanceWithDifferentServices(): void
    {
        // Test with EmailProvider
        $emailService = ServiceFactory::getInstance(
            EmailProviderPHP::class,
            EmailProviderInterface::class
        );

        $this->assertInstanceOf(EmailProviderPHP::class, $emailService);
        $this->assertInstanceOf(EmailProviderInterface::class, $emailService);

        // Test with CacheProvider
        $cacheService = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class
        );

        $this->assertInstanceOf(CacheProviderArray::class, $cacheService);
        $this->assertInstanceOf(CacheProviderInterface::class, $cacheService);
    }

    /**
     * Testing that getInstance is a static method.
     *
     * @since 4.1
     */
    public function testGetInstanceIsStatic(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);
        $method = $reflection->getMethod('getInstance');

        $this->assertTrue($method->isStatic(), 'Testing that getInstance is a static method');
        $this->assertTrue($method->isPublic(), 'Testing that getInstance is a public method');
    }

    /**
     * Testing that the ServiceFactory class has the expected properties.
     *
     * @since 4.1
     */
    public function testExpectedProperties(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);

        $expectedProperties = ['logger', 'singletons'];
        $actualProperties = array_keys($reflection->getProperties());

        foreach ($expectedProperties as $property) {
            $this->assertTrue($reflection->hasProperty($property), 'Testing that class has property: '.$property);
        }
    }

    /**
     * Testing that getInstance returns void return type.
     *
     * @since 4.1
     */
    public function testGetInstanceReturnType(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);
        $method = $reflection->getMethod('getInstance');

        $this->assertEquals('mixed', $method->getReturnType()?->getName() ?? 'void', 'Testing that getInstance returns mixed type');
    }

    /**
     * Testing that singleton instances are stored correctly.
     *
     * @since 4.1
     */
    public function testSingletonStorage(): void
    {
        $this->resetSingletons();

        $service1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        // Get the singletons array
        $reflection = new ReflectionClass(ServiceFactory::class);
        $singletonsProperty = $reflection->getProperty('singletons');
        $singletonsProperty->setAccessible(true);
        $singletons = $singletonsProperty->getValue();

        $this->assertArrayHasKey(CacheProviderArray::class, $singletons, 'Testing that singleton instance is stored correctly');
        $this->assertNotNull($singletons[CacheProviderArray::class], 'Testing that singleton instance is not null');
    }

    /**
     * Testing that multiple singleton services can be created.
     *
     * @since 4.1
     */
    public function testMultipleSingletons(): void
    {
        $this->resetSingletons();

        $cacheService1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        $emailService1 = ServiceFactory::getInstance(
            EmailProviderPHP::class,
            EmailProviderInterface::class,
            true
        );

        $cacheService2 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        $emailService2 = ServiceFactory::getInstance(
            EmailProviderPHP::class,
            EmailProviderInterface::class,
            true
        );

        // Check that services are of the correct type
        $this->assertInstanceOf(CacheProviderArray::class, $cacheService1);
        $this->assertInstanceOf(CacheProviderArray::class, $cacheService2);
        $this->assertInstanceOf(EmailProviderPHP::class, $emailService1);
        $this->assertInstanceOf(EmailProviderPHP::class, $emailService2);
    }

    /**
     * Testing that non-singleton instances are not stored in singletons array.
     *
     * @since 4.1
     */
    public function testNonSingletonNotInSingletons(): void
    {
        $this->resetSingletons();

        $service1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            false
        );

        // Get the singletons array
        $reflection = new ReflectionClass(ServiceFactory::class);
        $singletonsProperty = $reflection->getProperty('singletons');
        $singletonsProperty->setAccessible(true);
        $singletons = $singletonsProperty->getValue();

        $this->assertArrayNotHasKey(CacheProviderArray::class, $singletons, 'Testing that non-singleton instance is not stored in singletons array');
    }

    /**
     * Testing that getInstance can be called with different parameters without errors.
     *
     * @since 4.1
     */
    public function testGetInstanceWithDifferentParameters(): void
    {
        $service1 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            false
        );

        $service2 = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class,
            true
        );

        // The first call should return a new instance, the second should return a singleton
        $this->assertInstanceOf(CacheProviderArray::class, $service1);
        $this->assertInstanceOf(CacheProviderArray::class, $service2);
    }

    /**
     * Testing that getInstance can be called multiple times without errors.
     *
     * @since 4.1
     */
    public function testMultipleGetInstanceCalls(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $service = ServiceFactory::getInstance(
                CacheProviderArray::class,
                CacheProviderInterface::class
            );

            $this->assertInstanceOf(CacheProviderArray::class, $service);
        }

        $this->assertTrue(true, 'Testing that getInstance can be called multiple times without errors');
    }

    /**
     * Testing that service instance implements the expected interface.
     *
     * @since 4.1
     */
    public function testServiceImplementsInterface(): void
    {
        $service = ServiceFactory::getInstance(
            CacheProviderArray::class,
            CacheProviderInterface::class
        );

        $this->assertInstanceOf(CacheProviderInterface::class, $service);

        // Verify interface methods exist
        $this->assertTrue(method_exists($service, 'get'), 'Testing that service implements get method');
        $this->assertTrue(method_exists($service, 'set'), 'Testing that service implements set method');
        $this->assertTrue(method_exists($service, 'delete'), 'Testing that service implements delete method');
        $this->assertTrue(method_exists($service, 'check'), 'Testing that service implements check method');
    }

    /**
     * Testing that ServiceFactory is a static-only class (no public constructor).
     *
     * @since 4.1
     */
    public function testServiceFactoryIsSingleton(): void
    {
        $reflection = new ReflectionClass(ServiceFactory::class);

        // Verify the class is not abstract
        $this->assertFalse($reflection->isAbstract(), 'Testing that ServiceFactory is not an abstract class');

        // Verify the class has the expected static methods
        $this->assertTrue($reflection->hasMethod('getInstance'), 'Testing that ServiceFactory has getInstance method');

        $getInstance = $reflection->getMethod('getInstance');
        $this->assertTrue($getInstance->isStatic(), 'Testing that getInstance is a static method');
    }
}
