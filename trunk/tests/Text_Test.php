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
	private $txt;	
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->txt = new Text();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->txt);        
    }
    
    /**
     * testing the text constructor for acceptance of correct data
     */
    public function testConstructorPass() {
    	$this->txt = new Text('A Text Value!');
    	
    	$this->assertEquals('A Text Value!', $this->txt->getValue(), "testing the Text constructor for pass");
    }
    
	/**
     * testing the text setValue method with bad data when the default validation rule is overridden
     */
    public function testSetValueFail() {
    	$this->txt->setRule(REQUIRED_TEXT);
    	
    	try {
    		$this->txt->setValue('');
    		$this->fail('testing the text setValue method with bad data when the default validation rule is overridden');
    	}catch (IllegalArguementException $e) {
    		$this->assertTrue(true, 'testing the text setValue method with bad data when the default validation rule is overridden');
    	}
    }
    
	/**
     * testing the text setValue method with good data when the default validation rule is overridden
     */
    public function testSetValuePass() {
    	$this->txt->setRule(REQUIRED_TEXT);
    	
    	try {
    		$this->txt->setValue('Some text');
    		
    		$this->assertEquals('Some text', $this->txt->getValue(), 'testing the text setValue method with good data when the default validation rule is overridden');
    	}catch (IllegalArguementException $e) {
    		$this->fail('testing the text setValue method with good data when the default validation rule is overridden');
    	}
    }
        
    /**
     * testing the setSize method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->txt = new Text();
    	$this->txt->setSize(4);
    	
    	try {
    		$this->txt->setValue('Too many characters!');
    		$this->fail('testing the setSize method to see if validation fails');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid text value!  A maximum of 4 characters is allowed.'
    			, $e->getMessage()
    			, 'testing the setSize method to see if validation fails');
    	}
    }    
	    
	/**
     * testing the __toString method
     */
    public function testToString() {
    	$this->txt = new Text('__toString result');    	
    	
    	$this->assertEquals('The value of __toString result', 'The value of '.$this->txt, 'testing the __toString method');
    }    
}

?>