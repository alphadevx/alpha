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
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->boolean1 = new Boolean();        
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
    	$this->assertTrue($this->boolean1->getBooleanValue(), "testing the constructor has set the Boolean to true by default");
    	$this->assertEquals($this->boolean1->getValue(), 1, "testing the constructor has set the Boolean to true by default");
    }
    
    /**
     * testing the constructor default can be overridden
     */
    public function testOverrideDefaultBooleanValue() {
    	$this->boolean1 = new Boolean(false);
    	
    	$this->assertFalse($this->boolean1->getBooleanValue(), "testing the constructor default can be overridden");
    	$this->assertEquals($this->boolean1->getValue(), 0, "testing the constructor default can be overridden");
    }
    
    /**
     * testing passing valid data to setValue
     */
    public function testSetValueValid() {
    	$this->boolean1->setValue(true);
    	
    	$this->assertTrue($this->boolean1->getBooleanValue(), "testing passing valid data to setValue");
    	$this->assertEquals($this->boolean1->getValue(), 1, "testing passing valid data to setValue");
    }
    
    /**
     * testing passing invalid data to setValue
     */
    public function testSetValueInvalid() {
    	$this->assertEquals($this->boolean1->getHelper(), $this->boolean1->setValue(3), "testing passing invalid data to setValue");
    }    
}

?>