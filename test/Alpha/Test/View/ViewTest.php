<?php

namespace Alpha\Test\View;

use Alpha\View\View;
use Alpha\Model\Article;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Session\SessionProviderFactory;

/**
 *
 * Test cases for the View class.
 *
 * @since 1.0
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
class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * View class for testing
     *
     * @var View
     * @since 1.0
     */
    private $view;

    /**
     * {@inheritDoc}
     *
     * @since 1.0
     */
    protected function setUp()
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $this->view = View::getInstance(new Article());
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.0
     */
    protected function tearDown()
    {
        unset($this->view);

        $denum = new DEnum();
        $denum->dropTable();

        $item = new DEnumItem();
        $item->dropTable();
    }

    /**
     * Testing that passing a bad object to the getInstance method will throw an IllegalArguementException
     *
     * @since 1.0
     */
    public function testGetInstanceBad()
    {
        try {
            $bad = View::getInstance(new ViewTest());
            $this->fail('testing that passing a bad object to the getInstance method will throw an IllegalArguementException');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The BO provided [Alpha\Test\View\ViewTest] is not defined anywhere!', $e->getMessage(), 'testing that passing a bad object to the getInstance method will throw an IllegalArguementException');
        }
    }

    /**
     * Testing that passing a good object to the getInstance method will return the child view object
     *
     * @since 1.0
     */
    public function testGetInstanceGood()
    {
        try {
            $good = View::getInstance(new Article());
            $this->assertTrue($good instanceof \Alpha\View\ArticleView, 'testing that passing a good object to the getInstance method will return the child view object');
        } catch (IllegalArguementException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Testing that we can force the return of an View object even when a child definition for the provided BO exists
     *
     * @since 1.0
     */
    public function testGetInstanceForceParent()
    {
        try {
            $good = View::getInstance(new Article(), true);
            $this->assertTrue($good instanceof View, 'testing that we can force the return of an View object even when a child definition for the provided BO exists');
        } catch (IllegalArguementException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Testing that we can attach a good BO to an existing view object
     *
     * @since 1.0
     */
    public function testSetBOGood()
    {
        try {
            $this->view->setBO(new Article());
            $this->assertTrue(true);
        } catch (IllegalArguementException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Testing that attempting to attach a bad BO object to an existing view object will cause an exception
     *
     * @since 1.0
     */
    public function testSetBOBad()
    {
        try {
            $this->view->setBO(new ViewTest());
            $this->fail('testing that attempting to attach a bad BO object to an existing view object will cause an exception');
        } catch (IllegalArguementException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Testing that a bad mode param provided to the loadTemplate method will throw an exception
     *
     * @since 1.0
     */
    public function testLoadTemplateBad()
    {
        try {
            $this->view->loadTemplate($this->view->getBO(), 'BadMode', array());
            $this->fail('testing that a bad mode param provided to the loadTemplate method will throw an exception');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('No [BadMode] HTML template found for class [Article]', $e->getMessage(), 'testing that a bad mode param provided to the loadTemplate method will throw an exception');
        }
    }

    /**
     * Testing accessing the attached BO via getBO()
     *
     * @since 1.0
     */
    public function testGetBO()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view->setBO($article);

        $this->assertEquals('Test Article', $this->view->getBO()->get('title'), 'testing accessing the attached BO via getBO()');
    }

    /**
     * Testing that a generated HTML fragment can load from a file
     *
     * @since 1.2.3
     */
    public function testLoadTemplateFragment()
    {

        $generatedHTML = View::loadTemplateFragment('html', 'footer.phtml', array());

        $pos = strpos($generatedHTML, '<footer>');

        $this->assertTrue(strpos($generatedHTML, '<footer>') > 0, 'Testing that a generated HTML fragment can load from a file');
    }

    /**
     * Testing the get/setProvider methods
     *
     * @since 2.0
     */
    public function testSetGetProvider()
    {
        $view = View::getInstance(new Article());
        $view->setProvider('auto', 'application/json');

        $this->assertTrue($view->getProvider() instanceof \Alpha\View\Renderer\Json\RendererProviderJSON, 'Testing the get/setProvider methods');

        $view->setProvider('auto');

        $this->assertTrue($view->getProvider() instanceof \Alpha\View\Renderer\Html\RendererProviderHTML, 'Testing the get/setProvider methods');

        $view->setProvider('Alpha\View\Renderer\Html\RendererProviderHTML');

        $this->assertTrue($view->getProvider() instanceof \Alpha\View\Renderer\Html\RendererProviderHTML, 'Testing the get/setProvider methods');

        try {
            $view->setProvider('Alpha\Controller\ArticleController');
            $this->fail('Testing the get/setProvider methods');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The provider class [Alpha\Controller\ArticleController] does not implement the RendererProviderInterface interface!', $e->getMessage(), 'Testing the get/setProvider methods');
        }

        try {
            $view->setProvider('Alpha\Not\There');
            $this->fail('Testing the get/setProvider methods');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The provider class [Alpha\Not\There] does not exist!', $e->getMessage(), 'Testing the get/setProvider methods');
        }
    }

    /**
     * Testing the renderAllFields() method
     *
     * @since 2.0
     */
    public function testRenderAllFields()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $this->assertNotEmpty($this->view->renderAllFields('view'), 'Testing the renderAllFields() method');
        $this->assertTrue(strpos($this->view->renderAllFields('view'),'Test Article') !== false, 'Testing the renderAllFields() method');
    }
}

?>