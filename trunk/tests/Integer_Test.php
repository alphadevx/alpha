<?php

/**
 *
 * Test case for the Integer data type
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
class Integer_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Integer for testing
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	private $int1;
	
	/**
	 * An Integer for testing
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	private $int2;
		
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {        
        $this->int1 = new Integer();
        $this->int2 = new Integer();
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->int1);
        unset($this->int2);
    }
    
    /**
     * Testing the int constructor for acceptance of correct data
     * 
     * @since 1.0
     */
    public function testConstructorPass() {
    	$this->int1 = new Integer(25);
    	
    	$this->assertEquals(25, $this->int1->getValue(), "testing the Integer constructor for pass");
    }
    
    /**
     * Testing passing invalid data to setValue
     * 
     * @since 1.0
     */
    public function testSetValueInvalid() {
    	try {
    		$this->int1->setValue("blah");
    		$this->fail('testing passing invalid data to setValue');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid integer value!  A maximum of 11 characters is allowed'
    			, $e->getMessage()
    			, 'testing passing invalid data to setValue');
    	}
    }
    
	/**
     * Testing passing valid data to setValue
     * 
     * @since 1.0
     */
    public function testSetValueValid() {
    	$this->int1->setValue(7);
    	
    	$this->assertEquals(7, $this->int1->getValue(), 'testing passing valid data to setValue');
    }
    
    /**
     * Testing the setSize method to see if validation fails
     * 
     * @since 1.0
     */
    public function testSetSizeInvalid() {
    	$this->int1 = new Integer();
    	$this->int1->setSize(2);
    	
    	try {
    		$this->int1->setValue(200);
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid integer value!  A maximum of 2 characters is allowed'
    			, $e->getMessage()
    			, 'testing the setSize method to see if validation fails');
    	}
    }
    
	/**
     * Testing addition of two Integer values
     * 
     * @since 1.0
     */
    public function testAddIntegers() {
    	$this->int1 = new Integer(1500);
    	$this->int2 = new Integer(3577);
    	
    	$this->assertEquals(5077, ($this->int1->getValue()+$this->int2->getValue()), 'testing addition of two Integer values');
    }

    /**
     * Testing the __toString method
     * 
     * @since 1.0
     */
    public function testToString() {
    	$this->int1 = new Integer(2008);    	
    	
    	$this->assertEquals('The year is 2008', 'The year is '.$this->int1, 'testing the __toString method');
    }
}

?>