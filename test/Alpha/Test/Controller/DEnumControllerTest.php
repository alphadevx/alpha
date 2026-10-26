<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\DEnumController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Type\DEnum;
use Alpha\Model\Type\DEnumItem;

/**
 * Test cases for the DEnumController class.
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
class DEnumControllerTest extends ControllerTestCase
{
    /**
     * A DEnum for testing.
     *
     * @var DEnum
     *
     * @since 2.0
     */
    private $denum;

    /**
     * Set up tests.
     *
     * @since 2.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $denum = new DEnum();
        $denum->rebuildTable();

        $item = new DEnumItem();
        $item->rebuildTable();

        $this->denum = new DEnum('Article::section');
        $item->set('DEnumID', $this->denum->getID());
        $item->set('value', 'Test');
        $item->save();
    }

    /**
     * Testing the doGET method.
     */
    public function testDoGET(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/denum'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $denum = new DEnum();
        $denum->dropTable();
        $this->assertFalse($denum->checkTableExists());

        $request = new Request(array('method' => 'GET', 'URI' => '/denum'));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method with no DEnum table to see if createDEnumTables() is called to recreate it');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
    }

    /**
     * Testing the doPOST method.
     */
    public function testDoPOST(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new DEnumController();

        $securityParams = $controller->generateSecurityFields();

        $item = new DEnumItem();
        $denumItems = $item->loadItems($this->denum->getID());
        $item = $denumItems[0];

        $params = array('saveBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1], 'value_'.$item->getID() => 'updated');
        $params = array_merge($params, $item->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/denum/'.$this->denum->getID(), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doPOST method');
    }

    /**
     * Testing doGET method with a specific DEnum ID.
     */
    public function testDoGETWithID(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/denum', 'params' => array('denumID' => $this->denum->getID())));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doGET with DEnum ID');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doGET with DEnum ID');
    }

    /**
     * Testing doGET method with non-existent DEnum ID.
     */
    public function testDoGETWithInvalidID(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/denum', 'params' => array('denumID' => 99999)));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doGET with invalid DEnum ID');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing doGET with invalid DEnum ID');
    }

    /**
     * Testing doPOST method without security fields.
     */
    public function testDoPOSTWithoutSecurityFields(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new DEnumController();

        $securityParams = $controller->generateSecurityFields();

        // POST without security fields (using wrong hash values)
        $params = array('saveBut' => true, 'var1' => 'wrong', 'var2' => 'wrong', 'denumID' => $this->denum->getID());

        $request = new Request(array('method' => 'POST', 'URI' => '/denum', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST without security fields');
        $this->assertStringContainsString('This page cannot accept post data from remote servers', $response->getBody(), 'Testing doPOST without security fields');
    }

    /**
     * Testing doPOST method with no denumID provided.
     */
    public function testDoPOSTWithNoDenumID(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new DEnumController();

        $securityParams = $controller->generateSecurityFields();

        // POST with no denumID
        $params = array('saveBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);

        $request = new Request(array('method' => 'POST', 'URI' => '/denum', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with no denumID');
        $this->assertStringContainsString('Could not load the DEnum object', $response->getBody(), 'Testing doPOST with no denumID');
    }

    /**
     * Testing doPOST method with new DEnumItem creation.
     */
    public function testDoPOSTWithNewDenumItem(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new DEnumController();

        $securityParams = $controller->generateSecurityFields();

        // Create a new DEnum with an item
        $newDenum = new DEnum('Test::NewDenum');
        $newDenum->save();

        $params = array(
            'saveBut' => true,
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'denumID' => $newDenum->getID(),
            'value_1' => 'Existing Item',
            'new_value' => 'New Item', // This should create a new DEnumItem
            'oid' => $newDenum->getID(),
            'name' => 'Test::NewDenum',
            'description' => ''
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/denum', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with new DEnumItem');
        $this->assertStringContainsString('saved successfully', $response->getBody(), 'Testing doPOST with new DEnumItem');
    }

    /**
     * Testing doPOST method with a non-existent DEnum ID.
     */
    public function testDoPOSTWithNonExistentDenumID(): void
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();
        $controller = new DEnumController();

        $securityParams = $controller->generateSecurityFields();

        // POST with a non-existent DEnum ID
        $params = array(
            'saveBut' => true,
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'denumID' => 99999,
            'oid' => 99999,
            'name' => 'Test::NonExistent',
            'description' => ''
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/denum', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing doPOST with non-existent DEnum ID');
        $this->assertStringContainsString('Failed to load the requested item', $response->getBody(), 'Testing doPOST with non-existent DEnum ID');
    }
}
