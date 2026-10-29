<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\LogController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Service\ServiceFactory;

/**
 * Test cases for the LogController class.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
class LogControllerTest extends ControllerTestCase
{
    /**
     * Testing the doGET method.
     */
    public function testDoGET()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/log/'.urlencode($config->get('app.file.store.dir').'logs/alpha.log')));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');
    }

    /**
     * Test the LogController constructor.
     *
     * @since 4.1
     */
    public function testConstructor(): void
    {
        $controller = new LogController();

        $this->assertInstanceOf(LogController::class, $controller);
    }

    /**
     * Test doGET with an alpha log file.
     *
     * @since 4.1
     */
    public function testDoGETWithAlphaLogFile(): void
    {
        $path = $this->createTempLogFile('alpha');

        try {
            $response = $this->processLogRequest($path);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        } finally {
            $this->removeTempLogFile($path);
        }
    }

    /**
     * Test doGET with a search log file.
     *
     * @since 4.1
     */
    public function testDoGETWithSearchLogFile(): void
    {
        $path = $this->createTempLogFile('search');

        try {
            $response = $this->processLogRequest($path);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        } finally {
            $this->removeTempLogFile($path);
        }
    }

    /**
     * Test doGET with a feeds log file.
     *
     * @since 4.1
     */
    public function testDoGETWithFeedsLogFile(): void
    {
        $path = $this->createTempLogFile('feeds');

        try {
            $response = $this->processLogRequest($path);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        } finally {
            $this->removeTempLogFile($path);
        }
    }

    /**
     * Test doGET with a tasks log file.
     *
     * @since 4.1
     */
    public function testDoGETWithTasksLogFile(): void
    {
        $path = $this->createTempLogFile('tasks');

        try {
            $response = $this->processLogRequest($path);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        } finally {
            $this->removeTempLogFile($path);
        }
    }

    /**
     * Test doGET when the requested log file does not exist (exception branch).
     *
     * @since 4.1
     */
    public function testDoGETWithNoLogFile(): void
    {
        $response = $this->processLogRequest('/this/path/does/not/exist.log');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
    }

    /**
     * Route a request for the given log path through the FrontController so that
     * the full request context (used by the View layer) is established.
     *
     * @param string $path
     *
     * @return \Alpha\Util\Http\Response
     *
     * @since 4.1
     */
    private function processLogRequest(string $path): Response
    {
        $front = new FrontController();
        $request = new Request(array('method' => 'GET', 'URI' => '/log/'.urlencode($path)));

        return $front->process($request);
    }

    /**
     * Create a temporary log file with the given prefix in its basename.
     *
     * @param string $prefix
     *
     * @return string
     *
     * @since 4.1
     */
    private function createTempLogFile(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . '_' . uniqid() . '.log';
        file_put_contents($path, '');

        return $path;
    }

    /**
     * Remove a temporary log file if it exists.
     *
     * @param string $path
     *
     * @since 4.1
     */
    private function removeTempLogFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
