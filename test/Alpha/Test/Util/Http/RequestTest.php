<?php

namespace Alpha\Test\Util\Http;

use Alpha\Util\Http\Request;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;

/**
 *
 * Test cases for the Request class
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
class RequestTest extends \PHPUnit_Framework_TestCase
{
    private $serverGlobalCopy;

    /**
     * Sets up the unit tests
     *
     * @since 2.0
     */
    protected function setup()
    {
        if (!isset($this->serverGlobalCopy))
            $this->serverGlobalCopy = $_SERVER;
        $_SERVER = array();
    }

    protected function teardown()
    {
        $_SERVER = $this->serverGlobalCopy;
    }

    /**
     * Testing that the HTTP method can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPMethod()
    {
        $request = new Request(array('method' => 'GET'));

        $this->assertEquals('GET', $request->getMethod(), 'Testing that the HTTP method can be set from overrides or super-globals during object construction');

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $request = new Request();

        $this->assertEquals('PUT', $request->getMethod(), 'Testing that the HTTP method can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing providing a bad HTTP method value will cause an exception during object construction
     */
    public function testSetHTTPMethodBad()
    {
        try {
            $request = new Request(array('method' => 'GETT'));
            $this->fail('Testing providing a bad HTTP method value will cause an exception during object construction');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('No valid HTTP method provided when creating new Request object', $e->getMessage());
        }

        try {
            $_SERVER['REQUEST_METHOD'] = 'PUTT';
            $request = new Request();
            $this->fail('Testing providing a bad HTTP method value will cause an exception during object construction');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('No valid HTTP method provided when creating new Request object', $e->getMessage());
        }
    }

    /**
     * Testing that the HTTP headers can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPHeaders()
    {
        $request = new Request(array('method' => 'GET', 'headers' => array('Accept' => 'application/json')));

        $this->assertEquals('application/json', $request->getHeader('Accept'), 'Testing that the HTTP headers can be set from overrides or super-globals during object construction');

        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $this->assertEquals('application/json', $request->getHeader('Accept'), 'Testing that the HTTP headers can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the Content-Type and Content-Length headers are accessible in the Request once available in globals
     */
    public function testGetContentHeaders()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = 500;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $this->assertEquals('application/json', $request->getHeader('Content-Type'), 'Testing that the Content-Type and Content-Length headers are accessible in the Request once available in globals');
        $this->assertEquals(500, $request->getHeader('Content-Length'), 'Testing that the Content-Type and Content-Length headers are accessible in the Request once available in globals');
    }

    /**
     * Testing that the HTTP cookies can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPCookies()
    {
        $request = new Request(array('method' => 'GET', 'cookies' => array('username' => 'bob')));

        $this->assertEquals('bob', $request->getCookie('username'), 'Testing that the HTTP cookies can be set from overrides or super-globals during object construction');

        $_COOKIE['username'] = 'bob';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $this->assertEquals('bob', $request->getCookie('username'), 'Testing that the HTTP cookies can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the HTTP params can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPParams()
    {
        $request = new Request(array('method' => 'GET', 'params' => array('username' => 'bob')));

        $this->assertEquals('bob', $request->getParam('username'), 'Testing that the HTTP params can be set from overrides or super-globals during object construction');

        $_GET['username'] = 'bob';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $this->assertEquals('bob', $request->getParam('username'), 'Testing that the HTTP params can be set from overrides or super-globals during object construction');

        $_POST['username'] = 'bob';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $this->assertEquals('bob', $request->getParam('username'), 'Testing that the HTTP params can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the HTTP body can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPBody()
    {
        $request = new Request(array('method' => 'POST', 'body' => 'test post'));

        $this->assertEquals('test post', $request->getBody(), 'Testing that the HTTP body can be set from overrides or super-globals during object construction');

        $GLOBALS['HTTP_RAW_POST_DATA'] = 'test post';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();

        $this->assertEquals('test post', $request->getBody(), 'Testing that the HTTP body can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the HTTP host can be set from overrides or super-globals during object construction
     */
    public function testSetHTTPHost()
    {
        $request = new Request(array('method' => 'GET', 'host' => 'localhost'));

        $this->assertEquals('localhost', $request->getHost(), 'Testing that the HTTP host can be set from overrides or super-globals during object construction');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $request = new Request();

        $this->assertEquals('localhost', $request->getHost(), 'Testing that the HTTP host can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the client IP can be set from overrides or super-globals during object construction
     */
    public function testSetIP()
    {
        $request = new Request(array('method' => 'GET', 'IP' => '127.0.0.1'));

        $this->assertEquals('127.0.0.1', $request->getIP(), 'Testing that the client IP can be set from overrides or super-globals during object construction');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $request = new Request();

        $this->assertEquals('127.0.0.1', $request->getIP(), 'Testing that the client IP can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the URI can be set from overrides or super-globals during object construction
     */
    public function testSetURI()
    {
        $request = new Request(array('method' => 'GET', 'URI' => '/controller/param'));

        $this->assertEquals('/controller/param', $request->getURI(), 'Testing that the URI can be set from overrides or super-globals during object construction');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/controller/param';
        $request = new Request();

        $this->assertEquals('/controller/param', $request->getURI(), 'Testing that URI can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that the URL can be set from overrides or super-globals during object construction
     */
    public function testSetURL()
    {
        $request = new Request(array('method' => 'GET', 'URI' => '/controller/param'));

        $config = ConfigProvider::getInstance();

        $this->assertEquals($config->get('app.url').'controller/param', $request->getURL(), 'Testing that the URL can be set from overrides or super-globals during object construction');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/controller/param';
        $request = new Request();

        $this->assertEquals($config->get('app.url').'controller/param', $request->getURL(), 'Testing that URL can be set from overrides or super-globals during object construction');
    }

    /**
     * Testing that we can override the HTTP method via X-HTTP-Method-Override or _METHOD
     */
    public function testHTTPMethodOverride()
    {
        $_POST['_METHOD'] = 'HEAD';
        $request = new Request();

        $this->assertEquals('HEAD', $request->getMethod(), 'Testing that we can override the HTTP method via _METHOD');

        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'HEAD';
        $request = new Request();

        $this->assertEquals('HEAD', $request->getMethod(), 'Testing that we can override the HTTP method via X-HTTP-Method-Override');
    }
}

?>