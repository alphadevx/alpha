<?php

/**
 *
 * Test case for the Timestamp data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Timestamp_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Timestamp for testing
	 * @var Timestamp
	 */
	private $timestamp1;	
	
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->timestamp1 = new Timestamp();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->timestamp1);        
    }
    
    /**
     * testing the constructor has set the Timestamp to today by default
     */
    public function testDefaultTimestampValue() {
    	$this->assertEquals(date("Y-m-d H:i:s"), $this->timestamp1->getValue(), "testing the constructor has set the Timestamp to now by default");
    }
    
    /**
     * testing the setValue method
     */
    public function testSetValuePass() {
    	$this->timestamp1->setValue(2000, 1, 1, 23, 33, 5);
    	
    	$this->assertEquals("2000-01-01 23:33:05", $this->timestamp1->getValue(), "testing the setValue method");
    }
    
    /**
     * testing the setValue method with a bad month
     */
    public function testSetValueInvalidMonth() {
    	try {    	
    		$this->timestamp1->setValue(2000, 'blah', 1, 0, 0, 0);
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: the month value blah provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad month");
    	}    	
    }
    
	/**
     * testing the setValue method with a bad timestamp value (out of range)
     */
    public function testSetValueInvalidValue() {
    	try {    	
    		$this->timestamp1->setValue(2000, 13, 1, 0, 0, 0);
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: the day value 2000-13-1 provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad timestamp value (out of range)");
    	}    	
    }
    
    /**
     * testing the populate_from_string method
     */
    public function testPopulateFromString() {
    	$this->timestamp1->populateFromString("2007-08-13 23:44:07");
    	
    	$this->assertEquals("2007-08-13 23:44:07", $this->timestamp1->getValue(), "testing the populateFromString method");
    }
    
    /**
     * testing that the validation will cause an invalid timestamp to fail on the constructor
     */
    public function testValidationOnConstructor() {
    	try {
    		$timestamp = new Timestamp("blah");    	
    	}catch (AlphaFrameworkException $e) {
    		$this->assertTrue(true, "testing that the validation will cause an invalid timestamp to fail on the constructor");
    	}
    }
    
    /**
     * testing the get_euro_value method for converting to European timestamp format
     */
    public function testGetEuroValue() {
    	$this->assertEquals(date("d/m/y"), $this->timestamp1->getEuroValue(), "testing the get_euro_value method for converting to European timestamp format");
    }
    
    /**
     * testing the getWeekday() method when the default constructor is used
     */
    public function testGetWeekday() {
    	$this->assertEquals(date('l'), $this->timestamp1->getWeekday(), "testing the getWeekday() method when the default constructor is used");
    }
}

?>