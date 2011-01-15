<?php

/**
 *
 * Test case for the DEnum data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class DEnum_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * a DEnum for testing
	 * @var DEnum
	 */
	private $denum1;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->denum1 = new DEnum('article_object::section');        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->denum1);        
    }    
    
    /**
     * test to check that the denum options loaded from the database
     */
    public function testDEnumLoadedOptionsFromDB() {
    	$this->assertGreaterThan(0, count($this->denum1->getOptions()), 'test to check that the denum options loaded from the database');
    }
    
    /**
     * testing the setValue method with a bad options array index value
     */
    public function testSetValueInvalid() {
    	try {
    		$this->denum1->setValue('blah');
    		$this->fail('testing the setValue method with a bad options array index value');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid denum option!'
    			, $e->getMessage()
    			, 'testing the setValue method with a bad options array index value');
    	}
    }
    
	/**
     * testing the setValue method with a good options index array value
     */
    public function testSetValueValid() {
    	try {
    		$options = $this->denum1->getOptions();
    		$optionIDs = array_keys($options);    		
    		$this->denum1->setValue($optionIDs[0]);
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the setValue method with a good options index array value, exception: '.$e->getMessage());
    	}
    }
    
    /**
     * testing the getDisplayValue method
     */
    public function testGetDisplayValue() {
    	try {
    		$options = $this->denum1->getOptions();
    		$optionIDs = array_keys($options);    		
    		$this->denum1->setValue($optionIDs[0]);

    		$this->assertEquals($options[$optionIDs[0]], $this->denum1->getDisplayValue(), 'testing the getDisplayValue method');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the getDisplayValue method, exception: '.$e->getMessage());
    	}
    }
    
	/**
     * testing the getOptionID method
     */
    public function testGetOptionID() {
    	try {
    		$options = $this->denum1->getOptions();
    		$optionIDs = array_keys($options);    		
    		
    		$this->assertEquals($optionIDs[0], $this->denum1->getOptionID($options[$optionIDs[0]]), 'testing the getOptionID method');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the getOptionID method, exception: '.$e->getMessage());
    	}
    }
    
    /**
     * testing the getItemCount method
     */
    public function testGetItemCount() {
    	$options = $this->denum1->getOptions();
    	
    	$this->assertEquals(count($options), $this->denum1->getItemCount(), 'testing the getItemCount method');
    }
}

?>