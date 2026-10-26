<?php

namespace Alpha\Test\Exception;

use Alpha\Exception\AlphaException;
use Alpha\Exception\FileNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for FileNotFoundException class.
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
class FileNotFoundExceptionTest extends TestCase
{
    /**
     * Testing FileNotFoundException constructor with message.
     *
     * @since 4.1
     */
    public function testConstructorWithMessage(): void
    {
        $exception = new FileNotFoundException('File not found');

        $this->assertEquals('File not found', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    /**
     * Testing FileNotFoundException constructor with message and code.
     *
     * @since 4.1
     */
    public function testConstructorWithMessageAndCode(): void
    {
        $exception = new FileNotFoundException('File not found', 500);

        $this->assertEquals('File not found', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    /**
     * Testing FileNotFoundException constructor with previous exception.
     *
     * @since 4.1
     */
    public function testConstructorWithPreviousException(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new FileNotFoundException('File not found', 500, $previousException);

        $this->assertEquals('File not found', $exception->getMessage());
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
        $exception = new FileNotFoundException('Original file error');

        $exception->setMessage('New file error');

        $this->assertEquals('New file error', $exception->getMessage());
    }

    /**
     * Testing setFile() method.
     *
     * @since 4.1
     */
    public function testSetFile(): void
    {
        $exception = new FileNotFoundException('Test message');

        $exception->setFile('/custom/path/file.php');

        $this->assertEquals('/custom/path/file.php', $exception->getFile());
    }

    /**
     * Testing FileNotFoundException extends AlphaException.
     *
     * @since 4.1
     */
    public function testFileNotFoundExceptionExtendsAlphaException(): void
    {
        $exception = new FileNotFoundException('File not found');

        $this->assertInstanceOf(AlphaException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * Testing exception can be thrown and caught.
     *
     * @since 4.1
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        try {
            throw new FileNotFoundException('File not found');
            $this->fail('Exception should have been thrown');
        } catch (FileNotFoundException $e) {
            $this->assertEquals('File not found', $e->getMessage());
        }
    }

    /**
     * Testing exception with custom code.
     *
     * @since 4.1
     */
    public function testExceptionWithCustomCode(): void
    {
        $exception = new FileNotFoundException('Test message', 42);

        $this->assertEquals(42, $exception->getCode());
    }

    /**
     * Testing exception string representation.
     *
     * @since 4.1
     */
    public function testExceptionStringRepresentation(): void
    {
        $exception = new FileNotFoundException('Test message', 100);

        $string = (string)$exception;

        $this->assertStringContainsString('Test message', $string);
        $this->assertNotEmpty($string);
    }

    /**
     * Testing exception trace.
     *
     * @since 4.1
     */
    public function testExceptionHasTrace(): void
    {
        $exception = new FileNotFoundException('Test message');

        $trace = $exception->getTrace();

        $this->assertIsArray($trace);
        $this->assertNotEmpty($trace);
    }
}
