<?php

// $Id$

/**
 *
 * Test case for the Enum data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class Enum_Test extends PHPUnit_TestCase
{
	/**
	 * an Enum for testing
	 * @var Enum
	 */
	var $enum1;
	
	/**
	 * a person for testing
	 * @var person_object
	 */
	var $person;
	
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function Enum_Test($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->enum1 = new Enum();
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->enum1);
        unset($this->person);
    }
    
    /**
     * testing the enum select option methods for pass
     */
    function test_set_select_pass() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('b');
    	
    	$this->assertTrue($result, "testing the enum select option methods for pass");
    }
    
    /**
     * testing the enum select option methods for fail
     */
    function test_set_select_fail() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('x');
    	
    	$this->assertFalse($result, "testing the enum select option methods for fail");
    }
    
    /**
     * testing that enum options are loaded correctly from the database
     */
    function test_load_enum_options() {
    	// here we are assuming that the first user in the DB
    	// table is an administrator
    	$this->person->load_object('1');
    	
    	$this->assertEquals('Administrator', $this->person->get_access_level(), "testing that enum options are loaded correctly from the database");
    }
    
    /**
     * testing the set/get enum option methods
     */
    function test_set_enum_options() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$this->assertEquals($this->enum1->get_options(), array('a','b','c'), "testing the set/get enum option methods");
    }
    
    /**
     * testing the default (alphabetical) sort order on the enum
     */
    function test_default_sort_order() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->get_options(true);
    	 
    	$this->assertEquals($options[1], "beta", "testing the default (alphabetical) sort order on the enum");
    }
}

?>