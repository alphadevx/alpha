<?php

namespace Alpha\Test\Util\Http;

use Alpha\Util\Http\Response;
use Alpha\Exception\IllegalArguementException;

/**
 *
 * Test cases for the Response class
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
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing that the constructor istantiates the object correctly
     */
    public function testConstructGood()
    {
        $response = new Response(200, 'the body', array('Content-Type' => 'application/json'));

        $this->assertEquals('the body', $response->getBody(), 'Testing that the constructor istantiates the object correctly');
        $this->assertEquals(200, $response->getStatus(), 'Testing that the constructor istantiates the object correctly');
        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing that the constructor istantiates the object correctly');
    }

    /**
     * Testing the constructor with bad arguements
     */
    public function testConstructBad()
    {
        try {
            $response = new Response(2000);
            $this->fail('Testing the constructor with bad arguements');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The status code provided [2000] is invalid', $e->getMessage());
        }
    }

    /**
     * Testing the getting and setting of the HTTP status and message
     */
    public function testStatus()
    {
        $response = new Response(200);

        $this->assertEquals(200, $response->getStatus(), 'Testing the getting and setting of the HTTP status and message');
        $this->assertEquals('OK', $response->getStatusMessage(), 'Testing the getting and setting of the HTTP status and message');

        try {
            $response->setStatus(2000);
            $this->fail('Testing the getting and setting of the HTTP status and message');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('The status code provided [2000] is invalid', $e->getMessage());
        }

        $response->setStatus(404);

        $this->assertEquals(404, $response->getStatus(), 'Testing the getting and setting of the HTTP status and message');
        $this->assertEquals('Not Found', $response->getStatusMessage(), 'Testing the getting and setting of the HTTP status and message');
    }

    /**
     * Testing the getting and setting of the HTTP headers
     */
    public function testHeaders()
    {
        $response = new Response(200, '', array('Content-Type' => 'application/json'));

        $this->assertEquals('application/json', $response->getHeader('Content-Type'), 'Testing the getting and setting of the HTTP headers');
        $this->assertTrue(in_array('application/json', $response->getHeaders()), 'Testing the getting and setting of the HTTP headers');

        $response->setHeader('Content-Type', 'text/html');

        $this->assertTrue(in_array('text/html', $response->getHeaders()), 'Testing the getting and setting of the HTTP headers');
        $this->assertTrue(in_array('Content-Type', array_keys($response->getHeaders())), 'Testing the getting and setting of the HTTP headers');
    }

    /**
     * Testing the getting and setting of the HTTP cookies
     */
    public function testCookies()
    {
        $response = new Response(200);

        $response->setCookie('username', 'bob');

        $this->assertEquals('bob', $response->getCookie('username'), 'Testing the getting and setting of the HTTP cookies');
        $this->assertTrue(in_array('bob', $response->getCookies()), 'Testing the getting and setting of the HTTP cookies');
        $this->assertTrue(in_array('username', array_keys($response->getCookies())), 'Testing the getting and setting of the HTTP cookies');
    }

    /**
     * Testing the setting of content length
     */
    public function testGetContentLength()
    {
        $response = new Response(200, '12345');

        $this->assertEquals(5, $response->getContentLength(), 'Testing the setting of content length');

        $response->setBody('1234567890');

        $this->assertEquals(10, $response->getContentLength(), 'Testing the setting of content length');
    }

    /**
     * Testing the redirect method
     */
    public function testRedirect()
    {
        $response = new Response(301);

        try {
            $response->redirect('notreallythere');
            $this->fail('Testing the redirect method');
        } catch (IllegalArguementException $e) {
            $this->assertEquals('Unable to redirect to URL [notreallythere] as it is invalid', $e->getMessage());
        }

        $response->redirect('http://alphaframework.org/');

        $this->assertEquals('http://alphaframework.org/', $response->getHeader('Location'), 'Testing the redirect method');
        $this->assertEquals(1, count($response->getHeaders()), 'Testing the redirect method');
    }
}

?>