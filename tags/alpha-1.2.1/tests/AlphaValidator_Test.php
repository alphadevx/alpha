<?php

/**
 *
 * Test case for the AlphaValidator helper class
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
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
 *  
 */
class AlphaValidator_Test extends PHPUnit_Framework_TestCase {
    /**
     * Validate that the provided value is a valid integer
     * 
     * @since 1.0
     */
    public function testIsInteger() {
        $this->assertTrue(AlphaValidator::isInteger(100));
		$this->assertTrue(AlphaValidator::isInteger(-100));
		$this->assertTrue(AlphaValidator::isInteger(0));
		$this->assertTrue(AlphaValidator::isInteger(00000000008));
		$this->assertTrue(AlphaValidator::isInteger('00000000008'));
		$this->assertTrue(AlphaValidator::isInteger('100'));
		$this->assertFalse(AlphaValidator::isInteger('1.1'));
		$this->assertFalse(AlphaValidator::isInteger(1.1));
		$this->assertFalse(AlphaValidator::isInteger('twenty'));
    }

    /**
     * Validate that the provided value is a valid double
     * 
     * @since 1.0
     */
    public function testIsDouble() {
        $this->assertTrue(AlphaValidator::isDouble(10.0));
		$this->assertTrue(AlphaValidator::isDouble(-10.0));
		$this->assertTrue(AlphaValidator::isDouble(0.10));
		$this->assertFalse(AlphaValidator::isDouble('twenty'));
		$this->assertFalse(AlphaValidator::isDouble(100));
		$this->assertFalse(AlphaValidator::isDouble('100'));
    }
    
	/**
     * Validate that the provided value is a valid boolean
     * 
     * @since 1.0
     */
    public function testIsBoolean() {
        $this->assertTrue(AlphaValidator::isBoolean(true));
		$this->assertTrue(AlphaValidator::isBoolean(1));
		$this->assertTrue(AlphaValidator::isBoolean(0));
		$this->assertFalse(AlphaValidator::isBoolean('test'), 'test');
		$this->assertFalse(AlphaValidator::isBoolean(5));
		$this->assertFalse(AlphaValidator::isBoolean(1.0));
    }

    /**
     * Validate that the provided value is a valid alphabetic string (strictly a-zA-Z)
     * 
     * @since 1.0
     */
    public function testIsAlpha() {
        $this->assertTrue(AlphaValidator::isAlpha('test'));
		$this->assertTrue(AlphaValidator::isAlpha('Test'));
		$this->assertTrue(AlphaValidator::isAlpha('TEST'));
		$this->assertFalse(AlphaValidator::isAlpha('number5'));
		$this->assertFalse(AlphaValidator::isAlpha('!-++#'));
		$this->assertFalse(AlphaValidator::isAlpha('100'));
    }

    /**
     * Validate that the provided value is a valid alpha-numeric string (strictly a-zA-Z0-9)
     * 
     * @since 1.0
     */
    public function testIsAlphaNum() {
        $this->assertTrue(AlphaValidator::isAlphaNum('test1'));
		$this->assertTrue(AlphaValidator::isAlphaNum('1Test'));
		$this->assertTrue(AlphaValidator::isAlphaNum('1TEST1'));
		$this->assertFalse(AlphaValidator::isAlphaNum('test value'));
		$this->assertFalse(AlphaValidator::isAlphaNum('!-++#'));
		$this->assertFalse(AlphaValidator::isAlphaNum('1.00'));
    }

    /**
     * Validate that the provided value is a valid URL
     * 
     * @since 1.0
     */
    public function testIsURL() {
        $this->assertTrue(AlphaValidator::isURL('http://www.alphaframework.org'));
		$this->assertTrue(AlphaValidator::isURL('http://www.alphaframework.org/controller/View.php?some=value'));
		$this->assertTrue(AlphaValidator::isURL('http://alphaframework.org/'));
		$this->assertFalse(AlphaValidator::isURL('http://alpha framework.org/'));
		$this->assertFalse(AlphaValidator::isURL('http//www.alphaframework.org'));
		$this->assertFalse(AlphaValidator::isURL('http:/www.alphaframework.org'));
    }

    /**
     * Validate that the provided value is a valid IP address
     * 
     * @since 1.0
     */
    public function testIsIP() {
        $this->assertTrue(AlphaValidator::isIP('127.0.0.1'));
		$this->assertTrue(AlphaValidator::isIP('254.254.254.254'));
		$this->assertTrue(AlphaValidator::isIP('100.100.100.100'));
		$this->assertFalse(AlphaValidator::isIP('127.0.0.1000'));
		$this->assertFalse(AlphaValidator::isIP('127.0.0'));
		$this->assertFalse(AlphaValidator::isIP('127.0.0.1.1'));
    }

    /**
     * Validate that the provided value is a valid email address
     * 
     * @since 1.0
     */
    public function testIsEmail() {
        $this->assertTrue(AlphaValidator::isEmail('nobody@alphaframework.org'));
		$this->assertTrue(AlphaValidator::isEmail('no.body@alphaframework.com'));
		$this->assertTrue(AlphaValidator::isEmail('no_body1@alphaframework.net'));
		$this->assertFalse(AlphaValidator::isEmail('nobodyalphaframework.org'));
		$this->assertFalse(AlphaValidator::isEmail('no body@alphaframework.org'));
		$this->assertFalse(AlphaValidator::isEmail('nobody@alphaframework'));
    }
    
    /**
     * Validate that the provided value is a valid Sequence value
     * 
     * @since 1.0
     */
    public function testIsSequence() {
    	$this->assertTrue(AlphaValidator::isSequence('BARS-150'));
    	$this->assertTrue(AlphaValidator::isSequence('ALPH-15'));
    	$this->assertTrue(AlphaValidator::isSequence('DESI-1'));
    	$this->assertFalse(AlphaValidator::isSequence('1'));
    	$this->assertFalse(AlphaValidator::isSequence('1.0'));
    	$this->assertFalse(AlphaValidator::isSequence('DESI8'));
    }
}

?>