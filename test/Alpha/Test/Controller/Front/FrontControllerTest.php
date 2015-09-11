<?php

namespace Alpha\Test\Controller\Front;

use Alpha\Controller\Front\FrontController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Filter\ClientBlacklistFilter;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Request;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;

/**
 * Test cases for the FrontController class.
 *
 * @since 1.0
 *
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
 */
class FrontControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the encodeQuery method with a known encrypted result for a test key.
     *
     * @since 1.0
     */
    public function testEncodeQuery()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey12345678901234567');
        $params = 'act=ViewArticleTitle&title=Test_Title';

        $this->assertEquals('LzScUG2btO7VEDFz5pvO4gvFK017l-_WSNFl1TnO5FcGUBgKXDnILQ==', FrontController::encodeQuery($params), 'testing the encodeQuery method with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing the decodeQueryParams method with a known encrypted result for a test key.
     *
     * @since 1.0
     */
    public function testDecodeQueryParams()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey12345678901234567');
        $tk = 'LzScUG2btO7VEDFz5pvO4gvFK017l-_WSNFl1TnO5FcGUBgKXDnILQ==';

        $this->assertEquals('act=ViewArticleTitle&title=Test_Title', FrontController::decodeQueryParams($tk), 'testing the decodeQueryParams method with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key.
     *
     * @since 1.0
     */
    public function testGetDecodeQueryParams()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $config->set('security.encryption.key', 'testkey12345678901234567');
        $tk = 'LzScUG2btO7VEDFz5pvO4gvFK017l-_WSNFl1TnO5FcGUBgKXDnILQ==';

        $decoded = FrontController::getDecodeQueryParams($tk);

        $this->assertEquals('ViewArticleTitle', $decoded['act'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');
        $this->assertEquals('Test_Title', $decoded['title'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');

        $config->set('security.encryption.key', $oldKey);
    }

    /**
     * Testing the registerFilter method with a valid filter object.
     *
     * @since 1.0
     */
    public function testRegisterFilterGood()
    {
        try {
            $_SERVER['REQUEST_URI'] = '/';
            $front = new FrontController();
            $front->registerFilter(new ClientBlacklistFilter());

            $found = false;

            foreach ($front->getFilters() as $filter) {
                if ($filter instanceof ClientBlacklistFilter) {
                    $found = true;
                }
            }
            $this->assertTrue($found, 'testing the registerFilter method with a valid filter object');
        } catch (IllegalArguementException $e) {
            $this->fail('testing the registerFilter method with a valid filter object');
        }
    }

    /**
     * Testing the registerFilter method with a bad filter object.
     *
     * @since 1.0
     */
    public function testRegisterFilterBad()
    {
        try {
            $_SERVER['REQUEST_URI'] = '/';
            $front = new FrontController();
            $front->registerFilter(new FrontController());

            $this->fail('testing the registerFilter method with a bad filter object');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Supplied filter object is not a valid FilterInterface instance!', $e->getMessage(), 'testing the registerFilter method with a bad filter object');
        }
    }

    /**
     * Testing the generateSecureURL method.
     *
     * @since 1.2.1
     */
    public function testGenerateSecureURL()
    {
        $config = ConfigProvider::getInstance();

        $oldKey = $config->get('security.encryption.key');
        $oldRewriteSetting = $config->get('app.use.mod.rewrite');

        $config->set('security.encryption.key', 'testkey12345678901234567');
        $params = 'act=ViewArticleTitle&title=Test_Title';

        $config->set('app.use.mod.rewrite', true);
        $this->assertEquals($config->get('app.url').'/tk/LzScUG2btO7VEDFz5pvO4gvFK017l-_WSNFl1TnO5FcGUBgKXDnILQ==', FrontController::generateSecureURL($params), 'Testing the generateSecureURL() returns the correct URL with mod_rewrite style URLs enabled');

        $config->set('app.use.mod.rewrite', false);
        $this->assertEquals($config->get('app.url').'?tk=LzScUG2btO7VEDFz5pvO4gvFK017l-_WSNFl1TnO5FcGUBgKXDnILQ==', FrontController::generateSecureURL($params), 'Testing the generateSecureURL() returns the correct URL with mod_rewrite style URLs disabled');

        $config->set('security.encryption.key', $oldKey);
        $config->set('app.use.mod.rewrite', $oldRewriteSetting);
    }

    /**
     * Testing adding good and bad routes with callbacks.
     */
    public function testAddRoute()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->addRoute('/hello', function () {
            return new Response(200, 'hello');
        });

        $this->assertTrue(is_callable($front->getRouteCallback('/hello')), 'Testing adding good and bad routes with callbacks');
        $this->assertEquals('hello', call_user_func($front->getRouteCallback('/hello'))->getBody(), 'Testing adding good and bad routes with callbacks');

        try {
            $front->addRoute('/hello', 'not_a_callable');
            $this->fail('Testing adding good and bad routes with callbacks');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Callback provided for route [/hello] is not callable', $e->getMessage());
        }

        try {
            $front->getRouteCallback('/not_there');
            $this->fail('Testing adding good and bad routes with callbacks');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('No callback defined for URI [/not_there]', $e->getMessage());
        }
    }

    /**
     * Testing adding and matching routes with URI params.
     */
    public function testAddRouteWithParams()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->addRoute('/one/{param}', function ($request) {
            return new Response(200);
        });

        $this->assertTrue(is_callable($front->getRouteCallback('/one/paramvalue1')), 'Testing adding and matching routes with URI params');

        $front->addRoute('/two/{param1}/{param2}', function ($request) {
            return new Response(200);
        });

        $this->assertTrue(is_callable($front->getRouteCallback('/two/paramvalue1/paramvalue2')), 'Testing adding and matching routes with URI params');

        $front->addRoute('/three/{param1}/params/{param2}/{params3}', function ($request) {
            return new Response(200);
        });

        $this->assertTrue(is_callable($front->getRouteCallback('/three/paramvalue1/params/paramvalue2/paramsvalue3')), 'Testing adding and matching routes with URI params');
    }

    /**
     * Testing that URL params are automatically added to the Request object passed to the callback.
     */
    public function testAddParamsToRequest()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->addRoute('/one/{param}', function ($request) {
            return new Response(200, $request->getParam('param'));
        });

        $request = new Request(array('method' => 'GET', 'URI' => '/one/paramvalue1'));

        $response = $front->process($request);

        $this->assertEquals('paramvalue1', $response->getBody(), 'Testing that URL params are automatically added to the Request object passed to the callback');

        $front->addRoute('/two/{param1}/{param2}', function ($request) {
            return new Response(200, $request->getParam('param1').' '.$request->getParam('param2'));
        });

        $request = new Request(array('method' => 'GET', 'URI' => '/two/paramvalue1/paramvalue2'));

        $response = $front->process($request);

        $this->assertEquals('paramvalue1 paramvalue2', $response->getBody(), 'Testing that URL params are automatically added to the Request object passed to the callback');

        $front->addRoute('/three/{param1}/params/{param2}/{param3}', function ($request) {
            return new Response(200, $request->getParam('param1').' '.$request->getParam('param2').' '.$request->getParam('param3'));
        });

        $request = new Request(array('method' => 'GET', 'URI' => '/three/paramvalue1/params/paramvalue2/paramvalue3'));

        $response = $front->process($request);

        $this->assertEquals('paramvalue1 paramvalue2 paramvalue3', $response->getBody(), 'Testing that URL params are automatically added to the Request object passed to the callback');
    }

    /**
     * Testing the process method.
     */
    public function testProcess()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->addRoute('/hello', function ($request) {
            return new Response(200, 'hello '.$request->getParam('username'));
        });

        $request = new Request(array('method' => 'GET', 'URI' => '/hello', 'params' => array('username' => 'bob')));

        $response = $front->process($request);

        $this->assertEquals('hello bob', $response->getBody(), 'Testing the process method');

        try {
            $request = new Request(array('method' => 'GET', 'URI' => '/not_there'));
            $front->process($request);
            $this->fail('Testing the process method');
        } catch (ResourceNotFoundException $e) {
            $this->assertEquals('Resource not found', $e->getMessage());
        }

        try {
            $request = new Request(array('method' => 'GET', 'URI' => '/not_good'));
            $front->addRoute('/not_good', function ($request) {
                return 'Should be a Response object not a string';
            });
            $front->process($request);
            $this->fail('Testing the process method');
        } catch (AlphaException $e) {
            $this->assertEquals('Unable to process request', $e->getMessage());
        }
    }

    /**
     * Testing default param values are handled correctly.
     */
    public function testDefaultParamValues()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $front = new FrontController();
        $front->addRoute('/one/{param}', function ($request) {
            return new Response(200, $request->getParam('param'));
        })->value('param', 'blah');

        $request = new Request(array('method' => 'GET', 'URI' => '/one'));

        $response = $front->process($request);

        $this->assertEquals('blah', $response->getBody(), 'Testing default param values are handled correctly');

        $front->addRoute('/two/{param1}/{param2}', function ($request) {
            return new Response(200, $request->getParam('param1').' '.$request->getParam('param2'));
        })->value('param1', 'two')->value('param2', 'params');

        $request = new Request(array('method' => 'GET', 'URI' => '/two'));

        $response = $front->process($request);

        $this->assertEquals('two params', $response->getBody(), 'Testing default param values are handled correctly');

        $request = new Request(array('method' => 'GET', 'URI' => '/two/two'));

        $response = $front->process($request);

        $this->assertEquals('two params', $response->getBody(), 'Testing default param values are handled correctly');

        $front->addRoute('/three/{param1}/params/{param2}/{param3}', function ($request) {
            return new Response(200, $request->getParam('param1').' '.$request->getParam('param2'));
        })->value('param1', 'has')->value('param2', 'three')->value('param3', 'params');

        $request = new Request(array('method' => 'GET', 'URI' => '/three/has/params'));

        $response = $front->process($request);

        $this->assertEquals('has three', $response->getBody(), 'Testing default param values are handled correctly');
    }
}
