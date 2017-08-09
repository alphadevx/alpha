<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\ActiveRecordController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\Person;
use Alpha\Model\Rights;

/**
 * Test cases for the ActiveRecordController class.
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
class ActiveRecordControllerTest extends ControllerTestCase
{
    /**
     * Testing the doGET method.
     */
    public function testDoGET()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();

        // get a single record
        $person = $this->createPersonObject('test');
        $person->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID()));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
        $this->assertTrue(strpos($response->getBody(), 'Viewing a Person') !== false, 'Testing the doGET method');

        $request = new Request(
            array(
                'method' => 'GET',
                'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID(),
                'headers' => array('Accept' => 'application/json'),
            )
        );

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the doGET method');
        $this->assertEquals('test', json_decode($response->getBody())->username, 'Testing the doGET method');

        // GET a list this time...
        $person = $this->createPersonObject('test2');
        $person->save();

        $request = new Request(array('method' => 'GET', 'URI' => '/records/'.urlencode('Alpha\Model\Person').'/0/2'));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
        $this->assertTrue(strpos($response->getBody(), 'Listing all Person') !== false, 'Testing the doGET method');

        $request = new Request(
            array(
                'method' => 'GET',
                'URI' => '/records/'.urlencode('Alpha\Model\Person').'/1/2',
                'headers' => array('Accept' => 'application/json'),
            )
        );

        $response = $front->process($request);
        $records = json_decode($response->getBody());

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals(2, count($records), 'Testing the doGET method');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the doGET method');
        $this->assertEquals('test', $records[0]->username, 'Testing the doGET method');
        $this->assertEquals('test2', $records[1]->username, 'Testing the doGET method');

        // get the record creation screen
        $request = new Request(array('method' => 'GET', 'URI' => '/record/'.urlencode('Alpha\Model\Person')));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
        $this->assertTrue(strpos($response->getBody(), 'Create a new Person') !== false, 'Testing the doGET method');
    }

    /**
     * Testing the doPOST method.
     */
    public function testDoPOST()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new ActiveRecordController();

        $securityParams = $controller->generateSecurityFields();

        $person = $this->createPersonObject('test');

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/record/'.urlencode('Alpha\Model\Person'), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPOST method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/record/'.urlencode('Alpha\Model\Person')) !== false, 'Testing the doGET method');

        $person = $this->createPersonObject('test2');

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(array('method' => 'POST', 'URI' => '/tk/'.FrontController::encodeQuery('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType=Alpha\Model\Person'), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPOST method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/tk/') !== false, 'Testing the doPOST method');

        $person = $this->createPersonObject('test3');

        $params = array('createBut' => true, 'var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(
            array(
                'method' => 'POST',
                'URI' => '/record/'.urlencode('Alpha\Model\Person'),
                'params' => $params,
                'headers' => array('Accept' => 'application/json'),
            )
        );

        $response = $front->process($request);

        $this->assertEquals(201, $response->getStatus(), 'Testing the doPOST method');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the doPOST method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/record/'.urlencode('Alpha\Model\Person')) !== false, 'Testing the doPOST method');
        $this->assertEquals('test3', json_decode($response->getBody())->username, 'Testing the doPOST method');
    }

    /**
     * Testing the doPOST method.
     */
    public function testDoPUT()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new ActiveRecordController();

        $securityParams = $controller->generateSecurityFields();

        $person = $this->createPersonObject('test');
        $person->save();

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID(), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPUT method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID().'/edit') !== false, 'Testing the doGET method');

        $person->reload();
        $person->set('email', 'updated1@test.com');
        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(array('method' => 'PUT', 'URI' => '/tk/'.FrontController::encodeQuery('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType=Alpha\Model\Person&ActiveRecordID='.$person->getID()), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPUT method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/tk/') !== false, 'Testing the doPUT method');

        $person->reload();
        $person->set('email', 'updated2@test.com');
        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);
        $params = array_merge($params, $person->toArray());

        $request = new Request(
            array(
                'method' => 'PUT',
                'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID(),
                'params' => $params,
                'headers' => array('Accept' => 'application/json'),
            )
        );

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPUT method');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the doPUT method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID()) !== false, 'Testing the doPUT method');
        $this->assertEquals('updated2@test.com', json_decode($response->getBody())->email, 'Testing the doPUT method');
    }

    /**
     * Testing the doDELETE method.
     */
    public function testDoDELETE()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $front = new FrontController();
        $controller = new ActiveRecordController();

        $securityParams = $controller->generateSecurityFields();

        $person = $this->createPersonObject('test');
        $person->save();

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);

        $request = new Request(array('method' => 'DELETE', 'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID(), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doDELETE method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/records/'.urlencode('Alpha\Model\Person')) !== false, 'Testing the doDELETE method');

        $person = $this->createPersonObject('test');
        $person->save();

        $params = array('var1' => $securityParams[0], 'var2' => $securityParams[1]);

        $request = new Request(array('method' => 'DELETE', 'URI' => '/tk/'.FrontController::encodeQuery('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType=Alpha\Model\Person&ActiveRecordID='.$person->getID()), 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doDELETE method');
        $this->assertTrue(strpos($response->getHeader('Location'), '/tk/') !== false, 'Testing the doDELETE method');

        $person = $this->createPersonObject('test');
        $person->save();

        $request = new Request(
            array(
                'method' => 'DELETE',
                'URI' => '/record/'.urlencode('Alpha\Model\Person').'/'.$person->getID(),
                'params' => $params,
                'headers' => array('Accept' => 'application/json'),
            )
        );

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doDELETE method');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the doDELETE method');
        $this->assertEquals('deleted', json_decode($response->getBody())->message, 'Testing the doDELETE method');
    }
}
