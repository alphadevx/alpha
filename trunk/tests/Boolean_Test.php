<?php

// $Id$

require_once '../model/types/Boolean.inc';
require_once '../model/person_object.inc';
require_once '../../config/db_connect.inc';

/**
 *
 * Test case for the Boolean data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class Boolean_Test extends PHPUnit_TestCase
{
	/**
	 * an Boolean for testing
	 * @var Boolean
	 */
	var $boolean1;
	
	/**
	 * a person for testing
	 * @var person_object
	 */
	var $person;
	
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function Boolean_Test($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->boolean1 = new Boolean();
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->boolean1);
        unset($this->person);
    }
    
    /**
     * testing the constructor has set the Boolean to true by default
     */
    function test_default_boolean_value() {
    	$this->assertTrue($this->boolean1->get_value(), "testing the constructor has set the Boolean to true by default");
    }
    
    /**
     * testing the constructor default can be overridden
     */
    function test_override_default_boolean_value() {
    	$this->boolean1 = new Boolean(0);
    	
    	$this->assertFalse($this->boolean1->get_value(), "testing the constructor default can be overridden");
    }
    
    /**
     * testing passing valid data to set_value
     */
    function test_set_value_valid() {
    	$this->boolean1->set_value(1);
    	
    	$this->assertTrue($this->boolean1->get_value(), "testing passing valid data to set_value");
    }
    
    /**
     * testing passing invalid data to set_value
     */
    function test_set_value_invalid() {
    	$this->assertEquals($this->boolean1->get_helper(), $this->boolean1->set_value(3), "testing passing invalid data to set_value");
    }
    
    /**
     * testing that the validation rule can be changed
     */
    function test_change_validation_rule() {
    	$this->boolean1->set_validation("/[x|y]/");    	
    	
    	$this->assertEquals($this->boolean1->get_helper(), $this->boolean1->set_value("g"), "testing that the validation rule can be changed");
    }    
}

?>