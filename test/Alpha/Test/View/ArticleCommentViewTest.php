<?php

namespace Alpha\Test\View;

use Alpha\View\View;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Test\Controller\ControllerTestCase;

/**
 * Test cases for the ArticleCommentView class.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class ArticleCommentViewTest extends ControllerTestCase
{
    /**
     * {@inheritdoc}
     *
     * @since 2.0
     */
    protected function setUp()
    {
        parent::setUp();

        $article = new Article();
        $article->rebuildTable();

        $articleComment = new ArticleComment();
        $articleComment->rebuildTable();
    }

    /**
     * {@inheritdoc}
     *
     * @since 2.0
     */
    protected function tearDown()
    {
        parent::tearDown();

        $article = new Article();
        $article->dropTable();

        $articleComment = new ArticleComment();
        $articleComment->dropTable();
    }

    /**
     * Testing the markdownView() method.
     *
     * @since 2.0
     */
    public function testMarkdownView()
    {
        $articleComment = new ArticleComment();
        $articleComment->set('content', 'test comment');
        $articleComment->save();
        $view = View::getInstance($articleComment);

        $this->assertNotEmpty($view->markdownView(array('formAction' => '/')), 'Testing the markdownView() method');
        $this->assertTrue(strpos($view->markdownView(array('formAction' => '/')), 'test comment') !== false, 'Testing the markdownView() method');
    }

    /**
     * Testing the createView() method.
     *
     * @since 2.0
     */
    public function testCreateView()
    {
        $articleComment = new ArticleComment();
        $view = View::getInstance($articleComment);

        $this->assertNotEmpty($view->createView(array('formAction' => '/')), 'Testing the createView() method');
        $this->assertTrue(strpos($view->createView(array('formAction' => '/')), 'Post Comment') !== false, 'Testing the createView() method');
    }

    /**
     * Testing the editView() method.
     *
     * @since 2.0
     */
    public function testEditView()
    {
        $articleComment = new ArticleComment();
        $articleComment->set('content', 'test comment');
        $articleComment->save();
        $view = View::getInstance($articleComment);

        $this->assertNotEmpty($view->editView(array('formAction' => '/')), 'Testing the editView() method');
        $this->assertTrue(strpos($view->editView(array('formAction' => '/')), 'Update Your Comment') !== false, 'Testing the editView() method');
    }
}
