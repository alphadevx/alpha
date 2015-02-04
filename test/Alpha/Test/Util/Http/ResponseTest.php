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
}

?>