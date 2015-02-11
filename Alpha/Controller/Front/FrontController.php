<?php

namespace Alpha\Controller\Front;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Security\SecurityUtils;
use Alpha\Util\Http\Filter\FilterInterface;
use Alpha\Util\Http\Response;
use Alpha\Util\Http\Request;
use Alpha\Exception\BadRequestException;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\SecurityException;
use Alpha\Exception\LibraryNotInstalledException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Exception\AlphaException;
use Alpha\Controller\Controller;

/**
 *
 * The front controller designed to optionally handle all requests
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *
 */
class FrontController
{
	/**
	 * The GET query string
	 *
	 * @var string
	 * @since 1.0
	 */
	private $queryString;

	/**
	 * The name of the page controller we want to invoke
	 *
	 * @var string
	 * @since 1.0
	 */
	private $pageController;

	/**
	 * Boolean to flag if the GET query string is encrypted or not
	 *
	 * @var boolean
	 * @since 1.0
	 */
	private $encryptedQuery = false;

	/**
	 * An array of controller alias
	 *
	 * @var array
	 * @since 1.0
     * @deprecated
	 */
	private $controllerAlias = array();

	/**
	 * An array of HTTP filters applied to each request to the front controller.  Each
	 * member must implement FilterInterface!
	 *
	 * @var array
	 * @since 1.0
	 */
	private $filters = array();

	/**
	 * The name of the current alias
	 *
	 * @var string
	 * @since 1.0
     * @deprecated
	 */
	private $currentAlias;

    /**
     * An associative array of URIs to callable methods to service matching requests
     *
     * @var array
     * @since 2.0
     */
    private $routes;

	/**
	 * Trace logger
	 *
	 * @var Alpha\Util\Logging\Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * The constructor method
	 *
	 * @throws Alpha\Exception\ResourceNotFoundException
	 * @throws Alpha\Exception\BadRequestException
	 * @since 1.0
     * @todo Remove all calls to super globals, only Request should do that now
	 */
	public function __construct()
	{
		self::$logger = new Logger('FrontController');

		self::$logger->debug('>>__construct()');

		$config = ConfigProvider::getInstance();

		mb_internal_encoding('UTF-8');
		mb_http_output('UTF-8');
		mb_http_input('UTF-8');
		ini_set('default_charset', 'utf-8');
		if (!mb_check_encoding())
			throw new BadRequestException('Request character encoding does not match expected UTF-8');

        if (!isset($_GET['act']) && !isset($_SERVER['REQUEST_URI']) && !isset($_GET['tk'])) {
            self::$logger->warn('No controller action set for the front controller, request URI not set');
            throw new ResourceNotFoundException('The file that you have requested cannot be found!');
        }

		// direct calls to the front controller
		if (isset($_GET['act'])) {
			self::$logger->debug('Processing direct request to the front controller');
			$this->pageController = $_GET['act'];
		// calls to the front controller via mod_rewrite
		} elseif($config->get('app.use.mod.rewrite') && !isset($_GET['tk'])) {
			self::$logger->debug('Processing a mod_rewrite request');
			$this->handleModRewriteRequests();
		// direct calls to the front controller with an encrypted query string
		} else{
			if (!isset($_GET['tk'])) {
				self::$logger->warn('No controller action set for the front controller, URL is ['.$url.']');
				throw new ResourceNotFoundException('The file that you have requested cannot be found!');
			} else{
				self::$logger->debug('Processing a direct request to the front controller with an encrypted token param');
				$this->setEncrypt(true);
				try {
					$this->decodeQuery();
					$this->populateGetVars();
					if(isset($_GET['act']))
						$this->pageController = $_GET['act'];
					else
						throw new SecurityException('No act param provided in the secure token!');
				} catch (SecurityException $e) {
					self::$logger->error('Error while attempting to decode a secure token in the FrontController: '.$e->getMessage());
					throw new ResourceNotFoundException('The file that you have requested cannot be found!');
				}
			}
		}

		self::$logger->debug('<<__construct');
	}

	/**
	 * Sets the encryption flag
	 *
	 * @param boolean $encryptedQuery
	 * @since 1.0
	 */
	public function setEncrypt($encryptedQuery)
	{
		$this->encryptedQuery = $encryptedQuery;
	}

	/**
	 * Method to populate the global _GET and _REQUEST arrays with the decoded
	 * query string
	 *
	 * @since 1.0
     * @deprecated
	 */
	private function populateGetVars()
	{
		$pairs = explode('&', $this->queryString);

		foreach($pairs as $pair) {
			$keyValue = explode('=', $pair);
			if(count($keyValue) == 2) {
				$_GET[$keyValue[0]] = $keyValue[1];
				$_REQUEST[$keyValue[0]] = $keyValue[1];
			}
		}
	}

	/**
	 * Static method for generating an absolute, secure URL for a page controller
	 *
	 * @param string $params
	 * @return string
	 * @since 1.0
	 */
	public static function generateSecureURL($params)
	{
		$config = ConfigProvider::getInstance();

		if($config->get('app.use.mod.rewrite'))
			return $config->get('app.url').'tk/'.FrontController::encodeQuery($params);
		else
			return $config->get('app.url').'?tk='.FrontController::encodeQuery($params);
	}

	/**
	 * Static method for encoding a query string
	 *
	 * @param string $queryString
	 * @return string
	 * @since 1.0
	 */
	public static function encodeQuery($queryString)
	{
		$config = ConfigProvider::getInstance();

		$return = base64_encode(SecurityUtils::encrypt($queryString));
		// remove any characters that are likely to cause trouble on a URL
		$return = strtr($return, '+/', '-_');

		return $return;
	}

	/**
	 * Method to decode the current query string
	 *
	 * @throws Alpha\Exception\SecurityException
	 * @since 1.0
	 */
	private function decodeQuery()
	{
		$config = ConfigProvider::getInstance();

		if (!isset($_GET['tk'])) {
			throw new SecurityException('No token provided for the front controller!');
		}else{
			// replace any troublesome characters from the URL with the original values
			$token = strtr($_GET['tk'], '-_', '+/');
			$token = base64_decode($token);
			$this->queryString = trim(SecurityUtils::decrypt($token));
		}
	}

	/**
	 * Static method to return the decoded GET paramters from an encrytpted tk value
	 *
	 * @return string
	 * @since 1.0
	 */
	public static function decodeQueryParams($tk)
	{
		$config = ConfigProvider::getInstance();

		// replace any troublesome characters from the URL with the original values
		$token = strtr($tk, '-_', '+/');
		$token = base64_decode($token);
		$params = trim(SecurityUtils::decrypt($token));

		return $params;
	}

	/**
	 * Static method to return the decoded GET paramters from an encrytpted tk value as an array of key/value pairs.
	 *
	 * @return array
	 * @since 1.0
	 */
	public static function getDecodeQueryParams($tk)
	{
		$config = ConfigProvider::getInstance();

		// replace any troublesome characters from the URL with the original values
		$token = strtr($tk, '-_', '+/');
		$token = base64_decode($token);
		$params = trim(SecurityUtils::decrypt($token));

		$pairs = explode('&', $params);

		$parameters = array();

		foreach ($pairs as $pair) {
			$split = explode('=', $pair);
			$parameters[$split[0]] = $split[1];
		}

		return $parameters;
	}

	/**
	 * Method to load the page controller
	 *
	 * @param boolean $allowRedirects Defaults to true, set to false if you want to prevent the front controller from redirecting the request
	 * @throws Alpha\Exception\ResourceNotFoundException
	 * @since 1.0
     * @deprecated
	 */
	public function loadController($allowRedirects = true)
	{
		$config = ConfigProvider::getInstance();

		if ($allowRedirects && $config->get('app.check.installed') && $this->pageController != 'Install' && $this->pageController != 'Login') {
			if (!ActiveRecord::isInstalled()) {
				self::$logger->info('Invoking the Install controller as the system DB is not installed...');
				$url = FrontController::generateSecureURL('act=Install');
				self::$logger->info('Redirecting to ['.$url.']');
				header('Location: '.$url);
				exit;
			}
		}

		// first process any attached filters
		foreach ($this->filters as $filter)
			$filter->process();

		if ($allowRedirects) {
			// if there is an alias configured for the above page controller, redirect there
			if ($config->get('app.force.front.controller') && $this->hasAlias($this->pageController)) {
				// make sure that it is not already an alias-based request to prevent re-direct loop
				if (empty($this->currentAlias)) {
					// set the correct HTTP header for the response
			    	header('HTTP/1.1 301 Moved Permanently');

			    	// see if there are any other GET params appart from the controller name
			    	if (count($_GET) > 1) {
			    		$keys = array_keys($_GET);
			    		$param = $_GET[$keys[1]];
			    		// if its a title then replace spaces with underscores in the URL
			    		if($keys[1] == 'title')
			    			$param = str_replace(' ','_',$param);

			    		$URL = $config->get('app.url').'/'.$this->getControllerAlias($this->pageController).'/'.
			    			$this->getControllerParam($this->pageController).$param;
			    	} else {
			    		$URL = $config->get('app.url').'/'.$this->getControllerAlias($this->pageController);
			    	}

			    	header('Location: '.$URL);
			    	exit;
				}
			}
		}

		try {
			Controller::loadControllerDef($this->pageController);
			$pageController = new $this->pageController();

	    	if (!empty($_POST)) {
				$pageController->doPOST($_REQUEST);
			} else {
				$pageController->doGET($_GET);
			}
		} catch (LibraryNotInstalledException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".var_export($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new LibraryNotInstalledException($e->getMessage());
		} catch (ResourceNotAllowedException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".var_export($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new ResourceNotAllowedException($e->getMessage());
		} catch (ResourceNotFoundException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".var_export($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);
			throw new ResourceNotFoundException($e->getMessage());
		} catch (IllegalArguementException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".var_export($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);

			if ($config->get('security.client.temp.blacklist.filter.enabled')) {
				if(isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['REQUEST_URI'])) {
					$request = new BadRequestObject();
					$request->set('client', $_SERVER['HTTP_USER_AGENT']);
					$request->set('IP', $_SERVER['REMOTE_ADDR']);
					$request->set('requestedResource', $_SERVER['REQUEST_URI']);
					$request->save();
				}
			}

			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		} catch (AlphaException $e) {
			self::$logger->warn($e->getMessage()."\nStacktrace:\n".$e->getTraceAsString()."\nRequest params:\n".var_export($_REQUEST, true)."\nRequested resource:\n".$_SERVER['REQUEST_URI']);

			if($config->get('security.client.temp.blacklist.filter.enabled')) {
				if(isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['REQUEST_URI'])) {
					$request = new BadRequestObject();
					$request->set('client', $_SERVER['HTTP_USER_AGENT']);
					$request->set('IP', $_SERVER['REMOTE_ADDR']);
					$request->set('requestedResource', $_SERVER['REQUEST_URI']);
					$request->save();
				}
			}

			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}
	}

	/**
	 * Used to register a controller alias to enable shorter URLs with mod_rewrite support enabled.  Note that
	 * only controllers with a single parameter are supported.
	 *
	 * @param string $controller The name of the page controller class
	 * @param string $alias The URL alias for the page controller
	 * @param string $param The name of the GET parameter on the alias URL request
	 * @since 1.0
     * @deprecated
	 */
	public function registerAlias($controller, $alias, $param=null)
	{
		$this->controllerAlias[$alias] = $controller;
		if (isset($param))
			$this->controllerAlias[$alias.'_param'] = $param;

		// set up the page controller
		$this->handleModRewriteRequests();
	}

	/**
	 * Check to see if an alias exists for the given alias name
	 *
	 * @param string $alias
	 * @return boolean
	 * @since 1.0
     * @deprecated
	 */
	public function checkAlias($alias)
	{
		if (array_key_exists($alias, $this->controllerAlias))
			return true;
		else
			return false;
	}

	/**
	 * Check to see if an alias exists for the given controller name
	 *
	 * @param string $controller
	 * @return boolean
	 * @since 1.0
     * @deprecated
	 */
	public function hasAlias($controller)
	{
		if (in_array($controller, $this->controllerAlias))
			return true;
		else
			return false;
	}

	/**
	 * Gets the full name of the controller for the given alias
	 *
	 * @param string $alias
	 * @return string
	 * @since 1.0
     * @deprecated
	 */
	public function getAliasController($alias)
	{
		if (array_key_exists($alias, $this->controllerAlias))
			return $this->controllerAlias[$alias];
	}

	/**
	 * Gets the name of the alias for the given controller
	 *
	 * @param string $controller
	 * @return string
	 * @since 1.0
     * @deprecated
	 */
	public function getControllerAlias($controller)
	{
		if (in_array($controller, $this->controllerAlias)) {
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
	 * @since 1.0
     * @deprecated
	 */
	public function getAliasParam($alias)
	{
		if (array_key_exists($alias.'_param', $this->controllerAlias))
			return $this->controllerAlias[$alias.'_param'];
		else
			return '';
	}

	/**
	 * Gets the parameter name expected in requests to the controller with the given controller name
	 *
	 * @param string $controller
	 * @return string
	 * @since 1.0
     * @deprecated
	 */
	public function getControllerParam($controller)
	{
		$alias = $this->getControllerAlias($controller);
		if (array_key_exists($alias.'_param', $this->controllerAlias))
			return $this->controllerAlias[$alias.'_param'];
		else
			return '';
	}

	/**
	 * Explodes the provided string into an array based on the array of delimiters provided
	 *
	 * @param string $string The string to explode.
	 * @param array $delimiters An array of delimiters.
	 * @todo move to string utils class
	 * @return array
	 * @since 1.2
	 */
	private static function multipleExplode($string, $delimiters = array())
	{
		$mainDelim=$delimiters[count($delimiters)-1];

		array_pop($delimiters);

		foreach ($delimiters as $delimiter) {
			$string = str_replace($delimiter, $mainDelim, $string);
		}

		$result = explode($mainDelim, $string);

		return $result;
	}

	/**
	 * Handles all of the rules for mod_rewrite style URL parsing
	 *
	 * @since 1.0
     * @deprecated
	 */
	private function handleModRewriteRequests()
	{
		self::$logger->debug('>>handleModRewriteRequests');
		$config = ConfigProvider::getInstance();

		$request = $_SERVER['REQUEST_URI'];
		self::$logger->debug('$request is ['.$request.']');
		$params = self::multipleExplode($request, array('/','?','&'));
		self::$logger->debug('$params are ['.var_export($params, true).']');

		try {
			// first param will always be the controller alias
			if(empty($this->currentAlias) && !empty($params[0]))
				$this->currentAlias = $params[0];

			// check to see if we can load the page controller without an alias
			Controller::loadControllerDef($params[0]);
			self::$logger->debug('Page controller name set on the request URL is ['.$params[0].']');
			$this->pageController = $params[0];
		} catch (IllegalArguementException $iae) {
			// handle request with alias
			self::$logger->debug('The supplied controller alias is ['.$this->currentAlias.']');

			// check to see if the controller is an alias for something
			if ($this->checkAlias($this->currentAlias)) {
				$this->pageController = $this->getAliasController($this->currentAlias);
				self::$logger->debug('Page controller name obtained from the URL alias is ['.$this->pageController.']');

				if (isset($params[1])) {
					if (!empty($_POST))
						$_REQUEST[$this->getAliasParam($this->currentAlias)] = $params[1];
					else
						$_GET[$this->getAliasParam($this->currentAlias)] = $params[1];
				}
			}
		}

		self::$logger->debug('$params are ['.var_export($params, true).']');
		self::$logger->debug('currentAlias is ['.$this->currentAlias.']');

		// now populate the _GET vars
		if ($this->currentAlias == 'tk') {
			self::$logger->debug('Setting the GET vars for a mod_rewrite request with a tk param');
			$this->setEncrypt(true);
			$this->queryString = FrontController::decodeQueryParams($params[1]);
			$_GET['tk'] = $params[1];
			$this->populateGetVars();
			$this->pageController = $_GET['act'];
		} else {
			$count = count($params);

			for ($i = 1; $i < $count; $i+=2) {
				if (isset($params[$i+1])) {
					if (!empty($_POST))
						$_REQUEST[$params[$i]] = $params[$i+1];
					else
						$_GET[$params[$i]] = $params[$i+1];
				}
			}
		}

		self::$logger->debug('$_GET is ['.var_export($_GET, true).']');
		self::$logger->debug('<<handleModRewriteRequests');
	}

	/**
	 * Getter for the page controller
	 *
	 * @return string
	 * @since 1.0
	 */
	public function getPageController()
	{
		return $this->pageController;
	}

	/**
	 * Add the supplied filter object to the list of filters ran on each request to the front controller
	 *
	 * @param Alpha\Util\Http\Filter\FilterInterface $filterObject
	 * @throws Alpha\Exception\IllegalArguementException
	 * @since 1.0
	 */
	public function registerFilter($filterObject)
	{
		if ($filterObject instanceof FilterInterface)
			array_push($this->filters, $filterObject);
		else
			throw new IllegalArguementException('Supplied filter object is not a valid FilterInterface instance!');
	}

	/**
	 * Returns the array of filters currently attached to this FrontController
	 *
	 * @return array
	 * @since 1.0
	 */
	public function getFilters()
	{
		return $this->filters;
	}

    /**
     * Add a new route to map a URI to the callback that will service its requests,
     * normally by invoking a controller class
     *
     * @param string $URI The URL to match, can include params within curly {} braces.
     * @param callable $callback The method to service the matched requests (should return a Response!).
     * @throws Alpha\Exception\IllegalArguementException
     * @since 2.0
     */
    public function addRoute($URI, $callback)
    {
        if (is_callable($callback))
            $this->routes[$URI] = $callback;
        else
            throw new IllegalArguementException('Callback provided for route ['.$URI.'] is not callable');
    }

    /**
     * Get the defined callback in the routes array for the URI provided
     *
     * @param string $URI The URI to search for.
     * @return callable
     * @throws Alpha\Exception\IllegalArguementException
     * @since 2.0
     */
    public function getRouteCallback($URI)
    {
        if (array_key_exists($URI, $this->routes)) { // direct hit due to URL containing no params
            return $this->routes[$URI];
        } else { // we need to use a regex to match URIs with params
            foreach ($this->routes as $route => $callback) {
                $pattern = '#^'.$route.'$#s';
                $pattern = preg_replace('#\{\w+\}#', '\w+', $pattern);

                if (preg_match($pattern, $URI))
                    return $callback;
            }
        }

        // if we made it this far then no match was found
        throw new IllegalArguementException('No callback defined for URI ['.$URI.']');
    }

    /**
     * Processes the supplied request by invoking the callable defined matching the request's URI.
     *
     * @param Alpha\Util\Http\Request $request The request to process
     * @return Alpha\Util\Http\Response
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\AlphaException
     * @since 2.0
     */
    public function process($request)
    {
        try {
            $callback = $this->getRouteCallback($request->getURI());
        } catch (IllegalArguementException $e) {
            self::$logger->warn($e->getMessage());
            throw new ResourceNotFoundException('Resource not found');
        }

        $response = call_user_func($callback, $request);

        if ($response instanceof Response) {
            return $response;
        } else {
            self::$logger->error('The callable defined for route ['.$request->getURI().'] does not return a Response object');
            throw new AlphaException('Unable to process request');
        }
    }
}

?>