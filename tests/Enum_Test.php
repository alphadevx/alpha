<?php

/**
 *
 * Test case for the Enum data type
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
class Enum_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Enum for testing
	 * 
	 * @var Enum
	 * @since 1.0
	 */
	private $enum1;
	
	/**
	 * A person for testing
	 * 
	 * @var person_object
	 * @since 1.0
	 */
	private $person;
	
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {        
        $this->enum1 = new Enum();
        $this->person = new person_object();
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->enum1);
        unset($this->person);
    }    
    
    /**
     * Testing that enum options are loaded correctly from the database
     * 
     * @since 1.0
     */
    public function testLoadEnumOptions() {
    	$this->person->loadByAttribute('displayName', $_SESSION['currentUser']->getDisplayName(), true);
    	
    	$this->assertEquals('Active', $this->person->getPropObject('state')->getValue(), "testing that enum options are loaded correctly from the database");
    }
    
    /**
     * Testing the set/get enum option methods
     * 
     * @since 1.0
     */
    public function testSetEnumOptions() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	$this->assertEquals($this->enum1->getOptions(), array('a','b','c'), "testing the set/get enum option methods");
    }
    
	/**
     * Testing the setValue method with good and bad values
     * 
     * @since 1.0
     */
    public function testSetValue() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	try {    	
    		$this->enum1->setValue('b');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the setValue method with a good value');
    	}
    	
    	try {    	
    		$this->enum1->setValue('z');
    		$this->fail('testing the setValue method with a good value');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid enum option!'
    			, $e->getMessage()
    			, 'testing the setValue method with a bad value');
    	}
    }
    
	/**
     * Testing the getValue method
     * 
     * @since 1.0
     */
    public function testGetValue() {
    	$this->enum1->setOptions(array('a','b','c'));
    	
    	try {    	
    		$this->enum1->setValue('b');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing the getValue method');
    	}
    	
    	$this->assertEquals('b', $this->enum1->getValue(), 'testing the getValue method');
    }
    
    /**
     * Test the constructor failing when a bad array is provided
     * 
     * @since 1.0
     */
    public function testConstructorFail() {
    	try {    	
    		$enum = new Enum('blah');
    		$this->fail('test the constructor failing when a bad array is provided');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid enum option array!'
    			, $e->getMessage()
    			, 'test the constructor failing when a bad array is provided');
    	}
    }
    
    /**
     * Testing the default (non-alphabetical) sort order on the enum
     * 
     * @since 1.0
     */
    public function testDefaultSortOrder() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->getOptions();
    	 
    	$this->assertEquals($options[1], 'gamma', 'testing the default (non-alphabetical) sort order on the enum');
    }
    
	/**
     * Testing the alphabetical sort order on the enum
     * 
     * @since 1.0
     */
    public function testAlphaSortOrder() {
    	$this->enum1 = new Enum(array("alpha","gamma","beta"));
    	
    	$options = $this->enum1->getOptions(true);
    	 
    	$this->assertEquals($options[1], 'beta', 'testing the alphabetical sort order on the enum');
    }
}

?>