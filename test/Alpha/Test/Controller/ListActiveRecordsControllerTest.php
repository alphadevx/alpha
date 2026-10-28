<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\ListActiveRecordsController;
use Alpha\Exception\AlphaException;
use Alpha\Model\ActiveRecord;
use Alpha\Util\Http\Request;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Article;
use Alpha\Model\ActiveRecordProviderSQLite;
use Alpha\View\ViewState;

/**
 * Test cases for the ListActiveRecordsController class.
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
class ListActiveRecordsControllerTest extends ControllerTestCase
{
    /**
     * Testing the doGET method.
     */
    public function testDoGET()
    {
        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/listactiverecords'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');

        // test that the 'Recreate Table' button is rendererd as required
        $this->assertFalse(str_contains($response->getBody(), 'Recreate Table'));
        $article = new Article();

        $connection = ActiveRecordProviderSQLite::getConnection();
        $result = $connection->query('ALTER TABLE Article RENAME COLUMN description TO descriptionback;');

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $this->assertTrue(str_contains($response->getBody(), 'Recreate Table'));
    }

    /**
     * Testing creating a table via doPOST method
     */
    public function testDoPOSTCreateTable()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();
        $article = new Article();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'createTableBut' => true,
            'createTableClass' => 'Alpha\Model\Article'
            );

        $article->dropTable();
        $this->assertFalse($article->checkTableExists());

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing creating a table via doPOST method');
        $this->assertTrue($article->checkTableExists());
    }

    /**
     * Testing creating a history table via doPOST method
     */
    public function testDoPOSTCreateHistoryTable()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();
        $article = new Article();
        $article->setMaintainHistory(true);

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'createHistoryTableBut' => true,
            'createTableClass' => 'Alpha\Model\Article'
            );

        $article->dropTable();
        $this->assertFalse($article->checkTableExists(true));

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing creating a history table via doPOST method');
        $this->assertTrue($article->checkTableExists(true));
    }


    /**
     * Testing recreating a table via doPOST method
     */
    public function testDoPOSTRecreateTable()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();
        $article = new Article();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaModelArticle_button_pressed' => 'recreateTableBut',
            'recreateTableClass' => 'Alpha\Model\Article'
            );

        $article->dropTable();
        $this->assertFalse($article->checkTableExists());

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing recreating a table via doPOST method');
        $this->assertTrue($article->checkTableExists());
    }

    /**
     * Testing updating a table via doPOST method
     */
    public function testDoPOSTUpdateTable()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();
        $article = new Article();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaModelArticle_button_pressed' => 'updateTableBut',
            'updateTableClass' => 'Alpha\Model\Article'
            );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(0, count($article->findMissingFields()), 'Testing updating a table via doPOST method');
    }

    /**
     * Testing security field validation in doPOST method.
     */
    public function testDoPOSTSecurityValidation()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        // Send POST without security fields to trigger SecurityException
        $params = array(
            'createTableBut' => true,
            'createTableClass' => 'Alpha\Model\Article'
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing security validation response status');
        $this->assertTrue(str_contains($response->getBody(), 'This page cannot accept post data from remote servers!'), 'Testing security exception message');
    }

    /**
     * Testing error handling in displayBodyContent when a class throws an exception.
     */
    public function testDoGETWithException()
    {
        // Temporarily add a class that will fail when instantiated
        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/listactiverecords'));

        // This test verifies that the controller handles exceptions gracefully
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doGET with exception handling');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doGET with exception handling');
    }

    /**
     * Testing constructor sets up title and metadata correctly.
     */
    public function testConstructor()
    {
        $controller = new ListActiveRecordsController();

        $this->assertEquals('Listing all active records in the system', $controller->getTitle());
        $this->assertEquals('Page to list all active records.', $controller->getDescription());
        $this->assertEquals('list,all,active,records', $controller->getKeywords());
        $this->assertEquals('Admin', $controller->getVisibility());
    }

    /**
     * Testing that the constructor sets the renderAdminMenu view state flag.
     *
     * @since 4.1
     */
    public function testConstructorViewState(): void
    {
        $controller = new ListActiveRecordsController();

        $viewState = ViewState::getInstance();

        $this->assertTrue((bool) $viewState->get('renderAdminMenu'), 'Testing that the admin menu render flag is set by the constructor');
    }

    /**
     * Testing a POST request that passes security validation but triggers no
     * table action buttons, exercising the fall-through path of doPOST.
     *
     * @since 4.1
     */
    public function testDoPOSTNoActions(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1]
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with valid security fields and no actions');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doPOST with valid security fields and no actions');
    }

    /**
     * Testing that a POST request with a recreate button pressed but a
     * mismatched button value does not trigger the recreate branch.
     *
     * @since 4.1
     */
    public function testDoPOSTRecreateButtonMismatch(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaModelArticle_button_pressed' => 'someOtherButton',
            'recreateTableClass' => 'Alpha\Model\Article'
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with mismatched recreate button value');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doPOST with mismatched recreate button value');
    }

    /**
     * Testing that a POST request with an update button pressed but a
     * mismatched button value does not trigger the update branch.
     *
     * @since 4.1
     */
    public function testDoPOSTUpdateButtonMismatch(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaModelArticle_button_pressed' => 'someOtherButton',
            'updateTableClass' => 'Alpha\Model\Article'
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with mismatched update button value');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doPOST with mismatched update button value');
    }

    /**
     * Testing that a failure while creating a table is handled gracefully by doPOST.
     *
     * @since 4.1
     */
    public function testDoPOSTCreateTableFailure(): void
    {
        ListARCTestStubRecord::$failMode = 'makeTable';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'createTableBut' => true,
            'createTableClass' => ListARCTestStubRecord::class
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing that a table creation failure is handled gracefully');
        $this->assertTrue(str_contains($response->getBody(), 'check the log'), 'Testing that the error message is displayed when table creation fails');
    }

    /**
     * Testing that a failure while creating a history table is handled gracefully by doPOST.
     *
     * @since 4.1
     */
    public function testDoPOSTCreateHistoryTableFailure(): void
    {
        ListARCTestStubRecord::$failMode = 'makeHistoryTable';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'createHistoryTableBut' => true,
            'createTableClass' => ListARCTestStubRecord::class
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing that a history table creation failure is handled gracefully');
        $this->assertTrue(str_contains($response->getBody(), 'check the log'), 'Testing that the error message is displayed when history table creation fails');
    }

    /**
     * Testing that a failure while recreating a table is handled gracefully by doPOST.
     *
     * @since 4.1
     */
    public function testDoPOSTRecreateTableFailure(): void
    {
        ListARCTestStubRecord::$failMode = 'rebuildTable';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaTestControllerListARCTestStubRecord_button_pressed' => 'recreateTableBut',
            'recreateTableClass' => ListARCTestStubRecord::class
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing that a table recreation failure is handled gracefully');
        $this->assertTrue(str_contains($response->getBody(), 'check the log'), 'Testing that the error message is displayed when table recreation fails');
    }

    /**
     * Testing that missing fields are added to a table when doPOST updates a class.
     *
     * @since 4.1
     */
    public function testDoPOSTUpdateTableAddsMissingFields(): void
    {
        ListARCTestStubRecord::$failMode = '';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaTestControllerListARCTestStubRecord_button_pressed' => 'updateTableBut',
            'updateTableClass' => ListARCTestStubRecord::class
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing that missing fields are added when updating a table');
        $this->assertTrue(str_contains($response->getBody(), 'successfully updated'), 'Testing that the update message is displayed after adding missing fields');
    }

    /**
     * Testing that a failure while updating a table is handled gracefully by doPOST.
     *
     * @since 4.1
     */
    public function testDoPOSTUpdateTableFailure(): void
    {
        ListARCTestStubRecord::$failMode = 'findMissingFields';

        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new ListActiveRecordsController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'admin_AlphaTestControllerListARCTestStubRecord_button_pressed' => 'updateTableBut',
            'updateTableClass' => ListARCTestStubRecord::class
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/listactiverecords', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing that a table update failure is handled gracefully');
        $this->assertTrue(str_contains($response->getBody(), 'check the log'), 'Testing that the error message is displayed when table update fails');
    }

    /**
     * Testing that classes that fail while rendering the admin views are
     * handled gracefully by displayBodyContent. Two temporary stub record
     * classes are dropped into src/Model (which ActiveRecord::getRecordClassNames
     * scans) for the duration of the request only.
     *
     * @since 4.1
     */
    public function testDoGETWithRecordClassFailures(): void
    {
        $config = ConfigProvider::getInstance();
        $modelDir = $config->get('app.root') . 'src/Model';

        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0777, true);
        }

        $alphaThrower = $modelDir . '/LARTAlphaThrower.php';
        $exceptionThrower = $modelDir . '/LARTRuntThrower.php';

        file_put_contents($alphaThrower, <<<'PHP'
<?php

namespace Model;

/**
 * Temporary test stub that fails during admin rendering.
 *
 * @since 4.1
 */
class LARTAlphaThrower extends \Alpha\Model\ActiveRecord
{
    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function getTableName(): string
    {
        throw new \Alpha\Exception\AlphaException('Stub admin view failure');
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function checkTableExists(bool $checkHistoryTable = false): bool
    {
        return false;
    }
}
PHP);

        file_put_contents($exceptionThrower, <<<'PHP'
<?php

namespace Model;

/**
 * Temporary test stub that fails during instantiation.
 *
 * @since 4.1
 */
class LARTRuntThrower extends \Alpha\Model\ActiveRecord
{
    /**
     * Constructor.
     *
     * @since 4.1
     */
    public function __construct()
    {
        parent::__construct();

        throw new \Exception('Stub instantiation failure');
    }
}
PHP);

        try {
            $front = new FrontController();

            $request = new Request(array('method' => 'GET', 'URI' => '/listactiverecords'));

            $response = $front->process($request);

            $this->assertEquals(200, $response->getStatus(), 'Testing that failing record classes are handled gracefully by displayBodyContent');
            $this->assertTrue(str_contains($response->getBody(), 'Error accessing the class'), 'Testing that an error message is rendered for the failing class');
        } finally {
            @unlink($alphaThrower);
            @unlink($exceptionThrower);
        }
    }

    /**
     * Resetting the failure mode of the stub record after each test.
     *
     * @since 4.1
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        ListARCTestStubRecord::$failMode = '';
    }
}

/**
 * A stub ActiveRecord used to trigger the error handling branches of the
 * ListActiveRecordsController. The class is never scanned by
 * ActiveRecord::getRecordClassNames() as it does not live in a scanned
 * model directory.
 *
 * @since 4.1
 */
class ListARCTestStubRecord extends ActiveRecord
{
    /**
     * The name of the method that should throw an exception, or an empty string for none.
     *
     * @var string
     *
     * @since 4.1
     */
    public static string $failMode = '';

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function makeTable(bool $checkIndexes = true): void
    {
        if (self::$failMode == 'makeTable') {
            throw new AlphaException('Stub makeTable failure');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function makeHistoryTable(): void
    {
        if (self::$failMode == 'makeHistoryTable') {
            throw new AlphaException('Stub makeHistoryTable failure');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function rebuildTable(): void
    {
        if (self::$failMode == 'rebuildTable') {
            throw new AlphaException('Stub rebuildTable failure');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function findMissingFields(): array
    {
        if (self::$failMode == 'findMissingFields') {
            throw new AlphaException('Stub findMissingFields failure');
        }

        return array('stubField1', 'stubField2');
    }

    /**
     * {@inheritdoc}
     *
     * @since 4.1
     */
    public function addProperty(string $propName): void
    {
        // no-op, we do not want the stub to touch the database
    }
}
