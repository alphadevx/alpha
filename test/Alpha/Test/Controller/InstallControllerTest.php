<?php

namespace Alpha\Test\Controller;

use Alpha\Controller\Front\FrontController;
use Alpha\Controller\InstallController;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Person;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Request;
use Alpha\Util\Service\ServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the InstallController class.
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
class InstallControllerTest extends TestCase
{
    /**
     * @since 4.1
     */
    protected function setup(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        $testInstallDir = '/tmp/alphainstalltestdir';
        $config->set('app.file.store.dir', $testInstallDir.'/store/');
        $config->set('db.file.path', $testInstallDir.'/unittests.db');
        $config->set('db.file.test.path', $testInstallDir.'/unittests.db');
        ActiveRecord::disconnect();
        if (!file_exists($testInstallDir)) {
            mkdir($testInstallDir);
        }
        if (!file_exists($testInstallDir.'/store')) {
            mkdir($testInstallDir.'/store');
        }
        ActiveRecord::dropDatabase();
        $person = new Person();
        $person->set('email', $config->get('app.install.username'));
        $person->set('password', 'testpassword');
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', $person);
    }

    /**
     * @since 4.1
     */
    protected function teardown(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.root', '');
        $config->set('app.file.store.dir', '/tmp/');
        $config->set('db.file.path', '/tmp/unittests.db');
        ActiveRecord::disconnect();
    }

    /**
     * @since 4.1
     */
    public function testDoGET(): void
    {
        $front = new FrontController();
        $request = new Request(array('method' => 'GET', 'URI' => '/install'));
        $response = $front->process($request);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        $testInstallDir = '/tmp/alphainstalltestdir/store';
        $this->assertTrue(file_exists($testInstallDir.'/logs'));
        $this->assertTrue(file_exists($testInstallDir.'/attachments'));
        $this->assertTrue(file_exists($testInstallDir.'/cache'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/files'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/images'));
        $this->assertTrue(file_exists($testInstallDir.'/cache/xls'));
        $person = new Person();
        $this->assertTrue($person->checkTableExists());
    }

    /**
     * @since 4.1
     */
    public function testCreateApplicationDirs(): void
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
        $body = $controller->createApplicationDirs();
        $this->assertIsString($body);
        $this->assertTrue(file_exists($testInstallDir.'/src'));
        $this->assertTrue(file_exists($testInstallDir.'/src/Model'));
        $this->assertTrue(file_exists($testInstallDir.'/src/View'));
        $this->assertTrue(file_exists($testInstallDir.'/store/logs'));
        $this->assertTrue(file_exists($testInstallDir.'/store/attachments'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/files'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/images'));
        $this->assertTrue(file_exists($testInstallDir.'/store/cache/xls'));
    }

    /**
     * @since 4.1
     */
    public function testDoGETNotLoggedIn(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->delete('currentUser');
        $front = new FrontController();
        $request = new Request(array('method' => 'GET', 'URI' => '/install'));
        $response = $front->process($request);
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * @since 4.1
     */
    public function testCheckRightsDatabaseInstalled(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $person = new Person();
        $person->set('email', $config->get('app.install.username'));
        $person->set('password', 'testpassword');
        $session->set('currentUser', $person);
        $front = new FrontController();
        $request = new Request(array('method' => 'GET', 'URI' => '/install'));
        $front->process($request);
        $controller = new InstallController();
        $controller->setVisibility('Admin');
        $result = $controller->checkRights();
        $this->assertFalse($result);
    }

    /**
     * @since 4.1
     */
    public function testCheckRightsPublicVisibility(): void
    {
        $controller = new InstallController();
        $controller->setVisibility('Public');
        $result = $controller->checkRights();
        $this->assertTrue($result);
    }

    /**
     * @since 4.1
     */
    public function testCheckRightsNotInstallUser(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        ActiveRecord::dropDatabase();
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $person = new Person();
        $person->set('email', 'noninstall@example.com');
        $person->set('password', 'testpassword');
        $session->set('currentUser', $person);
        $controller = new InstallController();
        $controller->setVisibility('Admin');
        $this->expectException(\TypeError::class);
        $controller->checkRights();
    }

    /**
     * @since 4.1
     */
    public function testCheckRightsInstallUserMatch(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        ActiveRecord::dropDatabase();
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $person = new Person();
        $person->set('email', $config->get('app.install.username'));
        $person->set('password', 'testpassword');
        $session->set('currentUser', $person);
        $controller = new InstallController();
        $controller->setVisibility('Admin');
        $result = $controller->checkRights();
        $this->assertTrue($result);
    }

    /**
     * @since 4.1
     */
    public function testCheckRightsNoUser(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        ActiveRecord::dropDatabase();
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->delete('currentUser');
        $controller = new InstallController();
        $controller->setVisibility('Admin');
        $this->expectException(\TypeError::class);
        $controller->checkRights();
    }

    /**
     * @since 4.1
     */
    public function testCreateApplicationDirsExisting(): void
    {
        $controller = new InstallController();
        $testInstallDir = '/tmp/alphainstalltestdir';
        $config = ConfigProvider::getInstance();
        $config->set('app.root', $testInstallDir.'/');
        $config->set('app.file.store.dir', $testInstallDir.'/store/');
        $dirs = array(
            $testInstallDir.'/src',
            $testInstallDir.'/src/Model',
            $testInstallDir.'/src/View',
            $testInstallDir.'/store/logs',
            $testInstallDir.'/store/attachments',
            $testInstallDir.'/store/cache',
            $testInstallDir.'/store/cache/files',
            $testInstallDir.'/store/cache/images',
            $testInstallDir.'/store/cache/xls',
        );
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0774, true);
            }
        }
        $body = $controller->createApplicationDirs();
        $this->assertIsString($body);
        $this->assertStringContainsString('successfully created', $body);
    }

    /**
     * @since 4.1
     */
    public function testDoGETExistingTables(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');
        $session = ServiceFactory::getInstance('Alpha\Util\Http\Session\SessionProviderArray', 'Alpha\Util\Http\Session\SessionProviderInterface');
        $person = new Person();
        $person->set('email', $config->get('app.install.username'));
        $person->set('password', 'testpassword');
        $session->set('currentUser', $person);
        $front = new FrontController();
        $request = new Request(array('method' => 'GET', 'URI' => '/install'));
        $first = $front->process($request);
        $this->assertEquals(200, $first->getStatus());
        $second = $front->process($request);
        $this->assertEquals(200, $second->getStatus());
        $this->assertStringContainsString('business object tables created successfully', $second->getBody());
    }
}
