<?php

require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';

/**
 *
 * Test cases for the AlphaView class.
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$ 
 * 
 */
class AlphaView_Test extends PHPUnit_Framework_TestCase {
	/**
	 * View class for testing
	 * 
	 * @var AlphaView
	 */
	private $view;	
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::setUp()
	 */
    protected function setUp() {
    	$this->view = AlphaView::getInstance(new article_object());
    }
    
	/**
	 * (non-PHPdoc)
	 * @see alpha/lib/PEAR/PHPUnit-3.2.9/PHPUnit/Framework/PHPUnit_Framework_TestCase::tearDown()
	 */
    protected function tearDown() {
    	unset($this->view);
    }
    
    /**
     * testing that passing a bad object to the getInstance method will throw an IllegalArguementException
     */
    public function testGetInstanceBad() {
    	try {
    		$bad = AlphaView::getInstance(new AlphaView_Test());
    		$this->fail('testing that passing a bad object to the getInstance method will throw an IllegalArguementException');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals('The BO provided [AlphaView_Test] is not defined anywhere!', $e->getMessage(), 'testing that passing a bad object to the getInstance method will throw an IllegalArguementException');
    	}
    }
    
    /**
     * testing that passing a good object to the getInstance method will return the child view object
     */
    public function testGetInstanceGood() {
    	try{
    		$good = AlphaView::getInstance(new article_object());
    		$this->assertTrue($good instanceof ArticleView, 'testing that passing a good object to the getInstance method will return the child view object');
    	}catch (IllegalArguementException $e) {
    		$this->fail($e->getMessage());
    	}
    }
    
	/**
     * testing that we can force the return of an AlphaView object even when a child definition for the provided BO exists
     */
    public function testGetInstanceForceParent() {
    	try{
    		$good = AlphaView::getInstance(new article_object(), true);
    		$this->assertTrue($good instanceof AlphaView, 'testing that we can force the return of an AlphaView object even when a child definition for the provided BO exists');
    	}catch (IllegalArguementException $e) {
    		$this->fail($e->getMessage());
    	}
    }

}

?>