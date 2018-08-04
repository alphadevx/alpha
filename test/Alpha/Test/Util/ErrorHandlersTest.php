<?php

namespace Alpha\Test\Util;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\PHPException;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the exception handling functionality.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
    protected function setUp()
    {
        $config = ConfigProvider::getInstance();

        set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
        set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));
    }

    /**
     * Testing that a division by 0 exception is caught by the general exception handler.
     *
     * @since 1.0
     */
    public function testDivideByZeroCaught()
    {
        $exceptionCaught = false;
        try {
            2 / 0;
        } catch (PHPException $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Testing that a division by 0 exception is caught by the general exception handler');
    }

    /**
     * Testing that calling a property on a non-object will throw an exception.
     *
     * @since 1.0
     */
    public function testPropertyNonObjectCaught()
    {
        $exceptionCaught = false;
        try {
            $e = $empty->test;
        } catch (PHPException $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Testing that calling a property on a non-object will throw an exception');
    }
}
