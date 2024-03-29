<?php

namespace Alpha\Test\Model\Type;

use Alpha\Test\Model\ModelTestCase;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\Article;
use Alpha\Model\Type\RelationLookup;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\FailedLookupCreateException;
use Alpha\Util\Config\Configprovider;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the RelationLookup data type.
 *
 * @since 1.2.1
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
class RelationLookupTest extends ModelTestCase
{
    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.2.1
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

            $person = new Person();
            $person->rebuildTable();

            $article = new Article();
            $article->rebuildTable();
        }
    }

    /**
     * Testing the RelationLookup constructor.
     *
     * @since 1.2.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testConstruct(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        try {
            $lookup = new RelationLookup('', '');
            $this->fail('testing the RelationLookup constructor');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Cannot create RelationLookup object without providing the left and right class names!', $e->getMessage(), 'testing the RelationLookup constructor');
        }

        $article = new Article();

        try {
            $article->dropTable();

            $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Article');
            $this->fail('testing the RelationLookup constructor');
        } catch (FailedLookupCreateException $e) {
            $this->assertEquals('Error trying to create a lookup table [Person2Article], as tables for records [Alpha\Model\Person] or [Alpha\Model\Article] don\'t exist!', $e->getMessage(), 'testing the RelationLookup constructor');
        }

        $article->rebuildTable();

        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Article');

        $this->assertTrue($lookup->checkTableExists(), 'testing the RelationLookup constructor');
    }

    /**
     * Testing the getTableName() method.
     *
     * @since 1.2.1
     */
    public function testGetTableName()
    {
        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Article');
        $this->assertEquals('Person2Article', $lookup->getTableName(), 'testing the getTableName() method');

        $lookup = new RelationLookup('Alpha\Model\Article', 'Alpha\Model\Person');
        $this->assertEquals('Article2Person', $lookup->getTableName(), 'testing the getTableName() method');
    }

    /**
     * Testing the setValue() method with good params.
     *
     * @since 1.2.1
     */
    public function testSetValuePass()
    {
        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Article');
        $lookup->setValue(array(1, 2));

        $this->assertTrue(is_array($lookup->getValue()), 'testing the setValue() method with good params');
        $this->assertTrue(in_array(2, $lookup->getValue(), true), 'testing the setValue() method with good params');
    }

    /**
     * Testing the setValue() method with bad params.
     *
     * @since 1.2.1
     */
    public function testSetValueFail()
    {
        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Article');

        try {
            $lookup->setValue(2);
            $this->fail('testing the setValue() method with bad params');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Array value passed to setValue is not valid [2], array should contain two IDs', $e->getMessage(), 'testing the setValue() method with bad params');
        }
    }

    /**
     * Testing the loadAllbyAttribute() method.
     *
     * @since 1.2.1
     */
    #[DataProvider('getActiveRecordProviders')]
    public function testLoadAllbyAttribute(string $provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('db.provider.name', $provider);

        $group = new Rights();
        $group->set('name', 'unittestgroup');
        $group->save();

        $person1 = new Person();
        $person1->set('username', 'user1');
        $person1->set('email', 'user1@test.com');
        $person1->set('password', 'password');
        $person1->save();
        $lookup = $person1->getPropObject('rights')->getLookup();
        $lookup->setValue(array($person1->getID(), $group->getID()));
        $lookup->save();

        $person2 = new Person();
        $person2->set('username', 'user2');
        $person2->set('email', 'user2@test.com');
        $person2->set('password', 'password');
        $person2->save();
        $lookup = $person2->getPropObject('rights')->getLookup();
        $lookup->setValue(array($person2->getID(), $group->getID()));
        $lookup->save();

        $lookup = new RelationLookup('Alpha\Model\Person', 'Alpha\Model\Rights');
        $this->assertEquals(2, count($lookup->loadAllbyAttribute('rightID', $group->getID())), 'testing the loadAllbyAttribute() method');
    }
}
