<?php

/**
 *
 * Test case for the AlphaDAO class
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class AlphaDAO_Test extends PHPUnit_Framework_TestCase
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
    	AlphaDAO::begin();
    	$this->person = $this->createPersonObject('unitTestUser');
        // just making sure no previous test user is in the DB
        $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/');
        $this->person->deleteAllByAttribute('displayName', 'unitTestUser');
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {    	
    	AlphaDAO::rollback();
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
     * test that the constructor sets the correct values of the "house keeping" attributes
     */
    public function testDefaultHouseKeepingValues() {
    	// make sure the person logged in is the same person to create/update the object
    	$this->assertEquals($_SESSION['currentUser']->getID(), $this->person->getCreatorId()->getValue(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    	$this->assertEquals($_SESSION['currentUser']->getID(), $this->person->getUpdatorId()->getValue(), 'test that the constructor sets the correct values of the "house keeping" attributes');
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
    	$this->person->loadByAttribute('displayName','unitTestUser');
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

    /**
     * testing the validation method
     */
    public function testValidation() {    	
    	try {
    		$person = new person_object();
    		$person->save();
    		$this->fail('testing the validation method');
    	}catch (ValidationException $e) {
    		$this->assertEquals('Failed to save, validation error is:',
    						substr($e->getMessage(), 0, 36),
    						'testing the validation method');    		
    	}
    }
    
    /**
     * testing the delete method
     */
    public function testDelete() {
    	$this->person->save();
    	$this->assertFalse($this->person->isTransient(), 'testing the delete method');
    	$id = $this->person->getID();
    	$this->person->delete();
    	// gone from memory (all attributes null)    	
    	$this->assertEquals(0, count(get_object_vars($this->person)), 'testing the delete method');
    	// gone from the database
    	try {
    		$this->person = new person_object();
    		$this->person->load($id);
    		$this->fail('testing the delete method');
    	}catch (BONotFoundException $e) {
    		$this->assertEquals('Failed to load object',
    						substr($e->getMessage(), 0, 21),
    						'testing the delete method');
    	}
    }
    
    /**
     * testing the deleteAllByAttribute method
     */
    public function testDeleteAllByAttribute() {
    	$person1 = new person_object();
        $person1->setDisplayname('unitTestUser1');        
        $person1->set('email', 'unitTestUser1@test.com');
        $person1->set('password', 'passwordTest');
        $person1->set('URL', 'http://unitTestUser/');
    	
        $person2 = new person_object();
        $person2->setDisplayname('unitTestUser2');        
        $person2->set('email', 'unitTestUser2@test.com');
        $person2->set('password', 'passwordTest');
        $person2->set('URL', 'http://unitTestUser/');
        
        $person3 = new person_object();
        $person3->setDisplayname('unitTestUser3');        
        $person3->set('email', 'unitTestUser3@test.com');
        $person3->set('password', 'passwordTest');
        $person3->set('URL', 'http://unitTestUser/');
        
        $person1->save();
        $person2->save();
        $person3->save();
        $this->assertEquals(3, $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/'), 'testing the deleteAllByAttribute method');
    }
    
    /**
     * testing the version numbers of business objects
     */
    public function testGetVersion() {
    	$this->assertEquals(0, $this->person->getVersion(), 'testing the version numbers of business objects');
    	$this->assertEquals(0, $this->person->getVersionNumber()->getValue(), 'testing the version numbers of business objects');
    	$this->person->save();
    	$this->assertEquals(1, $this->person->getVersion(), 'testing the version numbers of business objects');
    	$this->assertEquals(1, $this->person->getVersionNumber()->getValue(), 'testing the version numbers of business objects');
    	$this->person->save();
    	$this->assertEquals(2, $this->person->getVersion(), 'testing the version numbers of business objects');
    	$this->assertEquals(2, $this->person->getVersionNumber()->getValue(), 'testing the version numbers of business objects');
    }
    
    /**
     * testing the getMAX method
     */
    public function testGetMAX() {
    	$this->person->save();
    	$max = $this->person->getMAX();
    	$person2 = $this->createPersonObject('unitTestUser2');
    	$person2->save();    	
    	$this->assertEquals($max+1, $this->person->getMAX(), 'testing the getMAX method');
    }
    
    /**
     * testing the getCount method
     */
    public function testGetCount() {    	
    	$count = $this->person->getCount();
    	$this->person->save();    	
    	$this->assertEquals($count+1, $this->person->getCount(), 'testing the getCount method');
    }
    
    /**
     * testing the setEnumOptions method is loading enum options correctly
     */
    public function testSetEnumOptions() {
    	$this->person->save();
    	$id = $this->person->getMAX();
    	$this->person->load($id);
    	$this->assertTrue(in_array('Active', $this->person->getPropObject('state')->getOptions()), 'testing the setEnumOptions method is loading enum options correctly');
    }
    
    /**
     * testing that checkTableExists returns true for the person BO
     */
    public function testCheckTableExists() {
    	$this->assertTrue($this->person->checkTableExists(), 'testing that checkTableExists returns true for the person BO');
    }
    
	/**
     * testing that checkTableNeedsUpdate returns false for the person BO
     */
    public function testCheckTableNeedsUpdate() {
    	$this->assertFalse($this->person->checkTableNeedsUpdate(), 'testing that checkTableNeedsUpdate returns false for the person BO');
    }
    
    /**
     * testing to ensure that the getTableName method can read the TABLE_NAME constant declared in the child class
     */
    public function testGetTableName() {
    	$this->assertEquals('person', $this->person->getTableName(), 'testing to ensure that the getTableName method can read the TABLE_NAME constant declared in the child class'); 
    }
    
    /**
     * testing the getDataLabel method
     */
    public function testGetDataLabel() {
    	$this->assertEquals('E-mail Address', $this->person->getDataLabel('email'), 'testing the getDataLabel method');
    }
    
    /**
     * testing get on a String attribute with no child get method available
     */
    public function testGetNoChildMethod() {
    	$email = $this->person->get('email');
    	
    	$this->assertEquals('unitTestUser@test.com', $email, 'testing get on a String attribute with no child get method available');
    }
    
	/**
     * testing get on an Enum attribute with a child method available, with $noChildMethods disabled (default)
     */
    public function testGetNoChildMethodsDisabled() {
    	$state = $this->person->getPropObject('state');
    	
    	$this->assertEquals('Enum', get_class($state), 'testing get on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)');
    	$this->assertEquals('Active', $state->getValue(), 'testing get on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)');
    }
    
	/**
     * testing get on an Enum attribute with a child method available, with $noChildMethods enabled
     */
    public function testGetNoChildMethodsEnabled() {
    	$state = $this->person->get('state', true);
    	
    	$this->assertEquals('Active', $state, 'testing get on an Enum attribute with a child method avaialble, with $noChildMethods enabled');
    }
    
    /**
     * testing get on a simple data type
     */
    public function testGetSimpleType() {
    	$labels = $this->person->get('dataLabels');
    	
    	$this->assertTrue(is_array($labels), 'testing get on a simple data type');
    }    
    
    /**
     * testing set on a String attribute with no child get method available
     */
    public function testSetNoChildMethod() {
    	$this->person->set('email','test@test.com');    	
    	
    	$this->assertEquals('test@test.com', $this->person->get('email'), 'testing set on a String attribute with no child get method available');
    }
    
	/**
     * testing set on an Enum attribute with a child method available, with $noChildMethods disabled (default)
     */
    public function testSetNoChildMethodsDisabled() {
    	$this->person->set('state','Active');

    	$this->assertEquals('Active', $this->person->get('state'), 'testing set on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)');
    }
    
	/**
     * testing set on an Enum attribute with a child method available, with $noChildMethods enabled
     */
    public function testSetNoChildMethodsEnabled() {
    	$this->person->set('state','Active', true);
    	    	
    	$this->assertEquals('Active', $this->person->get('state'), 'testing set on an Enum attribute with a child method avaialble, with $noChildMethods enabled');
    }
    
    /**
     * testing set on a simple data type
     */
    public function testSetSimpleType() {
    	$this->person->set('dataLabels', array('key'=>'value'));
    	
    	$labels = $this->person->get('dataLabels');
    	
    	$this->assertTrue(is_array($labels), 'testing set on a simple data type');
    	$this->assertEquals('value', $labels['key'], 'testing set on a simple data type');
    }
    
    /**
     * testing getPropObject on a complex type
     */
    public function testGetPropObjectComplexType() {
    	$state = $this->person->getPropObject('state');
    	
    	$this->assertEquals('Enum', get_class($state), 'testing getPropObject on a complex type');
    	$this->assertEquals('Active', $state->getValue(), 'testing getPropObject on a complex type');
    }
    
	/**
     * testing getPropObject on a simple type
     */
    public function testGetPropObjectSimpleType() {
    	$labels = $this->person->getPropObject('dataLabels');
    	
    	$this->assertTrue(is_array($labels), 'testing getPropObject on a simple type');
    	$this->assertEquals('E-mail Address', $labels['email'], 'testing getPropObject on a simple type');
    }
    
    /**
     * testing that markTransient and markPersistent methods 
     */
    public function testMarkTransientPersistent() {
    	// initial save
    	$this->person->save();
    	
    	// now mark the URL transient, and save again (old URL value should not be overwritten)
    	$this->person->markTransient('URL');
    	$this->assertTrue(in_array('URL', $this->person->getTransientAttributes()), 'testing that markTransient and markPersistent methods');
    	$this->person->set('URL','http://www.alphaframework.org/');
    	$this->person->save();
    	
    	// used to ensure that we attempt to reload it from the DB
    	$this->person->markPersistent('URL');
    	$this->assertFalse(in_array('URL', $this->person->getTransientAttributes()), 'testing that markTransient and markPersistent methods');  	
    	// reload from DB
    	$this->person->reload();
    	
    	$this->assertEquals('http://unitTestUser/', $this->person->get('URL'), 'testing that markTransient and markPersistent methods');
    }
    
    /**
     * testing the getDataLabels method
     */
    public function testGetDataLabels() {
    	$this->assertTrue(is_array($this->person->getDataLabels()), 'testing the getDataLabels method');
    	$labels = $this->person->getDataLabels();
    	$this->assertTrue(in_array('OID', array_keys($labels)), 'testing the getDataLabels method');
    	$this->assertTrue(in_array('E-mail Address', $labels), 'testing the getDataLabels method');
    }
    
    /**
     * testing the getTransientAttributes method in conjunction with markTransient/markPersistent
     */
    public function testGetTransientAttributes() {
    	$this->assertTrue(is_array($this->person->getTransientAttributes()), 'testing the getTransientAttributes method in conjunction with markTransient/markPersistent');
    	$this->person->markTransient('URL');
    	$this->assertTrue(in_array('URL', $this->person->getTransientAttributes()), 'testing the getTransientAttributes method in conjunction with markTransient/markPersistent');
    	$this->person->markPersistent('URL');
    	$this->assertFalse(in_array('URL', $this->person->getTransientAttributes()), 'testing the getTransientAttributes method in conjunction with markTransient/markPersistent');
    }
    
    /**
     * testing isTransient before and after save
     */
    public function testIsTransient() {
    	$this->assertTrue($this->person->isTransient(), 'testing isTransient before and after save');
    	$this->person->save();
    	$this->assertFalse($this->person->isTransient(), 'testing isTransient before and after save');
    }
    
    /**
     * testing the getLastQuery method after various persistance calls
     */
    public function testGetLastQuery() {
    	$this->person->save();
    	$this->assertEquals('INSERT INTO person', substr($this->person->getLastQuery(), 0, 18), 'testing the getLastQuery method after various persistance calls');
    	$this->person->checkTableNeedsUpdate();
    	$this->assertEquals('SHOW INDEX FROM person', substr($this->person->getLastQuery(), 0, 22), 'testing the getLastQuery method after various persistance calls');
    	$this->person->getCount();
    	$this->assertEquals('SELECT COUNT(OID)', substr($this->person->getLastQuery(), 0, 17), 'testing the getLastQuery method after various persistance calls');
    	$this->person->getMAX();
    	$this->assertEquals('SELECT MAX(OID)', substr($this->person->getLastQuery(), 0, 15), 'testing the getLastQuery method after various persistance calls');
    	$this->person->load($this->person->getID());
    	$this->assertEquals('SHOW COLUMNS FROM person', substr($this->person->getLastQuery(), 0, 24), 'testing the getLastQuery method after various persistance calls');
    }
    
    /**
     * testing the clear method for unsetting the attributes of an object 
     */
    public function testClear() {
    	$state = $this->person->get('state');
    	$this->assertTrue(!empty($state), 'testing the clear method for unsetting the attributes of an object');
    	
    	$reflection = new ReflectionClass(get_class($this->person));
    	$properties = $reflection->getProperties();

		foreach($properties as $propObj) {
			$propName = $propObj->name;			
			if(!in_array($propName, $this->person->getDefaultAttributes()) && !in_array($propName, $this->person->getTransientAttributes())) {
				$this->assertNotNull($this->person->get($propName), 'testing the clear method for unsetting the attributes of an object');
			}
		}
		
		// delete will invoke clear(), which is private
    	$this->person->delete();
    	
    	try {
    		$state = $this->person->get('state');
    		$this->fail('testing the clear method for unsetting the attributes of an object');
    	} catch (AlphaException $e) {
	    	$reflection = new ReflectionClass(get_class($this->person));
	    	$properties = $reflection->getProperties();
	
			foreach($properties as $propObj) {
				$propName = $propObj->name;
				
				try {
					$this->person->get($propName);
				} catch (PHPException $e) {
					$this->assertEquals('[PHP error]: Undefined property:  person_object::$'.$propName, $e->getMessage(), 'testing the clear method for unsetting the attributes of an object');
				} catch (AlphaException $e) {
					$this->assertEquals('Could not access the property ['.$propName.'] on the object of class [person_object]', $e->getMessage(), 'testing the clear method for unsetting the attributes of an object');
				}
			}
    	}
    }
    
    /**
     * Testing the saveAttribute method
     */
    public function testSaveAttribute() {
    	$this->person->save();
    	$this->person->saveAttribute('displayName', 'unitTestUserNew');
    	
    	$this->assertEquals('unitTestUserNew', $this->person->getDisplayName()->getValue(), 'Testing that the value was set on the object in memory along with saving to the database');
    	
    	$person = new person_object();
    	
    	try {
    		$person->loadByAttribute('displayName', 'unitTestUserNew');
    		$this->assertEquals('unitTestUserNew', $person->getDisplayName()->getValue(), 'Testing that the value was saved to the database');
    	} catch (BONotFoundException $e) {
    		$this->fail('Failed to load the BO that was updated with the saveAttribute method');
    	}
    }
}

?>