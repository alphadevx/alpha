<?php

/**
 *
 * Test case for the SearchProviderTags class
 *
 * @package alpha::tests
 * @since 1.2.3
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
class SearchProviderTags_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An ArticleObject for testing
	 *
	 * @var ArticleObject
	 * @since 1.2.3
	 */
	private $article;

	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.2.3
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
     * @since 1.2.3
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
     * @since 1.2.3
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
     * Testing that the index method is generating tags as expected
     *
     * @since 1.2.3
     */
    public function testIndex() {
        $this->article->save();

        $tag = new TagObject();
        $tag->dropTable();
        $tag->rebuildTable();

        $provider = SearchProviderFactory::getInstance('SearchProviderTags');
        $provider->index($this->article);

        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $found = false;
        foreach($tags as $tag) {
            if($tag->get('content') == 'unittestarticle') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Testing that the index method is generating tags as expected');
    }

    /**
     * Testing that tags have been deleted once a DAO has been deleted from the search index
     *
     * @since 1.2.3
     */
    public function testDelete() {
        $this->article->save();
        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $this->assertTrue(count($tags) > 0, 'Confirming that tags exist after saving the article (ArticleObject::after_save_callback())');

        $provider = SearchProviderFactory::getInstance('SearchProviderTags');
        $provider->delete($this->article);

        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $this->assertTrue(count($tags) == 0, 'Testing that tags have been deleted once a DAO has been deleted from the search index');
    }
}

?>