<?php

namespace Alpha\Test\Model;

use Alpha\Model\ActiveRecord;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\BadRequest;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\ArticleVote;
use Alpha\Model\BlacklistedIP;
use Alpha\Model\Tag;
use Alpha\Model\Type\RelationLookup;
use Alpha\Model\Type\SmallText;
use Alpha\Exception\LockingException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\PHPException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;

/**
 * Test case for the ActiveRecord class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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
 */
class ActiveRecordTest extends ModelTestCase
{
    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);

            $rights = new Rights();
            $rights->rebuildTable();

            $standardGroup = new Rights();
            $standardGroup->set('name', 'Standard');
            $standardGroup->save();

            $request = new BadRequest();
            $request->rebuildTable();

            $this->person = $this->createPersonObject('unitTestUser');
            $this->person->rebuildTable();

            $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Rights');

            // just making sure no previous test user is in the DB
            $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/');
            $this->person->deleteAllByAttribute('username', 'unitTestUser');

            $article = new Article();
            $article->rebuildTable();
            $comment = new ArticleComment();
            $comment->rebuildTable();
            $tag = new Tag();
            $tag->rebuildTable();
        }
    }

    /**
     * Testing the createForeignIndex method
     *
     * @since 2.0.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testCreateForeignIndex($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $article = new Article();

        $article->createForeignIndex('author', 'Alpha\Model\Person', 'username');

        $this->assertTrue(in_array('Article_author_fk_idx', $article->getIndexes()), 'Testing the createForeignIndex method');
    }

    /**
     * Test that the constructor sets the correct values of the "house keeping" attributes.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testDefaultHouseKeepingValues($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', $this->person);

        $request = new BadRequest();
        $request->set('IP', '127.0.0.1');
        $request->save();

        // make sure the person logged in is the same person to create/update the object
        $this->assertEquals(
            $session->get('currentUser')->getID(),
            $request->getCreatorId()->getValue(),
            'test that the constructor sets the correct values of the "house keeping" attributes'
        );
        $this->assertEquals(
            $session->get('currentUser')->getID(),
            $request->getUpdatorId()->getValue(),
            'test that the constructor sets the correct values of the "house keeping" attributes'
        );

        $request = new BadRequest();

        // as it is a new object, make sure the version number is zero
        $this->assertEquals(
            0,
            $request->getVersionNumber()->getValue(),
            'test that the constructor sets the correct values of the "house keeping" attributes'
        );

        // check that the date created and updated equal to today
        $today = date('Y-m-d');
        $this->assertEquals(
            $today,
            $request->getCreateTS()->getDate(),
            'test that the constructor sets the correct values of the "house keeping" attributes'
        );
        $this->assertEquals(
            $today,
            $request->getUpdateTS()->getDate(),
            'test that the constructor sets the correct values of the "house keeping" attributes'
        );

        // make sure the object is transient
        $this->assertTrue($request->isTransient(), 'test that the constructor sets the correct values of the "house keeping" attributes');
    }

    /**
     * Testing the basic load/save functionality.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testBasicLoadSave($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $id = $this->person->getMAX();
        $this->person->load($id);
        $this->assertEquals('unitTestUser', $this->person->getUsername()->getValue(), 'Testing the basic load/save functionality');
    }

    /**
     * Testing that load will create a table if it does not already exist
     *
     * @since 2.0.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadCreatesMissingTable($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->checkTableExists(), 'Testing that load will create a table if it does not already exist');
        $this->person->dropTable();
        $this->assertFalse($this->person->checkTableExists(), 'Testing that load will create a table if it does not already exist');

        try {
            $this->person->load('123');
            $this->fail('Testing that load will create a table if it does not already exist');
        } catch (RecordNotFoundException $e) {
            $this->assertEquals('Failed to load object of ID [123], table [Person] did not exist so had to create!', $e->getMessage(), 'Testing that load will create a table if it does not already exist');
        }

        $this->assertTrue($this->person->checkTableExists(), 'Testing that load will create a table if it does not already exist');
    }

    /**
     * Testing the checkRecordExists method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testCheckRecordExists($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $person = new Person();
        $this->assertTrue($person->checkRecordExists($this->person->getID()), 'Testing the checkRecordExists method');
    }

    /**
     * Testing the loadByAttribute method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadByAttribute($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $this->person->loadByAttribute('username', 'unitTestUser');
        $this->assertEquals('unitTestUser@test.com', $this->person->get('email'), 'Testing the loadByAttribute method');
        $this->person->loadByAttribute('email', 'unitTestUser@test.com');
        $this->assertEquals('unitTestUser', $this->person->getUsername()->getValue(), 'Testing the loadByAttribute method');
    }

    /**
     * Testing loadAllOldVersions method.
     *
     * @since 2.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAllOldVersions($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->setMaintainHistory(true);
        $this->person->rebuildTable();

        $this->person->set('username', 'unitTestUser1');
        $this->person->save();

        $this->assertEquals(1, $this->person->getHistoryCount(), 'Testing loadAllOldVersions method');
        $this->assertEquals('unitTestUser1', $this->person->loadAllOldVersions($this->person->getID())[0]->getUsername()->getValue());

        $this->person->saveAttribute('username', 'unitTestUser2');

        $this->assertEquals(2, $this->person->getHistoryCount(), 'Testing loadAllOldVersions method');
        $this->assertEquals('unitTestUser1', $this->person->loadAllOldVersions($this->person->getID())[0]->getUsername()->getValue());
        $this->assertEquals('unitTestUser2', $this->person->loadAllOldVersions($this->person->getID())[1]->getUsername()->getValue());

        $this->person->dropTable('Person_history');
    }

    /**
     * Testing loadAll method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAll($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $peopleCount = $this->person->getCount();
        $people = $this->person->loadAll();
        $this->assertEquals($peopleCount, count($people), 'Testing loadAll method');
        // only load 1
        $people = $this->person->loadAll(0, 1);
        $this->assertEquals(1, count($people), 'Testing loadAll method');
    }

    /**
     * Testing the loadAllByAttribute method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAllByAttribute($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $people = $this->person->loadAllByAttribute('email', 'unitTestUser@test.com');
        $this->assertEquals(1, count($people), 'Testing the loadAllByAttribute method');
        $this->assertEquals('unitTestUser', $people[0]->getUsername()->getValue(), 'Testing the loadAllByAttribute method');
        $people[0]->delete();
    }

    /**
     * Testing the loadAllByAttributes method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAllByAttributes($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $people = $this->person->loadAllByAttributes(array('ID'), array($this->person->getID()));
        $this->assertEquals(1, count($people), 'Testing the loadAllByAttribute method');
        $this->assertEquals('unitTestUser', $people[0]->getUsername()->getValue(), 'Testing the loadAllByAttributes method');
        $people[0]->delete();
    }

    /**
     * Testing the loadAllByDayUpdated method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAllByDayUpdated($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $people = $this->person->loadAllByDayUpdated(date('Y-m-d'));
        $this->assertGreaterThan(0, count($people), 'Testing the loadAllByDayUpdated method');
        $people[0]->delete();
    }

    /**
     * Testing the loadAllFieldValuesByAttribute method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadAllFieldValuesByAttribute($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $emails = $this->person->loadAllFieldValuesByAttribute('email', $this->person->get('email'), 'email');
        $this->assertEquals($this->person->get('email'), $emails[0], 'Testing the loadAllFieldValuesByAttribute method');
    }

    /**
     * Testing the save method on transient and non-transient objects.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveTransientOrPersistent($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->isTransient(), 'Testing the save method on transient and non-transient objects');
        $this->assertEquals(0, $this->person->getVersionNumber()->getValue(), 'Testing the save method on transient and non-transient objects');

        $this->person->save();

        $this->assertFalse($this->person->isTransient(), 'Testing the save method on transient and non-transient objects');
        $this->assertEquals(1, $this->person->getVersionNumber()->getValue(), 'Testing the save method on transient and non-transient objects');
    }

    /**
     * Testing to ensure that a transient object, once saved, will have an ID.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveTransientID($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->isTransient(), 'Testing to ensure that a transient object, once saved, will have an ID');
        $this->person->save();
        $this->assertGreaterThan(0, $this->person->getID(), 'Testing to ensure that a transient object, once saved, will have an ID');
        $this->assertFalse($this->person->isTransient(), 'Testing to ensure that a transient object, once saved, will have an ID');
    }

    /**
     * Testing optimistic locking mechanism#.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveObjectLocking($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $this->person->save();

            $personInstance1 = new Person();
            $personInstance1->load($this->person->getID());
            $personInstance2 = new Person();
            $personInstance2->load($this->person->getID());

            $personInstance1->save();
            $personInstance2->save();
            $this->fail('Testing optimistic locking mechanism');
        } catch (LockingException $e) {
            $this->assertEquals(
                'Could not save the object as it has been updated by another user.  Please try saving again.',
                $e->getMessage(),
                'Testing optimistic locking mechanism'
            );
        }
    }

    /**
     * Testing the validation method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testValidation($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $person = new Person();
            $person->save();
            $this->fail('Testing the validation method');
        } catch (ValidationException $e) {
            $this->assertEquals(
                'Failed to save, validation error is:',
                mb_substr($e->getMessage(), 0, 36),
                'Testing the validation method'
            );
        }
    }

    /**
     * Testing the delete method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testDelete($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $this->assertFalse($this->person->isTransient(), 'Testing the delete method');
        $id = $this->person->getID();
        $this->person->delete();
        // gone from memory (all attributes null)
        $this->assertEquals(0, count(get_object_vars($this->person)), 'Testing the delete method');
        // gone from the database
        try {
            $this->person = new Person();
            $this->person->load($id);
            $this->fail('Testing the delete method');
        } catch (RecordNotFoundException $e) {
            $this->assertEquals(
                'Failed to load object',
                mb_substr($e->getMessage(), 0, 21),
                'Testing the delete method'
            );
        }
    }

    /**
     * Testing the delete method also removes Tags related to the deleted record.
     *
     * @since 2.0.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testDeleteRelatedTags($provider)
    {
        $article = new Article();
        $tag = new Tag();
        $this->assertEquals(0, $tag->getCount(), 'Testing the delete method also removes Tags related to the deleted record.');

        $article->set('title', 'Unit test');
        $article->set('description', 'Unit test');
        $article->set('author', 'Unit test');
        $article->set('content', 'jupiter neptune venus');
        $article->save();

        $this->assertEquals(3, $tag->getCount(), 'Testing the delete method also removes Tags related to the deleted record.');

        $article->delete();

        $this->assertEquals(0, $article->getCount(), 'Testing the delete method also removes Tags related to the deleted record.');
        $this->assertEquals(0, $tag->getCount(), 'Testing the delete method also removes Tags related to the deleted record.');
    }


    /**
     * Testing the deleteAllByAttribute method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testDeleteAllByAttribute($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $person1 = new Person();
        $person1->setUsername('unitTestUser1');
        $person1->set('email', 'unitTestUser1@test.com');
        $person1->set('password', 'passwordTest');
        $person1->set('URL', 'http://unitTestUser/');

        $person2 = new Person();
        $person2->setUsername('unitTestUser2');
        $person2->set('email', 'unitTestUser2@test.com');
        $person2->set('password', 'passwordTest');
        $person2->set('URL', 'http://unitTestUser/');

        $person3 = new Person();
        $person3->setUsername('unitTestUser3');
        $person3->set('email', 'unitTestUser3@test.com');
        $person3->set('password', 'passwordTest');
        $person3->set('URL', 'http://unitTestUser/');

        $person1->save();
        $person2->save();
        $person3->save();
        $this->assertEquals(3, $this->person->deleteAllByAttribute('URL', 'http://unitTestUser/'), 'Testing the deleteAllByAttribute method');
    }

    /**
     * Testing the version numbers of business objects.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetVersion($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertEquals(0, $this->person->getVersion(), 'Testing the version numbers of business objects');
        $this->assertEquals(0, $this->person->getVersionNumber()->getValue(), 'Testing the version numbers of business objects');
        $this->person->save();
        $this->assertEquals(1, $this->person->getVersion(), 'Testing the version numbers of business objects');
        $this->assertEquals(1, $this->person->getVersionNumber()->getValue(), 'Testing the version numbers of business objects');
        $this->person->save();
        $this->assertEquals(2, $this->person->getVersion(), 'Testing the version numbers of business objects');
        $this->assertEquals(2, $this->person->getVersionNumber()->getValue(), 'Testing the version numbers of business objects');
    }

    /**
     * Testing the getMAX method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetMAX($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $max = $this->person->getMAX();
        $person2 = $this->createPersonObject('unitTestUser2');
        $person2->save();
        $this->assertEquals($max + 1, $this->person->getMAX(), 'Testing the getMAX method');
    }

    /**
     * Testing the getCount method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetCount($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $count = $this->person->getCount();
        $this->person->save();
        $this->assertEquals($count + 1, $this->person->getCount(), 'Testing the getCount method');
    }

    /**
     * Testing the setEnumOptions method is loading enum options correctly.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSetEnumOptions($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $id = $this->person->getMAX();
        $this->person->load($id);
        $this->assertTrue(
            in_array('Active', $this->person->getPropObject('state')->getOptions()),
            'Testing the setEnumOptions method is loading enum options correctly'
        );
    }

    /**
     * Testing that checkTableExists returns true for the person BO.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testCheckTableExists($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->checkTableExists(), 'Testing that checkTableExists returns true for the person BO');
    }

    /**
     * Testing that checkTableNeedsUpdate returns false for the person BO.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testCheckTableNeedsUpdate($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertFalse($this->person->checkTableNeedsUpdate(), 'Testing that checkTableNeedsUpdate returns false for the person BO');
    }

    /**
     * Testing to ensure that the getTableName method can read the TABLE_NAME constant declared in the child class.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetTableName($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertEquals(
            'Person',
            $this->person->getTableName(),
            'Testing to ensure that the getTableName method can read the TABLE_NAME constant declared in the child class'
        );
    }

    /**
     * Testing the getDataLabel method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetDataLabel($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertEquals('E-mail Address', $this->person->getDataLabel('email'), 'Testing the getDataLabel method');
    }

    /**
     * Testing get on a String attribute with no child get method available.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetNoChildMethod($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $email = $this->person->get('email');

        $this->assertEquals('unitTestUser@test.com', $email, 'Testing get on a String attribute with no child get method available');
    }

    /**
     * Testing get on an Enum attribute with a child method available, with $noChildMethods disabled (default).
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetNoChildMethodsDisabled($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $state = $this->person->getPropObject('state');

        $this->assertEquals(
            'Alpha\Model\Type\Enum',
            get_class($state),
            'Testing get on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)'
        );
        $this->assertEquals(
            'Active',
            $state->getValue(),
            'Testing get on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)'
        );
    }

    /**
     * Testing get on an Enum attribute with a child method available, with $noChildMethods enabled.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetNoChildMethodsEnabled($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $state = $this->person->get('state', true);

        $this->assertEquals('Active', $state, 'Testing get on an Enum attribute with a child method avaialble, with $noChildMethods enabled');
    }

    /**
     * Testing get on a simple data type.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetSimpleType($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $labels = $this->person->get('dataLabels');

        $this->assertTrue(is_array($labels), 'Testing get on a simple data type');
    }

    /**
     * Testing set on a String attribute with no child get method available.
     *
     * @since 1.0
     */
    public function testSetNoChildMethod()
    {
        $this->person->set('email', 'test@test.com');

        $this->assertEquals('test@test.com', $this->person->get('email'), 'Testing set on a String attribute with no child get method available');
    }

    /**
     * Testing set on an Enum attribute with a child method available, with $noChildMethods disabled (default).
     *
     * @since 1.0
     */
    public function testSetNoChildMethodsDisabled()
    {
        $this->person->set('state', 'Active');

        $this->assertEquals(
            'Active',
            $this->person->get('state'),
            'Testing set on an Enum attribute with a child method avaialble, with $noChildMethods disabled (default)'
        );
    }

    /**
     * Testing set on an Enum attribute with a child method available, with $noChildMethods enabled.
     *
     * @since 1.0
     */
    public function testSetNoChildMethodsEnabled()
    {
        $this->person->set('state', 'Active', true);

        $this->assertEquals(
            'Active',
            $this->person->get('state'),
            'Testing set on an Enum attribute with a child method avaialble, with $noChildMethods enabled'
        );
    }

    /**
     * Testing set on a simple data type.
     *
     * @since 1.0
     */
    public function testSetSimpleType()
    {
        $this->person->set('dataLabels', array('key' => 'value'));

        $labels = $this->person->get('dataLabels');

        $this->assertTrue(is_array($labels), 'Testing set on a simple data type');
        $this->assertEquals('value', $labels['key'], 'Testing set on a simple data type');
    }

    /**
     * Testing getPropObject on a complex type.
     *
     * @since 1.0
     */
    public function testGetPropObjectComplexType()
    {
        $state = $this->person->getPropObject('state');

        $this->assertEquals('Alpha\Model\Type\Enum', get_class($state), 'Testing getPropObject on a complex type');
        $this->assertEquals('Active', $state->getValue(), 'Testing getPropObject on a complex type');
    }

    /**
     * Testing getPropObject on a simple type.
     *
     * @since 1.0
     */
    public function testGetPropObjectSimpleType()
    {
        $labels = $this->person->getPropObject('dataLabels');

        $this->assertTrue(is_array($labels), 'Testing getPropObject on a simple type');
        $this->assertEquals('E-mail Address', $labels['email'], 'Testing getPropObject on a simple type');
    }

    /**
     * Testing that markTransient and markPersistent methods.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testMarkTransientPersistent($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        // initial save
        $this->person->save();

        // now mark the URL transient, and save again (old URL value should not be overwritten)
        $this->person->markTransient('URL');
        $this->assertTrue(in_array('URL', $this->person->getTransientAttributes()), 'Testing that markTransient and markPersistent methods');
        $this->person->set('URL', 'http://www.alphaframework.org/');
        $this->person->save();

        // used to ensure that we attempt to reload it from the DB
        $this->person->markPersistent('URL');
        $this->assertFalse(in_array('URL', $this->person->getTransientAttributes()), 'Testing that markTransient and markPersistent methods');
        // reload from DB
        $this->person->reload();

        $this->assertEquals('http://unitTestUser/', $this->person->get('URL'), 'Testing that markTransient and markPersistent methods');
    }

    /**
     * Testing the getDataLabels method.
     *
     * @since 1.0
     */
    public function testGetDataLabels()
    {
        $this->assertTrue(is_array($this->person->getDataLabels()), 'Testing the getDataLabels method');
        $labels = $this->person->getDataLabels();
        $this->assertTrue(in_array('ID', array_keys($labels)), 'Testing the getDataLabels method');
        $this->assertTrue(in_array('E-mail Address', $labels), 'Testing the getDataLabels method');
    }

    /**
     * Testing the getTransientAttributes method in conjunction with markTransient/markPersistent.
     *
     * @since 1.0
     */
    public function testGetTransientAttributes()
    {
        $this->assertTrue(
            is_array($this->person->getTransientAttributes()),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
        $this->person->markTransient('URL');
        $this->assertTrue(
            in_array('URL', $this->person->getTransientAttributes()),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
        $this->person->markPersistent('URL');
        $this->assertFalse(
            in_array('URL', $this->person->getTransientAttributes()),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
    }

    /**
     * Testing isTransient before and after save.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testIsTransient($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->isTransient(), 'Testing isTransient before and after save');
        $this->person->save();
        $this->assertFalse($this->person->isTransient(), 'Testing isTransient before and after save');
    }

    /**
     * Testing the getLastQuery method after various persistance calls.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testGetLastQuery($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();

        if ($config->get('db.provider.name') == 'Alpha\Model\ActiveRecordProviderMySQL') {
            $this->assertEquals(
                'INSERT INTO Person',
                mb_substr($this->person->getLastQuery(), 0, 18),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->checkTableNeedsUpdate();
            $this->assertEquals(
                'SHOW INDEX FROM Person',
                mb_substr($this->person->getLastQuery(), 0, 22),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->getCount();
            $this->assertEquals(
                'SELECT COUNT(ID)',
                mb_substr($this->person->getLastQuery(), 0, 16),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->getMAX();
            $this->assertEquals(
                'SELECT MAX(ID)',
                mb_substr($this->person->getLastQuery(), 0, 14),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->load($this->person->getID());
            $this->assertEquals(
                'SHOW COLUMNS FROM Person LIKE \'state\'',
                $this->person->getLastQuery(),
                'Testing the getLastQuery method after various persistance calls'
            );
        }

        if ($config->get('db.provider.name') == 'Alpha\Model\ActiveRecordProviderSQLite') {
            $this->assertEquals(
                'PRAGMA table_info(Person)',
                mb_substr($this->person->getLastQuery(), 0, 25),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->checkTableNeedsUpdate();
            $this->assertEquals(
                'PRAGMA foreign_key_list(Person)',
                mb_substr($this->person->getLastQuery(), 0, 49),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->getCount();
            $this->assertEquals(
                'SELECT COUNT(ID)',
                mb_substr($this->person->getLastQuery(), 0, 16),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->getMAX();
            $this->assertEquals(
                'SELECT MAX(ID)',
                mb_substr($this->person->getLastQuery(), 0, 14),
                'Testing the getLastQuery method after various persistance calls'
            );
            $this->person->load($this->person->getID());
            $this->assertEquals(
                'SELECT username,email,password,state,URL,ID,version_num,created_ts,created_by,updated_ts,updated_by FROM Person WHERE ID = :ID LIMIT 1;',
                $this->person->getLastQuery(),
                'Testing the getLastQuery method after various persistance calls'
            );
        }
    }

    /**
     * Testing the clear method for unsetting the attributes of an object.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testClear($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $state = $this->person->get('state');
        $this->assertTrue(!empty($state), 'Testing the clear method for unsetting the attributes of an object');

        $reflection = new \ReflectionClass(get_class($this->person));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->person->getDefaultAttributes()) && !in_array($propName, $this->person->getTransientAttributes())) {
                $this->assertNotNull($this->person->get($propName), 'Testing the clear method for unsetting the attributes of an object');
            }
        }

        // delete will invoke clear(), which is private
        $this->person->delete();

        try {
            $state = $this->person->get('state');
            $this->fail('Testing the clear method for unsetting the attributes of an object');
        } catch (AlphaException $e) {
            $reflection = new \ReflectionClass(get_class($this->person));
            $properties = $reflection->getProperties();

            foreach ($properties as $propObj) {
                $propName = $propObj->name;

                try {
                    $this->person->get($propName);
                } catch (PHPException $e) {
                    $this->assertEquals(
                        preg_match('/Undefined property/', $e->getMessage()),
                        1,
                        'Testing the clear method for unsetting the attributes of an object'
                    );
                } catch (AlphaException $e) {
                    $this->assertEquals(
                        'Could not access the property ['.$propName.'] on the object of class [Alpha\Model\Person]',
                        $e->getMessage(),
                        'Testing the clear method for unsetting the attributes of an object'
                    );
                }
            }
        }
    }

    /**
     * Testing the saveAttribute method.
     *
     * @since 1.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveAttribute($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $this->person->saveAttribute('username', 'unitTestUserNew');

        $this->assertEquals(
            'unitTestUserNew',
            $this->person->getUsername()->getValue(),
            'Testing that the value was set on the object in memory along with saving to the database'
        );

        $person = new Person();

        try {
            $person->loadByAttribute('username', 'unitTestUserNew');
            $this->assertEquals('unitTestUserNew', $person->getUsername()->getValue(), 'Testing that the value was saved to the database');
        } catch (RecordNotFoundException $e) {
            $this->fail('Failed to load the BO that was updated with the saveAttribute method');
        }

        $oldTimestamp = $person->get('updated_ts');

        sleep(1);

        $person->saveAttribute('username', 'unitTestUserNew');

        $this->assertNotEquals($oldTimestamp, $person->get('updated_ts'), 'Testing that updated_ts changed');

        $person->reload();

        $this->assertNotEquals($oldTimestamp, $person->get('updated_ts'), 'Testing that updated_ts changed');
    }

    /**
     * Testing to ensure that a history table was created automatically.
     *
     * @since 1.2.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testHistoryTableCreated($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->setMaintainHistory(true);
        $this->person->rebuildTable(); // this should result in the _history table being created

        $this->assertTrue($this->person->checkTableExists(true), 'Testing to ensure that a history table was created automatically');

        $this->person->dropTable('Person_history');
    }

    /**
     * Testing that the saveHistory() method is automatically invoked when it should be.
     *
     * @since 1.2.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveHistory($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->setMaintainHistory(true);
        $this->person->rebuildTable(); // this should result in the _history table being created

        $this->person->set('password', 'passwordhist1');
        $this->person->save();

        $this->assertEquals(1, $this->person->getHistoryCount(), 'Testing that a normal save is propegated to the history table for this class');
        $this->person->saveAttribute('password', 'passwordhist2');

        $this->assertEquals(2, $this->person->getHistoryCount(), 'Testing that an attribute save is propegated to the history table for this class');

        $this->person->dropTable('Person_history');
    }

    /**
     * Testing the hasAttribute method.
     *
     * @since 1.2.1
     */
    public function testHasAttribute()
    {
        $this->assertTrue($this->person->hasAttribute('password'), 'testing the hasAttribute method for true');
        $this->assertFalse($this->person->hasAttribute('doesnotexist'), 'testing the hasAttribute method for false');
    }

    /**
     * Testing that you can add a DAO directly to the cache without saving.
     *
     * @since 1.2.3
     */
    public function testAddToCache()
    {
        $config = ConfigProvider::getInstance();

        $oldSetting = $config->get('cache.provider.name');
        $config->set('cache.provider.name', 'Alpha\Util\Cache\CacheProviderArray');

        $this->person->setID('123');
        $this->person->addToCache();

        $fromCache = new Person();
        $fromCache->setID($this->person->getID());

        $this->assertTrue($fromCache->loadFromCache(), 'testing that the item loads from the cache');
        $this->assertEquals('unitTestUser', $fromCache->get('username', true), 'testing that you can add a DAO directly to the cache without saving');

        $config->set('cache.provider.name', $oldSetting);
    }

    /**
     * Testing that a saved record is subsequently retrievable from the cache.
     *
     * @since 1.2.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLoadFromCache($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $oldSetting = $config->get('cache.provider.name');
        $config->set('cache.provider.name', 'Alpha\Util\Cache\CacheProviderArray');

        $this->person->save();

        $fromCache = new Person();
        $fromCache->setID($this->person->getID());

        $this->assertTrue($fromCache->loadFromCache(), 'testing that the item loads from the cache');
        $this->assertEquals('unitTestUser', $fromCache->get('username', true), 'testing that a saved record is subsequently retrievable from the cache');

        $config->set('cache.provider.name', $oldSetting);
    }

    /**
     * Testing the removeFromCache method.
     *
     * @since 1.2.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testRemoveFromCache($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $oldSetting = $config->get('cache.provider.name');
        $config->set('cache.provider.name', 'Alpha\Util\Cache\CacheProviderArray');

        $this->person->save();

        $fromCache = new Person();
        $fromCache->setID($this->person->getID());

        $this->assertTrue($fromCache->loadFromCache(), 'testing that the item loads from the cache');

        $fromCache->removeFromCache();

        $this->assertFalse($fromCache->loadFromCache(), 'testing that the item is gone from the cache');

        $config->set('cache.provider.name', $oldSetting);
    }

    /**
     * Testing the getFriendlyClassName() method.
     *
     * @since 1.2.1
     */
    public function testGetFriendlyClassName()
    {
        $person = new Person();
        $article = new Article();
        $comment = new ArticleComment();

        $this->assertEquals('Person', $person->getFriendlyClassName(), 'testing the getFriendlyClassName() method');
        $this->assertEquals('Article', $article->getFriendlyClassName(), 'testing the getFriendlyClassName() method');
        $this->assertEquals('ArticleComment', $comment->getFriendlyClassName(), 'testing the getFriendlyClassName() method');
    }

    /**
     * Testing the cast() method.
     *
     * @since 1.2.1
     */
    public function testCast()
    {
        $original = new BadRequest();
        $original->set('IP', '127.0.0.1');
        $copy = $original->cast('Alpha\Model\BlacklistedIP', $original);

        $this->assertTrue($copy instanceof BlacklistedIP, 'testing the cast() method');
        $this->assertTrue($copy->hasAttribute('IP'), 'testing the cast() method');
        $this->assertEquals($original->get('IP'), $copy->get('IP'), 'testing the cast() method');
    }

    /**
     * Testing the toArray() method.
     *
     * @since 2.0
     */
    public function testToArray()
    {
        $record = new BadRequest();
        $record->set('IP', '127.0.0.1');

        $this->assertTrue($record instanceof BadRequest, 'Testing the toArray() method');
        $this->assertEquals('127.0.0.1', $record->get('IP'), 'Testing the toArray() method');

        $hashArray = $record->toArray();

        $this->assertTrue(is_array($hashArray), 'Testing the toArray() method');
        $this->assertEquals('127.0.0.1', $hashArray['IP'], 'Testing the toArray() method');
    }

    /**
     * Testing the addProperty() method.
     *
     * @since 2.0
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testAddProperty($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);
        
        $record = new BadRequest();
        $record->newStringField = new SmallText();

        $record->addProperty('newStringField');

        $record->set('newStringField', 'test value');
        $record->save();

        $record->reload();

        $this->assertEquals('test value', $record->get('newStringField'), 'Testing that we can save and retrieve from a newly-added column');

        $record = new BadRequest();
        $record->setMaintainHistory(true);
        $record->rebuildTable();
        $record->anotherNewStringField = new SmallText();

        $record->addProperty('anotherNewStringField');

        $record->set('anotherNewStringField', 'test value');
        $record->save();

        $record->load($record->getID(), 1);

        $this->assertEquals('test value', $record->get('anotherNewStringField'), 'Testing that the new column was added to the _history table');
    }

    /**
     * Testing the populateFromArray() method
     *
     * @since 3.0.0
     */
    public function testPopulateFromArray()
    {
        $record = new BadRequest();
        $record->populateFromArray(array('client'=>'SomeBot', 'IP'=>'127.0.0.1', 'resource'=>'/test'));

        $this->assertEquals('SomeBot', $record->get('client'), 'Testing the populateFromArray() method');

        $record->populateFromArray(array('client'=>'SomeBot', 'updated_ts'=>'2001-01-01 20:20:20'));

        $this->assertEquals('2001-01-01 20:20:20', $record->getUpdateTS()->getValue(), 'Testing the populateFromArray() method');
    }

    /**
     * Testing the saveRelations() method.
     *
     * @since 3.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testSaveRelations($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $group = new Rights();
        $group->set('name', 'unittestgroup');
        $group->save();

        $group->getPropObject('members')->setRelatedIDs(array(1, 2, 3));
        $group->saveRelations();

        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Rights');
        $this->assertEquals(3, count($lookup->loadAllbyAttribute('rightID', $group->getID())), 'testing the loadAllbyAttribute() method');
    }
}
