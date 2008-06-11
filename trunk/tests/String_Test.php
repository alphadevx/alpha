<?php

/**
 *
 * Test case for the String data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class String_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * A String for testing
	 * @var String
	 */
	private $str1;
	
	/**
	 * A helper string for username reg-ex validation tests
	 *
	 * @var string
	 */
	private $usernameHelper = 'Please provide a name for display on the website (only letters, numbers, and .-_ characters are allowed!).';

	/**
	 * A helper string for email reg-ex validation tests
	 *
	 * @var string
	 */	
	private $emailHelper = 'Please provide a valid e-mail address as your username';
	
	/**
	 * A helper string for URL reg-ex validation tests
	 *
	 * @var string
	 */	
	private $urlHelper = 'URLs must be in the format http://some_domain/ or left blank!';
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->str1 = new String();        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {        
        unset($this->str1);        
    }
    
    /**
     * testing the str constructor for acceptance of correct data
     */
    public function testConstructorPass() {
    	$this->str1 = new String('A String Value!');
    	
    	$this->assertEquals('A String Value!', $this->str1->getValue(), "testing the String constructor for pass");
    }
    
    /**
     * testing passing an invalid username string
     */
    public function testSetUsernameValueInvalid() {
    	try {
    		$this->str1->setRule(RULE_USERNAME);
    		$this->str1->setSize(70);
			$this->str1->setHelper($this->usernameHelper);
			
			$this->str1->setValue('invalid user.');
			$this->fail('testing passing an invalid username string');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals($this->usernameHelper
    			, $e->getMessage()
    			, 'testing passing an invalid username string');
    	}
    }
    
	/**
     * testing passing a valid username string
     */
    public function testSetUsernameValueValid() {
    	try {
    		$this->str1->setRule(RULE_USERNAME);
    		$this->str1->setSize(70);
			$this->str1->setHelper($this->usernameHelper);
			
			$this->str1->setValue('user_name.-test123gg');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing passing a valid username string: '.$e->getMessage());    		
    	}
    }
    
	/**
     * testing passing an invalid email string
     */
    public function testSetEmailValueInvalid() {
    	try {
    		$this->str1->setRule(RULE_EMAIL);
    		$this->str1->setSize(70);
			$this->str1->setHelper($this->emailHelper);
			
			$this->str1->setValue('invalid email');
			$this->fail('testing passing an invalid email string');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals($this->emailHelper
    			, $e->getMessage()
    			, 'testing passing an invalid email string');
    	}
    }
    
	/**
     * testing passing a valid email string
     */
    public function testSetEmailValueValid() {
    	try {
    		$this->str1->setRule(RULE_EMAIL);
    		$this->str1->setSize(70);
			$this->str1->setHelper($this->emailHelper);
			
			$this->str1->setValue('user@somewhere.com');
			$this->str1->setValue('user@somewhere.ie');
			$this->str1->setValue('user@somewhere.co.uk');
			$this->str1->setValue('user@somewhere.net');
			$this->str1->setValue('user@somewhere.org');
			$this->str1->setValue('some.user@somewhere.com');
			$this->str1->setValue('some.user@somewhere.ie');
			$this->str1->setValue('some.user@somewhere.co.uk');
			$this->str1->setValue('some.user@somewhere.net');
			$this->str1->setValue('some.user@somewhere.org');
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing passing a valid email string: '.$e->getMessage());    		
    	}
    }
    
	/**
     * testing passing an invalid URL string
     */
    public function testSetURLValueInvalid() {
    	try {
    		$this->str1->setRule(RULE_URL_BLANK);    		
			$this->str1->setHelper($this->urlHelper);
			
			$this->str1->setValue('invalid url');
			$this->fail('testing passing an invalid URL string');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals($this->urlHelper
    			, $e->getMessage()
    			, 'testing passing an invalid URL string');
    	}
    }
    
	/**
     * testing passing a valid URL string
     */
    public function testSetURLValueValid() {
    	try {
    		$this->str1->setRule(RULE_URL_BLANK);    		
			$this->str1->setHelper($this->urlHelper);
			
			$this->str1->setValue('http://www.google.com/');
			$this->str1->setValue('http://slashdot.org/');
			$this->str1->setValue('http://www.yahoo.com/');
			$this->str1->setValue('http://www.design-ireland.net/');
			$this->str1->setValue('http://www.theregister.co.uk/');
			$this->str1->setValue('http://www.bbc.co.uk/');			
    	}catch (AlphaFrameworkException $e) {
    		$this->fail('testing passing a valid URL string: '.$e->getMessage());    		
    	}
    }
    
    /**
     * testing the setSize method to see if validation fails
     */
    public function testSetSizeInvalid() {
    	$this->str1 = new String();
    	$this->str1->setSize(4);
    	
    	try {
    		$this->str1->setValue('Too many characters!');
    		$this->fail('testing the setSize method to see if validation fails');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('Error: not a valid string value!  A maximum of 4 characters is allowed.'
    			, $e->getMessage()
    			, 'testing the setSize method to see if validation fails');
    	}
    }    
	    
	/**
     * testing the __toString method
     */
    public function testToString() {
    	$this->str1 = new String('__toString result');    	
    	
    	$this->assertEquals('The value of __toString result', 'The value of '.$this->str1, 'testing the __toString method');
    }
    
    /**
     * testing to see if the password setter/inspector is working
     */
    public function testIsPassword() {
    	$this->str1->isPassword();
    	
    	$this->assertTrue($this->str1->checkIsPassword(), 'testing to see if the password setter/inspector is working');
    }
    
    /**
     * testing to see that isPassword makes the string required
     */
    public function testIsPasswordRequired() {
    	$this->str1->isPassword();
    	
    	try {
    		$this->str1->setValue('');
    		$this->fail('testing to see that isPassword makes the string required');
    	}catch (AlphaFrameworkException $e) {
    		$this->assertEquals('This string requires a value!'
    			, $e->getMessage()
    			, 'testing to see that isPassword makes the string required');
    	}
    }
}

?>