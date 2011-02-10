<?php

require_once $config->get('sysRoot').'alpha/util/filters/ClientBlacklistFilter.inc';
require_once $config->get('sysRoot').'alpha/util/filters/ClientTempBlacklistFilter.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';

/**
 * Test cases for implementations of the AlphaFilterInterface
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class AlphaFilters_Test extends PHPUnit_Framework_TestCase {
	/**
	 * Blacklisted client string
	 * 
	 * @var blacklisted_client_object
	 * @since 1.0
	 */
	private $blacklistedClient;
	
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
	 * A test badrequest_object
	 * 
	 * @var badrequest_object
	 * @since 1.0
	 */
	private $badRequest1;
	
	/**
	 * A test badrequest_object
	 * 
	 * @var badrequest_object
	 * @since 1.0
	 */
	private $badRequest2;
	
	/**
	 * A test badrequest_object
	 * 
	 * @var badrequest_object
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
    protected function setUp() {
    	$this->blacklistedClient = new blacklisted_client_object();
    	$this->blacklistedClient->set('client', $this->badAgent);
    	$this->blacklistedClient->save();
    	
    	$this->badRequest1 = new badrequest_object();
    	$this->badRequest1->set('client', $this->badAgent);
		$this->badRequest1->set('IP', $this->badIP);
		$this->badRequest1->set('requestedResource', '/doesNotExist');
		$this->badRequest1->save();
		
		$this->badRequest2 = new badrequest_object();
    	$this->badRequest2->set('client', $this->badAgent);
		$this->badRequest2->set('IP', $this->badIP);
		$this->badRequest2->set('requestedResource', '/doesNotExist');
		$this->badRequest2->save();
		
		$this->badRequest3 = new badrequest_object();
    	$this->badRequest3->set('client', $this->badAgent);
		$this->badRequest3->set('IP', $this->badIP);
		$this->badRequest3->set('requestedResource', '/doesNotExist');
		$this->badRequest3->save();
    	
    	$this->oldAgent = $_SERVER['HTTP_USER_AGENT'];
    	$this->oldIP = $_SERVER['REMOTE_ADDR'];
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {
    	$this->blacklistedClient->delete();
    	unset($this->blacklistedClient);
    	
    	$this->badRequest1->delete();
    	unset($this->badRequest1);
    	
    	$this->badRequest2->delete();
    	unset($this->badRequest2);
    	
    	$this->badRequest3->delete();
    	unset($this->badRequest3);
    	
    	$_SERVER['HTTP_USER_AGENT'] = $this->oldAgent;
    	$_SERVER['REMOTE_ADDR'] = $this->oldIP;
    }
    
    /**
     * Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter
     * 
     * @since 1.0
     */
    public function testClientBlacklistFilter() {
    	$_SERVER['HTTP_USER_AGENT'] = $this->badAgent;
    	$_GET['act'] = 'Search';
    	
    	try {
    		$front = new FrontController();
    		$front->registerFilter(new ClientBlacklistFilter());
    		$front->loadController();
    		$this->fail('Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter');
    	}catch (ResourceNotAllowedException $e) {
    		$this->assertEquals('Not allowed!', $e->getMessage(), 'Testing that a blacklisted user agent string cannot pass the ClientBlacklistFilter filter');
    	}
    }
    
	/**
     * Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit
     * 
     * @since 1.0
     */
    public function testClientTempBlacklistFilter() {
    	global $config;
    	$config->set('sysTempBlacklistFilerLimit', 3);
    	
    	$_SERVER['HTTP_USER_AGENT'] = $this->badAgent;
    	$_SERVER['REMOTE_ADDR'] = $this->badIP;
    	$_GET['act'] = 'doesNotExist';
    	
    	try {
    		$front = new FrontController();
    		$front->registerFilter(new ClientTempBlacklistFilter());
    		$front->loadController();
    		$this->fail('Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit');
    	}catch (ResourceNotAllowedException $e) {
    		$this->assertEquals('Not allowed!', $e->getMessage(), 'Testing that a user agent string/IP compbo cannot pass the ClientTempBlacklistFilter filter beyond the config limit');
    	}
    }
}

?>