<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\ListActiveRecordsController;
use Alpha\Util\Http\Request;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Article;
use Alpha\Model\ActiveRecordProviderSQLite;

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
}
