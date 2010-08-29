<?php

require_once $config->get('sysRoot').'alpha/util/helpers/Validator.inc';

/**
 *
 * Test case for the Validator helper class
 * 
 * @package alpha::tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class Validator_Test extends PHPUnit_Framework_TestCase {
    /**
     * Validate that the provided value is a valid integer
     */
    public function testIsInteger() {
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
     * Validate that the provided value is a valid double
     */
    public function testIsDouble() {
        $this->assertTrue(Validator::isDouble(10.0));
		$this->assertTrue(Validator::isDouble(-10.0));
		$this->assertTrue(Validator::isDouble(0.10));
		$this->assertFalse(Validator::isDouble('twenty'));
		$this->assertFalse(Validator::isDouble(100));
		$this->assertFalse(Validator::isDouble('100'));
    }

    /**
     * Validate that the provided value is a valid alphabetic string (strictly a-zA-Z)
     */
    public function testIsAlpha() {
        $this->assertTrue(Validator::isAlpha('test'));
		$this->assertTrue(Validator::isAlpha('Test'));
		$this->assertTrue(Validator::isAlpha('TEST'));
		$this->assertFalse(Validator::isAlpha('number5'));
		$this->assertFalse(Validator::isAlpha('!-++#'));
		$this->assertFalse(Validator::isAlpha('100'));
    }

    /**
     * Validate that the provided value is a valid alpha-numeric string (strictly a-zA-Z0-9)
     */
    public function testIsAlphaNum() {
        $this->assertTrue(Validator::isAlphaNum('test1'));
		$this->assertTrue(Validator::isAlphaNum('1Test'));
		$this->assertTrue(Validator::isAlphaNum('1TEST1'));
		$this->assertFalse(Validator::isAlphaNum('test value'));
		$this->assertFalse(Validator::isAlphaNum('!-++#'));
		$this->assertFalse(Validator::isAlphaNum('1.00'));
    }

    /**
     * Validate that the provided value is a valid URL
     */
    public function testIsURL() {
        $this->assertTrue(Validator::isURL('http://www.alphaframework.org'));
		$this->assertTrue(Validator::isURL('http://www.alphaframework.org/controller/View.php?some=value'));
		$this->assertTrue(Validator::isURL('http://alphaframework.org/'));
		$this->assertFalse(Validator::isURL('http://alpha framework.org/'));
		$this->assertFalse(Validator::isURL('http//www.alphaframework.org'));
		$this->assertFalse(Validator::isURL('http:/www.alphaframework.org'));
    }

    /**
     * Validate that the provided value is a valid IP address
     */
    public function testIsIP() {
        $this->assertTrue(Validator::isIP('127.0.0.1'));
		$this->assertTrue(Validator::isIP('255.255.255.255'));
		$this->assertTrue(Validator::isIP('100.100.100.100'));
		$this->assertFalse(Validator::isIP('127.0.0.1000'));
		$this->assertFalse(Validator::isIP('127.0.0'));
		$this->assertFalse(Validator::isIP('127.0.0.1.1'));
    }

    /**
     * Validate that the provided value is a valid email address
     */
    public function testIsEmail() {
        $this->assertTrue(Validator::isEmail('nobody@alphaframework.org'));
		$this->assertTrue(Validator::isEmail('no.body@alphaframework.com'));
		$this->assertTrue(Validator::isEmail('no_body1@alphaframework.net'));
		$this->assertFalse(Validator::isEmail('nobodyalphaframework.org'));
		$this->assertFalse(Validator::isEmail('no body@alphaframework.org'));
		$this->assertFalse(Validator::isEmail('nobody@alphaframework'));
    }
    
    /**
     * Validate that the provided value is a valid Sequence value
     */
    public function testIsSequence() {
    	$this->assertTrue(Validator::isSequence('BARS-150'));
    	$this->assertTrue(Validator::isSequence('ALPH-15'));
    	$this->assertTrue(Validator::isSequence('DESI-1'));
    	$this->assertFalse(Validator::isSequence('1'));
    	$this->assertFalse(Validator::isSequence('1.0'));
    	$this->assertFalse(Validator::isSequence('DESI8'));
    }
}

?>