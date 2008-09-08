<?php

/**
 *
 * Test case for the Enum data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class Enum_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Enum for testing
	 * @var Enum
	 */
	private $enum1;
	
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
        $this->enum1 = new Enum();
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->enum1);
        unset($this->person);
    }    
    
    /**
     * testing that enum options are loaded correctly from the database
     */
    public function testLoadEnumOptions() {
    	// here we are assuming that the first user in the DB
    	// table is an administrator
    	$this->person->load('1');
    	
    	$this->assertEquals('Administrator', $this->person->getAccessLevel()->getValue(), "testing that enum options are loaded correctly from the database");
    }
    
    /**
     * testing the set/get enum option methods
     */
    public function testSetEnumOptions() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	$this->assertEquals($this->enum1->getOptions(), array('a','b','c'), "testing the set/get enum option methods");
    }
    
	/**
     * testing the setValue method with good and bad values
     */
    public function testSetValue() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	try {    	
    		$this->enum1->setValue('b');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the setValue method with a good value');
    	}
    	
    	try {    	
    		$this->enum1->setValue('z');
    		$this->fail('testing the setValue method with a good value');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Error: not a valid enum option!'
    			, $e->getMessage()
    			, 'testing the setValue method with a bad value');
    	}
    }
    
	/**
     * testing the getValue method
     */
    public function testGetValue() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	try {    	
    		$this->enum1->setValue('b');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the getValue method');
    	}
    	
    	$this->assertEquals('b', $this->enum1->getValue(), 'testing the getValue method');
    }
    
    /**
     * test the constructor failing when a bad array is provided
     */
    public function testConstructorFail() {
    	try {    	
    		$enum = new Enum('blah');
    		$this->fail('test the constructor failing when a bad array is provided');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Error: not a valid enum option array!'
    			, $e->getMessage()
    			, 'test the constructor failing when a bad array is provided');
    	}
    }
    
    /**
     * testing the default (non-alphabetical) sort order on the enum
     */
    public function testDefaultSortOrder() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->getOptions();
    	 
    	$this->assertEquals($options[1], 'gamma', 'testing the default (non-alphabetical) sort order on the enum');
    }
    
	/**
     * testing the alphabetical sort order on the enum
     */
    public function testAlphaSortOrder() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->getOptions(true);
    	 
    	$this->assertEquals($options[1], 'beta', 'testing the alphabetical sort order on the enum');
    }
}

?>