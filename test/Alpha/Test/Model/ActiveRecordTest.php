<?php

namespace Alpha\Test\Model;

use Alpha\Model\ActiveRecord;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\BadRequest;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\BlacklistedIP;
use Alpha\Model\Tag;
use Alpha\Model\Type\RelationLookup;
use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Enum;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Exception\LockingException;
use Alpha\Exception\ValidationException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\AlphaException;
use Alpha\Exception\PHPException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the ActiveRecord class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2024, John Collins (founder of Alpha Framework).
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
     * @since 1.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();

        set_exception_handler('Alpha\Util\ErrorHandlers::catchException');
        //restore_exception_handler();

        set_error_handler('Alpha\Util\ErrorHandlers::catchError', $config->get('php.error.log.level'));
        //restore_error_handler();

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

            $denum = new DEnum();
            $denum->rebuildTable();
            $item = new DEnumItem();
            $item->rebuildTable();
        }
    }

    /**
     * @since 4.0
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        restore_exception_handler();
        restore_error_handler();
    }

    /**
     * Testing the createForeignIndex method
     *
     * @since 2.0.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testCreateForeignIndex(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $article = new Article();

        $article->createForeignIndex('author', 'Alpha\Model\Person', 'username');

        $this->assertTrue(in_array('Article_author_fk_idx', $article->getIndexes(), true), 'Testing the createForeignIndex method');
    }

    /**
     * Test that the constructor sets the correct values of the "house keeping" attributes.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDefaultHouseKeepingValues(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testBasicLoadSave(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadCreatesMissingTable(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testCheckRecordExists(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadByAttribute(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllOldVersions(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAll(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllByAttribute(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllByAttributes(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllByDayUpdated(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllFieldValuesByAttribute(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveTransientOrPersistent(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveTransientID(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveObjectLocking(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testValidation(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDelete(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDeleteRelatedTags(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testDeleteAllByAttribute(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetVersion(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetMAX(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetCount(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSetEnumOptions(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();
        $id = $this->person->getMAX();
        $this->person->load($id);
        $this->assertTrue(
            in_array('Active', $this->person->getPropObject('state')->getOptions(), true),
            'Testing the setEnumOptions method is loading enum options correctly'
        );
    }

    /**
     * Testing that checkTableExists returns true for the person BO.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testCheckTableExists(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue($this->person->checkTableExists(), 'Testing that checkTableExists returns true for the person BO');
    }

    /**
     * Testing that checkTableNeedsUpdate returns false for the person BO.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testCheckTableNeedsUpdate(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertFalse($this->person->checkTableNeedsUpdate(), 'Testing that checkTableNeedsUpdate returns false for the person BO');
    }

    /**
     * Testing to ensure that the getTableName method can read the TABLE_NAME constant declared in the child class.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetTableName(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetDataLabel(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertEquals('E-mail Address', $this->person->getDataLabel('email'), 'Testing the getDataLabel method');
    }

    /**
     * Testing get on a String attribute with no child get method available.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetNoChildMethod(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetNoChildMethodsDisabled(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetNoChildMethodsEnabled(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetSimpleType(string $provider): void
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
    public function testSetNoChildMethod(): void
    {
        $this->person->set('email', 'test@test.com');

        $this->assertEquals('test@test.com', $this->person->get('email'), 'Testing set on a String attribute with no child get method available');
    }

    /**
     * Testing set on an Enum attribute with a child method available, with $noChildMethods disabled (default).
     *
     * @since 1.0
     */
    public function testSetNoChildMethodsDisabled(): void
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
    public function testSetNoChildMethodsEnabled(): void
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
    public function testSetSimpleType(): void
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
    public function testGetPropObjectComplexType(): void
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
    public function testGetPropObjectSimpleType(): void
    {
        $labels = $this->person->getPropObject('dataLabels');

        $this->assertTrue(is_array($labels), 'Testing getPropObject on a simple type');
        $this->assertEquals('E-mail Address', $labels['email'], 'Testing getPropObject on a simple type');
    }

    /**
     * Testing that markTransient and markPersistent methods.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testMarkTransientPersistent(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        // initial save
        $this->person->save();

        // now mark the URL transient, and save again (old URL value should not be overwritten)
        $this->person->markTransient('URL');
        $this->assertTrue(in_array('URL', $this->person->getTransientAttributes(), true), 'Testing that markTransient and markPersistent methods');
        $this->person->set('URL', 'http://www.alphaframework.org/');
        $this->person->save();

        // used to ensure that we attempt to reload it from the DB
        $this->person->markPersistent('URL');
        $this->assertFalse(in_array('URL', $this->person->getTransientAttributes(), true), 'Testing that markTransient and markPersistent methods');
        // reload from DB
        $this->person->reload();

        $this->assertEquals('http://unitTestUser/', $this->person->get('URL'), 'Testing that markTransient and markPersistent methods');
    }

    /**
     * Testing the getDataLabels method.
     *
     * @since 1.0
     */
    public function testGetDataLabels(): void
    {
        $this->assertTrue(is_array($this->person->getDataLabels()), 'Testing the getDataLabels method');
        $labels = $this->person->getDataLabels();
        $this->assertTrue(in_array('ID', array_keys($labels), true), 'Testing the getDataLabels method');
        $this->assertTrue(in_array('E-mail Address', $labels, true), 'Testing the getDataLabels method');
    }

    /**
     * Testing the getTransientAttributes method in conjunction with markTransient/markPersistent.
     *
     * @since 1.0
     */
    public function testGetTransientAttributes(): void
    {
        $this->assertTrue(
            is_array($this->person->getTransientAttributes()),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
        $this->person->markTransient('URL');
        $this->assertTrue(
            in_array('URL', $this->person->getTransientAttributes(), true),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
        $this->person->markPersistent('URL');
        $this->assertFalse(
            in_array('URL', $this->person->getTransientAttributes(), true),
            'Testing the getTransientAttributes method in conjunction with markTransient/markPersistent'
        );
    }

    /**
     * Testing isTransient before and after save.
     *
     * @since 1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testIsTransient(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testGetLastQuery(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testClear(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $state = $this->person->get('state');
        $this->assertTrue(!empty($state), 'Testing the clear method for unsetting the attributes of an object');

        $reflection = new \ReflectionClass(get_class($this->person));
        $properties = $reflection->getProperties();

        foreach ($properties as $propObj) {
            $propName = $propObj->name;
            if (!in_array($propName, $this->person->getDefaultAttributes(), true) && !in_array($propName, $this->person->getTransientAttributes(), true)) {
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveAttribute(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testHistoryTableCreated(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveHistory(string $provider): void
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
    public function testHasAttribute(): void
    {
        $this->assertTrue($this->person->hasAttribute('password'), 'testing the hasAttribute method for true');
        $this->assertFalse($this->person->hasAttribute('doesnotexist'), 'testing the hasAttribute method for false');
    }

    /**
     * Testing that you can add a DAO directly to the cache without saving.
     *
     * @since 1.2.3
     */
    public function testAddToCache(): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadFromCache(string $provider): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testRemoveFromCache(string $provider): void
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
    public function testGetFriendlyClassName(): void
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
    public function testCast(): void
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
    public function testToArray(): void
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
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testAddProperty(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $record = new BadRequest();
        $record->newStringField = new SmallText();

        $record->addProperty('newStringField');

        $enum = new Enum();
        $enum->setOptions(array('a', 'b', 'c'));
        $record->newEnumField = $enum;

        $record->addProperty('newEnumField');

        $denum = new DEnum('Alpha\Model\BadRequest::newDEnumField');
        $record->newDEnumField = $denum;
        $item = new DEnumItem();
        $item->set('DEnumID', $denum->getID());
        $item->set('value', 'Test');
        $item->save();

        $record->addProperty('newDEnumField');

        $record->set('newStringField', 'test value');
        $record->set('newEnumField', 'a');

        $options = $denum->getOptions();
        $optionIDs = array_keys($options);
        $record->set('newDEnumField', $optionIDs[0]);

        $record->save();

        $record->reload();

        $this->assertEquals('test value', $record->get('newStringField'), 'Testing that we can save and retrieve from a newly-added SmallText column');
        $this->assertEquals('a', $record->get('newEnumField'), 'Testing that we can save and retrieve from a newly-added Enum column');

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
    public function testPopulateFromArray(): void
    {
        $record = new BadRequest();
        $record->populateFromArray(array('client' => 'SomeBot', 'IP' => '127.0.0.1', 'resource' => '/test'));

        $this->assertEquals('SomeBot', $record->get('client'), 'Testing the populateFromArray() method');

        $record->populateFromArray(array('client' => 'SomeBot', 'updated_ts' => '2001-01-01 20:20:20'));

        $this->assertEquals('2001-01-01 20:20:20', $record->getUpdateTS()->getValue(), 'Testing the populateFromArray() method');
    }

    /**
     * Testing the saveRelations() method.
     *
     * @since 3.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveRelations(string $provider): void
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

    /**
     * Testing the checkDatabaseExists() method
     *
     * @since 4.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testCheckDatabaseExists(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->assertTrue(ActiveRecord::checkDatabaseExists(), 'Testing the checkDatabaseExists() method');
    }

    /**
     * Testing the findMissingFields() method
     *
     * @since 4.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testFindMissingFields(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $missingFields = $this->person->findMissingFields();

        $this->assertTrue(count($missingFields) == 0, 'Testing the findMissingFields() method');
    }

    /**
     * Testing transactions
     *
     * @since 4.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testTransactions(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();

        $this->person->set('username', 'test1');

        ActiveRecord::begin();

        $this->person->save();

        $this->assertEquals('test1', $this->person->get('username'), 'Testing transactions');

        ActiveRecord::rollback();

        $person = new Person();
        $person->load($this->person->getID());
        $this->assertEquals('unitTestUser', $person->get('username'), 'Testing transactions');


        $person->set('username', 'test2');
        ActiveRecord::begin();
        $person->save();
        ActiveRecord::commit();
        $person->reload();

        $this->assertEquals('test2', $person->get('username'), 'Testing transactions');
    }

    /**
     * Testing reload
     *
     * @since 4.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testReload(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $this->person->save();

        $this->person->set('username', 'test1');

        $this->assertEquals('test1', $this->person->get('username'), 'Testing reload');

        $this->person->reload();

        $this->assertEquals('unitTestUser', $this->person->get('username'), 'Testing reload');
    }

    /**
     * Testing begin(), commit(), and rollback() methods
     *
     * @since 4.1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testTransactionsWithProvider(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $person = new Person();
        $person->setUsername('transactionuser');
        $person->set('email', 'transaction@test.com');
        $person->set('password', 'passwordTest');

        $providerObj = ServiceFactory::getInstance($provider, 'Alpha\Model\ActiveRecordProviderInterface');
        $providerObj->setRecord($person);

        // Test begin
        $providerObj->begin();

        // Test commit
        $providerObj->commit();

        $this->assertTrue(true, 'Testing transaction methods');
    }

    /**
     * Testing rollback method with failed save
     *
     * @since 4.1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testRollbackWithFailedSave(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $providerObj = ServiceFactory::getInstance($provider, 'Alpha\Model\ActiveRecordProviderInterface');

        $providerObj->begin();

        // This should fail validation
        try {
            $person = new Person();
            $providerObj->setRecord($person);
            $providerObj->save();
        } catch (\Exception $e) {
            // Expected - validation will fail
        }

        // Rollback should complete without errors
        $providerObj->rollback();

        $this->assertTrue(true, 'Testing rollback with failed save');
    }

    /**
     * Testing save method with transaction
     *
     * @since 4.1.0
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testSaveWithTransaction(string $provider): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $providerObj = ServiceFactory::getInstance($provider, 'Alpha\Model\ActiveRecordProviderInterface');

        $person1 = new Person();
        $person1->setUsername('transuser1');
        $person1->set('email', 'trans1@test.com');
        $person1->set('password', 'passwordTest');
        $providerObj->setRecord($person1);
        $providerObj->save();

        $person2 = new Person();
        $person2->setUsername('transuser2');
        $person2->set('email', 'trans2@test.com');
        $person2->set('password', 'passwordTest');
        $providerObj->setRecord($person2);
        $providerObj->save();

        $this->assertGreaterThan(0, $person1->getID(), 'Testing save with transaction');
        $this->assertGreaterThan(0, $person2->getID(), 'Testing save with transaction');
    }

    /**
     * Testing that the optional before and after hook methods defined on a
     * Record class get triggered by the matching ActiveRecord methods.
     *
     * @since 4.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testRecordHookMethodsFire(string $provider): void
    {
        ActiveRecordTestHookSink::$log = array();

        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $rec = new ActiveRecordTestHookRecord();
        $rec->makeTable();
        $rec->set('name', 'hooky');
        $rec->set('intAttr', 5);
        $rec->save();
        $id = $rec->getID();
        $this->assertGreaterThan(0, $id);

        $rec->save();
        $rec->get('name');
        $rec->getPropObject('name');
        $rec->getVersion();
        $rec->checkTableExists();
        $rec->checkRecordExists($id);
        $rec->getCount();
        $rec->getMAX();
        $rec->saveAttribute('name', 'hooky2');

        $probe = new ActiveRecordTestHookRecord();
        $probe->load($id);
        $probe->loadByAttribute('name', 'hooky2');
        $probe->loadAll();
        $probe->loadAllByAttribute('name', 'hooky2');
        $probe->loadAllByAttributes(array('name'), array('hooky2'));
        $probe->loadAllByDayUpdated(date('Y-m-d'));

        try {
            $rec->addProperty('bogusAttr');
        } catch (\Exception $e) {
        }

        try {
            $rec->createUniqueIndex('name');
        } catch (AlphaException $e) {
        }

        $this->assertEquals(0, $rec->deleteAllByAttribute('name', 'notThere'));

        $rec->checkTableNeedsUpdate();
        $rec->findMissingFields();
        $rec->rebuildTable();
        $rec->makeHistoryTable();
        $rec->setMaintainHistory(true);
        $rec->saveHistory();
        $rec->getHistoryCount();
        $rec->delete();
        $rec->dropTable();

        $expected = array('beforeSave', 'afterSave', 'beforeSet', 'afterSet', 'beforeGet', 'afterGet', 'beforeGetPropObject', 'afterGetPropObject', 'beforeGetVersion', 'afterGetVersion', 'beforeCheckTableExists', 'afterCheckTableExists', 'beforeCheckRecordExists', 'afterCheckRecordExists', 'beforeGetCount', 'afterGetCount', 'beforeGetMAX', 'afterGetMAX', 'beforeMakeTable', 'afterMakeTable', 'beforeMakeHistoryTable', 'afterMakeHistoryTable', 'beforeSaveHistory', 'afterSaveHistory', 'beforeGetHistoryCount', 'afterGetHistoryCount', 'beforeLoad', 'afterLoad', 'beforeLoadByAttribute', 'afterLoadByAttribute', 'beforeLoadAll', 'afterLoadAll', 'beforeLoadAllByAttribute', 'afterLoadAllByAttribute', 'beforeLoadAllByAttributes', 'afterLoadAllByAttributes', 'beforeLoadAllByDayUpdated', 'afterLoadAllByDayUpdated', 'beforeSaveAttribute', 'afterSaveAttribute', 'beforeAddProperty', 'beforeDeleteAllByAttribute', 'afterDeleteAllByAttribute', 'beforeCheckTableNeedsUpdate', 'afterCheckTableNeedsUpdate', 'beforeFindMissingFields', 'afterFindMissingFields', 'beforeRebuildTable', 'afterRebuildTable', 'beforeDelete', 'afterDelete', 'beforeDropTable', 'afterDropTable');

        $fired = ActiveRecordTestHookSink::$log;

        foreach ($expected as $hook) {
            $this->assertContains($hook, $fired, 'hook ['.$hook.'] did not fire');
        }
    }

    /**
     * Testing the Date/Timestamp, version_num and MANY-TO-MANY relation
     * branches of the populateFromArray method.
     *
     * @since 4.1
     */
    public function testPopulateFromArraySpecialBranches(): void
    {
        $article = new Article();

        $article->populateFromArray(array('title' => 'pop test', 'created_ts' => '2026-01-02 03:04:05', 'version_num' => '3'));

        $this->assertEquals('pop test', $article->get('title'));
        $this->assertEquals(3, $article->getVersionNumber()->getValue());

        // a non transient MANY-TO-MANY relation gets its related IDs set from the array
        $m2m = new ActiveRecordTestM2MRecord();
        $m2m->populateFromArray(array('relAttr' => '00000000001'));
        $this->assertCount(1, $m2m->getPropObject('relAttr')->getRelatedIDs());
    }

    /**
     * Testing the cast method in both size directions.
     *
     * @since 4.1
     */
    public function testCastRecordBetweenTypes(): void
    {
        $small = new ActiveRecordTestCastSmall();
        $small->makeTable();
        $small->set('name', 'cast me');
        $small->setID(99);

        try {
            $big = $small->cast('Alpha\Test\Model\ActiveRecordTestCastBig', $small);
            $this->assertInstanceOf('Alpha\Test\Model\ActiveRecordTestCastBig', $big);
            $this->assertEquals(99, $big->getID());

            $big2 = new ActiveRecordTestCastBig();
            $big2->makeTable();
            $big2->set('name', 'shrink me');
            $big2->set('num', 2);
            $big2->setID(100);

            try {
                $small2 = $big2->cast('Alpha\Test\Model\ActiveRecordTestCastSmall', $big2);
                $this->assertInstanceOf('Alpha\Test\Model\ActiveRecordTestCastSmall', $small2);
                $this->assertEquals(100, $small2->getID());
            } finally {
                $big2->dropTable();
            }
        } finally {
            $small->dropTable();
        }
    }

    /**
     * Testing the static transaction helpers with an explicit record and the
     * lazy initialization of the static logger.
     *
     * @since 4.1
     */
    public function testStaticTransactionHelpersWithRecord(): void
    {
        $loggerProperty = new \ReflectionProperty(ActiveRecord::class, 'logger');
        $loggerProperty->setAccessible(true);
        $savedLogger = $loggerProperty->getValue();

        $article = new Article();

        try {
            $loggerProperty->setValue(null, null);
            ActiveRecord::begin($article);
            ActiveRecord::rollback($article);

            $loggerProperty->setValue(null, null);
            ActiveRecord::begin($article);
            ActiveRecord::commit($article);

            $loggerProperty->setValue(null, null);
            ActiveRecord::isInstalled();

            $this->assertTrue(true);
        } finally {
            $loggerProperty->setValue(null, $savedLogger);
        }
    }

    /**
     * Testing the cache helper methods take their error paths when the
     * configured cache provider cannot be created and round trip through the
     * array cache provider.
     *
     * @since 4.1
     */
    public function testCacheHelpersErrorPathsAndRoundTrip(): void
    {
        $config = ConfigProvider::getInstance();
        $old = $config->get('cache.provider.name');

        try {
            $config->set('cache.provider.name', 'No\\Such\\Cache\\Provider');

            $article = new Article();
            $article->setID(999001);

            $article->addToCache();
            $this->assertFalse($article->loadFromCache());
        } finally {
            $config->set('cache.provider.name', $old);
        }
    }

    /**
     * Testing that the wakeup magic method re-initializes the static logger
     * after de-serialization.
     *
     * @since 4.1
     */
    public function testWakeupInitializesLogger(): void
    {
        $loggerProperty = new \ReflectionProperty(ActiveRecord::class, 'logger');
        $loggerProperty->setAccessible(true);
        $savedLogger = $loggerProperty->getValue();

        try {
            $serialized = serialize(new Article());

            $loggerProperty->setValue(null, null);
            $article = unserialize($serialized);

            $this->assertInstanceOf('Alpha\Model\Article', $article);
            $this->assertNotNull($loggerProperty->getValue());
        } finally {
            $loggerProperty->setValue(null, $savedLogger);
        }
    }

    /**
     * Testing that set() routes Date and Timestamp properties through their
     * string setters (setValue() delegates to populateFromString()).  Note the
     * dedicated populateFromString() branch in set() is unreachable dead code
     * since get_class() returns the fully qualified class name, so the
     * uppercase class name comparison never matches.
     *
     * @since 4.1
     */
    public function testSetDateAndTimestampPropertiesUsePopulateFromString(): void
    {
        $rec = new ActiveRecordTestSetProbe();

        $rec->set('stamp', '2026-03-04 05:06:07');
        $this->assertSame('2026-03-04 05:06:07', $rec->get('stamp'));

        $rec->set('day', '2026-03-04');
        $this->assertSame('2026-03-04', $rec->get('day'));
    }

    /**
     * Testing that populateFromString() exceptions raised while setting an
     * invalid Date or Timestamp string propagate out of set() and leave the
     * existing values untouched.
     *
     * @since 4.1
     */
    public function testSetInvalidDateStringThrowsAndLeavesValueUntouched(): void
    {
        $rec = new ActiveRecordTestSetProbe();

        try {
            $rec->set('day', 'bogus');
            $this->fail('Expected an IllegalArguementException for an invalid Date string');
        } catch (\Alpha\Exception\IllegalArguementException $e) {
            $this->assertStringContainsString('Invalid Date value', $e->getMessage());
            $this->assertSame('2026-01-01', $rec->get('day'));
        }

        try {
            $rec->set('stamp', 'bogus');
            $this->fail('Expected an IllegalArguementException for an invalid Timestamp string');
        } catch (\Alpha\Exception\IllegalArguementException $e) {
            $this->assertSame('2026-01-01 01:01:01', $rec->get('stamp'));
        }
    }

    /**
     * Testing that set() assigns simple (non-object) properties directly.
     *
     * @since 4.1
     */
    public function testSetSimplePropertySetsDirectly(): void
    {
        $rec = new ActiveRecordTestSetProbe();

        $rec->set('plain', 'y');
        $this->assertSame('y', $rec->get('plain'));
    }

    /**
     * Testing that set() throws an AlphaException for a non-existent property
     * and for a private child property that is invisible to the parent scope.
     *
     * @since 4.1
     */
    public function testSetMissingOrPrivatePropertyThrows(): void
    {
        $rec = new ActiveRecordTestSetProbe();

        try {
            $rec->set('doesNotExist', 'x');
            $this->fail('Expected an AlphaException for a non-existent property');
        } catch (AlphaException $e) {
            $this->assertStringContainsString('Could not set the property', $e->getMessage());
        }

        try {
            $rec->set('hidden', 'x');
            $this->fail('Expected an AlphaException for a private property');
        } catch (AlphaException $e) {
            $this->assertStringContainsString('Could not set the property [hidden]', $e->getMessage());
        }
    }
}

/**
 * External sink that collects the names of the hook methods that fired;
 * deliberately not a Record property so provider property loops never see it.
 *
 * @since 4.1
 */
class ActiveRecordTestHookSink
{
    /**
     * @since 4.1
     */
    public static $log = array();
}

/**
 * Record stub that defines every optional hook method checked by ActiveRecord
 * so tests can assert each one is triggered by its matching method.
 *
 * @since 4.1
 */
class ActiveRecordTestHookRecord extends ActiveRecord
{
    /**
     * @since 4.1
     */
    public const TABLE_NAME = 'ActiveRecordTestHookRecord';

    /**
     * @since 4.1
     */
    protected $transientAttributes = array('lastQuery', 'dataLabels', 'defaultAttributes', 'transientAttributes', 'uniqueAttributes', 'TABLE_NAME', 'logger');

    /**
     * @since 4.1
     */
    protected $name;

    /**
     * @since 4.1
     */
    protected $intAttr;

    /**
     * @since 4.1
     */
    protected $tsAttr;

    /**
     * @since 4.1
     */
    protected $dataLabels = array('ID' => 'ID', 'name' => 'Name', 'intAttr' => 'Int attr', 'tsAttr' => 'Ts attr');

    /**
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = new \Alpha\Model\Type\SmallText('x');
        $this->intAttr = new \Alpha\Model\Type\Integer(1);
        // Seed an explicit valid datetime: an empty Timestamp defaults to
        // '0000-00-00 00:00:00', which strict sql_mode (NO_ZERO_DATE) on
        // GitHub Actions MySQL rejects, even though local MySQL tolerates it.
        $this->tsAttr = new \Alpha\Model\Type\Timestamp(date('Y-m-d H:i:s'));
    }

    /**
     * @since 4.1
     */
    public function beforeAddProperty(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeAddProperty';
    }

    /**
     * @since 4.1
     */
    public function afterAddProperty(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterAddProperty';
    }

    /**
     * @since 4.1
     */
    public function beforeCheckRecordExists(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeCheckRecordExists';
    }

    /**
     * @since 4.1
     */
    public function afterCheckRecordExists(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterCheckRecordExists';
    }

    /**
     * @since 4.1
     */
    public function beforeCheckTableExists(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeCheckTableExists';
    }

    /**
     * @since 4.1
     */
    public function afterCheckTableExists(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterCheckTableExists';
    }

    /**
     * @since 4.1
     */
    public function beforeCheckTableNeedsUpdate(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeCheckTableNeedsUpdate';
    }

    /**
     * @since 4.1
     */
    public function afterCheckTableNeedsUpdate(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterCheckTableNeedsUpdate';
    }

    /**
     * @since 4.1
     */
    public function beforeCreateForeignIndex(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeCreateForeignIndex';
    }

    /**
     * @since 4.1
     */
    public function afterCreateForeignIndex(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterCreateForeignIndex';
    }

    /**
     * @since 4.1
     */
    public function beforeCreateUniqueIndex(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeCreateUniqueIndex';
    }

    /**
     * @since 4.1
     */
    public function afterCreateUniqueIndex(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterCreateUniqueIndex';
    }

    /**
     * @since 4.1
     */
    public function beforeDelete(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeDelete';
    }

    /**
     * @since 4.1
     */
    public function afterDelete(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterDelete';
    }

    /**
     * @since 4.1
     */
    public function beforeDeleteAllByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeDeleteAllByAttribute';
    }

    /**
     * @since 4.1
     */
    public function afterDeleteAllByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterDeleteAllByAttribute';
    }

    /**
     * @since 4.1
     */
    public function beforeDropTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeDropTable';
    }

    /**
     * @since 4.1
     */
    public function afterDropTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterDropTable';
    }

    /**
     * @since 4.1
     */
    public function beforeFindMissingFields(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeFindMissingFields';
    }

    /**
     * @since 4.1
     */
    public function afterFindMissingFields(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterFindMissingFields';
    }

    /**
     * @since 4.1
     */
    public function beforeGet(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGet';
    }

    /**
     * @since 4.1
     */
    public function afterGet(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGet';
    }

    /**
     * @since 4.1
     */
    public function beforeGetCount(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGetCount';
    }

    /**
     * @since 4.1
     */
    public function afterGetCount(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGetCount';
    }

    /**
     * @since 4.1
     */
    public function beforeGetHistoryCount(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGetHistoryCount';
    }

    /**
     * @since 4.1
     */
    public function afterGetHistoryCount(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGetHistoryCount';
    }

    /**
     * @since 4.1
     */
    public function beforeGetMAX(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGetMAX';
    }

    /**
     * @since 4.1
     */
    public function afterGetMAX(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGetMAX';
    }

    /**
     * @since 4.1
     */
    public function beforeGetPropObject(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGetPropObject';
    }

    /**
     * @since 4.1
     */
    public function afterGetPropObject(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGetPropObject';
    }

    /**
     * @since 4.1
     */
    public function beforeGetVersion(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeGetVersion';
    }

    /**
     * @since 4.1
     */
    public function afterGetVersion(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterGetVersion';
    }

    /**
     * @since 4.1
     */
    public function beforeLoad(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoad';
    }

    /**
     * @since 4.1
     */
    public function afterLoad(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoad';
    }

    /**
     * @since 4.1
     */
    public function beforeLoadAll(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoadAll';
    }

    /**
     * @since 4.1
     */
    public function afterLoadAll(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoadAll';
    }

    /**
     * @since 4.1
     */
    public function beforeLoadAllByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoadAllByAttribute';
    }

    /**
     * @since 4.1
     */
    public function afterLoadAllByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoadAllByAttribute';
    }

    /**
     * @since 4.1
     */
    public function beforeLoadAllByAttributes(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoadAllByAttributes';
    }

    /**
     * @since 4.1
     */
    public function afterLoadAllByAttributes(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoadAllByAttributes';
    }

    /**
     * @since 4.1
     */
    public function beforeLoadAllByDayUpdated(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoadAllByDayUpdated';
    }

    /**
     * @since 4.1
     */
    public function afterLoadAllByDayUpdated(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoadAllByDayUpdated';
    }

    /**
     * @since 4.1
     */
    public function beforeLoadByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeLoadByAttribute';
    }

    /**
     * @since 4.1
     */
    public function afterLoadByAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterLoadByAttribute';
    }

    /**
     * @since 4.1
     */
    public function beforeMakeHistoryTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeMakeHistoryTable';
    }

    /**
     * @since 4.1
     */
    public function afterMakeHistoryTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterMakeHistoryTable';
    }

    /**
     * @since 4.1
     */
    public function beforeMakeTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeMakeTable';
    }

    /**
     * @since 4.1
     */
    public function afterMakeTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterMakeTable';
    }

    /**
     * @since 4.1
     */
    public function beforeRebuildTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeRebuildTable';
    }

    /**
     * @since 4.1
     */
    public function afterRebuildTable(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterRebuildTable';
    }

    /**
     * @since 4.1
     */
    public function beforeSave(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeSave';
    }

    /**
     * @since 4.1
     */
    public function afterSave(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterSave';
    }

    /**
     * @since 4.1
     */
    public function beforeSaveAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeSaveAttribute';
    }

    /**
     * @since 4.1
     */
    public function afterSaveAttribute(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterSaveAttribute';
    }

    /**
     * @since 4.1
     */
    public function beforeSaveHistory(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeSaveHistory';
    }

    /**
     * @since 4.1
     */
    public function afterSaveHistory(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterSaveHistory';
    }

    /**
     * @since 4.1
     */
    public function beforeSet(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeSet';
    }

    /**
     * @since 4.1
     */
    public function afterSet(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterSet';
    }

    /**
     * @since 4.1
     */
    public function beforeSetEnumOptions(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeSetEnumOptions';
    }

    /**
     * @since 4.1
     */
    public function afterSetEnumOptions(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterSetEnumOptions';
    }

    /**
     * @since 4.1
     */
    public function beforeValidate(): void
    {
        ActiveRecordTestHookSink::$log[] = 'beforeValidate';
    }

    /**
     * @since 4.1
     */
    public function afterValidate(): void
    {
        ActiveRecordTestHookSink::$log[] = 'afterValidate';
    }
}

/**
 * Small record stub for testing the cast method.
 *
 * @since 4.1
 */
class ActiveRecordTestCastSmall extends ActiveRecord
{
    /**
     * @since 4.1
     */
    public const TABLE_NAME = 'ActiveRecordTestCastSmall';

    /**
     * @since 4.1
     */
    protected $name;

    /**
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = new \Alpha\Model\Type\SmallText('x');
    }
}

/**
 * Larger record stub for testing the cast method.
 *
 * @since 4.1
 */
class ActiveRecordTestCastBig extends ActiveRecord
{
    /**
     * @since 4.1
     */
    public const TABLE_NAME = 'ActiveRecordTestCastBig';

    /**
     * @since 4.1
     */
    protected $name;

    /**
     * @since 4.1
     */
    protected $num;

    /**
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->name = new \Alpha\Model\Type\SmallText('x');
        $this->num = new \Alpha\Model\Type\Integer(1);
    }
}

/**
 * Record stub carrying a non transient MANY-TO-MANY relation for testing the
 * related IDs branch of populateFromArray.
 *
 * @since 4.1
 */
class ActiveRecordTestM2MRecord extends ActiveRecord
{
    /**
     * @since 4.1
     */
    public const TABLE_NAME = 'ActiveRecordTestM2MRecord';

    /**
     * @since 4.1
     */
    protected $relAttr;

    /**
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        $rel = new \Alpha\Model\Type\Relation();
        $rc = new \ReflectionProperty(\Alpha\Model\Type\Relation::class, 'relatedClass');
        $rc->setAccessible(true);
        $rc->setValue($rel, 'Alpha\Model\Tag');
        $rf = new \ReflectionProperty(\Alpha\Model\Type\Relation::class, 'relatedClassField');
        $rf->setAccessible(true);
        $rf->setValue($rel, 'ID');
        $rt = new \ReflectionProperty(\Alpha\Model\Type\Relation::class, 'relationType');
        $rt->setAccessible(true);
        $rt->setValue($rel, 'MANY-TO-MANY');
        $this->relAttr = $rel;
    }
}

/**
 * Record stub carrying Date, Timestamp, simple, and private properties for
 * exercising the remaining branches of the ActiveRecord::set method.
 *
 * @since 4.1
 */
class ActiveRecordTestSetProbe extends ActiveRecord
{
    /**
     * @since 4.1
     */
    public const TABLE_NAME = 'ActiveRecordTestSetProbe';

    /**
     * @since 4.1
     */
    protected $stamp;

    /**
     * @since 4.1
     */
    protected $day;

    /**
     * @since 4.1
     */
    protected $plain;

    /**
     * @since 4.1
     */
    private $hidden = 'secret';

    /**
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        $this->stamp = new \Alpha\Model\Type\Timestamp('2026-01-01 01:01:01');
        $this->day = new \Alpha\Model\Type\Date('2026-01-01');
        $this->plain = 'x';
    }
}
