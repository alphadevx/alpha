<?php

/**
 *
 * A singleton config class
 *
 * <code>
 * // include the config file
 * if(!isset($config)) {
 * 	require_once 'AlphaConfig.inc';
 * 	$config = AlphaConfig::getInstance();
 * }
 * </code>
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id: configLoader.inc 901 2009-06-02 21:03:09Z johnc $ 
 * 
 */
class AlphaConfig {
	/**
	 * Array to store the config variables
	 *
	 * @var array
	 */
	private $configVars = array();

	/**
	 * The config object singleton
	 *
	 * @var Config
	 */
	private static $instance;

	/**
	 * Private constructor means the class cannot be instantiated from elsewhere
	 */
	private function __construct () {}
  
	/**
	 * Get the config object instance
	 *
	 * @return Config
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new AlphaConfig();
			self::$instance->setRootPath();
			self::$instance->setIncludePath();
      
			// check to see if a child class with callbacks has been implemented
			if(file_exists(self::$instance->get('rootPath').'config/ConfigCallbacks.inc')) {
				require_once self::$instance->get('rootPath').'config/ConfigCallbacks.inc';

				self::$instance = new ConfigCallbacks();
				self::$instance->setRootPath();
				self::$instance->setIncludePath();
			}
   
			// populate the config from the ini file
			self::$instance->loadConfig();
		}
		return self::$instance;
	}
  
	/**
	 * Get config value
	 *
	 * @param $key string
	 * @return string
	 */
	public function get($key) {
		return $this->configVars[$key];
	}
  
	/**
  	 * Set config value
	 *
	 * @param $key string
	 * @param $val string
	 */
	public function set($key, $val) {
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
	private function setRootPath() {
		$currentScript = __FILE__;
		
		// reverse the slashes in case we are running on Windows
		$currentScript = str_replace('\\', '/', $currentScript);
    
		$rootPath = '';  

		if(strrpos($currentScript, 'alpha/view/widgets/') !== false) {
			// set path for widgets
			$rootPath = substr($currentScript, 0, strrpos($currentScript, 'alpha/view/widgets/'));
		}elseif(strrpos($currentScript, 'alpha/util/') !== false) {
			// set the path for util scripts
			$rootPath = substr($currentScript, 0, strrpos($currentScript, 'alpha/util/'));
		}elseif(strrpos($currentScript, 'alpha/') !== false) {
			// check to see if it is a controller under /alpha
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

		$this->set('rootPath', $rootPath);
	}
   
	/**
	 * Attempt to set the include_path to include the alpha/lib directory
	 */
	private function setIncludePath() {
		$config = AlphaConfig::getInstance();
		$rootPath = $config->get('rootPath');
    
		ini_set('include_path', ini_get('include_path').';'.$rootPath.'alpha/lib');
	}
   
	/**
	 * Loads the config from the relevent .ini file, dependant upon the current
	 * environment (hostname).
	 * 
	 */
	private function loadConfig() {
		$rootPath = $this->get('rootPath');
    
		// first we need to see if we are in dev, pro or test environment
		if(isset($_SERVER['SERVER_NAME'])) {
			$server = $_SERVER['SERVER_NAME'];
		}elseif(isset($_ENV['HOSTNAME'])){
			// we may be running in CLI mode
			$server = $_ENV['HOSTNAME'];
		}else{ // TODO why is this hard-coded?!?
			$server = 'agileopensource.com';
		}

		// Load the servers to see which environment the current server is set as    
		$serverIni = $rootPath.'config/servers.ini';

		if(file_exists($serverIni)) {
			$envs = parse_ini_file($serverIni);
			
			if(in_array($server, $envs)) {
				$environment = array_search($server, $envs);
			}else{
				die('No environment configured for the server '.$server);
			}
		}else{     
			die('Failed to load the config file ['.$serverIni.']');
		}
     
		$envIni = $rootPath.'config/'.$environment.'.ini';
     
		$configArray = parse_ini_file($envIni);
    
		foreach(array_keys($configArray) as $key) {
			$this->set($key, $configArray[$key]);
		}    
	}
}

?>