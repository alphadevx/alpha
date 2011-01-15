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
	private $boolean;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->boolean = new Boolean();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->boolean);        
    }
    
    /**
     * testing the constructor has set the Boolean to true by default
     */
    public function testDefaultBooleanValue() {
    	$this->assertTrue($this->boolean->getBooleanValue(), "testing the constructor has set the Boolean to true by default");
    	$this->assertEquals($this->boolean->getValue(), 1, "testing the constructor has set the Boolean to true by default");
    }
    
    /**
     * testing the constructor default can be overridden
     */
    public function testOverrideDefaultBooleanValue() {
    	$this->boolean = new Boolean(false);
    	
    	$this->assertFalse($this->boolean->getBooleanValue(), "testing the constructor default can be overridden");
    	$this->assertEquals($this->boolean->getValue(), 0, "testing the constructor default can be overridden");
    }
    
	/**
     * testing passing invalid data to the constructor
     */
    public function testConstructorInvalid() {
    	try {
    		$this->boolean = new Boolean(7);
    		$this->boolean = new Boolean('abc');
    		$this->fail("testing passing invalid data to the constructor");
    	}catch (IllegalArguementException $e) {
    		$this->assertTrue(true, "testing passing invalid data to the constructor");
    	}
    }
    
    /**
     * testing passing valid data to setValue
     */
    public function testSetValueValid() {
    	$this->boolean->setValue(true);
    	
    	$this->assertTrue($this->boolean->getBooleanValue(), "testing passing valid data to setValue");
    	$this->assertEquals($this->boolean->getValue(), 1, "testing passing valid data to setValue");
    }
    
    /**
     * testing passing invalid data to setValue
     */
    public function testSetValueInvalid() {
    	try {
    		$this->boolean->setValue(3);
    		$this->fail("testing passing invalid data to setValue");
    	}catch (IllegalArguementException $e) {
    		$this->assertTrue(true, "testing passing invalid data to setValue");
    	}
    }
    
    /**
     * Testing the toString method
     */
    public function testToString() {
    	$this->assertEquals('true', $this->boolean->__toString(), 'Testing the toString method');
    }
}

?>