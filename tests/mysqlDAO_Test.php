<?php

/**
 *
 * Test case for the mySQLDAO class
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$ 
 * 
 */
class mysqlDAO_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * A person_object for testing
	 * @var person_object
	 */
	private $person;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {       
        
    }
    
    /*
     * TODO: add test methods for the following:
     * 
     * - constructor
     * - load
     * - loadByAttribute
     * - loadAll
     * - loadAllByAttribute
     */
}

?>