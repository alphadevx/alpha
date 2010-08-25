<?php

/**
 *
 * Test cases for the Sequence data type
 * 
 * @package Alpha Core Unit Tests
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 * 
 */
class Sequence_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * a Sequence for testing
	 * @var Sequence
	 */
	private $sequence;
	
	/**
     * called before the test functions will be executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */
    protected function setUp() {        
        $this->sequence = new Sequence();
        $this->sequence->set('prefix', 'TEST');
        $this->sequence->set('sequence', 1);
        $this->sequence->save();
    }
    
    /** 
     * called after the test functions are executed
     * this function is defined in PHPUnit_TestCase and overwritten
     * here
     */    
    protected function tearDown() {
    	$this->sequence->delete();   
        unset($this->sequence);        
    }
    
    /**
     * Testing the validation on the setValue method
     */
    public function testSetValueValidation() {
    	try {
    		$this->sequence->setValue('invalid');
    		$this->fail('Testing to ensure that a bad parameter will cause an IllegalArguementException');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals($this->sequence->getHelper(), $e->getMessage(), 'Testing to ensure that a bad parameter will cause an IllegalArguementException');
    	}
    	
    	try {
    		$this->sequence->setValue('VALID-1');
    		$this->assertEquals('VALID', $this->sequence->get('prefix'), 'Testing to ensure that a good parameter will not cause an IllegalArguementException');
    		$this->assertEquals(1, $this->sequence->get('sequence'), 'Testing to ensure that a good parameter will not cause an IllegalArguementException');
    	}catch (IllegalArguementException $e) {
    		$this->fail('Testing to ensure that a good parameter will not cause an IllegalArguementException');
    	}
    }
    
    /**
     * Testing that sequence prefixes are uppercase
     */
    public function testPrefixValidation() {
    	try {
    		$this->sequence->set('prefix', 'bad');
    	}catch (IllegalArguementException $e) {
    		$this->assertEquals($this->sequence->getPropObject('prefix')->getHelper(), $e->getMessage(), 'Testing that sequence prefixes are uppercase');
    	}
    }
    
    /**
     * Testing the setSequenceToNext methid increments the sequence number
     */
    public function testSetSequenceToNext() {
    	$this->sequence->setSequenceToNext();
    	
    	$this->assertEquals('TEST-2', $this->sequence->getValue(), 'Testing the setSequenceToNext methid increments the sequence number');
    }
}