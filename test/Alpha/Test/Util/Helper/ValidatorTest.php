<?php

namespace Alpha\Test\Util\Helper;

use Alpha\Util\Helper\Validator;

/**
 * Test case for the Validator helper class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 *
 * @version $Id$
 *
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Validate that the provided value is a valid integer.
     *
     * @since 1.0
     */
    public function testIsInteger()
    {
        $this->assertTrue(Validator::isInteger(100));
        $this->assertTrue(Validator::isInteger(-100));
        $this->assertTrue(Validator::isInteger(0));
        $this->assertTrue(Validator::isInteger(00000000008));
        $this->assertTrue(Validator::isInteger('00000000008'));
        $this->assertTrue(Validator::isInteger('100'));
        $this->assertFalse(Validator::isInteger('1.1'));
        $this->assertFalse(Validator::isInteger(1.1));
        $this->assertFalse(Validator::isInteger('twenty'));
    }

    /**
     * Validate that the provided value is a valid double.
     *
     * @since 1.0
     */
    public function testIsDouble()
    {
        $this->assertTrue(Validator::isDouble(10.0));
        $this->assertTrue(Validator::isDouble(-10.0));
        $this->assertTrue(Validator::isDouble(0.10));
        $this->assertFalse(Validator::isDouble('twenty'));
        $this->assertTrue(Validator::isDouble(100));
        $this->assertTrue(Validator::isDouble('100'));
    }

    /**
     * Validate that the provided value is a valid boolean.
     *
     * @since 1.0
     */
    public function testIsBoolean()
    {
        $this->assertTrue(Validator::isBoolean(true));
        $this->assertTrue(Validator::isBoolean(1));
        $this->assertTrue(Validator::isBoolean(0));
        $this->assertFalse(Validator::isBoolean('test'), 'test');
        $this->assertFalse(Validator::isBoolean(5));
        $this->assertFalse(Validator::isBoolean(1.0));
    }

    /**
     * Validate that the provided value is a valid alphabetic string (strictly a-zA-Z).
     *
     * @since 1.0
     */
    public function testIsAlpha()
    {
        $this->assertTrue(Validator::isAlpha('test'));
        $this->assertTrue(Validator::isAlpha('Test'));
        $this->assertTrue(Validator::isAlpha('TEST'));
        $this->assertFalse(Validator::isAlpha('number5'));
        $this->assertFalse(Validator::isAlpha('!-++#'));
        $this->assertFalse(Validator::isAlpha('100'));
    }

    /**
     * Validate that the provided value is a valid alpha-numeric string (strictly a-zA-Z0-9).
     *
     * @since 1.0
     */
    public function testIsAlphaNum()
    {
        $this->assertTrue(Validator::isAlphaNum('test1'));
        $this->assertTrue(Validator::isAlphaNum('1Test'));
        $this->assertTrue(Validator::isAlphaNum('1TEST1'));
        $this->assertFalse(Validator::isAlphaNum('test value'));
        $this->assertFalse(Validator::isAlphaNum('!-++#'));
        $this->assertFalse(Validator::isAlphaNum('1.00'));
    }

    /**
     * Validate that the provided value is a valid URL.
     *
     * @since 1.0
     */
    public function testIsURL()
    {
        $this->assertTrue(Validator::isURL('http://www.alphaframework.org'));
        $this->assertTrue(Validator::isURL('http://www.alphaframework.org/controller/View.php?some=value'));
        $this->assertTrue(Validator::isURL('http://alphaframework.org/'));
        $this->assertFalse(Validator::isURL('http://alpha framework.org/'));
        $this->assertFalse(Validator::isURL('http//www.alphaframework.org'));
        $this->assertFalse(Validator::isURL('http:/www.alphaframework.org'));
    }

    /**
     * Validate that the provided value is a valid IP address.
     *
     * @since 1.0
     */
    public function testIsIP()
    {
        $this->assertTrue(Validator::isIP('127.0.0.1'));
        $this->assertTrue(Validator::isIP('254.254.254.254'));
        $this->assertTrue(Validator::isIP('100.100.100.100'));
        $this->assertFalse(Validator::isIP('127.0.0.1000'));
        $this->assertFalse(Validator::isIP('127.0.0'));
        $this->assertFalse(Validator::isIP('127.0.0.1.1'));
    }

    /**
     * Validate that the provided value is a valid email address.
     *
     * @since 1.0
     */
    public function testIsEmail()
    {
        $this->assertTrue(Validator::isEmail('nobody@alphaframework.org'));
        $this->assertTrue(Validator::isEmail('no.body@alphaframework.com'));
        $this->assertTrue(Validator::isEmail('no_body1@alphaframework.net'));
        $this->assertFalse(Validator::isEmail('nobodyalphaframework.org'));
        $this->assertFalse(Validator::isEmail('no body@alphaframework.org'));
        $this->assertFalse(Validator::isEmail('nobody@alphaframework'));
    }

    /**
     * Validate that the provided value is a valid Sequence value.
     *
     * @since 1.0
     */
    public function testIsSequence()
    {
        $this->assertTrue(Validator::isSequence('BARS-150'));
        $this->assertTrue(Validator::isSequence('ALPH-15'));
        $this->assertTrue(Validator::isSequence('DESI-1'));
        $this->assertFalse(Validator::isSequence('1'));
        $this->assertFalse(Validator::isSequence('1.0'));
        $this->assertFalse(Validator::isSequence('DESI8'));
    }

    /**
     * Validate that the provided value is a base64 string.
     *
     * @since 1.2.3
     */
    public function testIsBase64()
    {
        $this->assertTrue(Validator::isBase64('YWJjZA=='));
        $this->assertTrue(Validator::isBase64('MTIzNA=='));
        $this->assertTrue(Validator::isBase64('YWJjZDEyMzQ='));
        $this->assertFalse(Validator::isBase64('abcde'));
        $this->assertFalse(Validator::isBase64('12345'));
        $this->assertFalse(Validator::isBase64('abcde12345'));
    }
}
