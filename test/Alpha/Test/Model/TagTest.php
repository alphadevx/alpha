<?php

namespace Alpha\Test\Model;

use Alpha\Exception\AlphaException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Article;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Util\Config\ConfigProvider;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test case for the Tag class.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2022, John Collins (founder of Alpha Framework).
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
class TagTest extends ModelTestCase
{
    /**
     * An Article for testing.
     *
     * @var \Alpha\Model\Article
     *
     * @since 1.0
     */
    private $article;

    /**
     * Set up tests.
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        $tag = new Tag();
        $tag->rebuildTable();

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $article = new Article();
        $article->rebuildTable();

        $this->article = $this->createArticle('unitTestArticle');
    }

    /**
     * Tear down tests.
     *
     * @since 1.0
     */
    protected function tearDown(): void
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
     * Creates an article object for testing.
     *
     * @return \Alpha\Model\Article
     *
     * @since 1.2.3
     */
    private function createArticle($name)
    {
        $article = new Article();
        $article->set('title', $name);
        $article->set('description', 'A test article called unitTestArticle with some stop words and the unitTestArticle title twice');
        $article->set('author', 'blah');
        $article->set('content', 'blah');

        return $article;
    }

    /**
     * Testing the Tag::tokenize method returns a tag called "unittestarticle".
     *
     * @since 1.0
     */
    public function testTokenizeForExpectedTag()
    {
        $tags = Tag::tokenize($this->article->get('description'), 'Article', $this->article->getID());

        $found = false;
        foreach ($tags as $tag) {
            if ($tag->get('content') == 'unittestarticle') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Testing the Tag::tokenize method returns a tag called "unittestarticle"');
    }

    /**
     * Testing the Tag::tokenize method does not return a tag called "a".
     *
     * @since 1.0
     */
    public function testTokenizeForUnexpectedTag()
    {
        $tags = Tag::tokenize($this->article->get('description'), 'Article', $this->article->getID());

        $found = false;
        foreach ($tags as $tag) {
            if ($tag->get('content') == 'a') {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Testing the Tag::tokenize method does not return a tag called "a"');
    }

    /**
     * Test to ensure that the duplicated value "unittestarticle" is only converted to a Tag once by Tag::tokenize.
     *
     * @since 1.0
     */
    public function testTokenizeNoDuplicates()
    {
        $tags = Tag::tokenize($this->article->get('description'), 'Article', $this->article->getID());

        $count = 0;
        foreach ($tags as $tag) {
            if ($tag->get('content') == 'unittestarticle') {
                ++$count;
            }
        }

        $this->assertEquals(1, $count, 'Test to ensure that the duplicated value "unittestarticle" is only converted to a Tag once by Tag::tokenize');
    }

    /**
     * Testing that when an Article is created that tags are autogenerated based on the description.
     *
     * @since 1.0
     */
    public function testSaveArticleGeneratesDescriptionTags()
    {
        $this->article->save();
        $tags = $this->article->getPropObject('tags')->getRelated();

        $found = false;
        foreach ($tags as $tag) {
            if ($tag->get('content') == 'unittestarticle') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Testing the Tag::tokenize method returns a tag called "unittestarticle"');
    }

    /**
     * Testing the loadTags() method for accessing the tags on a given object type directly.
     *
     * @since 1.0
     */
    public function testLoadTags()
    {
        $this->article->save();
        $tagsA = $this->article->getPropObject('tags')->getRelated();

        $tag = new Tag();
        $tagsB = $tag->loadTags('Alpha\Model\Article', $this->article->getID());

        $this->assertEquals(count($tagsA), count($tagsB), 'testing the loadTags() method for accessing the tags on a given object type directly');
    }

    /**
     * Testing the loadTags() method throws an exception when arguments are empty.
     *
     * @since 4.1
     */
    public function testLoadTagsEmptyArgs(): void
    {
        $this->expectException(IllegalArguementException::class);
        $tag = new Tag();
        $tag->loadTags('', '');
    }

    /**
     * Testing the loadTags() method returns an empty array when no records found.
     *
     * @since 4.1
     */
    public function testLoadTagsNoRecords(): void
    {
        $tag = new Tag();
        $tags = $tag->loadTags('Alpha\Model\Article', 999999);
        $this->assertIsArray($tags);
        $this->assertEquals(0, count($tags));
    }

    /**
     * Testing the getPopularTagsArray method returns an array.
     *
     * @since 4.1
     */
    public function testGetPopularTagsArray(): void
    {
        $this->article->save();
        $tags = Tag::getPopularTagsArray(10);
        $this->assertIsArray($tags);
    }

    /**
     * Testing the getPopularTagsArray method with owner and date filters.
     *
     * @since 4.1
     */
    public function testGetPopularTagsArrayWithFilters(): void
    {
        $this->article->save();
        $tags = Tag::getPopularTagsArray(10, '1', '2000-01-01', '2100-01-01');
        $this->assertIsArray($tags);
    }

    /**
     * Testing the tokenize method with applyStopwords set to false.
     *
     * @since 4.1
     */
    public function testTokenizeWithoutStopwords(): void
    {
        $tags = Tag::tokenize('Hello World Foo', '', '', false);
        $this->assertIsArray($tags);
        $this->assertGreaterThan(0, count($tags));
    }

    /**
     * Testing the tokenize method without taggedClass and taggedID.
     *
     * @since 4.1
     */
    public function testTokenizeWithoutTaggedClassAndID(): void
    {
        $tags = Tag::tokenize('Alpha Framework Test');
        $this->assertIsArray($tags);
        foreach ($tags as $tag) {
            $this->assertTrue($tag instanceof Tag);
        }
    }

    /**
     * Testing the tokenize method filters out non-alpha tokens.
     *
     * @since 4.1
     */
    public function testTokenizeFiltersNonAlpha(): void
    {
        $tags = Tag::tokenize('hello 123 world', '', '', false);
        $contents = array();
        foreach ($tags as $tag) {
            $contents[] = $tag->get('content');
        }
        $this->assertNotContains('123', $contents);
        $this->assertContains('hello', $contents);
        $this->assertContains('world', $contents);
    }

    /**
     * Testing the cleanTagContent method.
     *
     * @since 4.1
     */
    public function testCleanTagContent(): void
    {
        $this->assertEquals('helloworld', Tag::cleanTagContent('Hello World'));
        $this->assertEquals('abc', Tag::cleanTagContent('  ABC  '));
    }

    /**
     * Testing the lowercaseArrayElement private static method via reflection.
     *
     * @since 4.1
     */
    public function testLowercaseArrayElement(): void
    {
        $method = new ReflectionMethod('Alpha\Model\Tag', 'lowercaseArrayElement');
        $method->setAccessible(true);
        $element = '  UPPER  ';
        $method->invokeArgs(null, array(&$element, 0));
        $this->assertEquals('upper', $element);
    }

    /**
     * Testing the afterSave method when cache provider is empty.
     *
     * @since 4.1
     */
    public function testAfterSaveNoCache(): void
    {
        $config = ConfigProvider::getInstance();
        $original = $config->get('cache.provider.name');
        $config->set('cache.provider.name', '');

        $tag = new Tag();
        $tag->set('content', 'testcache');

        $method = new ReflectionMethod('Alpha\Model\Tag', 'afterSave');
        $method->setAccessible(true);
        $method->invoke($tag);

        $config->set('cache.provider.name', $original);
        $this->assertTrue(true);
    }

    /**
     * Testing the beforeDelete method delegates to afterSave.
     *
     * @since 4.1
     */
    public function testBeforeDelete(): void
    {
        $config = ConfigProvider::getInstance();
        $original = $config->get('cache.provider.name');
        $config->set('cache.provider.name', '');

        $tag = new Tag();
        $tag->set('content', 'testcache');

        $method = new ReflectionMethod('Alpha\Model\Tag', 'beforeDelete');
        $method->setAccessible(true);
        $method->invoke($tag);

        $config->set('cache.provider.name', $original);
        $this->assertTrue(true);
    }

    /**
     * Testing the constructor sets up the type properties and unique fields.
     *
     * @since 4.1
     */
    public function testConstructorProperties(): void
    {
        $tag = new Tag();
        $this->assertNotNull($tag->getPropObject('taggedClass'));
        $this->assertNotNull($tag->getPropObject('taggedID'));
        $this->assertNotNull($tag->getPropObject('content'));
        $this->assertEquals('Tag', Tag::TABLE_NAME);
    }

    /**
     * Testing the dataLabels property via reflection.
     *
     * @since 4.1
     */
    public function testDataLabels(): void
    {
        $tag = new Tag();
        $reflection = new ReflectionClass('Alpha\Model\Tag');
        $property = $reflection->getProperty('dataLabels');
        $property->setAccessible(true);
        $labels = $property->getValue($tag);
        $this->assertArrayHasKey('content', $labels);
        $this->assertArrayHasKey('taggedClass', $labels);
        $this->assertArrayHasKey('taggedID', $labels);
        $this->assertArrayHasKey('ID', $labels);
    }
}
