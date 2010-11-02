<?php

require_once $config->get('sysRoot').'alpha/controller/Search.php';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';

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
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
	 */
    protected function setUp() {
    	$this->controller = new Search();
    	$this->person = $this->createPersonObject('unitTestUser');
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 */
    protected function tearDown() {
    	unset($this->controller);
    	unset($this->person);
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
    function testMarkDirtyAdd() {
    	$this->controller->markDirty($this->person);
    	
    	$dirtyObjects = $this->controller->getDirtyObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $dirtyObjects[0]->get('URL'), 'testing that objects are being added to the dirtyObject array correctly');	
    }
}

?>