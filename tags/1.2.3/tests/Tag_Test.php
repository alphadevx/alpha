<?php

/**
 *
 * Test case for the TagObject class
 *
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class Tag_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An ArticleObject for testing
	 *
	 * @var ArticleObject
	 * @since 1.0
	 */
	private $article;

	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
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

        $denum = new DEnum('ArticleObject::section');
        $item->set('DEnumID', $denum->getOID());
        $item->set('value', 'Test');
        $item->save();

    	$this->article = $this->createArticleObject('unitTestArticle');
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.0
     */
    protected function tearDown() {
    	$article = new ArticleObject();
        $article->dropTable();

        $tag = new TagObject();
        $tag->dropTable();

        $denum = new DEnum();
    	$denum->dropTable();

        $item = new DEnumItem();
        $item->dropTable();

        unset($this->article);
    }

    /**
     * Creates an article object for testing
     *
     * @return ArticleObject
     * @since 1.0
     */
    private function createArticleObject($name) {
    	$article = new ArticleObject();
        $article->set('title', $name);
        $article->set('description', 'A test article called unitTestArticle with some stop words and the unitTestArticle title twice');
        $article->set('author', 'blah');
        $article->set('content', 'blah');

        return $article;
    }

    /**
     * Testing the TagObject::tokenize method returns a tag called "unittestarticle"
     *
     * @since 1.0
     */
    public function testTokenizeForExpectedTag() {
    	$tags = TagObject::tokenize($this->article->get('description'), 'ArticleObject', $this->article->getOID());

    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertTrue($found, 'Testing the TagObject::tokenize method returns a tag called "unittestarticle"');
    }

    /**
     * Testing the TagObject::tokenize method does not return a tag called "a"
     *
     * @since 1.0
     */
    public function testTokenizeForUnexpectedTag() {
    	$tags = TagObject::tokenize($this->article->get('description'), 'ArticleObject', $this->article->getOID());

    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'a') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertFalse($found, 'Testing the TagObject::tokenize method does not return a tag called "a"');
    }

    /**
     * Test to ensure that the duplicated value "unittestarticle" is only converted to a TagObject once by TagObject::tokenize
     *
     * @since 1.0
     */
    public function testTokenizeNoDuplicates() {
    	$tags = TagObject::tokenize($this->article->get('description'), 'ArticleObject', $this->article->getOID());

    	$count = 0;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$count++;
    		}
    	}

    	$this->assertEquals(1, $count, 'Test to ensure that the duplicated value "unittestarticle" is only converted to a TagObject once by TagObject::tokenize');
    }

    /**
     * Testing that when an ArticleObject is created that tags are autogenerated based on the description
     *
     * @since 1.0
     */
    public function testSaveArticleGeneratesDescriptionTags() {
    	$this->article->save();
    	$tags = $this->article->getPropObject('tags')->getRelatedObjects();

    	$found = false;
    	foreach($tags as $tag) {
    		if($tag->get('content') == 'unittestarticle') {
    			$found = true;
    			break;
    		}
    	}
    	$this->assertTrue($found, 'Testing the TagObject::tokenize method returns a tag called "unittestarticle"');
    }

    /**
     * Testing the loadTags() method for accessing the tags on a given object type directly
     *
     * @since 1.0
     */
    public function testLoadTags() {
    	$this->article->save();
    	$tagsA = $this->article->getPropObject('tags')->getRelatedObjects();

    	$tag = new TagObject();
    	$tagsB = $tag->loadTags('ArticleObject', $this->article->getOID());

    	$this->assertEquals(count($tagsA), count($tagsB), 'testing the loadTags() method for accessing the tags on a given object type directly');
    }
}

?>