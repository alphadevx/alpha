<?php

require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';

/**
 *
 * Test case for the Image generation widget
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class Image_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * an Image for testing
	 * @var Image
	 */
	private $img;
		
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {
    	global $config;
    	        
        $this->img = new Image($config->get('sysRoot').'/alpha/images/icons/accept.png', 16, 16, 'png');
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
    	unset($this->img);
    }
    
    /**
     * testing for an expected exception when a bad source file path is provided
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
     * testing for an expected exception when a bad source type is provided
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
     * testing for an expected exception when a quality value is provided
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
     * testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically
     */
    public function testConstructorSetFilename() {
    	global $config;
    	
    	$this->assertEquals($config->get('sysRoot').'cache/images/accept_16x16.png', $this->img->getFilename(), 'testing that the constructor will call setFilename internally to get up a filename  to store the generated image automatically');
    }
    
    /**
     * testing the convertImageURLToPath method
     */
    public function testConvertImageURLToPath() {
    	global $config;
    	
    	$this->assertEquals('images/testimage.png', Image::convertImageURLToPath($config->get('sysURL').'/images/testimage.png'), 'testing the convertImageURLToPath method');
    }
}

?>