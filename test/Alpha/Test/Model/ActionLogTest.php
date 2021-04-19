<?php

namespace Alpha\Test\Model;

use Alpha\Model\ActionLog;
use Alpha\Model\Person;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Http\Request;
use Alpha\Util\Config\Configprovider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Exception\AlphaException;

/**
 * Test case for the ActionLog class.
 *
 * @since 3.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2019, John Collins (founder of Alpha Framework).
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
class ActionLogTest extends ModelTestCase
{
    /**
     * Build required tables
     *
     * @since 3.1
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ConfigProvider::getInstance();

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);

            $config = ConfigProvider::getInstance();
            $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

            $person = new Person();
            $person->rebuildTable();

            $action = new ActionLog();
            $action->rebuildTable();
        }

        $this->person = $this->createPersonObject('john');
    }

    /**
     * Testing ActionLog is honouring the app.log.action.logging config setting
     *
     * @since 3.1
     *
     * @dataProvider getActiveRecordProviders
     */
    public function testLogAction($provider)
    {
        $config = ConfigProvider::getInstance();
        $config->set('app.log.action.logging', true);
        $config->set('db.provider.name', $provider);

        $request = new Request(array('method' => 'GET'));

        $sessionProvider = $config->get('session.provider.name');
        $session = ServiceFactory::getInstance($sessionProvider, 'Alpha\Util\Http\Session\SessionProviderInterface');
        $session->set('currentUser', $this->person);

        $logger = new Logger('ActionLogTest');
        $logger->action('test action 1');
        $logger->action('test action 2');

        $action = new ActionLog();

        $this->assertEquals(2, $action->getCount());

        $config->set('app.log.action.logging', false);

        $logger->action('test action 3');

        $this->assertEquals(2, $action->getCount());
    }
}
