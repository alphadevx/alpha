<?php

/**
 *
 * Test case for the Text data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Text_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * A Text for testing
	 * @var Text
	 */
	private $txt1;	
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->txt1 = new Text();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->txt1);        
    }
    
    /**
     * testing the str constructor for acceptance of correct data
     */
    public function testConstructorPass() {
    	$this->txt1 = new Text('A Text Value!');
    	
    	$this->assertEquals('A Text Value!', $this->txt1->getValue(), "testing the Text constructor for pass");
    }
        
    /**
     * testing the setSize method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->txt1 = new Text();
    	$this->txt1->setSize(4);
    	
    	try {
    		$this->txt1->setValue('Too many characters!');
    		$this->fail('testing the setSize method to see if validation fails');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Error: not a valid text value!  A maximum of 4 characters is allowed.'
    			, $e->getMessage()
    			, 'testing the setSize method to see if validation fails');
    	}
    }    
	    
	/**
     * testing the __toString method
     */
    public function testToText() {
    	$this->txt1 = new Text('__toString result');    	
    	
    	$this->assertEquals('The value of __toString result', 'The value of '.$this->txt1, 'testing the __toString method');
    }    
}

?>