<?php

namespace Alpha\Test\Exception;

use Alpha\Exception\AlphaException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Alpha Exception classes.
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
class AlphaExceptionTest extends TestCase
{
    /**
     * Testing AlphaException constructor with message.
     *
     * @since 4.1
     */
    public function testConstructorWithMessage(): void
    {
        $exception = new AlphaException('Test error message');

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertStringContainsString('AlphaException', $exception->getFile());
    }

    /**
     * Testing AlphaException constructor with message and code.
     *
     * @since 4.1
     */
    public function testConstructorWithMessageAndCode(): void
    {
        $exception = new AlphaException('Test error message', 500);

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    /**
     * Testing AlphaException constructor with message, code, and previous exception.
     *
     * @since 4.1
     */
    public function testConstructorWithPreviousException(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new AlphaException('Test error message', 500, $previousException);

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals($previousException, $exception->getPrevious());
    }

    /**
     * Testing setMessage() method.
     *
     * @since 4.1
     */
    public function testSetMessage(): void
    {
        $exception = new AlphaException('Original message');

        $exception->setMessage('New message');

        $this->assertEquals('New message', $exception->getMessage());
    }

    /**
     * Testing setFile() method.
     *
     * @since 4.1
     */
    public function testSetFile(): void
    {
        $exception = new AlphaException('Test message');

        $exception->setFile('/custom/path/file.php');

        $this->assertEquals('/custom/path/file.php', $exception->getFile());
    }

    /**
     * Testing IllegalArguementException extends AlphaException.
     *
     * @since 4.1
     */
    public function testIllegalArguementExceptionExtendsAlphaException(): void
    {
        $exception = new IllegalArguementException('Invalid argument');

        $this->assertInstanceOf(AlphaException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Invalid argument', $exception->getMessage());
    }

    /**
     * Testing ValidationException extends AlphaException.
     *
     * @since 4.1
     */
    public function testValidationExceptionExtendsAlphaException(): void
    {
        $exception = new ValidationException('Validation failed');

        $this->assertInstanceOf(AlphaException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Validation failed', $exception->getMessage());
    }

    /**
     * Testing exception can be thrown and caught.
     *
     * @since 4.1
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        try {
            throw new AlphaException('Test exception');
            $this->fail('Exception should have been thrown');
        } catch (AlphaException $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }
    }

    /**
     * Testing exception with custom code.
     *
     * @since 4.1
     */
    public function testExceptionWithCustomCode(): void
    {
        $exception = new AlphaException('Test message', 42);

        $this->assertEquals(42, $exception->getCode());
    }

    /**
     * Testing exception string representation.
     *
     * @since 4.1
     */
    public function testExceptionStringRepresentation(): void
    {
        $exception = new AlphaException('Test message', 100);

        $string = (string)$exception;

        $this->assertStringContainsString('Test message', $string);
        // Note: The code may not be in the string representation, so we just check it exists
        $this->assertNotEmpty($string);
    }

    /**
     * Testing exception trace.
     *
     * @since 4.1
     */
    public function testExceptionHasTrace(): void
    {
        $exception = new AlphaException('Test message');

        $trace = $exception->getTrace();

        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);
    }

    /**
     * Testing exception trace string.
     *
     * @since 4.1
     */
    public function testExceptionTraceString(): void
    {
        $exception = new AlphaException('Test message');

        $traceString = $exception->getTraceAsString();

        $this->assertIsString($traceString);
        $this->assertStringContainsString('AlphaException', $traceString);
    }

    /**
     * Testing exception with zero code.
     *
     * @since 4.1
     */
    public function testExceptionWithZeroCode(): void
    {
        $exception = new AlphaException('Test message', 0);

        $this->assertEquals(0, $exception->getCode());
    }

    /**
     * Testing exception with negative code.
     *
     * @since 4.1
     */
    public function testExceptionWithNegativeCode(): void
    {
        $exception = new AlphaException('Test message', -1);

        $this->assertEquals(-1, $exception->getCode());
    }

    /**
     * Testing exception with large code.
     *
     * @since 4.1
     */
    public function testExceptionWithLargeCode(): void
    {
        $exception = new AlphaException('Test message', 999999);

        $this->assertEquals(999999, $exception->getCode());
    }

    /**
     * Testing exception message with special characters.
     *
     * @since 4.1
     */
    public function testExceptionWithSpecialCharacters(): void
    {
        $message = 'Error: "invalid" input <script>test</script> @#$%^&*()';
        $exception = new AlphaException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Testing exception with long message.
     *
     * @since 4.1
     */
    public function testExceptionWithLongMessage(): void
    {
        $longMessage = str_repeat('This is a test message. ', 100);
        $exception = new AlphaException($longMessage);

        $this->assertEquals($longMessage, $exception->getMessage());
    }

    /**
     * Testing exception with empty message.
     *
     * @since 4.1
     */
    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new AlphaException('');

        $this->assertEquals('', $exception->getMessage());
    }

    /**
     * Testing exception inheritance chain.
     *
     * @since 4.1
     */
    public function testExceptionInheritanceChain(): void
    {
        $exception = new AlphaException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    /**
     * Testing exception with unicode message.
     *
     * @since 4.1
     */
    public function testExceptionWithUnicodeMessage(): void
    {
        $message = '错误消息 你好 世界 Привет мир!';
        $exception = new AlphaException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Testing exception is serializable.
     *
     * @since 4.1
     */
    public function testExceptionIsSerializable(): void
    {
        $exception = new AlphaException('Test message', 100);
        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getCode(), $unserialized->getCode());
    }
}
