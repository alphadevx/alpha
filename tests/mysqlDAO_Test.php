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
        $this->person->setDisplayname('unitTestUser');        
        $this->person->set('email', 'unitTestUser@test.com');
        $this->person->set('password', 'passwordTest');
        // just making sure no previous test user is in the DB
        $this->person->deleteAllByAttribute('displayName', 'unitTestUser');
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
    
    /**
     * testing the basic load/save functionality
     */
    public function testBasicLoadSave() {
    	$this->person->save();
    	$id = $this->person->getMAX();
    	$this->person->load($id);
    	$this->assertEquals('unitTestUser', $this->person->getDisplayname()->getValue(), 'testing the basic load/save functionality');
    }
    
    /**
     * testing the loadByAttribute method
     */
    public function testLoadByAttribute() {
    	$this->person->save();
    	$this->person->loadByAttribute('displayname','unitTestUser');
    	$this->assertEquals('unitTestUser@test.com', $this->person->get('email'), 'testing the loadByAttribute method');
    	$this->person->loadByAttribute('email','unitTestUser@test.com');
    	$this->assertEquals('unitTestUser', $this->person->getDisplayname()->getValue(), 'testing the loadByAttribute method');
    }
    
    /**
     * testing loadAll method
     */
    public function testLoadAll() {
    	$peopleCount = $this->person->getCount();
    	$people = $this->person->loadAll();
    	$this->assertEquals($peopleCount, count($people), 'testing loadAll method');
    	// only load 1
    	$people = $this->person->loadAll(0, 1);
    	$this->assertEquals(1, count($people), 'testing loadAll method');
    }
    
    /**
     * testing the loadAllByAttribute method
     */
    public function testLoadAllByAttribute() {
    	$this->person->save();
    	$people = $this->person->loadAllByAttribute('email','unitTestUser@test.com');
    	$this->assertEquals(1, count($people), 'testing the loadAllByAttribute method');
    	$this->assertEquals('unitTestUser', $people[0]->getDisplayname()->getValue(), 'testing the loadAllByAttribute method');
    	$people[0]->delete();
    }
    
    /**
     * testing the save method on transient and non-transient objects
     */
    public function testSaveTransientOrPersistent() {
    	// its transient, so query will insert
    	$this->person->save();
    	$this->assertEquals('INSERT', substr($this->person->getLastQuery(), 0, 6), 'testing the save method on transient and non-transient objects');
    	// its now persistent, so query will update
    	$this->person->save();
    	$this->assertEquals('UPDATE', substr($this->person->getLastQuery(), 0, 6), 'testing the save method on transient and non-transient objects');
    }
    
    /**
     * testing to ensure that a transient object, once saved, will have an OID
     */
    public function testSaveTransientOID() {
    	$this->assertTrue($this->person->isTransient(), 'testing to ensure that a transient object, once saved, will have an OID');
    	$this->person->save();
    	$this->assertGreaterThan(0, $this->person->getID(), 'testing to ensure that a transient object, once saved, will have an OID');
    	$this->assertFalse($this->person->isTransient(), 'testing to ensure that a transient object, once saved, will have an OID');
    }
    
    /**
     * testing optimistic locking mechanism
     */
    public function testSaveObjectLocking() {
    	try {
    		$this->person->save();
    		
    		$personInstance1 = new person_object();
    		$personInstance1->load($this->person->getID());
    		$personInstance2 = new person_object();
    		$personInstance2->load($this->person->getID());
    		
    		$personInstance1->save();
    		$personInstance2->save();
    		$this->fail('testing optimistic locking mechanism');
    	}catch (LockingException $e) {
    		$this->assertEquals('Could not save the object as it has been updated by another user.  Please try saving again.',
    						$e->getMessage(),
    						'testing optimistic locking mechanism');
    	}
    }    
}

?>