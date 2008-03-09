<?php

// $Id$

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

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
	var $controller_alias = array();
	var $current_alias;
	
	/**
	 * the constructor method
	 */
	function Front_Controller() {
		global $config;
		
		// direct calls to the front controller
		if (isset($_GET["act"])) {
			$this->page_controller = $_GET["act"];
		// calls to the front controller via mod_rewrite
		}elseif($config->get("sysUseModRewrite") && !isset($_GET["tk"])) {
			$this->handle_mod_rewrite_requests();			
		// direct calls to the front controller with an encrypted query string
		}else{
			if (!isset($_GET["tk"])) {
				$error = new handle_error($_SERVER["PHP_SELF"],'No controller action set for the front controller!','Front_Controller()');
				exit;
			}else{
				$this->set_encrypt(true);
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
	 * Static method for generating an absolute, secure URL for a page controller
	 */
	function generate_secure_URL($controller) {
		global $config;
		
		return $config->get('sysURL').'/FC.php?tk='.Front_Controller::encode_query($controller);
	}
	
	/**
	 * static method for encoding a query string
	 */
	function encode_query($query_string) {
		global $config;
   
		$td = mcrypt_module_open ('tripledes', '', 'ecb', ''); 
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND); 
		mcrypt_generic_init ($td, $config->get('sysQSKey'), $iv); 
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
		global $config;		
		
		if (!isset($_GET["tk"])) {
			$error = new handle_error($_SERVER["PHP_SELF"],'No token provided for the front controller!','decode_query()');
			exit;
		}else{			
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
			// replace any troublesome characters from the URL with the original values
			$token = strtr($_GET["tk"], '-_', '+/');
			$token = base64_decode($token);			
			$this->get_string = trim(mcrypt_decrypt('tripledes', $config->get('sysQSKey'), $token, 'ecb', $iv));
		}
	}
	
	/**
	 * Static method to return the decoded GET paramters from an encrytpted tk value (excludes the act paramter)
	 */
	function decode_query_params($query_string) {
		global $config;		
		
		if (!isset($_GET["tk"])) {
			$error = new handle_error($_SERVER["PHP_SELF"],'No token provided for the front controller!','decode_query()');
			exit;
		}else{			
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
			// replace any troublesome characters from the URL with the original values
			$token = strtr($_GET["tk"], '-_', '+/');
			$token = base64_decode($token);
			$params = trim(mcrypt_decrypt('tripledes', $config->get('sysQSKey'), $token, 'ecb', $iv));
			// now remove the first param ("act" the page controller action)
			$params = substr($params, strpos($params,'&'));
			return $params;
		}
	}
	
	/**
	 * method to load the page controller
	 * @param bool $allowRedirects Defaults to true, set to false if you want to prevent the front controller from redirecting the request
	 */
	function load_controller($allowRedirects = true) {
		global $config;
		
		if($allowRedirects) {
			// if there is an alias configured for the above page controller, redirect there
			if($config->get('sysForceFC') && $this->has_alias($this->page_controller)) {
				// make sure that it is not already an alias-based request to prevent re-direct loop			
				if(empty($this->current_alias)) {
					// set the correct HTTP header for the response
			    	header('HTTP/1.1 301 Moved Permanently');
			    	
			    	// see if there are any other GET params appart from the controller name
			    	if (count($_GET) > 1) {
			    		$keys = array_keys($_GET);
			    		$param = $_GET[$keys[1]];
			    		// if its a title then replace spaces with underscores in the URL
			    		if($keys[1] == 'title')
			    			$param = str_replace(' ','_',$param);
			    		
			    		$URL = $config->get('sysURL').'/'.$this->get_controller_alias($this->page_controller).'/'.$this->get_controller_param($this->page_controller).$param;
			    	}else{
			    		$URL = $config->get('sysURL').'/'.$this->get_controller_alias($this->page_controller).'/'.$this->get_controller_param($this->page_controller);
			    	}
			    	
			    	header('Location: '.$URL);
			 
				    // we're done here
			    	exit();
				}
			}
		}
		
		// handle encrypted query strings
		if($this->encrypted_query) {
			$this->decode_query();			
			$this->populate_get_vars();
			$this->page_controller = $_GET["act"];
		}
		
		$handle = opendir($config->get('sysRoot').'controller');
   		
        // loop over the business object directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/".$this->page_controller.".php/", $file)) {	    		
	    		
	    		require_once $config->get('sysRoot').'controller/'.$this->page_controller.'.php';
	    		
	    		$page_controller = new $this->page_controller();
	    		return true;
	    	}
	    }
	    
	    // now loop over the core controller provided with Alpha
	    
	    $handle = opendir($config->get('sysRoot').'alpha/controller');
   		
        // loop over the default controller directory
	    while (false !== ($file = readdir($handle))) {
	    	if (preg_match("/".$this->page_controller.".php/", $file)) {	    		
	    		
	    		require_once $config->get('sysRoot').'alpha/controller/'.$this->page_controller.'.php';
	    		
	    		$page_controller = new $this->page_controller();
	    		return true;
	    	}
	    }
	    
	    $error = new handle_error($_SERVER["PHP_SELF"],'Failed to find the definition for the class :-'.$this->page_controller,'load_controller()','framework');
		return false;
	}
	
	/**
	 * Used to register a controller alias to enable shorter URLs with mod_rewrite support enabled.  Note that
	 * only controllers with a single parameter are supported.
	 */
	function register_alias($controller, $alias, $param) {
		$this->controller_alias[$alias] = $controller;
		$this->controller_alias[$alias.'_param'] = $param;
		
		// set up the page controller 
		$this->handle_mod_rewrite_requests();
	}
	
	/**
	 * Check to see if an alias exists for the given alias name
	 */
	function check_alias($alias) {		
		if(array_key_exists($alias, $this->controller_alias))
			return true;
		else
			return false;
	}
	
	/**
	 * Check to see if an alias exists for the given controller name
	 */
	function has_alias($controller) {
		if(in_array($controller, $this->controller_alias))
			return true;
		else
			return false;
	}
	
	/**
	 * Gets the full name of the controller for the given alias
	 */
	function get_alias_controller($alias) {
		if(array_key_exists($alias, $this->controller_alias))
			return $this->controller_alias[$alias];
	}
	
	/**
	 * Gets the name of the alias for the given controller
	 */
	function get_controller_alias($controller) {
		if(in_array($controller, $this->controller_alias)) {
			$keys = array_keys($this->controller_alias, $controller);
			// there should only ever be one key per controller
			return $keys[0];
		}
	}
	
	/**
	 * Gets the parameter name expected in requests to the controller with the given alias
	 */
	function get_alias_param($alias) {
		if(array_key_exists($alias.'_param', $this->controller_alias))
			return $this->controller_alias[$alias.'_param'];
	}
	
	/**
	 * Gets the parameter name expected in requests to the controller with the given controller name
	 */
	function get_controller_param($controller) {
		if(in_array($controller.'_param', $this->controller_alias))
			return $this->controller_alias[$controller.'_param'];
	}
	
	/**
	 * Handles all of the rules for mod_rewrite style URL parsing
	 */
	function handle_mod_rewrite_requests() {
		global $config;
		
		// strip off the system URL from the request URL
		$request = str_replace($config->get('sysURL'), '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		$params = explode('/', $request);
			
		// second param will always be the controller
		if(empty($this->page_controller))
			$this->page_controller = $params[1];
			
		// check to see if the controller is an alias for something
		if($this->check_alias($params[1])) {
			$this->page_controller = $this->get_alias_controller($params[1]);
			$this->current_alias = $params[1];
			$_GET[$this->get_alias_param($params[1])] = $params[2];
		}else{			
			// now populate the _GET vars
			for($i = 2; $i < count($params); $i++) {
				if(isset($params[$i+1]))				
					$_GET[$params[$i]] = $params[$i+1];
			}
		}
	}
}

?>
