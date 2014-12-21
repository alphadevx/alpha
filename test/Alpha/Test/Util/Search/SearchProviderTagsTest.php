<?php

namespace Alpha\Test\Util\Search;

use Alpha\Model\Article;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Util\Search\SearchProviderFactory;

/**
 *
 * Test case for the SearchProviderTags class
 *
 * @since 1.2.3
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class SearchProviderTagsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * An Article for testing
     *
     * @var Alpha\Model\Article
     * @since 1.2.3
     */
    private $article;

    /**
     * ID of the artice section DEnum item to use during testing
     *
     * @var int
     */
    private $DEnumID;

    /**
     * Set up tests
     *
     * @since 1.2.3
     */
    protected function setUp()
    {
        $tag = new Tag();
        $tag->rebuildTable();

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $article = new Article();
        $article->rebuildTable();

        $denum = new DEnum('Article::section');
        $item->set('DEnumID', $denum->getOID());
        $item->set('value', 'Test');
        $item->save();

        $this->DEnumID = $denum->getOID();

        $this->article = $this->createArticle('unitTestArticle');
    }

    /**
     * Tear down tests
     *
     * @since 1.2.3
     */
    protected function tearDown()
    {
        $article = new Article();
        $article->dropTable();

        $tag = new Tag();
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
     * @return Alpha\Model\Article
     * @since 1.2.3
     */
    private function createArticle($name)
    {
        $article = new Article();
        $article->set('title', $name);
        $article->set('description', 'A test article called unitTestArticle with some stop words and the unitTestArticle title twice');
        $article->set('author', 'blah');
        $article->set('content', 'blah');
        $article->set('section', $this->DEnumID);

        return $article;
    }

    /**
     * Testing that the index method is generating tags as expected
     *
     * @since 1.2.3
     */
    public function testIndex()
    {
        $this->article->save();

        $tag = new Tag();
        $tag->dropTable();
        $tag->rebuildTable();

        $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');
        $provider->index($this->article);

        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $found = false;
        foreach ($tags as $tag) {
            if ($tag->get('content') == 'unittestarticle') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Testing that the index method is generating tags as expected');
    }

    /**
     * Testing that tags have been deleted once a record has been deleted from the search index
     *
     * @since 1.2.3
     */
    public function testDelete()
    {
        $this->article->save();
        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $this->assertTrue(count($tags) > 0, 'Confirming that tags exist after saving the article (ArticleObject::after_save_callback())');

        $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');
        $provider->delete($this->article);

        $tags = $this->article->getPropObject('tags')->getRelatedObjects();

        $this->assertTrue(count($tags) == 0, 'Testing that tags have been deleted once a DAO has been deleted from the search index');
    }

    /**
     * Testing the search method for expected results
     *
     * @since 1.2.3
     */
    public function testSearch()
    {
        $this->article->save();

        $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');
        $results = $provider->search('unitTestArticle');

        $this->assertTrue(count($results) == 1, 'Testing the search method for expected results');
        $this->assertEquals($this->article->getOID(), $results[0]->getOID(), 'Testing the search method for expected results');

        $results = $provider->search('unitTestArticle', 'PersonObject');

        $this->assertTrue(count($results) == 0, 'Testing the search method honours returnType filtering');
    }

    /**
     * Testing the method for getting the expected number of results
     *
     * @since 1.2.3
     */
    public function testGetNumberFound()
    {
        $this->article->save();

        $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');
        $results = $provider->search('unitTestArticle');

        $this->assertTrue($provider->getNumberFound() == 1, 'Testing the method for getting the expected number of results');

        $article2 = $this->createArticle('unitTestArticle 2');
        $article2->save();

        $article3 = $this->createArticle('unitTestArticle 3');
        $article3->save();

        $results = $provider->search('unitTestArticle');

        $this->assertTrue($provider->getNumberFound() == 3, 'Testing the method for getting the expected number of results');
    }

    /**
     * Testing the method for getting related objects
     *
     * @since 1.2.3
     */
    public function testGetRelated()
    {
        $this->article->save();

        $article2 = $this->createArticle('unitTestArticle 2');
        $article2->save();

        $article3 = $this->createArticle('unitTestArticle 3');
        $article3->save();

        $provider = SearchProviderFactory::getInstance('Alpha\Util\Search\SearchProviderTags');
        $results = $provider->getRelated($this->article);

        $this->assertTrue(count($results) == 2, 'Testing the method for getting related objects');

        $results = $provider->getRelated($this->article, 'all', 0, 1);

        $this->assertTrue(count($results) == 1, 'Testing the method for getting related objects honours limit param');

        $results = $provider->getRelated($this->article, 'PersonObject');

        $this->assertTrue(count($results) == 0, 'Testing the get related objects method honours returnType filtering');
    }
}

?>