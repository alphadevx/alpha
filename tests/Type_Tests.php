<?php

require_once '../model/types/Enum.inc';
require_once '../model/person_object.inc';
require_once '../../alpha/util/db_connect.inc';

/**
 *
 * Group of unit tests for the complex type classes
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class Type_Tests extends PHPUnit_TestCase
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
    function Type_Tests($name) {
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
    	
    	$result = $this->enum1->setValue('b');
    	
    	$this->assertTrue($result);
    }
    
    /**
     * testing the enum select option methods for fail
     */
    function test_set_select_fail() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->setValue('x');
    	
    	$this->assertFalse($result);
    }
    
    /**
     * testing that enum options are loaded correctly from the database
     */
    function test_load_enum_options() {
    	// here we are assuming that the first user in the DB
    	// table is an administrator
    	$this->person->load_object('1');
    	
    	$this->assertEquals('Administrator', $this->person->get_access_level());
    }
    
    /**
     * testing the set/get enum option methods
     */
    function test_set_enum_options() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$this->assertEquals($this->enum1->get_options(), array('a','b','c'));
    }
}

?>

