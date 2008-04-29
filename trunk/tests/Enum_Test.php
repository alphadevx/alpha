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
     * testing the enum select option methods for pass
     */
    public function testSetSelectPass() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('b');
    	
    	$this->assertTrue($result, "testing the enum select option methods for pass");
    }
    
    /**
     * testing the enum select option methods for fail
     */
    public function testSetSelectFail() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$result = $this->enum1->set_value('x');
    	
    	$this->assertFalse($result, "testing the enum select option methods for fail");
    }
    
    /**
     * testing that enum options are loaded correctly from the database
     */
    public function testLoadEnumOptions() {
    	// here we are assuming that the first user in the DB
    	// table is an administrator
    	$this->person->load_object('1');
    	
    	$this->assertEquals('Administrator', $this->person->get_access_level(), "testing that enum options are loaded correctly from the database");
    }
    
    /**
     * testing the set/get enum option methods
     */
    public function testSetEnumOptions() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$this->assertEquals($this->enum1->get_options(), array('a','b','c'), "testing the set/get enum option methods");
    }
    
	/**
     * testing the set_value method with good and bad values
     */
    public function testSetValue() {
    	$this->enum1->set_options(array('a','b','c'));
    	
    	$this->assertTrue($this->enum1->set_value('a'), "testing the set_value method with a good value");
    	$this->assertFalse($this->enum1->set_value('x'), "testing the set_value method with a bad value");
    }
    
    /**
     * testing the default (alphabetical) sort order on the enum
     */
    public function testDefaultSortOrder() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->get_options(true);
    	 
    	$this->assertEquals($options[1], "beta", "testing the default (alphabetical) sort order on the enum");
    }
}

?>