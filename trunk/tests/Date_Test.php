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
    	$this->assertEquals(date("Y-m-d"), $this->date1->getValue(), "testing the constructor has set the Date to today by default");
    }
    
    /**
     * testing the setValue method
     */
    public function testSetValuePass() {
    	$this->date1->setValue(2000, 1, 1);
    	
    	$this->assertEquals("2000-01-01", $this->date1->getValue(), "testing the setValue method");
    }
    
    /**
     * testing the setValue method with a bad month
     */
    public function testSetValueInvalidMonth() {
    	try {    	
    		$this->date1->setValue(2000, 'blah', 1);
    		$this->fail("testing the setValue method with a bad month");
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: the month value blah provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad month");
    	}    	
    }
    
	/**
     * testing the setValue method with a bad date value (out of range)
     */
    public function testSetValueInvalidValue() {
    	try {    	
    		$this->date1->setValue(2000, 13, 1);
    		$this->fail("testing the setValue method with a bad date value (out of range)");
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: the day value 2000-13-1 provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad date value (out of range)");
    	}    	
    }
    
    /**
     * testing the populate_from_string method
     */
    public function testPopulateFromString() {
    	$this->date1->populateFromString("2007-08-13");
    	
    	$this->assertEquals("2007-08-13", $this->date1->getValue(), "testing the populateFromString method");
    }
    
    /**
     * testing that the validation will cause an invalid date to fail on the constructor
     */
    public function testValidationOnConstructor() {
    	try {
    		$date = new Date("blah");
    		$this->fail("testing that the validation will cause an invalid date to fail on the constructor");    	
    	}catch (AlphaFrameworkException $e) {
    		$this->assertTrue(true, "testing that the validation will cause an invalid date to fail on the constructor");
    	}
    }
    
    /**
     * testing the get_euro_value method for converting to European date format
     */
    public function testGetEuroValue() {
    	$this->assertEquals(date("d/m/y"), $this->date1->getEuroValue(), "testing the get_euro_value method for converting to European date format");
    }
    
    /**
     * testing the getWeekday() method when the default constructor is used
     */
    public function testGetWeekday() {
    	$this->assertEquals(date('l'), $this->date1->getWeekday(), "testing the getWeekday() method when the default constructor is used");
    }
}

?>