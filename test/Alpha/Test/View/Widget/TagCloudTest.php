<?php

namespace Alpha\Test\View\Widget;

use Alpha\View\Widget\TagCloud;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Tag;
use Alpha\Model\Article;

/**
 *
 * Test case for the TagCloud widget
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
class TagCloudTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up tests
     *
     * @since 2.0
     */
    protected function setUp()
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        $tag = new Tag();
        $tag->rebuildTable();

        $article = new Article();
        $article->rebuildTable();
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

        return $article;
    }
    /**
     * Testing the render() method
     *
     * @since 2.0
     */
    public function testRender()
    {
        $article = $this->createArticle('unitTestArticle');
        $article->save();

        $cloud = new TagCloud(10);
        $html = $cloud->render();
        $this->assertTrue(strpos($html,'blah') !== false, 'Testing the render() method');
    }
}

?>