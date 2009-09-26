<?php

// $Id$

/**
 *
 * A singleton config class
 * 
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2007 John Collins 
 * @deprecated use AlphaConfig instead! 
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
			$instance[0]->setRootPath();
			$instance[0]->setIncludePath();
						
			// check to see if a child class with callbacks has been implemented
			if(file_exists($instance[0]->get('rootPath').'config/configCallbacks.inc')) {
				require_once $instance[0]->get('rootPath').'config/configCallbacks.inc';
				$instance = array(new configCallbacks());
				$instance[0]->setRootPath();
				$instance[0]->setIncludePath();
			}
			
			// populate the config from the ini file
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
		/*
		 * If you need to alter a config option after it has been set in the .ini
		 * files, you can override this class and implement this callback method
		 */
		if(method_exists($this, 'before_set_callback'))
			$val = $this->before_set_callback($key, $val, $this->configVars);
		$this->configVars[$key] = $val;		
  	}
  	
  	/**
  	 * Sets the root directory of the application
  	 */
  	function setRootPath() {
  		$currentScript = __FILE__;
  		
  		$rootPath = '';		
  		
  		// first check to see if it is a controller under /alpha
  		if(strrpos($currentScript, 'alpha/') !== false) {
  			$rootPath = substr($currentScript, 0, strrpos($currentScript, 'alpha/'));
  		}elseif(!strrpos($currentScript, 'alpha/') && strrpos($currentScript, 'controller/') != false) {
  			// handle custom controllers at a lower level
  			$rootPath = substr($currentScript, 0, strrpos($currentScript, 'controller/'));
  		}elseif(strrpos($currentScript, 'config/css/') !== false) {
  			// set path for CSS files
  			$rootPath = substr($currentScript, 0, strrpos($currentScript, 'config/css/'));
  		}elseif(strrpos($currentScript, 'AlphaCronManager') !== false) {
  			// set the path for the AlphaCronManager being run from CLI
  			$rootPath = '../../';
  		}else{
  			$rootPath = '';
  		}
  		
  		$config =&configLoader::getInstance();
  		$config->set('rootPath', $rootPath);
  	}
  	
  	/**
  	 * attempt to set the include_path to include the alpha/lib directory
  	 */
  	function setIncludePath() {
  		$config =&configLoader::getInstance();
  		$rootPath = $config->get('rootPath');
  		
  		ini_set('include_path', ini_get('include_path').':'.$rootPath.'alpha/lib');
  	}
  	
  	/**
  	 * Loads the config from the relevent .ini file, dependant upon the current
  	 * environment
  	 * 
  	 */
  	function loadConfig() {
  		$config =&configLoader::getInstance();
  		$rootPath = $config->get('rootPath');  		
  		
  		// first we need to see if we are in dev, pro or test environment
  		if(isset($_SERVER['SERVER_NAME'])) {
  			$server = $_SERVER['SERVER_NAME'];
  		}else{
  			// we may be running in CLI mode
  			$server = $_ENV['HOSTNAME'];
  		}
  			
  		
	  	// load the servers to see which environment the current server is set as  		
	  	$serverIni = $rootPath.'config/servers.ini';
	  	if(file_exists($serverIni)) {
			$envs = parse_ini_file($serverIni);
			if(in_array($server, $envs)) {	
				$environment = array_search($server, $envs);
			}else{	  			
				die('No environment configured for the server '.$server);
			}
	  	}else{  			
	  		die('Failed to load the config file '.$serverIni);
	  	}
	  		
	  	$envIni = $rootPath.'config/'.$environment.'.ini';
   		
  		$configArray = parse_ini_file($envIni);
  		
  		$config =&configLoader::getInstance();
  		
  		foreach(array_keys($configArray) as $key) {
  			$config->set($key, $configArray[$key]);
  		}  		
  	}
}

?>