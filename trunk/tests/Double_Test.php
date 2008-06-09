<?php

/**
 *
 * Test case for the Double data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Double_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Double for testing
	 * @var Double
	 */
	private $dbl1;
	
	/**
	 * an Double for testing
	 * @var Double
	 */
	private $dbl2;
		
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->dbl1 = new Double();
        $this->dbl2 = new Double();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->dbl1);
        unset($this->dbl2);
    }
    
    /**
     * testing the Double constructor for acceptance of correct data
     */
    public function testConstructorPass() {
    	$this->dbl1 = new Double(5.77);
    	
    	$this->assertEquals(5.77, $this->dbl1->getValue(), "testing the Double constructor for pass");
    }
    
    /**
     * testing passing invalid data to setValue
     */
    public function testSetValueInvalid() {
    	try {
    		$this->dbl1->setValue("blah");
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: not a valid double value!  A maximum of '.$this->dbl1->getSize().' characters is allowed, in the format 0.00'
    			, $e->getMessage()
    			, 'testing passing invalid data to setValue');
    	}
    }
    
	/**
     * testing passing valid data to setValue
     */
    public function testSetValueValid() {
    	$this->dbl1->setValue(0.25);
    	
    	$this->assertEquals(0.25, $this->dbl1->getValue(), 'testing passing valid data to setValue');
    }
    
    /**
     * testing the setSize method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->dbl1 = new Double();
    	$this->dbl1->setSize(2);
    	
    	try {
    		$this->dbl1->setValue(200);
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: not a valid double value!  A maximum of '.$this->dbl1->getSize().' characters is allowed, in the format 0.00'
    			, $e->getMessage()
    			, 'testing passing invalid data to setValue');
    	}
    }
    
    /**
     * testing addition of two Double values
     */
    public function testAddDoubles() {
    	$this->dbl1 = new Double(1.25);
    	$this->dbl2 = new Double(3.50);
    	
    	$this->assertEquals(4.75, ($this->dbl1->getValue()+$this->dbl2->getValue()), 'testing addition of two Double values');
    }
    
	/**
     * testing to see that a numberic value is rounded by the Double constructor to two decimal places
     */
    public function testConstructorRound() {
    	$this->dbl1 = new Double(1/3);
    	
    	$this->assertEquals(0.33, $this->dbl1->getValue(), 'testing to see that a numberic value is rounded by the Double constructor to two decimal places');
    }
    
	/**
     * testing to see that a numberic value is rounded by setValue to two decimal places
     */
    public function testSetValueRound() {
    	$this->dbl1->setValue(1/3);
    	
    	$this->assertEquals(0.33, $this->dbl1->getValue(), 'testing to see that a numberic value is rounded by setValue to two decimal places');
    }
    
	/**
     * testing the __toString method
     */
    public function testToString() {
    	$this->dbl1 = new Double(5.5);
    	
    	$this->assertEquals('The price is $5.50', 'The price is $'.$this->dbl1, 'testing the __toString method');
    }
}

?>