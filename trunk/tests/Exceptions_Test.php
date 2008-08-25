<?php

/**
 *
 * Test case for the exception handling functionality
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Exceptions_Test extends PHPUnit_Framework_TestCase
{    
    /**
     * Testing that a division by 0 exception is caught by the general exception handler
     */
    public function testDivideByZeroCaught() {
    	$exceptionCaught = false;
    	try {
    		2/0;
    	}catch (PHPException $e) {
    		$exceptionCaught = true;
    	}
    	
    	$this->assertTrue($exceptionCaught, "Testing that a division by 0 exception is caught by the general exception handler");
    }
    
	/**
     * Testing that calling a property on a non-object will throw an exception
     */
    public function testPropertyNonObjectCaught() {
    	$exceptionCaught = false;
    	try {
    		$e = $empty->test;
    	}catch (PHPException $e) {
    		$exceptionCaught = true;
    	}
    	
    	$this->assertTrue($exceptionCaught, "Testing that calling a property on a non-object will throw an exception");
    }
}

?>