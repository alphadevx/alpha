<?php

// $Id$

require_once '../model/types/Integer.inc';
require_once '../../config/db_connect.inc';
require_once 'PHPUnit.php';

/**
 *
 * Test case for the Integer data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins
 * 
 * 
 */
class Integer_Test extends PHPUnit_TestCase
{
	/**
	 * an Integer for testing
	 * @var Integer
	 */
	var $int1;
		
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function Integer_Test($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->int1 = new Integer();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->int1);        
    }
    
    /**
     * testing the int constructor for acceptance of correct data
     */
    function test_constructor_pass() {
    	$this->int1 = new Integer(25);    	
    	
    	$this->assertEquals($this->int1->get_value(), 25, "testing the Integer constructor for pass");
    }    
}

?>