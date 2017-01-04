<?php

namespace Alpha\Test\Util\Http;

use Alpha\Util\Http\PHPServerUtils;

/**
 * Test cases for implementations of the AlphaFilterInterface.
 *
 * @since 1.2.2
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
class PHPServerUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing that we can start the server and hit it with a curl request.
     *
     * @since 1.2.2
     */
    public function testStart()
    {
        $pid = PHPServerUtils::start('localhost', '8771', '.');
        sleep(1); // wait a second to give the server time to start...

        $this->assertTrue($pid > 0, 'Testing that a PID was returned after starting the server');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8771/');
        ob_start();
        $result = curl_exec($ch);
        ob_end_clean();

        $this->assertEquals(404, curl_getinfo($ch, CURLINFO_HTTP_CODE), 'Testing that the server returns a 404 not found');

        if (!empty($pid)) {
            PHPServerUtils::stop($pid);
        }
    }

    /**
     * Testing that we can stop the server and hit it with a curl request.
     *
     * @since 1.2.2
     */
    public function testStop()
    {
        $pid = PHPServerUtils::start('localhost', '8771', '.');
        sleep(1); // wait a second to give the server time to start...

        $this->assertTrue($pid > 0, 'Testing that a PID was returned after starting the server');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8771/');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        ob_start();
        $result = curl_exec($ch);
        ob_end_clean();

        $this->assertEquals(404, curl_getinfo($ch, CURLINFO_HTTP_CODE), 'Testing that the server returns a 404 not found');

        if (!empty($pid)) {
            PHPServerUtils::stop($pid);
            sleep(1); // wait a second to give the server time to stop...
        }

        $result = curl_exec($ch);

        $this->assertEquals(7, curl_errno($ch), 'Testing that second request after the server shutdown failed due being unable to connect');
    }

    /**
     * Testing that we can check the status of the server when stopped or running.
     *
     * @since 1.2.2
     */
    public function testStatus()
    {
        $pid = PHPServerUtils::start('localhost', '8771', '.');

        $this->assertTrue(PHPServerUtils::status($pid), 'Testing that the status of the server is true when it is running');

        if (!empty($pid)) {
            PHPServerUtils::stop($pid);
        }

        $this->assertFalse(PHPServerUtils::status($pid), 'Testing that the status of the server is false when it is stopped');
    }
}
