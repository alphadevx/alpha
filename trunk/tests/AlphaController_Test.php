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
    	AlphaDAO::begin();
    	$this->controller = new Search();
    	$this->person = $this->createPersonObject('unitTestUser');
    	$this->group = new rights_object();
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 */
    protected function tearDown() {
    	AlphaDAO::rollback();
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
     * testing that objects are being added to the dirtyObject array correctly
     */
    public function testMarkDirtyAdd() {
    	$this->controller->markDirty($this->person);
    	
    	$dirtyObjects = $this->controller->getDirtyObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $dirtyObjects[0]->get('URL'), 'testing that objects are being added to the dirtyObject array correctly');	
    }
    
    /**
     * testing that objects are being added to the newObject array correctly
     */
    public function testMarkNewAdd() {
    	$this->controller->markNew($this->person);
    	
    	$newObjects = $this->controller->getNewObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $newObjects[0]->get('URL'), 'testing that objects are being added to the newObject array correctly');	
    }
    
    /**
     * test cases to see if access rights on controllers are working as expected
     * 
     * @todo add more test cases!
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
}

?>