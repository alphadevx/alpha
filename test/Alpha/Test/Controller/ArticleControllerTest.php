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
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;

/**
 *
 * Test cases for the ArticleController class.
 *
 * @since 2.0
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
class ArticleControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up tests
     *
     * @since 2.0
     */
    protected function setUp()
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
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
     * Creates an article object for Testing
     *
     * @return Alpha\Model\Article
     * @since 2.0
     */
    private function createArticleObject($name)
    {
        $article = new Article();
        $article->set('title', $name);
        $article->set('description', 'unitTestArticleTagOneAA unitTestArticleTagTwo');
        $article->set('author', 'unitTestArticleTagOneBB');
        $article->set('content', 'unitTestArticleTagOneCC');
        $article->set('published', true);

        return $article;
    }

    /**
     * Creates a person object for Testing
     *
     * @return Alpha\Model\Person
     * @since 1.0
     */
    private function createPersonObject($name)
    {
        $person = new Person();
        $person->setDisplayname($name);
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');

        return $person;
    }

    /**
     * Testing the doGET method
     */
    public function testDoGET()
    {
        $article = $this->createArticleObject('test article');
        $article->save();

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/not-there'));

        $response = $front->process($request);

        $this->assertEquals(404, $response->getStatus(), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a', 'params' => array('file' => 'Markdown_Help.text')));

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
    }

    public function testDoPOST()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new ArticleController();

        $article = $this->createArticleObject('test article');
        $article->save();

        $person = $this->createPersonObject('test');
        $person->save();
        $session->set('currentUser', $person);

        $securityParams = $controller->generateSecurityFields();

        $request = new Request(array('method' => 'POST', 'URI' => '/a/test-article', 'params' => array('voteBut' => true, 'userVote' => 4, 'var1' => $securityParams[0], 'var2' => $securityParams[1])));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');

        $comment = new ArticleComment();
        $comment->set('articleOID', $article->getOID());
        $comment->set('content', 'A test comment');
        $params = array('createCommentBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $comment->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/a', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');

        $comment->set('content', 'Updated comment');
        $comment->set('version_num', 1);

        $params = array('article_comment_id' => 1, 'saveBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $comment->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/a', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');

        $article = $this->createArticleObject('another test article');

        $params = array('createBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $article->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/a', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPOST method');
    }

    public function testDoPUT()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

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

        $params = array('saveBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $article->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/a/test-article', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPUT method');

        $attachment = array(
            'name' => 'logo.png',
            'type' => 'image/png',
            'tmp_name' => $config->get('app.root').'public/images/logo-small.png'
        );

        $params = array('uploadBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $article->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/a/test-article', 'params' => $params, 'files' => array('userfile' => $attachment)));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPUT method');
        $this->assertTrue(file_exists($article->getAttachmentsLocation().'/logo.png'));

        $params = array('deletefile' => 'logo.png', 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $article->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/a/test-article', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPUT method');
        $this->assertFalse(file_exists($article->getAttachmentsLocation().'/logo.png'));
    }

    public function testDoDELETE()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new ArticleController();

        $securityParams = $controller->generateSecurityFields();

        $article = $this->createArticleObject('test article');
        $article->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/a/test-article'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doDELETE method');

        $request = new Request(array('method' => 'DELETE', 'URI' => '/a/test-article?var1='.urlencode($securityParams[0]).'&var2='.urlencode($securityParams[1])));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doDELETE method');

        $request = new Request(array('method' => 'GET', 'URI' => '/a/not-there'));

        $response = $front->process($request);

        $this->assertEquals(404, $response->getStatus(), 'Testing the doDELETE method');
    }
}

?>