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
	 * an Integer for testing
	 * @var Integer
	 */
	private $int2;
		
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->int1 = new Integer();
        $this->int2 = new Integer();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->int1);
        unset($this->int2);
    }
    
    /**
     * testing the int constructor for acceptance of correct data
     */
    public function testConstructorPass() {
    	$this->int1 = new Integer(25);
    	
    	$this->assertEquals(25, $this->int1->getValue(), "testing the Integer constructor for pass");
    }
    
    /**
     * testing passing invalid data to setValue
     */
    public function testSetValueInvalid() {
    	try {
    		$this->int1->setValue("blah");
    		$this->fail('testing passing invalid data to setValue');
    	}catch (AlphaException $e) {
    		$this->assertEquals('[blah] is not a valid Integer value!  A maximum of 11 characters is allowed'
    			, $e->getMessage()
    			, 'testing passing invalid data to setValue');
    	}
    }
    
	/**
     * testing passing valid data to setValue
     */
    public function testSetValueValid() {
    	$this->int1->setValue(7);
    	
    	$this->assertEquals(7, $this->int1->getValue(), 'testing passing valid data to setValue');
    }
    
    /**
     * testing the setSize method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->int1 = new Integer();
    	$this->int1->setSize(2);
    	
    	try {
    		$this->int1->setValue(200);
    	}catch (AlphaException $e) {
    		$this->assertEquals('[200] is not a valid Integer value!  A maximum of 2 characters is allowed'
    			, $e->getMessage()
    			, 'testing the setSize method to see if validation fails');
    	}
    }
    
	/**
     * testing addition of two Integer values
     */
    public function testAddIntegers() {
    	$this->int1 = new Integer(1500);
    	$this->int2 = new Integer(3577);
    	
    	$this->assertEquals(5077, ($this->int1->getValue()+$this->int2->getValue()), 'testing addition of two Integer values');
    }

    /**
     * testing the __toString method
     */
    public function testToString() {
    	$this->int1 = new Integer(2008);    	
    	
    	$this->assertEquals('The year is 2008', 'The year is '.$this->int1, 'testing the __toString method');
    }
}

?>