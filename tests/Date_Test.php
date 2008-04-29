<?php

/**
 *
 * Test case for the Date data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Date_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Date for testing
	 * @var Date
	 */
	private $date1;	
	
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->date1 = new Date();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->date1);        
    }
    
    /**
     * testing the constructor has set the Date to today by default
     */
    public function testDefaultDateValue() {
    	$this->assertEquals(date("Y-m-d"), $this->date1->get_value(), "testing the constructor has set the Date to today by default");
    }
    
    /**
     * testing the set_value method
     */
    public function testSetValue() {
    	$this->date1->set_value(2000, 1, 1);
    	
    	$this->assertEquals("2000-01-01", $this->date1->get_value(), "testing the set_value method");
    }    
    
    /**
     * testing the populate_from_string method
     */
    public function testPopulateFromString() {
    	$this->date1->populate_from_string("2007-08-13");
    	
    	$this->assertEquals("2007-08-13", $this->date1->get_value(), "testing the populate_from_string method");
    }
    
    /**
     * testing that the validation will cause an invalid date to fail on the constructor
     */
    public function testValidationOnConstructor() {
    	$date = new Date("blah");    	
    	$this->assertNull($date->get_year());
    }
    
    /**
     * testing the get_euro_value method for converting to European date format
     */
    public function testGetEuroValue() {
    	$this->assertEquals(date("d/m/y"), $this->date1->get_euro_value(), "testing the get_euro_value method for converting to European date format");
    }
    
    /**
     * testing the get_weekday() method when the default constructor is used
     */
    public function testGetWeekday() {
    	$this->assertEquals(date('l'),$this->date1->get_weekday(), "testing the get_weekday() method when the default constructor is used");
    }
}

?>