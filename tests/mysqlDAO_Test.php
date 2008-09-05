<?php

/**
 *
 * Test case for the mySQLDAO class
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class mysqlDAO_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * A person_object for testing (any business object will do)
	 * 
	 * @var person_object
	 */
	private $person;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->person = new person_object();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {
    	if(!$this->person->isTransient())
    		$this->person->delete(); 
        unset($this->person);
    }
    
    /*
     * TODO: add test methods for the following:
     * 
     * - constructor
     * - load
     * - loadByAttribute
     * - loadAll
     * - loadAllByAttribute
     */
    
    /**
     * test that the constructor sets the correct values of the "house keeping" attributes
     */
    public function testDefaultHouseKeepingValues() {
    	// make sure the person logged in is the same person to create/update the object
    	$this->assertEquals($_SESSION["current_user"]->getID(), $this->person->getCreatorId()->getValue(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    	$this->assertEquals($_SESSION["current_user"]->getID(), $this->person->getUpdatorId()->getValue(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    	// as it is a new object, make sure the version number is zero
    	$this->assertEquals(0, $this->person->getVersionNumber()->getValue(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    
    	// check that the date created and updated equal to today
    	$today = date('Y-m-d');
    	$this->assertEquals($today, $this->person->getCreateTS()->getDate(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    	$this->assertEquals($today, $this->person->getUpdateTS()->getDate(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    
    	// make sure the object is transient
    	$this->assertTrue($this->person->isTransient(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    }
    
}

?>