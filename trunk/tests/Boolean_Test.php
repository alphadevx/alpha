<?php

/**
 *
 * Test case for the Boolean data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Boolean_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Boolean for testing
	 * @var Boolean
	 */
	private $boolean1;
	
	/**
	 * a person for testing
	 * @var person_object
	 */
	private $person;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->boolean1 = new Boolean();
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->boolean1);
        unset($this->person);
    }
    
    /**
     * testing the constructor has set the Boolean to true by default
     */
    public function testDefaultBooleanValue() {
    	$this->assertTrue($this->boolean1->get_value(), "testing the constructor has set the Boolean to true by default");
    }
    
    /**
     * testing the constructor default can be overridden
     */
    public function testOverrideDefaultBooleanValue() {
    	$this->boolean1 = new Boolean(0);
    	
    	$this->assertFalse($this->boolean1->get_value(), "testing the constructor default can be overridden");
    }
    
    /**
     * testing passing valid data to set_value
     */
    public function testSetValueValid() {
    	$this->boolean1->set_value(1);
    	
    	$this->assertTrue($this->boolean1->get_value(), "testing passing valid data to set_value");
    }
    
    /**
     * testing passing invalid data to set_value
     */
    public function testSetValueInvalid() {
    	$this->assertEquals($this->boolean1->get_helper(), $this->boolean1->set_value(3), "testing passing invalid data to set_value");
    }
    
    /**
     * testing that the validation rule can be changed
     */
    public function testChangeValidationRule() {
    	$this->boolean1->set_validation("/[x|y]/");    	
    	
    	$this->assertEquals($this->boolean1->get_helper(), $this->boolean1->set_value("g"), "testing that the validation rule can be changed");
    }    
}

?>