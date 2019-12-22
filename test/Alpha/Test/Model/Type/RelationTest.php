<?php

namespace Alpha\Test\Model\Type;

use Alpha\Test\Model\ModelTestCase;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\Type\Relation;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;

/**
 * Test case for the Relation data type.
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
class RelationTest extends ModelTestCase
{
    /**
     * A Relation for testing.
     *
     * @var \Alpha\Model\Type\Relation
     *
     * @since 1.0
     */
    private $rel1;

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
        $this->rel1 = new Relation();

        $rights = new Rights();
        $rights->rebuildTable();

        $article = new Article();
        $article->rebuildTable();

        $comment = new ArticleComment();
        $comment->rebuildTable();

        $standardGroup = new Rights();
        $standardGroup->set('name', 'Standard');
        $standardGroup->save();

        $this->person = new Person();
        $this->person->set('username', 'unittestuser');
        $this->person->set('email', 'unittestuser@alphaframework.org');
        $this->person->set('password', 'password');
        $this->person->rebuildTable();
        $this->person->save();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here.
     *
     * @since 1.0
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->rel1);
        $person = new Person();
        $person->dropTable();

        $rights = new Rights();
        $rights->dropTable();
        $rights->dropTable('Person2Rights');

        $comment = new ArticleComment();
        $comment->dropTable();

        $article = new Article();
        $article->dropTable();
    }

    /**
     * Testing passing a valid BO name to setRelatedClass.
     *
     * @since 1.0
     */
    public function testSetRelatedClassPass()
    {
        try {
            $this->rel1->setRelatedClass('Alpha\Model\Article');
            $this->assertEquals('Alpha\Model\Article', $this->rel1->getRelatedClass());
        } catch (AlphaException $e) {
            $this->fail('Testing passing a valid BO name to setRelatedClass');
        }
    }

    /**
     * Testing passing an invalid BO name to setRelatedClass.
     *
     * @since 1.0
     */
    public function testSetRelatedClassFail()
    {
        try {
            $this->rel1->setRelatedClass('XyzObject');
            $this->fail('Testing passing an invalid BO name to setRelatedClass');
        } catch (AlphaException $e) {
            $this->assertEquals('The class [XyzObject] is not defined anywhere!', $e->getMessage(), 'Testing passing an invalid BO name to setRelatedClass');
        }
    }

    /**
     * Testing passing a valid field name to setRelatedClassField.
     *
     * @since 1.0
     */
    public function testSetRelatedClassFieldPass()
    {
        try {
            $this->rel1->setRelatedClass('Alpha\Model\Person');
            $this->rel1->setRelatedClassField('email');
            $this->assertEquals('email', $this->rel1->getRelatedClassField());
        } catch (AlphaException $e) {
            $this->fail('Testing passing a valid field name to setRelatedClassField');
        }
    }

    /**
     * Testing passing an invalid field name to setRelatedClassField.
     *
     * @since 1.0
     */
    public function testSetRelatedClassFieldFail()
    {
        try {
            $this->rel1->setRelatedClass('Alpha\Model\Person');
            $this->rel1->setRelatedClassField('doesNotExist');
            $this->fail('Testing passing an invalid field name to setRelatedClassField');
        } catch (AlphaException $e) {
            $this->assertEquals('The field [doesNotExist] was not found in the class [Alpha\Model\Person]', $e->getMessage(), 'Testing passing an invalid field name to setRelatedClassField');
        }
    }

    /**
     * Testing passing a valid type name to setRelationType.
     *
     * @since 1.0
     */
    public function testSetRelationTypePass()
    {
        try {
            $this->rel1->setRelationType('MANY-TO-ONE');
            $this->assertEquals('MANY-TO-ONE', $this->rel1->getRelationType());
        } catch (AlphaException $e) {
            $this->fail('Testing passing a valid type name to setRelationType');
        }
    }

    /**
     * Testing passing an invalid type name to setRelationType.
     *
     * @since 1.0
     */
    public function testSetRelationTypeFail()
    {
        try {
            $this->rel1->setRelationType('blah');
            $this->fail('Testing passing an invalid type name to setRelationType');
        } catch (AlphaException $e) {
            $this->assertEquals('Relation type of [blah] is invalid!', $e->getMessage(), 'Testing passing an invalid type name to setRelationType');
        }
    }

    /**
     * Testing setValue method with a valid value.
     *
     * @since 1.0
     */
    public function testSetValuePass()
    {
        try {
            $this->rel1->setValue(100);
            $this->rel1->setValue('2777');
            $this->assertEquals(2777, $this->rel1->getValue());
        } catch (AlphaException $e) {
            $this->fail('Testing setValue method with a valid value');
        }
    }

    /**
     * Testing setValue method with an invalid value.
     *
     * @since 1.0
     */
    public function testSetValueFail()
    {
        try {
            $this->rel1->setValue('xyz');
            $this->fail('Testing setValue method with an invalid value');
        } catch (AlphaException $e) {
            $this->assertEquals('[xyz] not a valid Relation value!  A maximum of 11 characters is allowed.', $e->getMessage(), 'Testing setValue method with an invalid value');
        }
    }

    /**
     * Testing that the display field value of the related class is accessed correctly.
     *
     * @since 1.0
     */
    public function testSetRelatedClassDisplayFieldPass()
    {
        try {
            $this->rel1->setRelatedClass('Alpha\Model\Person');
            // assuming here that user #1 is the default Administrator account
            $this->rel1->setValue(1);
            $this->rel1->setRelatedClassDisplayField('state');
            $this->assertEquals('Active', $this->rel1->getRelatedClassDisplayFieldValue(), 'Testing that the display field value of the related class is accessed correctly');
        } catch (AlphaException $e) {
            $this->fail('Testing that the display field value of the related class is accessed correctly');
        }
    }

    /**
     * Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition.
     *
     * @since 1.0
     */
    public function testGetRelatedClassDisplayFieldValueFail()
    {
        try {
            $this->rel1->setRelatedClass('NotThere');
            $this->rel1->setRelatedClassDisplayField('someField');
            $value = $this->rel1->getRelatedClassDisplayFieldValue();
            $this->fail('Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
        } catch (\Exception $e) {
            $this->assertEquals('The class [NotThere] is not defined anywhere!', $e->getMessage(), 'Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
        }
    }

    /**
     * Testing the getRelatedClassDisplayFieldValue() method on ONE-TO-MANY and MANY-TO-MANY relations.
     *
     * @since 1.2.1
     */
    public function testGetRelatedClassDisplayFieldValuePass()
    {
        $oneToManyRel = new Relation();
        $oneToManyRel->setRelatedClass('Alpha\Model\Person');
        $oneToManyRel->setRelatedClassField('ID');
        $oneToManyRel->setRelatedClassDisplayField('username');
        $oneToManyRel->setRelationType('ONE-TO-MANY');
        $oneToManyRel->setValue($this->person->getID());

        $this->assertEquals($this->person->getUsername(), $oneToManyRel->getRelatedClassDisplayFieldValue(), 'testing the getRelatedClassDisplayFieldValue() method on ONE-TO-MANY relation');

        $group = new Rights();
        $group->set('name', 'unittestgroup');
        $group->save();

        $person1 = new Person();
        $person1->set('username', 'user1');
        $person1->set('email', 'user1@test.com');
        $person1->set('password', 'password');
        $person1->save();
        $person1->addToGroup('unittestgroup');

        $person2 = new Person();
        $person2->set('username', 'user2');
        $person2->set('email', 'user2@test.com');
        $person2->set('password', 'password');
        $person2->save();
        $person2->addToGroup('unittestgroup');

        $person2->getPropObject('rights')->setValue($group->getID());

        $this->assertEquals(2, count($group->getPropObject('members')->getRelated('Alpha\Model\Rights')), 'testing the getRelatedClassDisplayFieldValue() method on MANY-TO-MANY relation');

        try {
            $this->assertEquals('user1@test.com,user2@test.com', $person2->getPropObject('rights')->getRelatedClassDisplayFieldValue(), 'testing the getRelatedClassDisplayFieldValue() method on MANY-TO-MANY relation');
            $this->fail('testing the getRelatedClassDisplayFieldValue() method on MANY-TO-MANY relation');
        } catch (IllegalArguementException $e) {
            $this->assertEquals($e->getMessage(), 'Tried to load related MANY-TO-MANY fields but no accessingClassName parameter set on the call to getRelatedClassDisplayFieldValue!', 'testing the getRelatedClassDisplayFieldValue() method on MANY-TO-MANY relation');
        }

        $this->assertEquals('user1@test.com,user2@test.com', $person2->getPropObject('rights')->getRelatedClassDisplayFieldValue('Alpha\Model\Rights'), 'testing the getRelatedClassDisplayFieldValue() method on MANY-TO-MANY relation');
    }

    /**
     * Testing the getRelatedClass() method with different relation types.
     *
     * @since 1.2.1
     */
    public function testGetRelatedClass()
    {
        $oneToOneRel = new Relation();
        $oneToOneRel->setRelatedClass('Alpha\Model\ArticleComment');
        $oneToOneRel->setRelatedClassField('articleID');
        $oneToOneRel->setRelatedClassDisplayField('content');
        $oneToOneRel->setRelationType('ONE-TO-ONE');

        $this->assertEquals('Alpha\Model\ArticleComment', $oneToOneRel->getRelatedClass(), 'testing the getRelatedClass() method on a ONE-TO-ONE relation');

        $oneToManyRel = new Relation();
        $oneToManyRel->setRelatedClass('Alpha\Model\ArticleComment');
        $oneToManyRel->setRelatedClassField('articleID');
        $oneToManyRel->setRelatedClassDisplayField('content');
        $oneToManyRel->setRelationType('ONE-TO-MANY');

        $this->assertEquals('Alpha\Model\ArticleComment', $oneToManyRel->getRelatedClass(), 'testing the getRelatedClass() method on a ONE-TO-MANY relation');

        $manyToManyRel = new Relation();
        $manyToManyRel->setRelatedClass('Alpha\Model\Person', 'left');
        $manyToManyRel->setRelatedClassDisplayField('email', 'left');
        $manyToManyRel->setRelatedClass('Alpha\Model\Rights', 'right');
        $manyToManyRel->setRelatedClassDisplayField('name', 'right');
        $manyToManyRel->setRelationType('MANY-TO-MANY');

        $this->assertEquals('Alpha\Model\Person', $manyToManyRel->getRelatedClass('left'), 'testing the getRelatedClass() method on a MANY-TO-MANY relation');
        $this->assertEquals('Alpha\Model\Rights', $manyToManyRel->getRelatedClass('right'), 'testing the getRelatedClass() method on a MANY-TO-MANY relation');
    }

    /**
     * Testing the getSide() method on a MANY-TO-MANY relation.
     *
     * @since 1.2.1
     */
    public function testGetSidePass()
    {
        $manyToManyRel = new Relation();
        $manyToManyRel->setRelatedClass('Alpha\Model\Person', 'left');
        $manyToManyRel->setRelatedClassDisplayField('email', 'left');
        $manyToManyRel->setRelatedClass('Alpha\Model\Rights', 'right');
        $manyToManyRel->setRelatedClassDisplayField('name', 'right');
        $manyToManyRel->setRelationType('MANY-TO-MANY');

        $this->assertEquals('left', $manyToManyRel->getSide('Alpha\Model\Person'), 'testing the getSide() method on a MANY-TO-MANY relation');
        $this->assertEquals('right', $manyToManyRel->getSide('Alpha\Model\Rights'), 'testing the getSide() method on a MANY-TO-MANY relation');
    }

    /**
     * Testing the getSide() method on a ONE-TO-MANY relation.
     *
     * @since 1.2.1
     */
    public function testGetSideFail()
    {
        $oneToManyRel = new Relation();
        $oneToManyRel->setRelatedClass('Alpha\Model\ArticleComment');
        $oneToManyRel->setRelatedClassField('articleID');
        $oneToManyRel->setRelatedClassDisplayField('content');
        $oneToManyRel->setRelationType('ONE-TO-MANY');

        try {
            $oneToManyRel->getSide('Alpha\Model\ArticleComment');
            $this->fail('testing the getSide() method on a ONE-TO-MANY relation');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Error trying to determine the MANY-TO-MANY relationship side for the classname [Alpha\Model\ArticleComment]', $e->getMessage(), 'testing the getSide() method on a ONE-TO-MANY relation');
        }
    }

    /**
     * Testing the getRelated method.
     *
     * @since 1.2.1
     */
    public function testGetRelated()
    {
        $oneToOneRel = new Relation();
        $oneToOneRel->setRelatedClass('Alpha\Model\Person');
        $oneToOneRel->setRelatedClassField('ID');
        $oneToOneRel->setRelatedClassDisplayField('username');
        $oneToOneRel->setRelationType('ONE-TO-ONE');
        $oneToOneRel->setValue($this->person->getID());

        $this->assertEquals($this->person->getUsername(), $oneToOneRel->getRelated()->get('username'), 'testing the getRelated method');

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

        $person2->getPropObject('rights')->setValue($group->getID());

        $this->assertEquals(2, count($group->getPropObject('members')->getRelated('Alpha\Model\Rights')), 'testing the getRelated method with a MANY-TO-MANY relation');
        $this->assertTrue($group->getPropObject('members')->getRelated('Alpha\Model\Rights')[0] instanceof Person, 'testing the getRelated method with a MANY-TO-MANY relation');

        $article = new Article();
        $article->set('title', 'unit test');
        $article->set('description', 'unit test');
        $article->set('content', 'unit test');
        $article->set('author', 'unit test');
        $article->save();

        $comment1 = new ArticleComment();
        $comment1->set('content', 'unit test');
        $comment1->getPropObject('articleID')->setValue($article->getID());
        $comment1->save();

        $comment2 = new ArticleComment();
        $comment2->set('content', 'unit test');
        $comment2->getPropObject('articleID')->setValue($article->getID());
        $comment2->save();

        $this->assertEquals(2, count($article->getPropObject('comments')->getRelated()), 'testing the getRelated method with a ONE-TO-MANY relation');
        $this->assertTrue($article->getPropObject('comments')->getRelated()[0] instanceof ArticleComment, 'testing the getRelated method with a ONE-TO-MANY relation');
    }
}
