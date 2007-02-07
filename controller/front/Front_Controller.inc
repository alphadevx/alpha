<?php

// $Id$

require_once $sysRoot.'alpha/util/catch_error.inc';
require_once $sysRoot.'alpha/util/handle_error.inc';

/**
* 
* The front controller designed to handle all requests
* 
* @package Alpha Controller Front
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
*
*/
class Front_Controller {
	
	
	var $get_array = array();
	var $page_controller;
	
	/**
	 * the constructor method
	 */
	function Front_Controller() {
		$this->decode_query();
		// need error checking!
		$this->page_controller = $this->get_array["action"];
		$this->populate_get_vars();		
		$this->load_controller();
	}
	
	/**
	 * method to populate the global _GET array with the decoded query string
	 */
	function populate_get_vars() {
		foreach(array_keys($this->get_array) as $var)
			$_GET[$var] = $this->get_array[$var];
	}
	
	/**
	 * static method for encoding a query string
	 */
	function encode_query($get_array) {
		return base64_encode(serialize($get_array));
	}
	
	/**
	 * method to decode the current query string
	 */
	function decode_query() {
		$this->get_array = unserialize(base64_decode($_SERVER["QUERY_STRING"]));
	}
	
	/**
	 * method to load the page controller
	 */
	function load_controller() {
		global $sysRoot;
		global $sysDB;
		
		$handle = opendir($sysRoot.'controller');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/".$this->page_controller.".php/", $file)) {	    		
	    		
	    		require_once $sysRoot.'controller/'.$this->page_controller.'.php';
	    		
	    		$page_controller = new $this->page_controller();
	    		return true;
	    	}
	    }
	    
	    // now loop over the core controller provided with Alpha
	    
	    $handle = opendir($sysRoot.'alpha/controller');
   		
        // loop over the default controller directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/".$this->page_controller.".php/", $file)) {	    		
	    		
	    		require_once $sysRoot.'alpha/controller/'.$this->page_controller.'.php';
	    		
	    		$page_controller = new $this->page_controller();
	    		return true;
	    	}
	    }
	    
	    $error = new handle_error($_SERVER["PHP_SELF"],'Failed to find the definition for the class :-'.$this->page_controller,'load_controller()','framework');
		return false;
	}	
}

?>
