<?php

namespace Alpha\Test\Util\Http\Filter;

use Alpha\Model\BlacklistedClient;
use Alpha\Model\BlacklistedIP;
use Alpha\Model\BadRequest;
use Alpha\Controller\Front\FrontController;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Filter\ClientTempBlacklistFilter;
use Alpha\Util\Http\Filter\ClientBlacklistFilter;
use Alpha\Util\Http\Filter\IPBlacklistFilter;

/**
 * Test cases for implementations of the FilterInterface
 *
 * @since 1.0
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
class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Blacklisted client string
     *
     * @var Alpha\Model\BlacklistedClient
     * @since 1.0
     */
    private $blacklistedClient;

    /**
     * Blacklisted IP
     *
     * @var Alpha\Model\BlacklistedIP
     * @since 1.2.3
     */
    private $blacklistedIP;

    /**
     * A "bad" (banned) user agent string for us to test with
     *
     * @var string
     * @since 1.0
     */
    private $badAgent = 'curl/7.16.2 (i686-redhat-linux-gnu) libcurl/7.16.2 OpenSSL/0.9.8b zlib/1.2.3 libidn/0.6.8';

    /**
     * Used to keep track of the real user-agent of the user running the tests
     *
     * @var string
     * @since 1.0
     */
    private $oldAgent;

    /**
     * Used to keep track of the real IP of the user running the tests
     *
     * @var string
     * @since 1.0
     */
    private $oldIP;

    /**
     * A test BadRequest
     *
     * @var Alpha\Model\BadRequest
     * @since 1.0
     */
    private $badRequest1;

    /**
     * A test BadRequestObject
     *
     * @var Alpha\Model\BadRequest
     * @since 1.0
     */
    private $badRequest2;

    /**
     * A test BadRequestObject
     *
     * @var Alpha\Model\BadRequest
     * @since 1.0
     */
    private $badRequest3;

    /**
     * A bad IP address
     *
     * @var string
     * @since 1.0
     */
    private $badIP = '127.0.0.1';

    /**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.0
     */
    protected function setUp()
    {
        $this->blacklistedClient = new BlacklistedClient();
        $this->blacklistedClient->rebuildTable();
        $this->blacklistedClient->set('client', $this->badAgent);
        $this->blacklistedClient->save();

        $this->blacklistedIP = new BlacklistedIP();
        $this->blacklistedIP->rebuildTable();
        $this->blacklistedIP->set('IP', $this->badIP);
        $this->blacklistedIP->save();

        $this->badRequest1 = new BadRequest();
        $this->badRequest1->rebuildTable();
        $this->badRequest1->set('client', $this->badAgent);
        $this->badRequest1->set('IP', $this->badIP);
        $this->badRequest1->set('requestedResource', '/doesNotExist');
        $this->badRequest1->save();

        $this->badRequest2 = new BadRequest();
        $this->badRequest2->set('client', $this->badAgent);
        $this->badRequest2->set('IP', $this->badIP);
        $this->badRequest2->set('requestedResource', '/doesNotExist');
        $this->badRequest2->save();

        $this->badRequest3 = new BadRequest();
        $this->badRequest3->set('client', $this->badAgent);
        $this->badRequest3->set('IP', $this->badIP);
        $this->badRequest3->set('requestedResource', '/doesNotExist');
        $this->badRequest3->save();
    }

    /**
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     *
     * @since 1.0
     */
    protected function tearDown()
    {
        $this->blacklistedClient->dropTable();
        unset($this->blacklistedClient);

        $this->badRequest1->dropTable();
        unset($this->badRequest1);

        unset($this->badRequest2);

        unset($this->badRequest3);
    }

    /**
     * Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter
     *
     * @since 1.0
     */
    public function testClientBlacklistFilter()
    {
        $_SERVER['HTTP_USER_AGENT'] = $this->badAgent;
        $_SERVER['REQUEST_URI'] = 'ViewImage';

        try {
            $front = new FrontController();
            $front->registerFilter(new ClientBlacklistFilter());
            $front->loadController(false);
            $this->fail('Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter');
        } catch (ResourceNotAllowedException $e) {
            $this->assertEquals('Not allowed!', $e->getMessage(), 'Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter');
        }
    }

    /**
     * Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit
     *
     * @since 1.0
     */
    public function testClientTempBlacklistFilter()
    {
        $config = ConfigProvider::getInstance();
        $config->set('security.client.temp.blacklist.filter.limit', 3);

        $_SERVER['HTTP_USER_AGENT'] = $this->badAgent;
        $_SERVER['REMOTE_ADDR'] = $this->badIP;
        $_SERVER['REQUEST_URI'] = 'DoesNotExist';

        try {
            $front = new FrontController();
            $front->registerFilter(new ClientTempBlacklistFilter());
            $front->loadController(false);
            $this->fail('Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit');
        } catch (ResourceNotAllowedException $e) {
            $this->assertEquals('Not allowed!', $e->getMessage(), 'Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit');
        }
    }

    /**
     * Testing that a blacklisted IP cannot pass the IPBlacklistFilter filter
     *
     * @since 1.2.3
     */
    public function testIPBlacklistFilter()
    {
        $_SERVER['REMOTE_ADDR'] = $this->badIP;
        $_SERVER['REQUEST_URI'] = 'ViewImage';

        try {
            $front = new FrontController();
            $front->registerFilter(new IPBlacklistFilter());
            $front->loadController(false);
            $this->fail('Testing that a blacklisted IP cannot pass the IPBlacklistFilter filter');
        } catch (ResourceNotAllowedException $e) {
            $this->assertEquals('Not allowed!', $e->getMessage(), 'Testing that a blacklisted IP cannot pass the IPBlacklistFilter filter');
        }
    }
}

?>