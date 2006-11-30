<?php

require_once '../view/photo.inc';
require_once '../config/config.conf';
require_once '../config/db_connect.inc';
require_once 'PHPUnit.php';

/**
 *
 * Group of unit tests for the DAO class
 * 
 * @package SimpleMVC_Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2005 John Collins
 * 
 * 
 */
class DAO_Tests extends PHPUnit_TestCase
{
	/**
	 * a photo for testing
	 * @var photo
	 */
	var $photo1;
	/**
	 * a photo for testing
	 * @var photo
	 */
	var $photo2;
		
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function DAO_Tests($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->photo1 = new photo();
        $this->photo2 = new photo();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->photo1);
        unset($this->photo2);     
    }
    
    /** 
     * testing that version numbers are incrementing when saved
     */
    function test_get_unit_duration_equal() {
        $this->photo1->load('1');
        $old_version = $this->photo1->get_version();
        
        $this->photo1->save();
        $this->photo1->load('1');
    
    	$this->assertEquals($this->photo1->get_version(), $old_version+1);
    }
    
    /**
     * testing optimistic locking mechanism
     */
    function test_save_locking() {
    	$this->photo1->load('1');
    	$this->photo2->load('1');
    	
    	$status_1 = $this->photo1->save();
    	$status_2 = $this->photo2->save();
    	
    	$this->assertFalse($status_1 == $status_2);
    }
    
    /**
     * testing object delete method for the database
     */
    function test_object_delete2() {
    	$this->photo1->load('4');
    	$this->photo1->delete_object();
    	
    	$result = $this->photo1->load('4');
    	
    	$this->assertFalse($result);
    }
    
}

?>

