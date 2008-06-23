<?php

/**
 *
 * Test case for the Relation data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Relation_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * A Relation for testing
	 * @var Relation
	 */
	private $rel1;	
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->rel1 = new Relation();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->rel1);        
    }
    
    /**
     * Testing passing a valid BO name to setRelatedClass
     */
    public function testSetRelatedClassPass() {
    	try {
    		$this->rel1->setRelatedClass('article_object');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('Testing passing a valid BO name to setRelatedClass');
    	}
    }
    
	/**
     * Testing passing an invalid BO name to setRelatedClass
     */
    public function testSetRelatedClassFail() {
    	try {
    		$this->rel1->setRelatedClass('xyz_object');
    		$this->fail('Testing passing an invalid BO name to setRelatedClass');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('The class [xyz_object] is not defined anywhere!'
    			, $e->getMessage()
    			, 'Testing passing an invalid BO name to setRelatedClass');
    	}
    }
}

?>