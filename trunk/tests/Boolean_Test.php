<?php

/**
 *
 * Test case for the Boolean data type
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
class Boolean_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Boolean for testing
	 * 
	 * @var Boolean
	 * @since 1.0
	 */
	private $boolean;
	
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {        
        $this->boolean = new Boolean();        
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->boolean);        
    }
    
    /**
     * Testing the constructor has set the Boolean to true by default
     * 
     * @since 1.0
     */
    public function testDefaultBooleanValue() {
    	$this->assertTrue($this->boolean->getBooleanValue(), "testing the constructor has set the Boolean to true by default");
    	$this->assertEquals($this->boolean->getValue(), 1, "testing the constructor has set the Boolean to true by default");
    }
    
    /**
     * Testing the constructor default can be overridden
     * 
     * @since 1.0
     */
    public function testOverrideDefaultBooleanValue() {
    	$this->boolean = new Boolean(false);
    	
    	$this->assertFalse($this->boolean->getBooleanValue(), "testing the constructor default can be overridden");
    	$this->assertEquals($this->boolean->getValue(), 0, "testing the constructor default can be overridden");
    }
    
	/**
     * Testing passing invalid data to the constructor
     * 
     * @since 1.0
     */
    public function testConstructorInvalid() {
    	try {
    		$this->boolean = new Boolean(7);
    		$this->boolean = new Boolean('abc');
    		$this->fail("testing passing invalid data to the constructor");
    	}catch (IllegalArguementException $e) {
    		$this->assertTrue(true, "testing passing invalid data to the constructor");
    	}
    }
    
    /**
     * Testing passing valid data to setValue
     * 
     * @since 1.0
     */
    public function testSetValueValid() {
    	$this->boolean->setValue(true);
    	
    	$this->assertTrue($this->boolean->getBooleanValue(), "testing passing valid data to setValue");
    	$this->assertEquals($this->boolean->getValue(), 1, "testing passing valid data to setValue");
    }
    
    /**
     * Testing passing invalid data to setValue
     * 
     * @since 1.0
     */
    public function testSetValueInvalid() {
    	try {
    		$this->boolean->setValue(3);
    		$this->fail("testing passing invalid data to setValue");
    	}catch (IllegalArguementException $e) {
    		$this->assertTrue(true, "testing passing invalid data to setValue");
    	}
    }
    
    /**
     * Testing the toString method
     * 
     * @since 1.0
     */
    public function testToString() {
    	$this->assertEquals('true', $this->boolean->__toString(), 'Testing the toString method');
    }
}

?>