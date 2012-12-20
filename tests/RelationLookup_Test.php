<?php

/**
 * Test case for the RelationLookup data type
 *
 * @package alpha::tests
 * @since 1.2.1
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).
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
class RelationLookup_Test extends PHPUnit_Framework_TestCase {

	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.2.1
     */
    protected function setUp() {
        $rights = new RightsObject();
        $rights->rebuildTable();

        $person = new PersonObject();
        $person->rebuildTable();

        $article = new ArticleObject();
        $article->rebuildTable();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.0
     */
    protected function tearDown() {
        $person = new PersonObject();
        $person->dropTable();

        $rights = new RightsObject();
        $rights->dropTable();
        $rights->dropTable('Person2Rights');
        $rights->dropTable('Person2Article');

        $article = new ArticleObject();
        $article->dropTable();
    }

    /**
     * Testing the RelationLookup constructor
     *
     * @since 1.2.1
     */
    public function testConstruct() {

        try{
            $lookup = new RelationLookup('','');
            $this->fail('testing the RelationLookup constructor');
        }catch(IllegalArguementException $e) {
            $this->assertEquals('Cannot create RelationLookup object without providing the left and right class names!', $e->getMessage(), 'testing the RelationLookup constructor');
        }

        $article = new ArticleObject();

        try {
            $article->dropTable();

            $lookup = new RelationLookup('PersonObject','ArticleObject');
            $this->fail('testing the RelationLookup constructor');
        }catch(FailedLookupCreateException $e) {
            $this->assertEquals('Error trying to create a lookup table [Person2Article], as tables for BOs [PersonObject] or [ArticleObject] don\'t exist!', $e->getMessage(), 'testing the RelationLookup constructor');
        }

        $article->rebuildTable();

        $lookup = new RelationLookup('PersonObject','ArticleObject');

        $this->assertTrue($lookup->checkTableExists(), 'testing the RelationLookup constructor');
    }

    /**
     * Testing the getTableName() method
     *
     * @since 1.2.1
     */
    public function testGetTableName() {

        $lookup = new RelationLookup('PersonObject','ArticleObject');
        $this->assertEquals('Person2Article', $lookup->getTableName(), 'testing the getTableName() method');

        $lookup = new RelationLookup('ArticleObject','PersonObject');
        $this->assertEquals('Article2Person', $lookup->getTableName(), 'testing the getTableName() method');
    }

    /**
     * Testing the setValue() method with good params
     *
     * @since 1.2.1
     */
    public function testSetValuePass() {
        $lookup = new RelationLookup('PersonObject','ArticleObject');
        $lookup->setValue(array(1,2));

        $this->assertTrue(is_array($lookup->getValue()), 'testing the setValue() method with good params');
        $this->assertTrue(in_array(2, $lookup->getValue()), 'testing the setValue() method with good params');
    }

    /**
     * Testing the setValue() method with bad params
     *
     * @since 1.2.1
     */
    public function testSetValueFail() {
        $lookup = new RelationLookup('PersonObject','ArticleObject');

        try {
            $lookup->setValue(2);
            $this->fail('testing the setValue() method with bad params');
        }catch (IllegalArguementException $e) {
            $this->assertEquals('Array value passed to setValue is not valid [2], array should contain two OIDs', $e->getMessage(), 'testing the setValue() method with bad params');
        }
    }
}

?>