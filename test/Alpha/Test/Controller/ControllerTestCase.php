<?php

namespace Alpha\Test\Controller;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Person;
use Alpha\Model\Rights;
use Alpha\Model\ActionLog;

/**
 * Test class used by tests that need to have a logged in admin user.
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
class ControllerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Creates the user and rights group tables, adds a user into both as
     * admin, then adds that user to the session.
     *
     * @since 2.0
     */
    protected function setUp()
    {
        $_POST = array();
        $_GET = array();
        $_COOKIE = array();

        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        $action = new ActionLog();
        $action->rebuildTable();

        $person = new Person();
        $person->rebuildTable();

        $rights = new Rights();
        $rights->rebuildTable();
        $rights->set('name', 'Standard');
        $rights->save();

        $rights = new Rights();
        $rights->set('name', 'Admin');
        $rights->save();

        $person = $this->createPersonObject('loggedin');
        $person->save();

        if (!$person->inGroup('Admin')) {
            $adminGroup = new Rights();
            $adminGroup->loadByAttribute('name', 'Admin');

            $lookup = $adminGroup->getMembers()->getLookup();
            $lookup->setValue(array($person->getID(), $adminGroup->getID()));
            $lookup->save();
        }

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', $person);
    }

    /**
     * Drop the user tables and session between tests.
     *
     * @since 2.0
     */
    protected function tearDown()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', null);

        $person = new Person();
        $person->dropTable();

        $rights = new Rights();
        $rights->dropTable();
        $rights->dropTable('Person2Rights');
    }

    /**
     * Creates a person object for Testing.
     *
     * @return \Alpha\Model\Person
     *
     * @since 2.0
     */
    protected function createPersonObject($name)
    {
        $person = new Person();
        $person->setUsername($name);
        $person->set('email', $name.'@test.com');
        $person->set('password', 'passwordTest');
        $person->set('URL', 'http://unitTestUser/');

        return $person;
    }
}
