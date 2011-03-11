<?php

/**
 *
 * Test case for the Relation data type
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
class Relation_Test extends PHPUnit_Framework_TestCase {
	/**
	 * A Relation for testing
	 * 
	 * @var Relation
	 * @since 1.0
	 */
	private $rel1;	
	
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {        
        $this->rel1 = new Relation();
        
        $person = new PersonObject();
        $person->set('displayName', $_SESSION['currentUser']->getDisplayName());
        $person->set('email', $_SESSION['currentUser']->get('email'));
        $person->set('password', 'password');
        $person->rebuildTable();
        $person->save();
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->rel1);
        $person = new PersonObject();
        $person->dropTable();
        
        $rights = new RightsObject();
        $rights->dropTable();
        $rights->dropTable('Person2Rights');
    }
    
    /**
     * Testing passing a valid BO name to setRelatedClass
     * 
     * @since 1.0
     */
    public function testSetRelatedClassPass() {
    	try {
    		$this->rel1->setRelatedClass('ArticleObject');
    	}catch (AlphaException $e) {
    		$this->fail('Testing passing a valid BO name to setRelatedClass');
    	}
    }
    
	/**
     * Testing passing an invalid BO name to setRelatedClass
     * 
     * @since 1.0
     */
    public function testSetRelatedClassFail() {
    	try {
    		$this->rel1->setRelatedClass('XyzObject');
    		$this->fail('Testing passing an invalid BO name to setRelatedClass');
    	}catch (AlphaException $e) {
    		$this->assertEquals('The class [XyzObject] is not defined anywhere!'
    			, $e->getMessage()
    			, 'Testing passing an invalid BO name to setRelatedClass');
    	}
    }
    
	/**
     * Testing passing a valid field name to setRelatedClassField
     * 
     * @since 1.0
     */
    public function testSetRelatedClassFieldPass() {
    	try {
    		$this->rel1->setRelatedClass('PersonObject');
    		$this->rel1->setRelatedClassField('email');
    	}catch (AlphaException $e) {
    		$this->fail('Testing passing a valid field name to setRelatedClassField');
    	}
    }
    
	/**
     * Testing passing an invalid field name to setRelatedClassField
     * 
     * @since 1.0
     */
    public function testSetRelatedClassFieldFail() {
    	try {
    		$this->rel1->setRelatedClass('PersonObject');
    		$this->rel1->setRelatedClassField('doesNotExist');
    		$this->fail('Testing passing an invalid field name to setRelatedClassField');
    	}catch (AlphaException $e) {
    		$this->assertEquals('The field [doesNotExist] was not found in the class [PersonObject]'
    			, $e->getMessage()
    			, 'Testing passing an invalid field name to setRelatedClassField');
    	}
    }
    
    /**
     * Testing passing a valid type name to setRelationType
     * 
     * @since 1.0
     */
    public function testSetRelationTypePass() {
    	try {
    		$this->rel1->setRelationType('MANY-TO-ONE');
    	}catch (AlphaException $e) {
    		$this->fail('Testing passing a valid type name to setRelationType');
    	}
    }
    
	/**
     * Testing passing an invalid type name to setRelationType
     * 
     * @since 1.0
     */
    public function testSetRelationTypeFail() {
    	try {
    		$this->rel1->setRelationType('blah');    		
    		$this->fail('Testing passing an invalid type name to setRelationType');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Relation type of [blah] is invalid!'
    			, $e->getMessage()
    			, 'Testing passing an invalid type name to setRelationType');
    	}
    }
    
	/**
     * Testing setValue method with a valid value
     * 
     * @since 1.0
     */
    public function testSetValuePass() {
    	try {
    		$this->rel1->setValue(100);
    		$this->rel1->setValue('2777');
    	}catch (AlphaException $e) {
    		$this->fail('Testing setValue method with a valid value');
    	}
    }
    
	/**
     * Testing setValue method with an invalid value
     * 
     * @since 1.0
     */
    public function testSetValueFail() {
    	try {
    		$this->rel1->setValue('xyz');
    		$this->fail('Testing setValue method with an invalid value');
    	}catch (AlphaException $e) {
    		$this->assertEquals('[xyz] not a valid Relation value!  A maximum of 11 characters is allowed.'
    			, $e->getMessage()
    			, 'Testing setValue method with an invalid value');
    	}
    }
    
    /**
     * Testing that the display field value of the related class is accessed correctly
     * 
     * @since 1.0
     */
    public function testSetRelatedClassDisplayFieldPass() {
    	try {
    		$this->rel1->setRelatedClass('PersonObject');
    		// assuming here that user #1 is the default Administrator account
    		$this->rel1->setValue(1);
    		$this->rel1->setRelatedClassDisplayField('state');
    		$this->assertEquals('Active', $this->rel1->getRelatedClassDisplayFieldValue(), 'Testing that the display field value of the related class is accessed correctly');    		
    	}catch (AlphaException $e) {
    		$this->fail('Testing that the display field value of the related class is accessed correctly');
    	}
    }
    
	/**
     * Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition
     * 
     * @since 1.0
     */
    public function testGetRelatedClassDisplayFieldValueFail() {
    	try {    		
    		$this->rel1->setRelatedClassDisplayField('someField');
    		$value = $this->rel1->getRelatedClassDisplayFieldValue();
    		$this->fail('Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
    	}catch (AlphaException $e) {
    		$this->assertEquals('The class [] is not defined anywhere!'
    			, $e->getMessage()
    			, 'Testing that getRelatedClassDisplayFieldValue() will fail to load an invalid class definition');
    	}
    }
}

?>