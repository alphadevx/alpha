<?php

require_once $config->get('sysRoot').'alpha/controller/Search.php';
require_once $config->get('sysRoot').'alpha/model/PersonObject.inc';
require_once $config->get('sysRoot').'alpha/model/ArticleObject.inc';
require_once $config->get('sysRoot').'alpha/model/RightsObject.inc';

/**
 *
 * Test cases for the AlphaController class.
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class AlphaController_Test extends PHPUnit_Framework_TestCase {
	/**
	 * Sample controller for Testing with
	 * 
	 * @var Search
	 * @since 1.0
	 */
	private $controller;
	
	/**
	 * An ArticleObject for Testing
	 * 
	 * @var ArticleObject
	 * @since 1.0
	 */
	private $article;
	
	/**
	 * A PersonObject for Testing (any business object will do)
	 * 
	 * @var PersonObject
	 * @since 1.0
	 */
	private $person;
	
	/**
	 * Test rights group
	 * 
	 * @var RightsObject
	 * @since 1.0
	 */
	private $group;
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
	 * 
	 * @since 1.0
	 */
    protected function setUp() {
    	$tag = new TagObject();
        $tag->rebuildTable();
        
    	$denum = new DEnum();
        $denum->rebuildTable();
        
        $item = new DEnumItem();
        $item->rebuildTable();
        
        $article = new ArticleObject();
        $article->rebuildTable();
        
    	$this->controller = new Search();
    	
    	$this->person = $this->createPersonObject('unitTestUser');
    	$this->person->rebuildTable();
    	
    	$this->article = $this->createArticleObject('unitTestArticle');
    	$this->article->rebuildTable();
    	
    	$this->group = new RightsObject();
    	$this->group->rebuildTable();
    	$this->group->set('name', 'Admin');
    	$this->group->save();
    	
    	
    	$lookup = $this->group->getMembers()->getLookup();
		$lookup->setValue(array($_SESSION['currentUser']->getOID(), $this->group->getOID()));
		$lookup->save();
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 * 
	 * @since 1.0
	 */
    protected function tearDown() {
		$this->controller->abort();
		
    	$this->article->dropTable();
        unset($this->article);
        
        unset($this->controller);
        
    	$this->person->dropTable();
    	unset($this->person);
    	
    	$this->group->dropTable();
    	$this->group->dropTable('Person2Rights');
    	unset($this->group);
    	
    	$article = new ArticleObject();
        $article->dropTable();
    	
    	$tag = new TagObject();
        $tag->dropTable();
    	
    	$denum = new DEnum();
    	$denum->dropTable();
        
        $item = new DEnumItem();
        $item->dropTable();
    }
    
	/**
     * Creates a person object for Testing
     * 
     * @return PersonObject
     * @since 1.0
     */
    private function createPersonObject($name) {
    	$person = new PersonObject();
        $person->setDisplayname($name);        
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');
        
        return $person;
    }
    
	/**
     * Creates an article object for Testing
     * 
     * @return ArticleObject
     * @since 1.0
     */
	private function createArticleObject($name) {
    	$article = new ArticleObject();
        $article->set('title', $name);
        $article->set('description', 'unitTestArticleTagOne unitTestArticleTagTwo');
        $article->set('author', 'unitTestArticleTagOne');
        $article->set('content', 'unitTestArticleTagOne');        
        
        return $article;
    }
    
    /**
     * Testing that objects are being added to the dirtyObjects array correctly
     * 
     * @since 1.0
     */
    public function testMarkDirtyAdd() {
    	$this->controller->markDirty($this->person);
    	
    	$dirtyObjects = $this->controller->getDirtyObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $dirtyObjects[0]->get('URL'), 'Testing that objects are being added to the dirtyObject array correctly');	
    }
    
	/**
     * Testing that objects are being added to the dirtyObject array correctly
     * and that this array is in the session being shared by controllers
     * 
     * @since 1.0
     */
    public function testMarkDirtySession() {
    	$this->person->set('email', 'changed@test.com');
    	$this->controller->markDirty($this->person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$dirty = $controller2->getDirtyObjects();    	
    	
    	$this->assertEquals('changed@test.com', $dirty[0]->get('email'), 'Testing that objects are being added to the dirtyObject array correctly and that this array is in the session being shared by controllers');	
    }
    
    /**
     * Testing that objects are being added to the newObjects array correctly
     * 
     * @since 1.0
     */
    public function testMarkNewAdd() {
    	$this->controller->markNew($this->person);
    	
    	$newObjects = $this->controller->getNewObjects();
    	
    	$this->assertEquals('http://unitTestUser/', $newObjects[0]->get('URL'), 'Testing that objects are being added to the newObject array correctly');	
    }
    
	/**
     * Testing that objects are being added to the newObjects array correctly
     * and that this array is in the session being shared by controllers
     * 
     * @since 1.0
     */
    public function testMarkNewSession() {    	
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$new = $controller2->getNewObjects();    	
    	
    	$this->assertEquals('newuser@test.com', $new[0]->get('email'), 'Testing that objects are being added to the newObjects array correctly and that this array is in the session being shared by controllers');	
    }
    
    /**
     * test cases to see if access rights on controllers are working as expected
     * 
     * @since 1.0
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
     * 
     * @since 1.0
     */
    public function testGetUnitDurationEqual() {
    	$controller1 = new Search();
    	$controller2 = new Search();
        $controller1->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    	$controller2->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    
    	$this->assertEquals($controller1->getUnitDuration(), $controller2->getUnitDuration(), 'test the getUnitDuration method for equality');
    }
    
	/** 
     * Test the getUnitDuration method for greater than
     * 
     * @since 1.0
     */
    public function testGetUnitDurationGreater() {
        $controller1 = new Search();
    	$controller2 = new Search();
        $controller1->setUnitEndTime(2006, 10, 30, 21, 15, 15);
    	$controller2->setUnitEndTime(2005, 10, 30, 21, 15, 15);
    
    	$this->assertTrue($controller1->getUnitDuration() > $controller2->getUnitDuration(), 'Test the getUnitDuration method for greater than');
    }
    
	/**
     * Testing the setUnitOfWork method with a bad controller name
     * 
     * @since 1.0
     */
    public function testSetUnitOfWorkBadControllerName() {
    	try {
    		$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','BadControllerName'));
    		$this->fail('Passed a bad controller name BadControllerName to setUnitOfWork() and did not get the expected exception!');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('', $this->controller->getFirstJob(), 'Testing the setUnitOfWork method with a bad controller name');
    	}
    }
    
	/**
     * Testing the setUnitOfWork method and getNextJob
     * 
     * @since 1.0
     */
    public function testSetUnitOfWorkNext() {
    	$this->controller->setName('Search');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Edit', $this->controller->getNextJob(), 'Testing the setUnitOfWork method and getNextJob');
    }
    
	/**
     * Testing the setUnitOfWork method and getFirstJob
     * 
     * @since 1.0
     */
    public function testSetUnitOfWorkFirst() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Search', $this->controller->getFirstJob(), 'Testing the setUnitOfWork method and getFirstJob');
    }
    
	/**
     * Testing the setUnitOfWork method and getPreviousJob
     * 
     * @since 1.0
     */
    public function testSetUnitOfWorkPrevious() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Create', $this->controller->getPreviousJob(), 'Testing the setUnitOfWork method and getPreviousJob');
    }
    
	/**
     * Testing the setUnitOfWork method and getLastJob
     * 
     * @since 1.0
     */
    public function testSetUnitOfWorkLast() {
    	$this->controller->setName('ListAll');
    	$this->controller->setUnitOfWork(array('Search','Edit','Create','ListAll','Detail'));
    	
    	$this->assertEquals('Detail', $this->controller->getLastJob(), 'Testing the setUnitOfWork method and getLastJob');
    }
    
	/**
     * Testing the commit method for new and dirty objects
     * 
     * @since 1.0
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
     * Testing that we can load dirty and new objects post commit
     * 
     * @since 1.0
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
    	
    	$newPerson = new PersonObject();
    	try {
    		$newPerson->loadByAttribute('email', 'newuser@test.com');
    	}catch (BONotFoundException $e) {
    		$this->fail('Failed to load the new person that we commited in the unit of work');
    	}
    	
    	$dirtyPerson = new PersonObject();
    	try {
    		$dirtyPerson->loadByAttribute('email', 'changed@test.com');
    	}catch (BONotFoundException $e) {
    		$this->fail('Failed to load the dirty person that we commited in the unit of work');
    	}
    }
    
    /**
     * Testing that aborting a unit of work clears the list of new objects
     * 
     * @since 1.0
     */
    public function testAbort() {
    	$person = $this->createPersonObject('newuser');
    	$person->set('email', 'newuser@test.com'); 
    	$this->controller->markNew($person);
    	
    	// calling the constructor of the other controller will check the session
    	$controller2 = new Search();
    	
    	$new = $controller2->getNewObjects();
    	
    	$this->assertEquals('newuser@test.com', $new[0]->get('email'), 'Testing that objects are being added to the newObjects array correctly and that this array is in the session being shared by controllers');

    	// now abort the unit of work from the second controller, and confirm that the new object array is empty
    	$controller2->abort();
    	
    	$new = $controller2->getNewObjects();
    	
    	$this->assertEquals(0, count($new), 'Testing that aborting a unit of work clears the list of new objects');
    }
    
    /**
     * Testing that the AlphaController constructor uses the controller name as the AlphaController->name (job) of the controller
     * 
     * @since 1.0
     */
    public function testConstructorJobControllerName() {
    	$this->assertEquals('Search', $this->controller->getName(), 'Testing that the AlphaController constructor defaults to using the controller name as the AlphaController->name of the controller');
    }
    
    /**
     * Testing that providing a bad BO name returns null
     * 
     * @since 1.0
     */
    public function testGetCustomControllerName() {
    	$this->assertNull(AlphaController::getCustomControllerName('DoesNotExistObject', 'view'), 'Testing that providing a bad BO name returns null');
    }
    
    /**
     * Testing the checkRights method with various account types
     * 
     * @since 1.0
     */
    public function testCheckRights() {
    	$controller = new Search('Admin');
    	$admin = $_SESSION['currentUser'];
		$_SESSION['currentUser'] = null;
		
		$this->assertFalse($controller->checkRights(), 'Testing that a user with no session cannot access an Admin controller');
		$controller = new Search('Public');
		$this->assertTrue($controller->checkRights(), 'Testing that a user with no session can access a Public controller');
		
		$_SESSION['currentUser'] = $admin;
    }
    
    /**
     * Testing the checkSecurityFields method
     * 
     * @since 1.0
     */
    public function testCheckSecurityFields() {
    	$securityFields = AlphaController::generateSecurityFields();
    	
    	$_REQUEST['var1'] = $securityFields[0];
    	$_REQUEST['var2'] = $securityFields[1];
    	
    	$this->assertTrue(AlphaController::checkSecurityFields(), 'Testing the checkSecurityFields method with valid security params');
    	
    	$_REQUEST['var1'] = null;
    	$_REQUEST['var2'] = null;
    	
    	$this->assertFalse(AlphaController::checkSecurityFields(), 'Testing the checkSecurityFields method with invalid security params');
    }
    
    /**
     * Testing that a bad controller name passed to loadControllerDef will cause an exception
     * 
     * @since 1.0
     */
    public function testLoadControllerDef() {
    	try {
    		$this->controller->loadControllerDef('DoesNotExist');
    		$this->fail('Testing that a bad controller name passed to loadControllerDef will cause an exception');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('The class [DoesNotExist] is not defined anywhere!', $e->getMessage(), 'Testing that a bad controller name passed to loadControllerDef will cause an exception');
    	}
    }
    
    /**
     * Testing that status messages can be shared between controllers via the session
     * 
     * @since 1.0
     */
    public function testStatusMessages() {
    	$this->controller->setStatusMessage('test message');
    	
    	$controller = new Search();
    	
    	$this->assertEquals('test message', $controller->getStatusMessage(), 'Testing that status messages can be shared between controllers via the session');
    }
    
    /**
     * Testing that a BO attached to a controller that contains tags will have those tags mapped to the controller's keywords
     * 
     * @since 1.0
     */
    public function testTagsMapToMetaKeywords() {
    	AlphaDAO::begin();
    	$this->article->save();
    	AlphaDAO::commit();
    	$tags = $this->article->getPropObject('tags')->getRelatedObjects();
    	
    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertTrue($found, 'Testing the TagObject::tokenize method returns a tag called "unittestarticle"');
    	
    	$this->controller->setBO($this->article);
    	
    	$this->assertEquals('unittestarticle,unittestarticletagone,unittestarticletagtwo', $this->controller->getKeywords(), 'Testing that a BO attached to a controller that contains tags will have those tags mapped to the controller\'s keywords');
    	
    }
}

?>