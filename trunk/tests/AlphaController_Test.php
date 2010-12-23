<?php

require_once $config->get('sysRoot').'alpha/controller/Search.php';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/model/rights_object.inc';

/**
 *
 * Test cases for the AlphaController class.
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$ 
 * 
 */
class AlphaController_Test extends PHPUnit_Framework_TestCase {
	/**
	 * Sample controller for testing with
	 * 
	 * @var Search
	 */
	private $controller;
	
	/**
	 * A person_object for testing (any business object will do)
	 * 
	 * @var person_object
	 */
	private $person;
	
	/**
	 * Test rights group
	 * 
	 * @var rights_object
	 */
	private $group;
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
	 */
    protected function setUp() {
    	$this->controller = new Search();
    	$this->person = $this->createPersonObject('unitTestUser');
    	$this->group = new rights_object();
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 */
    protected function tearDown() {
    	$this->controller->abort();
    	// just making sure no previous test user is in the DB
        $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/');
        $this->person->deleteAllByAttribute('displayName', 'unitTestUser');
        $this->person->deleteAllByAttribute('email', 'changed@test.com');
        $this->person->deleteAllByAttribute('email', 'newuser@test.com');
        $this->group->deleteAllByAttribute('name', 'testgroup');
        unset($this->controller);
    	unset($this->person);
    	unset($this->group);
    }
    
	/**
     * creates a person object for testing
     * 
     * @return person_object
     */
    private function createPersonObject($name) {
    	$person = new person_object();
        $person->setDisplayname($name);        
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');
        
        return $person;
    }
    
    /**
     * testing that objects are being added to the dirtyObjects array correctly
     */
    public function testMarkDirtyAdd() {
    	$this->controller->markDirty($this->person);
    	
    	$dirtyObjects = $this->controller->getDirtyObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $dirtyObjects[0]->get('URL'), 'testing that objects are being added to the dirtyObject array correctly');	
    }
    
	/**
     * testing that objects are being added to the dirtyObject array correctly
     * and that this array is in the session being shared by controllers
     */
    public function testMarkDirtySession() {
    	$this->person->set('email', 'changed@test.com');
    	$this->controller->markDirty($this->person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$dirty = $controller2->getDirtyObjects();    	
    	
    	$this->assertEquals('changed@test.com', $dirty[0]->get('email'), 'testing that objects are being added to the dirtyObject array correctly and that this array is in the session being shared by controllers');	
    }
    
    /**
     * testing that objects are being added to the newObjects array correctly
     */
    public function testMarkNewAdd() {
    	$this->controller->markNew($this->person);
    	
    	$newObjects = $this->controller->getNewObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $newObjects[0]->get('URL'), 'testing that objects are being added to the newObject array correctly');	
    }
    
	/**
     * testing that objects are being added to the newObjects array correctly
     * and that this array is in the session being shared by controllers
     */
    public function testMarkNewSession() {    	
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$new = $controller2->getNewObjects();    	
    	
    	$this->assertEquals('newuser@test.com', $new[0]->get('email'), 'testing that objects are being added to the newObjects array correctly and that this array is in the session being shared by controllers');	
    }
    
    /**
     * test cases to see if access rights on controllers are working as expected
     */
    public function testRightsAccess() {
    	$this->group->set('name', 'testgroup');
    	$this->group->save();
    	
    	$this->person->save();
    	
    	$lookup = $this->person->getPropObject('rights')->getLookup();
		$lookup->setValue(array($this->person->getOID(), $this->group->getOID()));
		$lookup->save();
		
		$admin = $_SESSION['currentUser'];
		$_SESSION['currentUser'] = $this->person;
		
		try {
			$controller = new Search('testgroup');
		}catch (PHPException $e) {
			$this->fail('failed to access a controller that I have access to by rights group membership');
		}
		
		$_SESSION['currentUser'] = $admin;
    }
    
	/** 
     * test the getUnitDuration method for equality
     */
    public function testGetUnitDurationEqual() {
    	$controller1 = new Search();
    	$controller2 = new Search();
        $controller1->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    	$controller2->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    
    	$this->assertEquals($controller1->getUnitDuration(), $controller2->getUnitDuration(), 'test the getUnitDuration method for equality');
    }
    
	/** 
     * test the getUnitDuration method for greater than
     */
    public function testGetUnitDurationGreater() {
        $controller1 = new Search();
    	$controller2 = new Search();
        $controller1->setUnitEndTime(2006, 10, 30, 21, 15, 15);
    	$controller2->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    
    	$this->assertTrue($controller1->getUnitDuration() > $controller2->getUnitDuration(), 'test the getUnitDuration method for greater than');
    }
    
	/**
     * testing the setUnitOfWork method with a bad controller name
     */
    public function testSetUnitOfWorkBadControllerName() {
    	try {
    		$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','BadControllerName'));
    		$this->fail('Passed a bad controller name BadControllerName to setUnitOfWork() and did not get the expected exception!');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('', $this->controller->getFirstJob(), 'testing the setUnitOfWork method with a bad controller name');
    	}
    }
    
	/**
     * testing the setUnitOfWork method and getNextJob
     */
    public function testSetUnitOfWorkNext() {
    	$this->controller->setName('Search');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Edit', $this->controller->getNextJob(), 'testing the setUnitOfWork method and getNextJob');
    }
    
	/**
     * testing the setUnitOfWork method and getFirstJob
     */
    public function testSetUnitOfWorkFirst() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Search', $this->controller->getFirstJob(), 'testing the setUnitOfWork method and getFirstJob');
    }
    
	/**
     * testing the setUnitOfWork method and getPreviousJob
     */
    public function testSetUnitOfWorkPrevious() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Create', $this->controller->getPreviousJob(), 'testing the setUnitOfWork method and getPreviousJob');
    }
    
	/**
     * testing the setUnitOfWork method and getLastJob
     */
    public function testSetUnitOfWorkLast() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Detail', $this->controller->getLastJob(), 'testing the setUnitOfWork method and getLastJob');
    }
    
	/**
     * testing the commit method for new and dirty objects
     */
    public function testCommit() {
    	$this->person->set('email', 'changed@test.com');
    	$this->controller->markDirty($this->person);
    	
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	try {
    		$this->controller->commit();
    	}catch (FailedUnitCommitException $e) {
    		$this->fail('Failed to commit the unit of work transaction for new and dirty objects');
    	}
    }
    
	/**
     * testing that we can load dirty and new objects post commit
     */
    public function testPostCommitLoad() {
    	$this->person->set('email', 'changed@test.com');
    	$this->controller->markDirty($this->person);
    	
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	try {
    		$this->controller->commit();
    	}catch (FailedUnitCommitException $e) {
    		$this->fail('Failed to commit the unit of work transaction for new and dirty objects');
    	}
    	
    	$newPerson = new person_object();
    	try {
    		$newPerson->loadByAttribute('email', 'newuser@test.com');
    	}catch (BONotFoundException $e) {
    		$this->fail('Failed to load the new person that we commited in the unit of work');
    	}
    	
    	$dirtyPerson = new person_object();
    	try {
    		$dirtyPerson->loadByAttribute('email', 'changed@test.com');
    	}catch (BONotFoundException $e) {
    		$this->fail('Failed to load the dirty person that we commited in the unit of work');
    	}
    }
    
    /**
     * testing that aborting a unit of work clears the list of new objects
     */
    public function testAbort() {
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$new = $controller2->getNewObjects();
    	
    	$this->assertEquals('newuser@test.com', $new[0]->get('email'), 'testing that objects are being added to the newObjects array correctly and that this array is in the session being shared by controllers');

    	// now abort the unit of work from the second controller, and confirm that the new object array is empty
    	$controller2->abort();
    	
    	$new = $controller2->getNewObjects();
    	
    	$this->assertEquals(0, count($new), 'testing that aborting a unit of work clears the list of new objects');
    }
    
    /**
     * testing that the AlphaController constructor uses the controller name as the AlphaController->name (job) of the controller
     */
    public function testConstructorJobControllerName() {
    	$this->assertEquals('Search', $this->controller->getName(), 'testing that the AlphaController constructor defaults to using the controller name as the AlphaController->name of the controller');
    }
    
    /**
     * testing that providing a bad BO name returns null
     */
    public function testGetCustomControllerName() {
    	$this->assertNull(AlphaController::getCustomControllerName('does_not_exist_object', 'view'), 'testing that providing a bad BO name returns null');
    }
    
    /**
     * testing the checkRights method with various account types
     */
    public function testCheckRights() {
    	$controller = new Search('Admin');
    	$admin = $_SESSION['currentUser'];
		$_SESSION['currentUser'] = null;
		
		$this->assertFalse($controller->checkRights(), 'testing that a user with no session cannot access an Admin controller');
		$controller = new Search('Public');
		$this->assertTrue($controller->checkRights(), 'testing that a user with no session can access a Public controller');
		
		$_SESSION['currentUser'] = $admin;
    }
    
    /**
     * testing the checkSecurityFields method
     */
    public function testCheckSecurityFields() {
    	$securityFields = AlphaController::generateSecurityFields();
    	
    	$_REQUEST['var1'] = $securityFields[0];
    	$_REQUEST['var2'] = $securityFields[1];
    	
    	$this->assertTrue(AlphaController::checkSecurityFields(), 'testing the checkSecurityFields method with valid security params');
    	
    	$_REQUEST['var1'] = null;
    	$_REQUEST['var2'] = null;
    	
    	$this->assertFalse(AlphaController::checkSecurityFields(), 'testing the checkSecurityFields method with invalid security params');
    }
    
    /**
     * testing that a bad controller name passed to loadControllerDef will cause an exception
     */
    public function testLoadControllerDef() {
    	try {
    		$this->controller->loadControllerDef('DoesNotExist');
    		$this->fail('testing that a bad controller name passed to loadControllerDef will cause an exception');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('The class [DoesNotExist] is not defined anywhere!', $e->getMessage(), 'testing that a bad controller name passed to loadControllerDef will cause an exception');
    	}
    }
}

?>