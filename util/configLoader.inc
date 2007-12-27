<?php

// $Id$

/**
 *
 * A singleton config class
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * 
 */
class configLoader{
	/**
	 * Array to store the config variables
	 */
	var $configVars = array();
  
	/**
	 * Create a new config object
	 */
	function &getInstance() {
		static $instance;
		if (!$instance) {
			$instance = array(new configLoader());
			$instance[0]->loadConfig();
		}
		return $instance[0];
	}
  
	/**
	 * Get config data
	 *
	 * @param $key string - array key of variable whose setting we are retrieving
	 * @return string the config setting
	 */
	function get($key) {
		return $this->configVars[$key];
	}
  
	/**
	 * Set config data
	 *
	 * @param	$key string variable we will update
	 * @param	$value string variable that contains data to store
	 * @return true
	 * Can use arrays as well as in  $config->setConfig('email_list', array(//... ));
	 * retrieve array by foreach($config->getConfig('email_list') as $email) { //... }
	 *
	 */
	function set($key, $val) {
		$this->configVars[$key] = $val;
		//return true;
  	}
  	
  	/**
  	 * Loads the config from the relevent .ini file, dependant upon the current
  	 * environment
  	 * 
  	 */
  	function loadConfig() {
  		// first we need to see if we are in dev, pro or test environment
  		$server = $_SERVER["SERVER_NAME"];
  		// load the servers to see which environment the current server is set as
  		$serverIni = 'config/servers.ini';
  		if(file_exists($serverIni)) {
	  		$envs = parse_ini_file($serverIni);
	  		if(in_array($server, $envs)) {	
	  			$environment = array_search($server, $envs);
	  		}else{
	  			//$error = new handle_error($_SERVER["PHP_SELF"],'No environment configured for the server '.$server,'loadConfig()','framework');
	  			//exit;
	  			die('No environment configured for the server '.$server);
	  		}
  		}else{
  			//$error = new handle_error($_SERVER["PHP_SELF"],'Failed to load the config file '.$serverIni,'loadConfig()','framework');
  			//exit;
  			die('Failed to load the config file '.$serverIni);
  		}
  		
  		$envIni = 'config/'.$environment.'.ini';
  		$configArray = parse_ini_file($envIni);
  		
  		$config =&configLoader::getInstance();
  		
  		foreach(array_keys($configArray) as $key) {
  			$config->set($key, $configArray[$key]);
  		}  		
  	}
}

?>
