<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\TagController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\Article;
use Alpha\Model\Tag;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;

/**
 *
 * Test cases for the TagController class
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
class TagControllerTest extends \PHPUnit_Framework_TestCase
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

        $denum = new DEnum('Alpha\Model\Article::section');
        $item->set('DEnumID', $denum->getOID());
        $item->set('value', 'Test');
        $item->save();

        $this->DEnumID = $denum->getOID();

        $this->article = $this->createArticle('unitTestArticle');
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
        $article->set('description', 'A test article called unitTestArticle');
        $article->set('author', 'blah');
        $article->set('content', 'blah');
        $article->set('section', $this->DEnumID);

        return $article;
    }

    /**
     * Testing the doGET method
     */
    public function testDoGET()
    {
        $config = ConfigProvider::getInstance();

        $front = new FrontController();

        $article = $this->createArticle('testing');
        $article->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/tag/'.urlencode('Alpha\Model\Article').'/'.$article->getOID()));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
    }

    /**
     * Testing the doPOST method
     */
    public function testDoPOST()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new TagController();

        $securityParams = $controller->generateSecurityFields();

        $article = $this->createArticle('testing');
        $article->save();

        $tags = $article->getPropObject('tags')->getRelatedObjects();
        $existingTags = array();

        foreach ($tags as $tag) {
            $existingTags['content_'.$tag->getOID()] = $tag->get('content');
        }

        $params = array('saveBut' => true, 'NewTagValue' => 'somenewtag', 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $existingTags);

        $request = new Request(array('method' => 'POST', 'URI' => '/tag/'.urlencode('Alpha\Model\Article').'/'.$article->getOID(), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');

        $tags = $article->getPropObject('tags')->getRelatedObjects();

        $found = false;
        $tagOID = '';

        foreach ($tags as $tag) {
            if ($tag->get('content') == 'somenewtag') {
                $found = true;
                $tagOID = $tag->getOID();
                break;
            }
        }

        $this->assertTrue($found, 'Checking that the new tag added was actually saved');

        $params = array('deleteOID' => $tagOID, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);

        $request = new Request(array('method' => 'POST', 'URI' => '/tag/'.urlencode('Alpha\Model\Article').'/'.$article->getOID(), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');

        $tags = $article->getPropObject('tags')->getRelatedObjects();

        $notFound = true;

        foreach ($tags as $tag) {
            if ($tag->get('content') == 'somenewtag') {
                $notFound = false;
                break;
            }
        }

        $this->assertTrue($notFound, 'Checking that a deleted tag was actually removed');
    }
}

?>