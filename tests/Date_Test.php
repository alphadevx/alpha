<?php

// $Id$

require_once '../model/types/Date.inc';
require_once '../model/person_object.inc';
require_once '../../config/db_connect.inc';
require_once 'PHPUnit.php';

/**
 *
 * Test case for the Date data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins 
 * 
 */
class Date_Test extends PHPUnit_TestCase
{
	/**
	 * an Date for testing
	 * @var Date
	 */
	var $date1;
	
	/**
	 * a person for testing
	 * @var person_object
	 */
	var $person;
	
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function Date_Test($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->date1 = new Date();
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->date1);
        unset($this->person);
    }
    
    /**
     * testing the constructor has set the Date to today by default
     */
    function test_default_date_value() {
    	$this->assertEquals(date("Y-m-d"), $this->date1->get_date(), "testing the constructor has set the Date to today by default");
    }
    
    /**
     * testing the set_value method
     */
    function test_set_value() {
    	$this->date1->set_value(2000, 1, 1, 0, 0, 0);
    	
    	$this->assertEquals("2000-01-01 00:00:00", $this->date1->get_value(), "testing the set_value method");
    }
    
    /**
     * testing the set_date method
     */
    function test_set_date() {
    	$this->date1->set_date(2000, 1, 1);
    	
    	$this->assertEquals("2000-01-01", $this->date1->get_date(), "testing the set_date method");
    }
    
    /**
     * testing the set_time method
     */
    function test_set_time() {
    	$this->date1->set_time(12, 30, 5);
    	
    	$this->assertEquals("12:30:05", $this->date1->get_time(), "testing the set_date method");
    }
    
    /**
     * testing the populate_from_string method
     */
    function test_populate_from_string() {
    	$this->date1->populate_from_string("2007-08-13 12:45:07");
    	
    	$this->assertEquals("2007-08-13 12:45:07", $this->date1->get_value(), "testing the populate_from_string method");
    }
}

?>