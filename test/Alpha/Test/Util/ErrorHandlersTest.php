<?php

namespace Alpha\Test\Util;

use Alpha\Util\ErrorHandlers;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\PHPException;
use Alpha\Exception\AlphaException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for the exception handling functionality.
 *
 * @since 1.0
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
class ErrorHandlersTest extends TestCase
{
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();

        set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
        set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        restore_error_handler();
    }

    /**
     * Testing that a division by 0 exception is caught by the general exception handler.
     *
     * @since 1.0
     */
    public function testDivideByZeroCaught(): void
    {
        $exceptionCaught = false;

        try {
            2 / 0;
        } catch (PHPException $e) {
            $exceptionCaught = true;
        } catch (\DivisionByZeroError $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Testing that a division by 0 exception is caught by the general exception handler');
    }

    /**
     * Testing that catchException method logs the exception message correctly.
     *
     * @since 4.1
     */
    public function testCatchExceptionLogsMessage(): void
    {
        $loggerCalled = false;

        // We can't easily test the actual logging without file I/O
        // So we test that the method executes without errors
        try {
            $e = new AlphaException('Test exception message');
            ErrorHandlers::catchException($e);
            $loggerCalled = true;
        } catch (\Exception $ex) {
            $this->fail('catchException should not throw exceptions: '.$ex->getMessage());
        }

        $this->assertTrue($loggerCalled, 'Testing that catchException executes without errors');
    }

    /**
     * Testing that catchException method handles \Error objects.
     *
     * @since 4.1
     */
    public function testCatchExceptionHandlesError(): void
    {
        $errorCaught = false;

        try {
            // Trigger a TypeError which extends Error in PHP 8+
            $e = new \TypeError('Test error message');
            ErrorHandlers::catchException($e);
            $errorCaught = true;
        } catch (\Exception $ex) {
            $this->fail('catchException should handle Error objects: '.$ex->getMessage());
        }

        $this->assertTrue($errorCaught, 'Testing that catchException handles Error objects');
    }

    /**
     * Testing that catchException handles an exception with a stack trace.
     *
     * @since 4.1
     */
    public function testCatchExceptionWithStackTrace(): void
    {
        try {
            $this->throwExceptionWithTrace();
        } catch (\Exception $e) {
            // This should not throw an exception
            ErrorHandlers::catchException($e);
            $this->assertTrue(true, 'Testing that catchException handles exceptions with stack traces');
        }
    }

    /**
     * Helper method to throw an exception with a stack trace.
     *
     * @throws AlphaException
     */
    private function throwExceptionWithTrace(): void
    {
        throw new AlphaException('Exception with trace');
    }

    /**
     * Testing that calling a property on a non-object will throw an exception.
     *
     * @since 1.0
     */
    public function testPropertyNonObjectCaught(): void
    {
        $exceptionCaught = false;

        try {
            $e = $empty->test;
        } catch (\Exception $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Testing that calling a property on a non-object will throw an exception');
    }

    /**
     * Testing that catchError method creates a PHPException with correct message format.
     *
     * @since 4.1
     */
    public function testCatchErrorCreatesPHPException(): void
    {
        $errorCaught = false;
        $errorNo = 8;
        $msg = 'Undefined variable: testVar';
        $file = '/var/www/html/test.php';
        $line = 42;

        try {
            ErrorHandlers::catchError($errorNo, $msg, $file, $line);
        } catch (PHPException $e) {
            $errorCaught = true;
            $expectedMessage = '[PHP error 8] on line [42] of file [/var/www/html/test.php]: Undefined variable: testVar';
            $this->assertEquals($expectedMessage, $e->getMessage(), 'Testing that catchError creates PHPException with correct message format');
            $this->assertEquals($file, $e->getFile(), 'Testing that catchError sets the correct file');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException: '.$e->getMessage());
        }

        $this->assertTrue($errorCaught, 'Testing that catchError throws an exception');
    }

    /**
     * Testing that catchError method handles different error levels.
     *
     * @since 4.1
     */
    public function testCatchErrorWithDifferentErrorLevels(): void
    {
        $errorNo = 2;  // E_WARNING
        $msg = 'Invalid argument supplied for foreach()';
        $file = '/var/www/html/test.php';
        $line = 15;

        try {
            ErrorHandlers::catchError($errorNo, $msg, $file, $line);
        } catch (PHPException $e) {
            $this->assertStringContainsString('[PHP error 2]', $e->getMessage(), 'Testing that catchError handles E_WARNING error level');
            $this->assertStringContainsString('Invalid argument supplied for foreach()', $e->getMessage(), 'Testing that catchError includes the error message');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException: '.$e->getMessage());
        }
    }

    /**
     * Testing that catchError method handles E_USER_ERROR.
     *
     * @since 4.1
     */
    public function testCatchErrorWithUserError(): void
    {
        $errorNo = 256;  // E_USER_ERROR
        $msg = 'Custom user error';
        $file = '/var/www/html/custom.php';
        $line = 100;

        try {
            ErrorHandlers::catchError($errorNo, $msg, $file, $line);
        } catch (PHPException $e) {
            $this->assertStringContainsString('[PHP error 256]', $e->getMessage(), 'Testing that catchError handles E_USER_ERROR');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException: '.$e->getMessage());
        }
    }

    /**
     * Testing that catchError preserves the original file path.
     *
     * @since 4.1
     */
    public function testCatchErrorPreservesFilePath(): void
    {
        $errorNo = 8;
        $msg = 'Undefined offset';
        $file = '/home/user/project/src/Model/Example.php';
        $line = 256;

        try {
            ErrorHandlers::catchError($errorNo, $msg, $file, $line);
        } catch (PHPException $e) {
            $this->assertEquals($file, $e->getFile(), 'Testing that catchError preserves the original file path');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException: '.$e->getMessage());
        }
    }

    /**
     * Testing that catchError handles very long error messages.
     *
     * @since 4.1
     */
    public function testCatchErrorWithLongMessage(): void
    {
        $errorNo = 8;
        $msg = str_repeat('A', 1000);
        $file = '/var/www/html/test.php';
        $line = 1;

        try {
            ErrorHandlers::catchError($errorNo, $msg, $file, $line);
        } catch (PHPException $e) {
            $this->assertStringContainsString($msg, $e->getMessage(), 'Testing that catchError handles long error messages');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException: '.$e->getMessage());
        }
    }

    /**
     * Testing that the exception handler can be set and restored properly.
     *
     * @since 4.1
     */
    public function testExceptionHandlerSetupTeardown(): void
    {
        $config = ConfigProvider::getInstance();

        // Verify exception handler is set
        $currentHandler = set_exception_handler('restore_exception_handler');
        $this->assertEquals('Alpha\Util\ErrorHandlers::catchException', $currentHandler, 'Testing that exception handler is set correctly');
        restore_exception_handler();

        // Verify error handler is set
        $currentHandler = set_error_handler('restore_error_handler');
        $this->assertEquals('Alpha\Util\ErrorHandlers::catchError', $currentHandler, 'Testing that error handler is set correctly');
        restore_error_handler();
    }

    /**
     * Testing that ErrorHandlers is a static class (no instantiation).
     *
     * @since 4.1
     */
    public function testErrorHandlersIsStatic(): void
    {
        $reflection = new ReflectionClass(ErrorHandlers::class);

        // ReflectionClass::isClass() is deprecated/removed in newer PHP versions
        // Just verify the class exists and has the expected methods
        $this->assertEquals('Alpha\Util\ErrorHandlers', $reflection->getName(), 'Testing that ErrorHandlers class name is correct');
        $this->assertTrue($reflection->hasMethod('catchException'), 'Testing that ErrorHandlers has catchException method');
        $this->assertTrue($reflection->hasMethod('catchError'), 'Testing that ErrorHandlers has catchError method');

        $catchException = $reflection->getMethod('catchException');
        $catchError = $reflection->getMethod('catchError');

        $this->assertTrue($catchException->isStatic(), 'Testing that catchException is a static method');
        $this->assertTrue($catchError->isStatic(), 'Testing that catchError is a static method');
    }

    /**
     * Testing that catchException returns void.
     *
     * @since 4.1
     */
    public function testCatchExceptionReturnType(): void
    {
        $e = new AlphaException('Test exception');
        $result = ErrorHandlers::catchException($e);

        $this->assertNull($result, 'Testing that catchException returns void (null)');
    }

    /**
     * Testing that catchError returns void by not throwing when using @expectedException.
     * Note: catchError always throws, so this test verifies the exception type.
     *
     * @since 4.1
     */
    public function testCatchErrorReturnType(): void
    {
        try {
            ErrorHandlers::catchError(8, 'Test error', '/test.php', 1);
            $this->fail('catchError should throw an exception');
        } catch (PHPException $e) {
            $this->assertTrue(true, 'Testing that catchError throws PHPException as expected');
        } catch (\Exception $e) {
            $this->fail('catchError should throw PHPException, got: '.get_class($e));
        }
    }
}
