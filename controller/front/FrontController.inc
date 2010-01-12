<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/SecurityException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/ResourceNotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/util/filters/AlphaFilterInterface.inc';

/**
 * 
 * The front controller designed to optionally handle all requests
 * 
 * @package alpha::controller::front
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 */
class FrontController {
	/**
	 * The GET query string
	 *
	 * @var string
	 */
	private $queryString;
	
	/**
	 * The name of the page controller we want to invoke
	 *
	 * @var string
	 */
	private $pageController;
	
	/**
	 * Boolean to flag if the GET query string is encrypted or not
	 *
	 * @var bool
	 */
	private $encryptedQuery = false;
	
	/**
	 * An array of controller alias
	 *
	 * @var array
	 */
	private $controllerAlias = array();
	
	/**
	 * An array of HTTP filters applied to each request to the front controller.  Each
	 * member must implement AlphaFilterInterface!
	 * 
	 * @var array
	 */
	private $filters = array();
	
	/**
	 * The name of the current alias
	 *
	 * @var string
	 */
	private $currentAlias;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * The constructor method
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('FrontController');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// direct calls to the front controller
		if (isset($_GET['act'])) {
			self::$logger->debug('Processing direct request to the front controller');
			$this->pageController = $_GET['act'];
		// calls to the front controller via mod_rewrite
		}elseif($config->get('sysUseModRewrite') && !isset($_GET['tk'])) {
			self::$logger->debug('Processing a mod_rewrite request');
			$this->handleModRewriteRequests();			
		// direct calls to the front controller with an encrypted query string
		}else{
			if (!isset($_GET['tk'])) {
				self::$logger->warn('No controller action set for the front controller, URL is ['.$_SERVER['REQUEST_URI'].']');
				throw new ResourceNotFoundException('The file that you have requested cannot be found!');
			}else{
				self::$logger->debug('Processing a direct request to the front controller with an encrypted token param');
				$this->setEncrypt(true);
			}
		}
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Sets the encryption flag
	 *
	 * @param bool $encryptedQuery
	 */
	public function setEncrypt($encryptedQuery) {
		$this->encryptedQuery = $encryptedQuery;
	}
	
	/**
	 * Method to populate the global _GET and _REQUEST arrays with the decoded 
	 * query string
	 */
	private function populateGetVars() {
				
		$pairs = explode('&', $this->queryString);		
		
		foreach($pairs as $pair) {
			$keyValue = explode('=', $pair);
			$_GET[$keyValue[0]] = $keyValue[1];
			$_REQUEST[$keyValue[0]] = $keyValue[1];
		}
	}
	
	/**
	 * Static method for generating an absolute, secure URL for a page controller
	 * 
	 * @param string $params
	 * @return string
	 */
	public static function generateSecureURL($params) {
		global $config;
		
		return $config->get('sysURL').'FC.php?tk='.FrontController::encodeQuery($params);
	}
	
	/**
	 * Static method for encoding a query string
	 * 
	 * @param string $queryString
	 * @return string
	 */
	public static function encodeQuery($queryString) {
		global $config;
   
		$td = mcrypt_module_open ('tripledes', '', 'ecb', ''); 
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND); 
		mcrypt_generic_init ($td, $config->get('sysQSKey'), $iv); 
		$encryptedData = mcrypt_generic ($td, $queryString); 
		mcrypt_generic_deinit ($td); 
		mcrypt_module_close ($td);
	   
		$return = base64_encode($encryptedData);
		// remove any characters that are likely to cause trouble on a URL		
		$return = strtr($return, '+/', '-_');
		
		return $return;
	}
	
	/**
	 * Method to decode the current query string
	 */
	private function decodeQuery() {
		global $config;		
		
		if (!isset($_GET['tk'])) {
			throw new SecurityException('No token provided for the front controller!');
		}else{			
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
			// replace any troublesome characters from the URL with the original values
			$token = strtr($_GET['tk'], '-_', '+/');
			$token = base64_decode($token);			
			$this->queryString = trim(mcrypt_decrypt('tripledes', $config->get('sysQSKey'), $token, 'ecb', $iv));
		}
	}
	
	/**
	 * Static method to return the decoded GET paramters from an encrytpted tk value (excludes the act paramter)
	 * 
	 * @return string
	 */
	public static function decodeQueryParams($tk) {
		global $config;		
		
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
		// replace any troublesome characters from the URL with the original values
		$token = strtr($tk, '-_', '+/');
		$token = base64_decode($token);
		$params = trim(mcrypt_decrypt('tripledes', $config->get('sysQSKey'), $token, 'ecb', $iv));
		// now remove the first param ("act" the page controller action)			
		$params = substr($params, strpos($params,'&')+1);
		
		return $params;
	}
	
	/**
	 * Method to load the page controller
	 * 
	 * @param bool $allowRedirects Defaults to true, set to false if you want to prevent the front controller from redirecting the request
	 * @return bool True if the controller loaded, false otherwise
	 */
	public function loadController($allowRedirects = true) {
		global $config;
		
		// first process any attached filters
		foreach ($this->filters as $filter)
			$filter->process();
		
		if($allowRedirects) {
			// if there is an alias configured for the above page controller, redirect there
			if($config->get('sysForceFC') && $this->hasAlias($this->pageController)) {
				// make sure that it is not already an alias-based request to prevent re-direct loop			
				if(empty($this->currentAlias)) {
					// set the correct HTTP header for the response
			    	header('HTTP/1.1 301 Moved Permanently');
			    	
			    	// see if there are any other GET params appart from the controller name
			    	if (count($_GET) > 1) {
			    		$keys = array_keys($_GET);
			    		$param = $_GET[$keys[1]];
			    		// if its a title then replace spaces with underscores in the URL
			    		if($keys[1] == 'title')
			    			$param = str_replace(' ','_',$param);
			    		
			    		$URL = $config->get('sysURL').'/'.$this->getControllerAlias($this->pageController).'/'.$this->getControllerParam($this->pageController).$param;
			    	}else{
			    		$URL = $config->get('sysURL').'/'.$this->getControllerAlias($this->pageController).'/'.$this->getControllerParam($this->pageController);
			    	}
			    	
			    	header('Location: '.$URL);
			 
				    // we're done here
			    	return true;
				}
			}
		}
		
		// handle encrypted query strings
		if($this->encryptedQuery) {
			$this->decodeQuery();			
			$this->populateGetVars();
			$this->pageController = $_GET['act'];
		}
		
		try {
			self::loadControllerDef($this->pageController);
			$pageController = new $this->pageController();
	    	if(!empty($_POST)) {			
				$pageController->doPOST($_REQUEST);
			}else{				
				$pageController->doGET($_GET);
			}
	    	return true;
		}catch (ResourceNotAllowedException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".print_r($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new ResourceNotAllowedException($e->getMessage());
			return false;
		}catch (IllegalArguementException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".print_r($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
			return false;
		}catch (AlphaException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".print_r($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
			return false;
		}
	}
	
	/**
	 * Used to register a controller alias to enable shorter URLs with mod_rewrite support enabled.  Note that
	 * only controllers with a single parameter are supported.
	 * 
	 * @param string $controller The name of the page controller class
	 * @param string $alias The URL alias for the page controller
	 * @param string $param The name of the GET parameter on the alias URL request
	 */
	public function registerAlias($controller, $alias, $param) {
		$this->controllerAlias[$alias] = $controller;
		$this->controllerAlias[$alias.'_param'] = $param;
		
		// set up the page controller 
		$this->handleModRewriteRequests();
	}
	
	/**
	 * Check to see if an alias exists for the given alias name
	 * 
	 * @param string $alias
	 * @return bool
	 */
	private function checkAlias($alias) {		
		if(array_key_exists($alias, $this->controllerAlias))
			return true;
		else
			return false;
	}
	
	/**
	 * Check to see if an alias exists for the given controller name
	 * 
	 * @param string $controller
	 * @return bool
	 */
	public function hasAlias($controller) {
		if(in_array($controller, $this->controllerAlias))
			return true;
		else
			return false;
	}
	
	/**
	 * Gets the full name of the controller for the given alias
	 * 
	 * @param string $alias
	 * @return string
	 */
	private function getAliasController($alias) {
		if(array_key_exists($alias, $this->controllerAlias))
			return $this->controllerAlias[$alias];
	}
	
	/**
	 * Gets the name of the alias for the given controller
	 * 
	 * @param string $controller
	 * @return string
	 */
	public function getControllerAlias($controller) {
		if(in_array($controller, $this->controllerAlias)) {
			$keys = array_keys($this->controllerAlias, $controller);
			// there should only ever be one key per controller
			return $keys[0];
		}
	}
	
	/**
	 * Gets the parameter name expected in requests to the controller with the given alias
	 * 
	 * @param string $alias
	 * @return string
	 */
	private function getAliasParam($alias) {
		if(array_key_exists($alias.'_param', $this->controllerAlias))
			return $this->controllerAlias[$alias.'_param'];
	}
	
	/**
	 * Gets the parameter name expected in requests to the controller with the given controller name
	 * 
	 * @param string $controller
	 * @return string
	 */
	private function getControllerParam($controller) {
		if(in_array($controller.'_param', $this->controllerAlias))
			return $this->controllerAlias[$controller.'_param'];
	}
	
	/**
	 * Handles all of the rules for mod_rewrite style URL parsing
	 */
	private function handleModRewriteRequests() {
		self::$logger->debug('>>handleModRewriteRequests');
		global $config;
		
		// strip off the system URL from the request URL
		$request = str_replace($config->get('sysURL'), '', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		self::$logger->debug('$request is ['.$request.']');
		$params = explode('/', $request);
		self::$logger->debug('$params are ['.print_r($params, true).']');
		
		try {
			// first param will always be the controller alias
			if(empty($this->currentAlias) && !empty($params[0]))
				$this->currentAlias = $params[0];

			// check to see if we can load the page controller without an alias
			self::loadControllerDef($params[0]);
			self::$logger->debug('Page controller name set on the request URL is ['.$params[0].']');
			$this->pageController = $params[0];
		}catch (IllegalArguementException $iae) {
			// handle request with alias		
			self::$logger->debug('The supplied controller alias is ['.$this->currentAlias.']');
			
			// check to see if the controller is an alias for something
			if($this->checkAlias($this->currentAlias)) {
				$this->pageController = $this->getAliasController($this->currentAlias);
				self::$logger->debug('Page controller name obtained from the URL alias is ['.$this->pageController.']');
				
				if(!empty($_POST))
					$_REQUEST[$this->getAliasParam($this->currentAlias)] = $params[1];
				else
					$_GET[$this->getAliasParam($this->currentAlias)] = $params[1];
			}
		}
		
		// now populate the _GET vars
		for($i = 1; $i < count($params); $i++) {
			if(isset($params[$i+1])) {
				if(!empty($_POST))
					$_REQUEST[$params[$i]] = $params[$i+1];
				else			
					$_GET[$params[$i]] = $params[$i+1];
			}
		}
		
		self::$logger->debug('$params are ['.print_r($_GET, true).']');		
		self::$logger->debug('<<handleModRewriteRequests');
	}
	
	/**
	 * Getter for the page controller
	 * 
	 * @return string
	 */
	public function getPageController() {
		return $this->pageController;
	}
	
	/**
	 * Loads the definition for the controller classname provided
	 * 
	 * @param string $controllerName 
	 */
	public static function loadControllerDef($controllerName) {
		if(self::$logger == null)
			self::$logger = new Logger('FrontController');
		self::$logger->debug('>>loadControllerDef(controllerName=['.$controllerName.'])');
		
		global $config;
		
		if(file_exists($config->get('sysRoot').'controller/'.$controllerName.'.php'))
			require_once $config->get('sysRoot').'controller/'.$controllerName.'.php';
		elseif(file_exists($config->get('sysRoot').'alpha/controller/'.$controllerName.'.php'))
			require_once $config->get('sysRoot').'alpha/controller/'.$controllerName.'.php';
		else
			throw new IllegalArguementException('The class ['.$controllerName.'] is not defined anywhere!');
		
		self::$logger->debug('<<loadControllerDef');
	}
	
	/**
	 * Renders a HTML error page with the supplied error code (typlically a HTTP code) and a message
	 * 
	 * @param $code
	 * @param $message
	 * @return string
	 */
	public function renderErrorPage($code, $message) {
		global $config;		
		
		$html = '<html><head>';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/ui.all.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/alpha.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		$html .= '<title>'.$code.' - '.$message.'</title></head>';
		$html .= '<body>';
		$html .= '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>'.$code.':</strong> '.$message.'</p>
				</div>';
		$html .= '</body></html>';
		
		return $html;
	}
	
	/**
	 * Add the supplied filter object to the list of filters ran on each request to the front controller
	 * 
	 * @param $filterObject AlphaFilterInterface
	 */
	public function registerFilter($filterObject) {
		if($filterObject instanceof AlphaFilterInterface)
			array_push($this->filters, $filterObject);
		else
			throw new IllegalArguementException('Supplied filter object is not a valid AlphaFilterInterface instance!');
	}
}

?>