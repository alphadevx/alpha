<?php

// $Id$

/**
 * The Enum complex data type
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
class Enum {
	/**
	 * an array of valid enum options
	 * @var array
	 */
	var $options;
	/**
	 * the currently selected enum option
	 * @var string
	 */
	var $value = '';
	/**
	 * the message to display to the user when validation fails
	 * @var string
	 */
	var $validation_helper = 'Error: not a valid enum option!';	
	
	/**
	 * constructor that sets up the enum options
	 * @param array $opts
	 */
	function Enum($opts=array('')) {
		$this->options = $opts;
	}
	
	/**
	 * setter for the enum options
	 * @param array $val
	 */
	function set_options($opts=array('')) {		
		$this->options = $opts;	
	}
	
	/**
	 * get the array of enum options, sorted in alphabetical order
	 * @return array enum options
	 */
	function get_options() {
		sort($this->options, SORT_STRING);		
		return $this->options;
	}
	
	/**
	 * getter for the validation helper string
	 * @return string validation_helper
	 */	
	function get_helper() {
		return $this->validation_helper;
	}
	
	/**
	 * used to get the current enum item
	 * @return string selected
	 */
	function get_value() {
		return $this->value;
	}
	
	/**
	 * used to select the current enum item
	 * @param string $item
	 * @return bool success status
	 */
	function set_value($item) {
		if (in_array($item, $this->options)) {
			$this->value = $item;
			return true;
		}else{
			return false;
		}
	}
}

?>
