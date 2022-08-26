<?php

namespace Alpha\Test\Model;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Model\ActiveRecord;
use Alpha\Model\Person;
use PHPUnit\Framework\TestCase;

/**
 * Test class used by tests that need to write to the unit test database.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2022, John Collins (founder of Alpha Framework).
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
class ModelTestCase extends TestCase
{
    /**
     * A Person for testing.
     *
     * @var \Alpha\Model\Person
     *
     * @since 1.0
     */
    protected $person;

    /**
     * Switches to using the test database.
     *
     * @since 2.0
     */
    protected function setUp(): void
    {
        $config = ConfigProvider::getInstance();
        $config->set('session.provider.name', 'Alpha\Util\Http\Session\SessionProviderArray');

        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);
            ActiveRecord::createDatabase();
        }
    }

    /**
     * Drop the test database between tests.
     *
     * @since 2.0
     */
    protected function tearDown(): void
    {
        $config = ConfigProvider::getInstance();
        foreach ($this->getActiveRecordProviders() as $provider) {
            $config->set('db.provider.name', $provider[0]);
            ActiveRecord::dropDatabase();
            ActiveRecord::disconnect();
        }
    }

    /**
     * Returns an array of Active Record providers.
     *
     * @return array
     *
     * @since 2.0
     */
    public function getActiveRecordProviders()
    {
        return array(
            array('Alpha\Model\ActiveRecordProviderSQLite'),
            array('Alpha\Model\ActiveRecordProviderMySQL'),
        );
    }

    /**
    * Creates a person object for Testing.
    *
    * @return \Alpha\Model\Person
    *
    * @since 1.0
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
