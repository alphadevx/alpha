<?php

require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';

/**
 *
 * Test case for the Image generation widget
 * 
 * @package alpha::tests
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
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
class Image_Test extends PHPUnit_Framework_TestCase {
	/**
	 * An Image for testing
	 * 
	 * @var Image
	 * @since 1.0
	 */
	private $img;
		
	/**
     * Called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */
    protected function setUp() {
    	global $config;
    	        
        $this->img = new Image($config->get('sysRoot').'/alpha/images/icons/accept.png', 16, 16, 'png');
    }
    
    /** 
     * Called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     * 
     * @since 1.0
     */    
    protected function tearDown() {        
    	unset($this->img);
    }
    
    /**
     * Testing for an expected exception when a bad source file path is provided
     * 
     * @since 1.0
     */
    public function testConstructorBadSource() {
    	try {
    		$this->img = new Image('/does/not/exist.png', 16, 16, 'png');
    		$this->fail('testing for an expected exception when a bad source file path is provided');
    	} catch (IllegalArguementException $e) {
    		$this->assertEquals('The source file for the Image widget [/does/not/exist.png] cannot be found!', $e->getMessage(), 'testing for an expected exception when a bad source file path is provided');
    	}
    }
    
	/**
     * Testing for an expected exception when a bad source type is provided
     * 
     * @since 1.0
     */
    public function testConstructorBadSourceType() {
    	global $config;
    	
    	try {
    		$this->img = new Image($config->get('sysRoot').'/alpha/images/icons/accept.png', 16, 16, 'tif');
    		$this->fail('testing for an expected exception when a bad source type is provided');
    	} catch (IllegalArguementException $e) {
    		$this->assertEquals('Not a valid enum option!', $e->getMessage(), 'testing for an expected exception when a bad source type is provided');
    	}
    }
    
	/**
     * Testing for an expected exception when a quality value is provided
     * 
     * @since 1.0
     */
    public function testConstructorQuality() {
    	global $config;
    	
    	try {
    		$this->img = new Image($config->get('sysRoot').'/alpha/images/icons/accept.png', 16, 16, 'png', 2.5);
    		$this->fail('testing for an expected exception when a quality value is provided');
    	} catch (IllegalArguementException $e) {
    		$this->assertEquals('The quality setting of [2.5] is outside of the allowable range of 0.0 to 1.0', $e->getMessage(), 'testing for an expected exception when a quality value is provided');
    	}
    }
    
    /**
     * Testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically
     * 
     * @since 1.0
     */
    public function testConstructorSetFilename() {
    	global $config;
    	
    	$this->assertEquals($config->get('sysRoot').'cache/images/accept_16x16.png', $this->img->getFilename(), 'testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically');
    }
    
    /**
     * Testing the convertImageURLToPath method
     * 
     * @since 1.0
     */
    public function testConvertImageURLToPath() {
    	global $config;
    	
    	$this->assertEquals('images/testimage.png', Image::convertImageURLToPath($config->get('sysURL').'images/testimage.png'), 'testing the convertImageURLToPath method');
    }
}

?>