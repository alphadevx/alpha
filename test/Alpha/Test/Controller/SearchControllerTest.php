<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\SearchController;
use Alpha\Model\Article;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the SearchController class.
 *
 * @since 2.0
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
class SearchControllerTest extends TestCase
{
    /**
     * Set up tests.
     *
     * @since 2.0
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        $config->set('app.url', 'http://localhost');
        $config->set('app.secure.url', 'https://localhost');
        $config->set('app.list.page.amount', 2);

        $storeDir = sys_get_temp_dir() . '/alpha-search-test-' . uniqid() . '/';
        $config->set('app.file.store.dir', $storeDir);

        if (!is_dir($storeDir . 'logs')) {
            @mkdir($storeDir . 'logs', 0777, true);
        }

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
        $article->set('published', true);

        return $article;
    }

    /**
     * Sets a protected/private property on an object via reflection.
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     *
     * @since 4.1
     */
    private function setProp(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionClass($object);
        while ($ref && !$ref->hasProperty($property)) {
            $ref = $ref->getParentClass();
        }
        if ($ref) {
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($object, $value);
        }
    }

    /**
     * Invokes a protected/private method on an object via reflection.
     *
     * @param object $object
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @since 4.1
     */
    private function callMethod(object $object, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($object);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($object, $args);
    }

    /**
     * Builds a real Request with the supplied params and URI.
     *
     * @param array  $params
     * @param string $uri
     *
     * @return \Alpha\Util\Http\Request
     *
     * @since 4.1
     */
    private function mockRequest(array $params = [], string $uri = '/search/foo'): Request
    {
        $request = new Request(array(
            'method' => 'GET',
            'URI' => $uri,
            'params' => $params,
            'userAgent' => 'phpunit',
            'ip' => '127.0.0.1'
        ));

        return $request;
    }

    /**
     * Testing the doGET method.
     */
    public function testDoGET(): void
    {
        $config = ConfigProvider::getInstance();

        $front = new FrontController();

        $article = $this->createArticle('testing');
        $article->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/search/blah'));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/search/blah/0/1'));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method with pagination params');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
    }

    /**
     * Testing the constructor.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $controller = new SearchController('Public');
        $this->assertInstanceOf(SearchController::class, $controller);

        $controller2 = new SearchController();
        $this->assertInstanceOf(SearchController::class, $controller2);
    }

    /**
     * Testing doGET with no query provided.
     *
     * @since 4.1
     */
    public function testDoGETNoQuery(): void
    {
        $controller = new SearchController();
        $request = $this->mockRequest(array(), '/search/');
        $this->setProp($controller, 'request', $request);

        $response = $controller->doGET($request);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
    }

    /**
     * Testing renderResultList with an empty result set and empty query.
     *
     * @since 4.1
     */
    public function testRenderResultListEmpty(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest());

        $body = $this->callMethod($controller, 'renderResultList', array(array(), '', true));
        $this->assertEquals('', $body);
    }

    /**
     * Testing renderResultList with a published article and a query heading.
     *
     * @since 4.1
     */
    public function testRenderResultListPublished(): void
    {
        $article = $this->createArticle('publishedOne');
        $article->save();

        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array(), '/search/foo'));
        $this->setProp($controller, 'resultCount', 1);

        $body = $this->callMethod($controller, 'renderResultList', array(array($article), 'foo', true));
        $this->assertStringContainsString('Displaying results for', $body);
    }

    /**
     * Testing renderResultList with an unpublished article (decrements count).
     *
     * @since 4.1
     */
    public function testRenderResultListUnpublished(): void
    {
        $article = new Article();
        $article->set('title', 'draft');
        $article->set('published', false);

        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest());
        $this->setProp($controller, 'resultCount', 5);

        $this->callMethod($controller, 'renderResultList', array(array($article), 'foo', true));
        $this->assertEquals(4, $controller->getResultCount());
    }

    /**
     * Testing renderResultList with showTags disabled.
     *
     * @since 4.1
     */
    public function testRenderResultListNoTags(): void
    {
        $article = $this->createArticle('noTagsArticle');
        $article->save();

        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array(), '/search/foo'));
        $this->setProp($controller, 'resultCount', 1);

        $body = $this->callMethod($controller, 'renderResultList', array(array($article), 'foo', false));
        $this->assertStringNotContainsString('Tags:', $body);
    }

    /**
     * Testing afterDisplayPageHead.
     *
     * @since 4.1
     */
    public function testAfterDisplayPageHead(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest());

        $body = $controller->afterDisplayPageHead();
        $this->assertStringContainsString('search_form', $body);
        $this->assertStringContainsString('Search', $body);
    }

    /**
     * Testing beforeDisplayPageFoot.
     *
     * @since 4.1
     */
    public function testBeforeDisplayPageFoot(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest());
        $this->setProp($controller, 'resultCount', 0);
        $this->setProp($controller, 'startPoint', 0);
        $this->setProp($controller, 'query', 'foo');

        $body = $controller->beforeDisplayPageFoot();
        $this->assertStringContainsString('<br>', $body);
    }

    /**
     * Testing renderPageLinks with no results but a query set.
     *
     * @since 4.1
     */
    public function testRenderPageLinksNoResultsWithQuery(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest());
        $this->setProp($controller, 'resultCount', 0);
        $this->setProp($controller, 'startPoint', 0);
        $this->setProp($controller, 'query', 'foo');

        $body = $this->callMethod($controller, 'renderPageLinks');
        $this->assertStringContainsString('no search results', $body);
    }

    /**
     * Testing renderPageLinks with results and pagination (non-secure).
     *
     * @since 4.1
     */
    public function testRenderPageLinksResults(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array(), '/search/foo'));
        $this->setProp($controller, 'resultCount', 5);
        $this->setProp($controller, 'startPoint', 0);
        $this->setProp($controller, 'query', 'foo');

        $body = $this->callMethod($controller, 'renderPageLinks');
        $this->assertStringContainsString('Displaying', $body);
        $this->assertStringContainsString('pagination', $body);
        $this->assertStringContainsString('active', $body);
    }

    /**
     * Testing renderPageLinks with a start point greater than zero (non-secure).
     *
     * @since 4.1
     */
    public function testRenderPageLinksStartPoint(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array(), '/search/foo'));
        $this->setProp($controller, 'resultCount', 5);
        $this->setProp($controller, 'startPoint', 2);
        $this->setProp($controller, 'query', 'foo');

        $body = $this->callMethod($controller, 'renderPageLinks');
        $this->assertStringContainsString('laquo', $body);
    }

    /**
     * Testing renderPageLinks with secure URL token (tk) in params.
     *
     * @since 4.1
     */
    public function testRenderPageLinksSecure(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array('tk' => 'abc123'), '/search/foo'));
        $this->setProp($controller, 'resultCount', 5);
        $this->setProp($controller, 'startPoint', 2);
        $this->setProp($controller, 'query', 'foo');

        $body = $this->callMethod($controller, 'renderPageLinks');
        $this->assertStringContainsString('pagination', $body);
    }

    /**
     * Testing renderPageLinks with secure URL token and start point zero.
     *
     * @since 4.1
     */
    public function testRenderPageLinksSecureStartZero(): void
    {
        $controller = new SearchController();
        $this->setProp($controller, 'request', $this->mockRequest(array('tk' => 'abc123'), '/search/foo'));
        $this->setProp($controller, 'resultCount', 5);
        $this->setProp($controller, 'startPoint', 0);
        $this->setProp($controller, 'query', 'foo');

        $body = $this->callMethod($controller, 'renderPageLinks');
        $this->assertStringContainsString('pagination', $body);
    }

    /**
     * Testing getResultCount and setResultCount.
     *
     * @since 4.1
     */
    public function testGetSetResultCount(): void
    {
        $controller = new SearchController();
        $this->assertEquals(0, $controller->getResultCount());

        $this->callMethod($controller, 'setResultCount', array(42));
        $this->assertEquals(42, $controller->getResultCount());
    }

    /**
     * Testing getSearchQuery and setSearchQuery.
     *
     * @since 4.1
     */
    public function testGetSetSearchQuery(): void
    {
        $controller = new SearchController();
        $this->callMethod($controller, 'setSearchQuery', array('myquery'));
        $this->assertEquals('myquery', $controller->getSearchQuery());
    }
}
