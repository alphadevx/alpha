<?php

require_once '../controller/Controller.inc';
require_once '../view/photo.inc';
require_once '../config/config.conf';
require_once '../config/db_connect.inc';

/**
 *
 * Group of unit tests for the Controller class
 * 
 * @package SimpleMVC_Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2005 John Collins
 * 
 * 
 */
class Controller_Tests extends PHPUnit_TestCase
{
	/**
	 * a Controller for testing
	 * @var Controller
	 */
	var $x;
	/**
	 * a Controller for testing
	 * @var Controller
	 */
	var $y;
	/**
	 * a business object for testing
	 * @var photo
	 */
	var $photo1;
	/**
	 * a business object for testing
	 * @var photo
	 */
	var $photo2;
	
	/**
	 * constructor of the test suite
	 * @param string $name the name of the test cases
	 */
    function Controller_Tests($name) {
       $this->PHPUnit_TestCase($name);
    }
    
    /**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    function setUp() {        
        $this->x = new Controller();
        $this->y = new Controller();
        $this->photo1 = new photo();
        $this->photo2 = new photo();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    function tearDown() {        
        unset($this->x);
        unset($this->y);
        unset($this->photo1);
        unset($this->photo2);
        session_unset();
    }
    
    /** 
     * test the get_unit_duration method for equality
     */
    function test_get_unit_duration_equal() {
        $this->x->set_unit_end_time('2005', '10', '30', '21', '15', '15');
    	$this->y->set_unit_end_time('2005', '10', '30', '21', '15', '15');
    
    	$this->assertEquals($this->x->get_unit_duration(), $this->y->get_unit_duration());
    }
    
    /** 
     * test the get_unit_duration method for greater than
     */
    function test_get_unit_duration_greater() {
        $this->x->set_unit_end_time('2006', '10', '30', '21', '15', '15');
    	$this->y->set_unit_end_time('2005', '10', '30', '21', '15', '15');
    
    	$this->assertTrue($this->x->get_unit_duration() > $this->y->get_unit_duration());
    }
    
    /**
     * testing the set_unit_of_work method and get_next_job
     */
    function test_set_unit_of_work_next() {
    	$this->x->set_name('job4.php');
    	$this->x->set_unit_of_work(array('job1.php','job2.php','job3.php','job4.php','job5.php'));
    	
    	$this->assertEquals('job5.php', $this->x->get_next_job());
    }
    
    /**
     * testing the set_unit_of_work method and get_first_job
     */
    function test_set_unit_of_work_first() {
    	$this->x->set_name('job4.php');
    	$this->x->set_unit_of_work(array('job1.php','job2.php','job3.php','job4.php','job5.php'));
    	
    	$this->assertEquals('job1.php', $this->x->get_first_job());
    }
    
    /**
     * testing the set_unit_of_work method and get_previous_job
     */
    function test_set_unit_of_work_previous() {
    	$this->x->set_name('job3.php');
    	$this->x->set_unit_of_work(array('job1.php','job2.php','job3.php','job4.php','job5.php'));
    	
    	$this->assertEquals('job2.php', $this->x->get_previous_job());
    }
    
    /**
     * testing the set_unit_of_work method and get_last_job
     */
    function test_set_unit_of_work_last() {
    	$this->x->set_name('job3.php');
    	$this->x->set_unit_of_work(array('job1.php','job2.php','job3.php','job4.php','job5.php'));
    	
    	$this->assertEquals('job5.php', $this->x->get_last_job());
    }
    
    /**
     * testing that objects are being added to the dirtyObject array correctly
     */
    function test_mark_dirty_add() {
    	$this->x = new Controller();
    	
    	$this->photo1->load('1');
    	$this->photo1->set_title('modified_object');
    	$this->x->mark_dirty($this->photo1);
    	
    	$dirty = $this->x->get_dirty_objects();
    	
    	$this->assertEquals('modified_object', $dirty[0]->get_title());	
    }
    
    /**
     * testing that objects are being added to the new_object array correctly
     */
    function test_mark_new_add() {
    	//session_unset();
    	$this->x = new Controller();
    	
    	$this->photo1->load('1');
    	$this->photo1->set_title('new_object');
    	$this->x->mark_new($this->photo1);
    	
    	$new = $this->x->get_new_objects();
    	
    	$this->assertEquals('new_object', $new[0]->get_title());	
    }
    
    /**
     * testing that objects are being added to the dirty_object array correctly
     * and that this array is in the session being shared by controllers
     */
    function test_mark_dirty_session() {
    	$this->photo1 = new photo('1');
    	$this->x = new Controller();
    	
    	$this->photo1->set_title('modified_object_session');
    	$this->x->mark_dirty($this->photo1);
    	
    	// calling the constructor of the other controller will check the session
    	$this->y = new Controller();
    	
    	$dirty = $this->y->get_dirty_objects();    	
    	
    	$this->assertEquals('modified_object_session', $dirty[0]->get_title());	
    }
    
    /**
     * testing that objects are being added to the new_object array correctly
     * and that this array is in the session being shared by controllers
     */
    function test_mark_new_session() {
    	$this->photo1 = new photo('1');
    	$this->x = new Controller();
    	
    	$this->photo1->set_title('new_object_session');
    	$this->x->mark_new($this->photo1);
    	
    	// calling the constructor of the other controller will check the session
    	$this->y = new Controller();
    	
    	$new = $this->y->get_new_objects();    	
    	
    	$this->assertEquals('new_object_session', $new[0]->get_title());	
    }
    
    /**
     * testing the commit method for new and dirty objects
     */
    function test_commit() {
    	$this->photo1 = new photo('1');
    	$this->photo1->set_title('Dirty Object');
    	$this->x->mark_dirty($this->photo1);
    	
    	$this->photo2->set_title('New Object');
    	$this->x->mark_new($this->photo2);
    	
    	$this->assertTrue($this->x->commit());
    }
}

?>

