<?php

require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/util/filters/ClientBlacklistFilter.inc';

/**
 *
 * Test cases for the AlphaController class.
 * 
 * @package alpha::tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$ 
 * 
 */
class FrontController_Test extends PHPUnit_Framework_TestCase {
	
	private $token;
		
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
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
	 */
    protected function tearDown() {
    	$_GET['tk'] = $this->token;
    }
    
    /**
     * testing that the constructor will detect the page controller action we want to invoke from the global _GET array
     */
    public function testConstructActParam() {
    	$_GET['act'] = 'ViewArticle';
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticle', $front->getPageController(), 'testing that the constructor will detect the page controller action we want to invoke from the global _GET array');
    }

    /**
     * testing that the constructor can parse the correct page controller action from a mod_rewrite style URL
     */
    public function testConstructModRewrite() {
    	global $config;
    	
    	$request = $config->get('sysURL').'ViewArticleTitle/title/Test_Title';
    	$_SERVER['REQUEST_URI'] = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $request);
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from a mod_rewrite style URL');
    }
    
    /**
     * testing that the constructor can parse the correct page controller action from a mod_rewrite style URL when a controller alias is used
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
     * testing that the constructor can parse the correct page controller action from an encrypted token param
     */
    public function testConstructorWithEncryptedToken() {
    	global $config;
    	
    	$params = 'act=ViewArticleTitle&title=Test_Title';
    	$_GET['tk'] = FrontController::encodeQuery($params);
    	$front = new FrontController();
    	
    	$this->assertEquals('ViewArticleTitle', $front->getPageController(), 'testing that the constructor can parse the correct page controller action from an encrypted token param');
    }
    
	/**
     * testing that the constructor can parse the correct page controller action from an encrypted token param provided on a mod-rewrite style URL
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
     * testing the encodeQuery method with a known encrypted result for a test key
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
     * testing the decodeQueryParams method with a known encrypted result for a test key
     */
    public function testDecodeQueryParams() {
    	global $config;
    	
    	$oldKey = $config->get('sysQSKey');
    	$config->set('sysQSKey', 'testkey');
    	$tk = '8kqoeebEej0V-FN5-DOdA1HBDDieFcNWTib2yLSUNjq0B0FWzAupIA==';
    	
    	$this->assertEquals('act=ViewArticleTitle&title=Test_Title', FrontController::decodeQueryParams($tk), 'testing the decodeQueryParams method with a known encrypted result for a test key');
    }
    
    /**
     * testing that the getDecodeQueryParams method will return the known params with a known encrypted result for a test key
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
     * testing that a request to a bad URL will result in a ResourceNotFoundException exception
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
     * testing the setting up and checking for the existence of a controller alias
     */
    public function testDefineAlias() {
    	$front = new FrontController();
    	$front->registerAlias('ViewArticleTitle','article','title');
    	
    	$this->assertTrue($front->hasAlias('ViewArticleTitle'), 'testing the setting up and checking for the existence of a controller alias');
    	$this->assertTrue($front->checkAlias('article'), 'testing the setting up and checking for the existence of a controller alias');
    	$this->assertEquals('ViewArticleTitle', $front->getAliasController('article'), 'testing the setting up and checking for the existence of a controller alias');
    	$this->assertEquals('article', $front->getControllerAlias('ViewArticleTitle'), 'testing the setting up and checking for the existence of a controller alias');
    }
    
    /**
     * testing the accessing of the expected param for a given alias/controller
     */
    public function testAccessingAliasParamNames() {
    	$front = new FrontController();
    	$front->registerAlias('ViewArticleTitle','article','title');
    	
    	$this->assertEquals('title', $front->getAliasParam('article'), 'testing the accessing of the expected param for a given alias/controller');
    	$this->assertEquals('title', $front->getControllerParam('ViewArticleTitle'), 'testing the accessing of the expected param for a given alias/controller');
    }
    
    /**
     * testing the registerFilter method with a valid filter object
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
     * testing the registerFilter method with a bad filter object
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