<?php

require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/util/filters/ClientBlacklistFilter.inc';

/**
 *
 * Test cases for the AlphaController class.
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class FrontController_Test extends PHPUnit_Framework_TestCase {
	/**
	 * A controller token to test with
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $token;
		
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
	 * 
	 * @since 1.0
	 */
    protected function setUp() {
    	if(!isset($this->token))
    		$this->token = $_GET['tk'];
    	$_GET['tk'] = null;
    	$_GET['act'] = null;
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 * 
	 * @since 1.0
	 */
    protected function tearDown() {
    	$_GET['tk'] = $this->token;
    }
    
    /**
     * Testing that the constructor will detect the page controller action we want to invoke from the global _GET array
     * 
     * @since 1.0
     */
    public function testConstructActParam() {
    	$_GET['act'] = 'ViewArticle';
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticle', $front->getPageController(), 'testing that the constructor will detect the page controller action we want to invoke from the global _GET array');
    }

    /**
     * Testing that the constructor can parse the correct page controller action from a mod_rewrite style URL
     * 
     * @since 1.0
     */
    public function testConstructModRewrite() {
    	global $config;
    	
    	$request = $config->get('sysURL').'ViewArticleTitle/title/Test_Title';
    	$_SERVER['REQUEST_URI'] = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $request);
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from a mod_rewrite style URL');
    }
    
    /**
     * Testing that the constructor can parse the correct page controller action from a mod_rewrite style URL when a controller alias is used
     * 
     * @since 1.0
     */
    public function testConstructModRewriteWithAlias() {
    	global $config;
    	
    	$request = $config->get('sysURL').'article/Test_Title';
    	$_SERVER['REQUEST_URI'] = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $request);
    	$front = new FrontController();
    	$front->registerAlias('ViewArticleTitle','article','title');
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from a mod_rewrite style URL when a controller alias is used');
    }
    
    /**
     * Testing that the constructor can parse the correct page controller action from an encrypted token param
     * 
     * @since 1.0
     */
    public function testConstructorWithEncryptedToken() {
    	global $config;
    	
    	$params = 'act=ViewArticleTitle&title=Test_Title';
    	$_GET['tk'] = FrontController::encodeQuery($params);
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from an encrypted token param');
    }
    
	/**
     * Testing that the constructor can parse the correct page controller action from an encrypted token param provided on a mod-rewrite style URL
     * 
     * @since 1.0
     */
    public function testConstructorModRewriteWithEncryptedToken() {
    	global $config;
    	
    	$params = 'act=ViewArticleTitle&title=Test_Title';
    	$request = $config->get('sysURL').'tk/'.FrontController::encodeQuery($params);
    	$_SERVER['REQUEST_URI'] = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $request);
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from an encrypted token param provided on a mod-rewrite style URL');
    }
    
    /**
     * Testing the encodeQuery method with a known encrypted result for a test key
     * 
     * @since 1.0
     */
    public function testEncodeQuery() {
    	global $config;
    	
    	$oldKey = $config->get('sysQSKey');
    	$config->set('sysQSKey', 'testkey');
    	$params = 'act=ViewArticleTitle&title=Test_Title';
    	
    	$this->assertEquals(FrontController::encodeQuery($params), '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==', 'testing the encodeQuery method with a known encrypted result for a test key');
    	
    	$config->set('sysQSKey', $oldKey);
    }
    
    /**
     * Testing the decodeQueryParams method with a known encrypted result for a test key
     * 
     * @since 1.0
     */
    public function testDecodeQueryParams() {
    	global $config;
    	
    	$oldKey = $config->get('sysQSKey');
    	$config->set('sysQSKey', 'testkey');
    	$tk = '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==';
    	
    	$this->assertEquals('act=ViewArticleTitle&title=Test_Title', FrontController::decodeQueryParams($tk), 'testing the decodeQueryParams method with a known encrypted result for a test key');
    }
    
    /**
     * Testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key
     * 
     * @since 1.0
     */
    public function testGetDecodeQueryParams() {
    	global $config;
    	
    	$oldKey = $config->get('sysQSKey');
    	$config->set('sysQSKey', 'testkey');
    	$tk = '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==';
    	
    	$decoded = FrontController::getDecodeQueryParams($tk);
    	
    	$this->assertEquals('ViewArticleTitle', $decoded['act'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');
    	$this->assertEquals('Test_Title', $decoded['title'], 'testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key');
    }
    
    /**
     * Testing that a request to a bad URL will result in a ResourceNotFoundException exception
     * 
     * @since 1.0
     */
    public function testLoadControllerFileNotFound() {
    	global $config;
    	
    	$request = $config->get('sysURL').'doesNotExists';
    	$_SERVER['REQUEST_URI'] = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $request);
    	$front = new FrontController();
    	
    	try{
    		$front->loadController(false);
    		$this->fail('testing that a request to a bad URL will result in a ResourceNotFoundException exception');
    	}catch (ResourceNotFoundException $e) {
    		$this->assertTrue($e->getMessage() != '', 'testing that a request to a bad URL will result in a ResourceNotFoundException exception');
    	}
    }
    
    /**
     * Testing the setting up and checking for the existence of a controller alias
     * 
     * @since 1.0
     */
    public function testDefineAlias() {
    	$front = new FrontController();
    	$front->registerAlias('ViewArticleTitle','article','title');
    	
    	$this->assertTrue($front->hasAlias('ViewArticleTitle'), 'testing the setting up and checking for the existence of a controller alias');
    	$this->assertTrue($front->checkAlias('article'), 'testing the setting up and checking for the existence of a controller alias');
    	$this->assertEquals('ViewArticleTitle', $front->getAliasController('article'), 
    		'testing the setting up and checking for the existence of a controller alias');
    	$this->assertEquals('article', $front->getControllerAlias('ViewArticleTitle'), 
    		'testing the setting up and checking for the existence of a controller alias');
    }
    
    /**
     * Testing the accessing of the expected param for a given alias/controller
     * 
     * @since 1.0
     */
    public function testAccessingAliasParamNames() {
    	$front = new FrontController();
    	$front->registerAlias('ViewArticleTitle','article','title');
    	
    	$this->assertEquals('title', $front->getAliasParam('article'), 'testing the accessing of the expected param for a given alias/controller');
    	$this->assertEquals('title', $front->getControllerParam('ViewArticleTitle'), 'testing the accessing of the expected param for a given alias/controller');
    }
    
    /**
     * Testing the registerFilter method with a valid filter object
     * 
     * @since 1.0
     */
    public function testRegisterFilterGood() {
    	try {
    		$front = new FrontController();
    		$front->registerFilter(new ClientBlacklistFilter());
    		
    		$found = false;
    		
    		foreach ($front->getFilters() as $filter) {
    			if($filter instanceof ClientBlacklistFilter)
    				$found = true;
    		}
    		$this->assertTrue($found, 'testing the registerFilter method with a valid filter object');
    	}catch (IllegalArguementException $e) {
    		$this->fail('testing the registerFilter method with a valid filter object');
    	}
    }
    
	/**
     * Testing the registerFilter method with a bad filter object
     * 
     * @since 1.0
     */
    public function testRegisterFilterBad() {
    	try {
    		$front = new FrontController();
    		$front->registerFilter(new FrontController());
    		
    		$this->fail('testing the registerFilter method with a bad filter object');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('Supplied filter object is not a valid AlphaFilterInterface instance!', $e->getMessage(), 'testing the registerFilter method with a bad filter object');
    	}
    }
}

?>