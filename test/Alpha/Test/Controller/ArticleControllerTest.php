<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\ArticleController;
use Alpha\Controller\Front\FrontController;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Model\Article;
use Alpha\Model\ArticleVote;
use Alpha\Model\ArticleComment;
use Alpha\Model\ActionLog;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Service\ServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the ArticleController class.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class ArticleControllerTest extends TestCase
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

        $action = new ActionLog();
        $action->rebuildTable();

        $tag = new Tag();
        $tag->rebuildTable();

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $article = new Article();
        $article->rebuildTable();

        $articleVote = new ArticleVote();
        $articleVote->rebuildTable();

        $articleComment = new ArticleComment();
        $articleComment->rebuildTable();

        $person = new Person();
        $person->rebuildTable();

        $rights = new Rights();
        $rights->rebuildTable();
        $rights->set('name', 'Standard');
        $rights->save();

        $rights = new Rights();
        $rights->set('name', 'Admin');
        $rights->save();
    }

    /**
     * Creates an article object for Testing.
     *
     * @return \Alpha\Model\Article
     *
     * @since 2.0
     */
    private function createArticleObject($name)
    {
        $config = ConfigProvider::getInstance();

        $markdown = 'First Header  | Second Header
------------- | -------------
Content Cell  | Content Cell
Content Cell  | Content Cell

![Alpha logo]('.$config->get('app.root').'public/images/logo-small.png "Alpha logo")';

        $article = new Article();
        $article->set('title', $name);
        $article->set('description', 'unitTestArticleTagOneAA unitTestArticleTagTwo');
        $article->set('author', 'unitTestArticleTagOneBB');
        $article->set('content', $markdown);
        $article->set('published', true);
        $article->set('headerContent', '<script>alert();</script>');

        return $article;
    }

    /**
     * Creates a person object for Testing.
     *
     * @return \Alpha\Model\Person
     *
     * @since 1.0
     */
    private function createPersonObject($name)
    {
        $person = new Person();
        $person->setUsername($name);
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');

        return $person;
    }

    /**
     * Testing the doGET method.
     */
    public function testDoGET()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $oldSetting = $config->get('cache.provider.name');
        $config->set('cache.provider.name', 'Alpha\Util\Cache\CacheProviderArray');

        $person = $this->createPersonObject('test');
        $person->save();
        $session->set('currentUser', $person);

        $article = $this->createArticleObject('test article');
        $article->save();

        $comment = new ArticleComment();
        $comment->set('content', 'Test comment');
        $comment->set('articleID', $article->getID());
        $comment->save();

        $vote = new ArticleVote();
        $vote->set('score', 10);
        $vote->set('articleID', $article->getID());
        $vote->save();

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');

        $this->assertStringContainsString('<script>alert();</script>', $response->getBody(), 'Testing that the article header content was rendered');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/not-there'));

        $response = $front->process($request);

        $this->assertEquals(404, $response->getStatus(), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a', 'params' => array('file' => getcwd().'/README.md')));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article', 'headers' => array('Accept' => 'application/pdf')));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('application/pdf', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article/edit'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');

        $config->set('cache.provider.name', $oldSetting);
    }

    /**
     * Testing the doPUT method.
     */
    public function testDoPUT()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ArticleController();

        $article = $this->createArticleObject('test article');
        $article->save();

        if (!file_exists($article->getAttachmentsLocation())) {
            mkdir($article->getAttachmentsLocation(), 0774);
        }

        $person = $this->createPersonObject('test');
        $person->save();
        $session->set('currentUser', $person);

        $securityParams = $controller->generateSecurityFields();

        $article->set('title', 'new put title');

        $params = array('saveBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1], 'ActiveRecordID' => $article->getID());
        $params = array_merge($params, $article->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/a/test-article', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPUT method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/a/new-put-title/edit') !== false, 'Testing the doPUT method');
    }

    /**
     * Testing the doDELETE method.
     */
    public function testDoDELETE()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ArticleController();

        $article = $this->createArticleObject('test article');
        $article->save();

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method with a hit');

        $securityParams = $controller->generateSecurityFields();

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);

        $request = new Request(array('method' => 'DELETE', 'URI' => '/a/test-article', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doDELETE method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article'));

        $response = $front->process($request);

        $this->assertEquals(404, $response->getStatus(), 'Testing the doGET method with a miss');
    }
}
