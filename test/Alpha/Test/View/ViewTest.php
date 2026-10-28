<?php

namespace Alpha\Test\View;

use Alpha\View\View;
use Alpha\Model\Article;
use Alpha\Model\ArticleComment;
use Alpha\Model\BlacklistedClient;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the View class.
 *
 * @since 1.0
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
class ViewTest extends TestCase
{
    /**
     * View class for testing.
     *
     * @var View
     *
     * @since 1.0
     */
    private $view;

    /**
     * {@inheritdoc}
     *
     * @since 1.0
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $article = new Article();
        $article->rebuildTable();

        $articleComment = new ArticleComment();
        $articleComment->rebuildTable();

        $this->view = View::getInstance(new Article());
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0
     */
    protected function tearDown(): void
    {
        unset($this->view);

        $denum = new DEnum();
        $denum->dropTable();

        $item = new DEnumItem();
        $item->dropTable();
    }

    /**
     * Testing that passing a good object to the getInstance method will return the child view object.
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
     * Testing that we can force the return of an View object even when a child definition for the provided record exists.
     *
     * @since 1.0
     */
    public function testGetInstanceForceParent()
    {
        try {
            $good = View::getInstance(new Article(), true);
            $this->assertTrue($good instanceof View, 'testing that we can force the return of an View object even when a child definition for the provided record exists');
        } catch (IllegalArguementException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Testing that we can attach a good record to an existing view object.
     *
     * @since 1.0
     */
    public function testSetRecordGood()
    {
        try {
            $this->view->setRecord(new Article());
            $this->assertTrue(true);
        } catch (IllegalArguementException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Testing that a bad mode param provided to the loadTemplate method will throw an exception.
     *
     * @since 1.0
     */
    public function testLoadTemplateBad()
    {
        try {
            $this->view->loadTemplate($this->view->getRecord(), 'BadMode', array());
            $this->fail('testing that a bad mode param provided to the loadTemplate method will throw an exception');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('No [BadMode] HTML template found for class [Article]', $e->getMessage(), 'testing that a bad mode param provided to the loadTemplate method will throw an exception');
        }
    }

    /**
     * Testing accessing the attached record via getRecord().
     *
     * @since 1.0
     */
    public function testGetRecord()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view->setRecord($article);

        $this->assertEquals('Test Article', $this->view->getRecord()->get('title'), 'testing accessing the attached record via getRecord()');
    }

    /**
     * Testing that a generated HTML fragment can load from a file.
     *
     * @since 1.2.3
     */
    public function testLoadTemplateFragment()
    {
        $generatedHTML = View::loadTemplateFragment('html', 'footer.phtml', array());

        $this->assertTrue(strpos($generatedHTML, '<script') > 0, 'Testing that a generated HTML fragment can load from a file');
    }

    /**
     * Testing the get/setProvider methods.
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
     * Testing the renderAllFields() method.
     *
     * @since 2.0
     */
    public function testRenderAllFields()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $this->assertNotEmpty($this->view->renderAllFields('view'), 'Testing the renderAllFields() method');
        $this->assertTrue(strpos($this->view->renderAllFields('view'), 'Test Article') !== false, 'Testing the renderAllFields() method');

        $this->view->setProvider('auto', 'application/json');
        $this->view->setRecord($article);

        $this->assertTrue($this->view->getProvider() instanceof \Alpha\View\Renderer\Json\RendererProviderJSON, 'Testing the renderAllFields() method');

        $this->assertNotEmpty($this->view->renderAllFields('view'), 'Testing the renderAllFields() method');
        $this->assertTrue(strpos($this->view->renderAllFields('view'), 'Test Article') !== false, 'Testing the renderAllFields() method');

        $result = json_decode($this->view->renderAllFields('view'));
        $this->assertTrue(json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Testing the editView() method.
     *
     * @since 3.0
     */
    public function testEditView()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $this->assertNotEmpty($this->view->editView(), 'Testing the editView() method');
        $this->assertTrue(strpos($this->view->editView(), 'Test Article') !== false, 'Testing the editView() method');

        $badClient = new BlacklistedClient();
        $badClient->set('client', 'very bad client');
        $this->view = View::getInstance($badClient);

        $this->assertNotEmpty($this->view->editView(array('formAction' => '/')), 'Testing the editView() method');
        $this->assertTrue(strpos($this->view->editView(array('formAction' => '/')), 'very bad client') !== false, 'Testing the editView() method');
    }

    /**
     * Testing the displayErrorMessage() method.
     *
     * @since 3.1
     */
    public function testDisplayErrorMessage()
    {
        $this->view->setProvider('Alpha\View\Renderer\Json\RendererProviderJSON');
        $this->assertTrue(strpos(View::displayErrorMessage('something went wrong'), 'something went wrong') > 0);

        $this->view->setProvider('Alpha\View\Renderer\Html\RendererProviderHTML');
        $this->assertTrue(strpos(View::displayErrorMessage('something went wrong'), 'something went wrong') > 0);
    }

    /**
     * Testing the renderIntegerField() method.
     *
     * @since 3.1
     */
    public function testRenderIntegerField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderIntegerField('fieldname', 'Integer field', 'edit', '1234');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'Integer field') > 0);
        $this->assertTrue(strpos($html, '1234') > 0);
    }

    /**
     * Testing the renderDoubleField() method.
     *
     * @since 3.1
     */
    public function testRenderDoubleField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderDoubleField('fieldname', 'Double field', 'edit', '12.34');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'Double field') > 0);
        $this->assertTrue(strpos($html, '12.34') > 0);
    }

    /**
     * Testing the renderBooleanField() method.
     *
     * @since 3.1
     */
    public function testRenderBooleanField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderBooleanField('fieldname', 'Boolean field', 'edit', 'true');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'Boolean field') > 0);
    }

    /**
     * Testing the renderEnumField() method.
     *
     * @since 3.1
     */
    public function testRenderEnumField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderEnumField('fieldname', 'Enum field', 'edit', array('red', 'green', 'blue'), 'blue');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'Enum field') > 0);
        $this->assertTrue(strpos($html, 'blue') > 0);
    }

    /**
     * Testing the renderDEnumField() method.
     *
     * @since 3.1
     */
    public function testRenderDEnumField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderDEnumField('fieldname', 'DEnum field', 'edit', array('red', 'green', 'blue'), 'blue');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'DEnum field') > 0);
        $this->assertTrue(strpos($html, 'blue') > 0);
    }

    /**
     * Testing the renderDefaultField() method.
     *
     * @since 3.1
     */
    public function testRenderDefaultField()
    {
        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderDefaultField('fieldname', 'Default field', 'edit', 'value 1');
        $this->assertTrue(strpos($html, 'fieldname') > 0);
        $this->assertTrue(strpos($html, 'Default field') > 0);
        $this->assertTrue(strpos($html, 'value 1') > 0);
    }

    /**
     * Testing the renderTextField() method.
     *
     * @since 3.1
     */
    public function testRenderTextField()
    {
        $config = ConfigProvider::getInstance();
        $config->set('security.encrypt.http.fieldnames', false);

        $article = new Article();
        $article->set('title', 'Test Article');
        $this->view = View::getInstance($article);

        $html = $this->view->renderTextField('content', 'Text field', 'edit', 'value 1');
        $this->assertTrue(strpos($html, 'content') > 0);
        $this->assertTrue(strpos($html, 'Text field') > 0);
    }

    /**
     * Testing the renderRelationField() method.
     *
     * @since 3.1
     */
    public function testRenderRelationField()
    {
        $config = ConfigProvider::getInstance();
        $config->set('security.encrypt.http.fieldnames', false);

        $article = new Article();
        $article->set('title', 'Test Article');
        $article->set('description', 'Test Description');
        $article->set('author', 'Author');
        $article->save();

        $comment = new ArticleComment();
        $comment->set('content', 'Test comment');
        $comment->set('articleID', $article->getID());
        $comment->save();

        $this->view = View::getInstance($article);

        $html = $this->view->renderRelationField('comments', 'Relation field', 'edit');
        $this->assertTrue(strpos($html, 'comments') > 0);
        $this->assertTrue(strpos($html, 'Relation field') > 0);
        $this->assertTrue(strpos($html, 'Test comment') > 0);
    }

    /**
     * Testing that a custom view class defined in src/View for a Record is
     * returned by getInstance and that the before/after render hooks defined
     * on the custom view are triggered.
     *
     * @since 4.1
     */
    public function testGetInstanceCustomChildViewWithHooks(): void
    {
        $config = ConfigProvider::getInstance();
        $appRoot = $config->get('app.root');

        $modelDir = $appRoot . 'src/Model';
        $viewDir = $appRoot . 'src/View';

        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0777, true);
        }
        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0777, true);
        }

        $recordFile = $modelDir . '/ViewTestStub.php';
        $viewFile = $viewDir . '/ViewTestStubView.php';

        file_put_contents($recordFile, <<<'PHP'
<?php

namespace Model;

/**
 * Temporary test record used by the View tests.
 *
 * @since 4.1
 */
class ViewTestStub extends \Alpha\Model\ActiveRecord
{
}
PHP);

        file_put_contents($viewFile, <<<'PHP'
<?php

namespace View;

/**
 * Temporary test view used by the View tests, defines all of the optional
 * before and after render hook methods.
 *
 * @since 4.1
 */
class ViewTestStubView extends \Alpha\View\View
{
    /**
     * Counter of the hook methods that were triggered.
     *
     * @var int
     *
     * @since 4.1
     */
    public static int $hooks = 0;

    /**
     * Constructor.
     *
     * @since 4.1
     */
    public function __construct(\Alpha\Model\ActiveRecord $record, string $acceptHeader = null)
    {
        parent::__construct($record, $acceptHeader);
    }

    public function beforeCreateView(): void
    {
        self::$hooks++;
    }

    public function afterCreateView(): void
    {
        self::$hooks++;
    }

    public function beforeEditView(): void
    {
        self::$hooks++;
    }

    public function afterEditView(): void
    {
        self::$hooks++;
    }

    public function beforeListView(): void
    {
        self::$hooks++;
    }

    public function afterListView(): void
    {
        self::$hooks++;
    }

    public function beforeDetailedView(): void
    {
        self::$hooks++;
    }

    public function afterDetailedView(): void
    {
        self::$hooks++;
    }

    public function beforeAdminView(): void
    {
        self::$hooks++;
    }

    public function afterAdminView(): void
    {
        self::$hooks++;
    }
}
PHP);

        try {
            $record = new \Model\ViewTestStub();

            $view = View::getInstance($record);

            $this->assertTrue($view instanceof \View\ViewTestStubView, 'Testing that a custom child view in src/View is returned by getInstance');

            // use the JSON renderer so that no HTML templates or DB tables are required
            View::setProvider('Alpha\View\Renderer\Json\RendererProviderJSON');
            View::getProvider()->setRecord($record);

            $this->assertEquals('', $view->createView());
            $this->assertEquals('', $view->editView());
            $this->assertIsString($view->listView());
            $this->assertIsString($view->detailedView());
            $this->assertEquals('', $view->adminView());

            $this->assertEquals(10, \View\ViewTestStubView::$hooks, 'Testing that the before and after render hooks were all triggered');
        } finally {
            @unlink($recordFile);
            @unlink($viewFile);

            View::setProvider('Alpha\View\Renderer\Html\RendererProviderHTML');
        }
    }

    /**
     * Testing that a custom template in src/View is loaded by the loadTemplate method.
     *
     * @since 4.1
     */
    public function testLoadTemplateCustomTemplate(): void
    {
        $config = ConfigProvider::getInstance();
        $templateDir = $config->get('app.root') . 'src/View/Html/Templates/Article';

        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0777, true);
        }

        $templateFile = $templateDir . '/zzzcustomtest.phtml';

        file_put_contents($templateFile, 'custom template for [<?php echo $summary ?>]');

        try {
            $article = new Article();

            $html = View::loadTemplate($article, 'zzzcustomtest', array('summary' => 'abc123'));

            $this->assertTrue(str_contains($html, 'custom template for [abc123]'), 'Testing that a custom template in src/View is loaded by loadTemplate');
        } finally {
            @unlink($templateFile);
        }
    }

    /**
     * Testing that a custom fragment in src/View is loaded by the
     * loadTemplateFragment method and that a missing fragment throws an
     * exception.
     *
     * @since 4.1
     */
    public function testLoadTemplateFragmentCustomAndMissing(): void
    {
        $config = ConfigProvider::getInstance();
        $fragmentDir = $config->get('app.root') . 'src/View/Html/Fragments';

        if (!is_dir($fragmentDir)) {
            mkdir($fragmentDir, 0777, true);
        }

        $fragmentFile = $fragmentDir . '/zzzcustomfragment.phtml';

        file_put_contents($fragmentFile, 'custom fragment content');

        try {
            $html = View::loadTemplateFragment('html', 'zzzcustomfragment.phtml', array());

            $this->assertEquals('custom fragment content', $html, 'Testing that a custom fragment in src/View is loaded');
        } finally {
            @unlink($fragmentFile);
        }

        $this->expectException(IllegalArguementException::class);

        View::loadTemplateFragment('html', 'zzzdoesnotexist.phtml', array());
    }

    /**
     * Testing the lazy initialization of the logger and renderer provider in
     * the static View methods, and the before/after hooks on the controller
     * used by the page head and foot rendering.
     *
     * @since 4.1
     */
    public function testViewStaticsLazyInitAndControllerHooks(): void
    {
        $loggerProperty = new \ReflectionProperty(View::class, 'logger');
        $providerProperty = new \ReflectionProperty(View::class, 'provider');

        $savedLogger = $loggerProperty->getValue();
        $savedProvider = $providerProperty->getValue();

        $config = ConfigProvider::getInstance();
        $savedRenderer = $config->get('app.renderer.provider.name');
        $config->set('app.renderer.provider.name', 'Alpha\View\Renderer\Html\RendererProviderHTML');

        $controller = new ViewTestHookedController();

        try {
            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::displayPageHead($controller), 'Testing displayPageHead lazy initialization and controller hooks');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::displayPageFoot($controller), 'Testing displayPageFoot lazy initialization and controller hooks');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::displayUpdateMessage('a message'), 'Testing displayUpdateMessage lazy initialization');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::displayErrorMessage('a message'), 'Testing displayErrorMessage lazy initialization');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::renderErrorPage('404', 'an error'), 'Testing renderErrorPage lazy initialization');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::renderDeleteForm('/test'), 'Testing renderDeleteForm lazy initialization');

            $loggerProperty->setValue(null, null);
            $providerProperty->setValue(null, null);

            $this->assertNotEmpty(View::renderSecurityFields(), 'Testing renderSecurityFields lazy initialization');

            $providerProperty->setValue(null, null);

            $this->assertTrue(View::getProvider() instanceof \Alpha\View\Renderer\RendererProviderInterface, 'Testing that getProvider lazily loads the configured provider');

            $this->assertEquals(4, ViewTestHookedController::$hooks, 'Testing that the controller display hooks were all triggered');
        } finally {
            $loggerProperty->setValue(null, $savedLogger);
            $providerProperty->setValue(null, $savedProvider);

            $config->set('app.renderer.provider.name', $savedRenderer);
        }
    }
}

/**
 * A stub controller that defines all of the optional before and after hooks
 * checked by the View page head and foot rendering.
 *
 * @since 4.1
 */
class ViewTestHookedController extends \Alpha\Controller\Controller
{
    /**
     * Counter of the hook methods that were triggered.
     *
     * @var int
     *
     * @since 4.1
     */
    public static int $hooks = 0;

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function getTitle(): string
    {
        return 'Hooked controller';
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function getDescription(): string
    {
        return 'A controller with render hooks';
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function beforeDisplayPageHead(): void
    {
        self::$hooks++;
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function afterDisplayPageHead(): string
    {
        self::$hooks++;

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function beforeDisplayPageFoot(): string
    {
        self::$hooks++;

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function afterDisplayPageFoot(): string
    {
        self::$hooks++;

        return '';
    }
}
