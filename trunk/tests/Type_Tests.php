<?php

require_once '../model/types/Enum.inc';
require_once '../config/config.conf';
require_once '../view/photo.inc';
require_once '../config/db_connect.inc';
require_once 'PHPUnit.php';

/**
 *
 * Group of unit tests for the complex type classes
 * 
 * @package SimpleMVC_Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2005 John Collins
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
	 * a photo for testing
	 * @var photo
	 */
	var $photo1;
	
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
        $this->photo1 = new photo();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->enum1);
        unset($this->photo1);
    }
    
    /**
     * testing the enum select option methods for pass
     */
    function test_set_select_pass() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('b');
    	
    	$this->assertTrue($result);
    }
    
    /**
     * testing the enum select option methods for fail
     */
    function test_set_select_fail() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('x');
    	
    	$this->assertFalse($result);
    }
    
    /**
     * testing that enum options are loaded correctly from the database
     */
    function test_load_enum_options() {
    	$this->photo1->load('1');
    	
    	$this->photo1->set_location('Dublin');
    	
    	$this->assertEquals('Dublin', $this->photo1->get_location());
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

