<?php

/**
 *
 * Test case for the Double data type
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
class Double_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Double for testing
	 * 
	 * @var Double
	 * @since 1.0
	 */
	private $dbl1;
	
	/**
	 * An Double for testing
	 * 
	 * @var Double
	 * @since 1.0
	 */
	private $dbl2;
		
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {        
        $this->dbl1 = new Double();
        $this->dbl2 = new Double();
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
        unset($this->dbl1);
        unset($this->dbl2);
    }
    
    /**
     * Testing the Double constructor for acceptance of correct data
     * 
     * @since 1.0
     */
    public function testConstructorPass() {
    	$this->dbl1 = new Double(5.77);
    	
    	$this->assertEquals(5.77, $this->dbl1->getValue(), "testing the Double constructor for pass");
    }
    
    /**
     * Testing passing invalid data to setValue
     * 
     * @since 1.0
     */
    public function testSetValueInvalid() {
    	try {
    		$this->dbl1->setValue("blah");
    		$this->fail('testing passing invalid data to setValue');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid double value!'
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
    	$this->dbl1->setValue(0.25);
    	
    	$this->assertEquals(0.25, $this->dbl1->getValue(), 'testing passing valid data to setValue');
    }
    
    /**
     * Testing the setSize method to see if validation fails
     * 
     * @since 1.0
     */
    public function testSetSizeInvalid() {
    	$this->dbl1 = new Double();
    	$this->dbl1->setSize(2);
    	
    	try {
    		$this->dbl1->setValue(200);
    		$this->fail('testing passing invalid data to setValue');
    	}catch (AlphaException $e) {
    		$this->assertEquals('Not a valid double value!'
    			, $e->getMessage()
    			, 'testing passing invalid data to setValue');
    	}
    }
    
    /**
     * Testing addition of two Double values
     * 
     * @since 1.0
     */
    public function testAddDoubles() {
    	$this->dbl1 = new Double(1.25);
    	$this->dbl2 = new Double(3.50);
    	
    	$this->assertEquals(4.75, ($this->dbl1->getValue()+$this->dbl2->getValue()), 'testing addition of two Double values');
    }	
    
	/**
     * Testing the __toString method
     * 
     * @since 1.0
     */
    public function testToString() {
    	$this->dbl1 = new Double(5.5);
    	
    	$this->assertEquals('The price is $5.50', 'The price is $'.$this->dbl1, 'testing the __toString method');
    }
}

?>