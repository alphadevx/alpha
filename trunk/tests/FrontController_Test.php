<?php

require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';

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
}

?>