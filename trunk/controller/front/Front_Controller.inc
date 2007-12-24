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
	
	
	var $get_string;
	var $page_controller;
	var $encrypted_query = false;
	
	/**
	 * the constructor method
	 */
	function Front_Controller() {
		if (isset($_GET["act"])) {
			$this->page_controller = $_GET["act"];
		}else{
			if (!isset($_GET["tk"])) {
				$error = new handle_error($_SERVER["PHP_SELF"],'No controller action set for the front controller!','Front_Controller()');
				exit;
			}
		}
	}
	
	function set_encrypt($encrypted_query) {
		$this->encrypted_query = $encrypted_query;
	}
	
	/**
	 * method to populate the global _GET array with the decoded query string
	 */
	function populate_get_vars() {
				
		$pairs = explode("&", $this->get_string);		
		
		foreach($pairs as $pair) {
			$key_value = explode("=", $pair);
			$_GET[$key_value[0]] = $key_value[1];
		}		
	}
	
	/**
	 * static method for encoding a query string
	 */
	function encode_query($query_string) {
		global $sysQSKey;
   
		$td = mcrypt_module_open ('tripledes', '', 'ecb', ''); 
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND); 
		mcrypt_generic_init ($td, $sysQSKey, $iv); 
		$encrypted_data = mcrypt_generic ($td, $query_string); 
		mcrypt_generic_deinit ($td); 
		mcrypt_module_close ($td);
	   
		$return = base64_encode($encrypted_data);
		// remove any characters that are likely to cause trouble on a URL		
		$return = strtr($return, '+/', '-_');
		return $return;
	}
	
	/**
	 * method to decode the current query string
	 */
	function decode_query() {
		global $sysQSKey;
		
		if (!isset($_GET["tk"])) {
			$error = new handle_error($_SERVER["PHP_SELF"],'No token provided for the front controller!','decode_query()');
			exit;
		}else{			
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
			// replace any troublesome characters from the URL with the original values
			$token = strtr($_GET["tk"], '-_', '+/');
			$token = base64_decode($token);			
			$this->get_string = trim(mcrypt_decrypt('tripledes', $sysQSKey, $token, 'ecb', $iv));
		}
	}
	
	/**
	 * method to load the page controller
	 */
	function load_controller() {
		global $sysRoot;
		global $sysDB;
		
		if($this->encrypted_query) {
			$this->decode_query();			
			$this->populate_get_vars();
			$this->page_controller = $_GET["act"];
		}
		
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
