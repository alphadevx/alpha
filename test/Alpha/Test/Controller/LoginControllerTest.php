<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\LoginController;
use Alpha\Controller\Controller;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Session\SessionProviderFactory;
use Alpha\Model\Person;
use Alpha\Model\Rights;

/**
 *
 * Test cases for the LoginController class
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
class LoginControllerTest extends \PHPUnit_Framework_TestCase
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

        $person = new Person();
        $person->rebuildTable();

        $rights = new Rights();
        $rights->rebuildTable();
        $rights->set('name', 'Standard');
        $rights->save();
    }

    /**
     * Creates a person object for Testing
     *
     * @return Alpha\Model\Person
     * @since 2.0
     */
    private function createPersonObject($name)
    {
        $person = new Person();
        $person->setDisplayname($name);
        $person->set('email', $name.'@test.com');
        $person->set('password', crypt('passwordTest', 'passwordTest'));
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

        $request = new Request(array('method' => 'GET', 'URI' => '/login'));
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

        $person = new Person();
        $person->dropTable();

        $front = new FrontController();
        $controller = new LoginController();

        $securityParams = $controller->generateSecurityFields();

        $params = array(
            'loginBut' => true,
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'email' => $config->get('app.install.username'),
            'password' => $config->get('app.install.password')
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/login', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPOST method during install');

        $person->makeTable();

        $person = $this->createPersonObject('logintest');
        $person->save();

        $params = array(
            'loginBut' => true,
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'email' => 'logintest@test.com',
            'password' => 'passwordTest'
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/login', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(301, $response->getStatus(), 'Testing the doPOST with correct password');

        $params['password'] = 'wrongPassword';

        $request = new Request(array('method' => 'POST', 'URI' => '/login', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST with incorrect password');

        $params = array(
            'resetBut' => true,
            'var1' => $securityParams[0],
            'var2' => $securityParams[1],
            'email' => 'logintest@test.com'
        );

        $request = new Request(array('method' => 'POST', 'URI' => '/login', 'params' => $params));

        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doPOST during password reset');

        $person->reload();

        $this->assertNotEquals($person->get('password'), crypt('passwordTest', 'passwordTest'), 'Checking that the password has changed in the database');
    }
}

?>