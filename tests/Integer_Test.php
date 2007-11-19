<?php

// $Id$

require_once '../model/types/Integer.inc';
require_once '../../config/db_connect.inc';

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
    	
    	$this->assertEquals(25, $this->int1->get_value(), "testing the Integer constructor for pass");
    }
    
    /**
     * testing passing invalid data to set_value
     */
    function test_set_value_invalid() {
    	$this->assertEquals($this->int1->get_helper(), $this->int1->set_value("blah"), "testing passing invalid data to set_value");
    }
    
    /**
     * testing the set_size method to see if validation fails
     */
    function test_set_size_invalid() {
    	$this->int1 = new Integer();
    	$this->int1->set_size(2);
    	
    	$this->assertEquals('Error: the value 5000 provided by set_value is greater than the size '.$this->int1->get_size().' of this data type.', $this->int1->set_value(5000), "testing setting the size of the integer type");
    }
    
    /**
     * testing that the overide of the default validation rule is working
     */
    function test_set_validation() {
    	$this->int1 = new Integer();
    	$this->int1->set_validation("/5/", "Only 5 is acceptable!");
    	
    	$this->assertEquals("Only 5 is acceptable!", $this->int1->set_value(2), "testing that the overide of the default validation rule is working");
    }
}

?>