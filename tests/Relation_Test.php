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
    
	/**
     * Testing passing a valid field name to setRelatedClassField
     */
    public function testSetRelatedClassFieldPass() {
    	try {
    		$this->rel1->setRelatedClass('person_object');
    		$this->rel1->setRelatedClassField('email');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('Testing passing a valid field name to setRelatedClassField');
    	}
    }
    
	/**
     * Testing passing an invalid field name to setRelatedClassField
     */
    public function testSetRelatedClassFieldFail() {
    	try {
    		$this->rel1->setRelatedClass('person_object');
    		$this->rel1->setRelatedClassField('doesNotExist');
    		$this->fail('Testing passing an invalid field name to setRelatedClassField');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('The field [doesNotExist] was not found in the class [person_object]'
    			, $e->getMessage()
    			, 'Testing passing an invalid field name to setRelatedClassField');
    	}
    }
    
    /**
     * Testing passing a valid type name to setRelationType
     */
    public function testSetRelationTypePass() {
    	try {
    		$this->rel1->setRelationType('MANY-TO-ONE');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('Testing passing a valid type name to setRelationType');
    	}
    }
    
	/**
     * Testing passing an invalid type name to setRelationType
     */
    public function testSetRelationTypeFail() {
    	try {
    		$this->rel1->setRelationType('blah');    		
    		$this->fail('Testing passing an invalid type name to setRelationType');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Relation type of [blah] is invalid!'
    			, $e->getMessage()
    			, 'Testing passing an invalid type name to setRelationType');
    	}
    }
    
	/**
     * Testing setValue method with a valid value
     */
    public function testSetValuePass() {
    	try {
    		$this->rel1->setValue(100);
    		$this->rel1->setValue('2777');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('Testing setValue method with a valid value');
    	}
    }
    
	/**
     * Testing setValue method with an invalid value
     */
    public function testSetValueFail() {
    	try {
    		$this->rel1->setValue('xyz');
    		$this->fail('Testing setValue method with an invalid value');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: not a valid Relation value!  A maximum of '.$this->rel1->getSize().' characters is allowed.'
    			, $e->getMessage()
    			, 'Testing setValue method with an invalid value');
    	}
    }
    
    /**
     * Testing that the display field value of the related class is accessed correctly
     */
    public function testSetRelatedClassDisplayFieldPass() {
    	try {
    		$this->rel1->setRelatedClass('person_object');
    		// assuming here that user #1 is the default Administrator account
    		$this->rel1->setValue(1);
    		$this->rel1->setRelatedClassDisplayField('access_level');
    		$this->assertEquals('Administrator', $this->rel1->getRelatedClassDisplayFieldValue(), 'Testing that the display field value of the related class is accessed correctly');    		
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('Testing that the display field value of the related class is accessed correctly');
    	}
    }
    
	/**
     * Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition
     */
    public function testGetRelatedClassDisplayFieldValueFail() {
    	try {    		
    		$this->rel1->setRelatedClassDisplayField('someField');
    		$value = $this->rel1->getRelatedClassDisplayFieldValue();
    		$this->fail('Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Could not load the definition for the BO class []'
    			, $e->getMessage()
    			, 'Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
    	}
    }
}

?>