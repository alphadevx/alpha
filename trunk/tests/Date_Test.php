<?php

/**
 *
 * Test case for the Date data type
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class Date_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Date for testing
	 * 
	 * @var Date
	 * @since 1.0
	 */
	private $date1;	
	
    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {
    	global $config;
    	// override setting to ensure dates default to now
    	$config->set('sysDefaultDateTime', 'now');
    	        
        $this->date1 = new Date();        
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->date1);        
    }
    
    /**
     * Testing the constructor has set the Date to today by default
     * 
     * @since 1.0
     */
    public function testDefaultDateValue() {
    	$this->assertEquals(date("Y-m-d"), $this->date1->getValue(), "testing the constructor has set the Date to today by default");
    }
    
    /**
     * Testing the setValue method
     * 
     * @since 1.0
     */
    public function testSetValuePass() {
    	$this->date1->setDateValue(2000, 1, 1);
    	
    	$this->assertEquals("2000-01-01", $this->date1->getValue(), "testing the setValue method");
    }
    
    /**
     * Testing the setValue method with a bad month
     * 
     * @since 1.0
     */
    public function testSetValueInvalidMonth() {
    	try {    	
    		$this->date1->setDateValue(2000, 'blah', 1);
    		$this->fail("testing the setValue method with a bad month");
    	}catch (AlphaException $e) {
    		$this->assertEquals('Error: the month value blah provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad month");
    	}    	
    }
    
	/**
     * Testing the setValue method with a bad date value (out of range)
     * 
     * @since 1.0
     */
    public function testSetValueInvalidValue() {
    	try {    	
    		$this->date1->setDateValue(2000, 13, 1);
    		$this->fail("testing the setValue method with a bad date value (out of range)");
    	}catch (AlphaException $e) {
    		$this->assertEquals('Error: the day value 2000-13-1 provided is invalid!'
    			, $e->getMessage()
    			, "testing the setValue method with a bad date value (out of range)");
    	}    	
    }
    
    /**
     * Testing the populate_from_string method
     * 
     * @since 1.0
     */
    public function testPopulateFromString() {
    	$this->date1->populateFromString("2007-08-13");
    	
    	$this->assertEquals("2007-08-13", $this->date1->getValue(), "testing the populateFromString method");
    }
    
    /**
     * Testing that the validation will cause an invalid date to fail on the constructor
     * 
     * @since 1.0
     */
    public function testValidationOnConstructor() {
    	try {
    		$date = new Date("blah");
    		$this->fail("testing that the validation will cause an invalid date to fail on the constructor");    	
    	}catch (AlphaException $e) {
    		$this->assertTrue(true, "testing that the validation will cause an invalid date to fail on the constructor");
    	}
    }
    
    /**
     * Testing the get_euro_value method for converting to European date format
     * 
     * @since 1.0
     */
    public function testGetEuroValue() {
    	$this->assertEquals(date("d/m/y"), $this->date1->getEuroValue(), "testing the get_euro_value method for converting to European date format");
    }
    
    /**
     * Testing the getWeekday() method when the default constructor is used
     * 
     * @since 1.0
     */
    public function testGetWeekday() {
    	$this->assertEquals(date('l'), $this->date1->getWeekday(), "testing the getWeekday() method when the default constructor is used");
    }
}

?>