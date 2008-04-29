<?php

/**
 *
 * Test case for the Integer data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Integer_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Integer for testing
	 * @var Integer
	 */
	private $int1;
		
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
    public function testConstructorPass() {
    	$this->int1 = new Integer(25);
    	
    	$this->assertEquals(25, $this->int1->get_value(), "testing the Integer constructor for pass");
    }
    
    /**
     * testing passing invalid data to set_value
     */
    public function testSetValueInvalid() {
    	$this->assertEquals($this->int1->get_helper(), $this->int1->set_value("blah"), "testing passing invalid data to set_value");
    }
    
    /**
     * testing the set_size method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->int1 = new Integer();
    	$this->int1->set_size(2);
    	
    	$this->assertEquals('Error: the value 5000 provided by set_value is greater than the size '.$this->int1->get_size().' of this data type.', $this->int1->set_value(5000), "testing setting the size of the integer type");
    }
    
    /**
     * testing that the overide of the default validation rule is working
     */
    public function testSetValidation() {
    	$this->int1 = new Integer();
    	$this->int1->set_validation("/5/", "Only 5 is acceptable!");
    	
    	$this->assertEquals("Only 5 is acceptable!", $this->int1->set_value(2), "testing that the overide of the default validation rule is working");
    }
}

?>