<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\FeedController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Model\Article;
use Alpha\Model\ArticleVote;
use Alpha\Model\ArticleComment;
use Alpha\Model\Person;
use Alpha\Model\Rights;

/**
 *
 * Test cases for the FeedController class
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
class FeedControllerTest extends \PHPUnit_Framework_TestCase
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

        // create a default article DEnum category
        $denum = new DEnum('Alpha\Model\Article::section');
        $item->set('value', 'Main');
        $item->set('DEnumID', $denum->getID());
        $item->save();

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
        $article->set('section', 1);

        return $article;
    }

    /**
     * Creates a person object for testing
     *
     * @return Alpha\Model\Person
     * @since 2.0
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
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();

        $article = $this->createArticleObject('test article');
        $article->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/feed/Article'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('application/atom+xml', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/feed/Article/RSS'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('application/rss+xml', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $request = new Request(array('method' => 'GET', 'URI' => '/feed/Article/RSS2'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('application/rss+xml', $response->getHeader('Content-Type'), 'Testing the doGET method');
    }
}

?>