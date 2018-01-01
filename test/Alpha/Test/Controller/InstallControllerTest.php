<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\InstallController;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Person;

/**
 * Test cases for the InstallController class.
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
class InstallControllerTest extends \PHPUnit_Framework_TestCase
{
    protected function setup()
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        $testInstallDir = '/tmp/alphainstalltestdir';

        $config = ConfigProvider::getInstance();
        $config->set('app.file.store.dir', $testInstallDir.'/store/');
        $config->set('db.file.path', $testInstallDir.'/unittests.db');
        $config->set('db.file.test.path', $testInstallDir.'/unittests.db');

        if (!file_exists($testInstallDir)) {
            mkdir($testInstallDir);
        }

        if (!file_exists($testInstallDir.'/store')) {
            mkdir($testInstallDir.'/store');
        }

        if (file_exists($testInstallDir.'/unittests.db')) {
            unlink($testInstallDir.'/unittests.db');
        }

        $person = new Person();
        $person->set('email', $config->get('app.install.username'));
        $person->set('password', 'testpassword');

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', $person);
    }

    protected function teardown()
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.root', '');
        $config->set('app.file.store.dir', '/tmp/');
        $config->set('db.file.path', '/tmp/unittests.db');
    }

    /**
     * Testing the doGET method.
     */
    public function testDoGET()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');

        $front = new FrontController();

        $request = new Request(array('method' => 'GET', 'URI' => '/install'));
        $response = $front->process($request);

        $this->assertEquals(200, $response->getStatus(), 'Testing the doGET method');
        $this->assertEquals('text/html', $response->getHeader('Content-Type'), 'Testing the doGET method');

        $testInstallDir = '/tmp/alphainstalltestdir/store';
        $this->assertTrue(file_exists($testInstallDir.'/logs'));
        $this->assertTrue(file_exists($testInstallDir.'/attachments'));
        $this->assertTrue(file_exists($testInstallDir.'/cache'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/html'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/images'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/pdf'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/xls'));

        $person = new Person();
        $this->assertTrue($person->checkTableExists(), 'Testing that the person database table was created');
    }

    /**
     * Testing the createApplicationDirs method.
     */
    public function testCreateApplicationDirs()
    {
        $controller = new InstallController();

        $testInstallDir = '/tmp/alphainstalltestdir';

        $config = ConfigProvider::getInstance();
        $config->set('app.root', $testInstallDir.'/');
        $config->set('app.file.store.dir', $testInstallDir.'/store/');

        if (!file_exists($testInstallDir)) {
            mkdir($testInstallDir);
        }

        if (!file_exists($testInstallDir.'/store')) {
            mkdir($testInstallDir.'/store');
        }

        $controller->createApplicationDirs();

        $this->assertTrue(file_exists($testInstallDir.'/src'));
        $this->assertTrue(file_exists($testInstallDir.'/src/Model'));
        $this->assertTrue(file_exists($testInstallDir.'/src/View'));
        $this->assertTrue(file_exists($testInstallDir.'/store/logs'));
        $this->assertTrue(file_exists($testInstallDir.'/store/attachments'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/html'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/images'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/pdf'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/xls'));
    }
}
