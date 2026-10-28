<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\PhpinfoController;
use Alpha\Util\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the PhpinfoController class.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2026, John Collins (founder of Alpha Framework).
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
class PhpinfoControllerTest extends TestCase
{
    /**
     * Testing that the doGET method renders the phpinfo() output when the
     * displayphpinfo parameter is provided.
     *
     * Note that the controller opens an output buffer to capture the phpinfo()
     * output and does not close it, so the test cleans up any buffers the
     * controller left open.
     *
     * @since 4.1
     */
    public function testDoGETWithDisplayPhpinfoParam(): void
    {
        $levelBefore = ob_get_level();

        try {
            $controller = new PhpinfoController();

            $request = new Request(array('method' => 'GET', 'URI' => '/phpinfo', 'params' => array('displayphpinfo' => 'true')));

            $response = $controller->doGET($request);

            $this->assertEquals(200, $response->getStatus(), 'Testing that the phpinfo page renders successfully');
            $this->assertTrue(str_contains($response->getBody(), 'PHP Version'), 'Testing that the phpinfo() output is rendered in the body');
        } finally {
            while (ob_get_level() > $levelBefore) {
                ob_end_clean();
            }
        }
    }
}
