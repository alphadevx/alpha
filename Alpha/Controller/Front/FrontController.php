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
use Alpha\Controller\ArticleController;
use Alpha\Controller\AttachmentController;
use Alpha\Controller\CacheController;
use Alpha\Controller\CreateController;
use Alpha\Controller\EditController;
use Alpha\Controller\DEnumController;
use Alpha\Controller\ExcelController;
use Alpha\Controller\FeedController;
use Alpha\Controller\GenSecureQueryStringController;
use Alpha\Controller\ImageController;
use Alpha\Controller\ListActiveRecordsController;
use Alpha\Controller\ListController;
use Alpha\Controller\LogController;
use Alpha\Controller\LoginController;
use Alpha\Controller\LogoutController;
use Alpha\Controller\MetricController;
use Alpha\Controller\RecordSelectorController;
use Alpha\Controller\SearchController;
use Alpha\Controller\SequenceController;
use Alpha\Controller\TagController;
use Alpha\Controller\ViewController;
use Alpha\Controller\IndexController;
use Alpha\Controller\InstallController;

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
	 * An array of HTTP filters applied to each request to the front controller.  Each
	 * member must implement FilterInterface!
	 *
	 * @var array
	 * @since 1.0
	 */
	private $filters = array();

    /**
     * An associative array of URIs to callable methods to service matching requests
     *
     * @var array
     * @since 2.0
     */
    private $routes;

    /**
     * The route for the current request
     *
     * @var string
     * @since 2.0
     */
    private $currentRoute;

    /**
     * An optional hash array of default request parameter values to use when those params are left off the request
     * @var array
     * @since 2.0
     */
    private $defaultParamValues;

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
	 * @throws Alpha\Exception\BadRequestException
	 * @since 1.0
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

		$this->addRoute('/', function($request) {
            $controller = new IndexController();
            return $controller->process($request);
        });

        $this->addRoute('/a/{title}/{mode}', function($request) {
            $controller = new ArticleController();
            return $controller->process($request);
        })->value('mode', 'read')->value('title', null);

        $this->addRoute('/attach/{articleOID}/{filename}', function($request) {
            $controller = new AttachmentController();
            return $controller->process($request);
        });

        $this->addRoute('/cache', function($request) {
            $controller = new CacheController();
            return $controller->process($request);
        });

        $this->addRoute('/create/{ActiveRecordType}', function($request) {
            $controller = new CreateController();
            return $controller->process($request);
        });

        $this->addRoute('/edit/{ActiveRecordType}/{ActiveRecordOID}', function($request) {
            $controller = new EditController();
            return $controller->process($request);
        });

        $this->addRoute('/denum/{denumOID}', function($request) {
            $controller = new DEnumController();
            return $controller->process($request);
        })->value('denumOID', null);

        $this->addRoute('/excel/{ActiveRecordType}/{ActiveRecordOID}', function($request) {
            $controller = new ExcelController();
            return $controller->process($request);
        })->value('ActiveRecordOID', null);

        $this->addRoute('/feed/{ActiveRecordType}/{type}', function($request) {
            $controller = new FeedController();
            return $controller->process($request);
        })->value('type', 'Atom');

        $this->addRoute('/gensecure', function($request) {
            $controller = new GenSecureQueryStringController();
            return $controller->process($request);
        });

        $this->addRoute('/image/{source}/{width}/{height}/{type}/{quality}/{scale}/{secure}/{var1}/{var2}', function($request) {
            $controller = new ImageController();
            return $controller->process($request);
        })->value('var1', null)->value('var2', null);

        $this->addRoute('/listactiverecords', function($request) {
            $controller = new ListActiveRecordsController();
            return $controller->process($request);
        });

        $this->addRoute('/listall/{ActiveRecordType}/{start}/{limit}', function($request) {
            $controller = new ListController();
            return $controller->process($request);
        })->value('start', 0)->value('limit', $config->get('app.list.page.amount'));

        $this->addRoute('/log/{logPath}', function($request) {
            $controller = new LogController();
            return $controller->process($request);
        });

        $this->addRoute('/login', function($request) {
            $controller = new LoginController();
            return $controller->process($request);
        });

        $this->addRoute('/logout', function($request) {
            $controller = new LogoutController();
            return $controller->process($request);
        });

        $this->addRoute('/metric', function($request) {
            $controller = new MetricController();
            return $controller->process($request);
        });

        $this->addRoute('/recordselector/{ActiveRecordOID}/{relationType}', function($request) {
            $controller = new RecordSelectorController();
            return $controller->process($request);
        });

        $this->addRoute('/search/{query}/{start}/{limit}', function($request) {
            $controller = new SearchController();
            return $controller->process($request);
        })->value('start', 0)->value('limit', $config->get('app.list.page.amount'));

        $this->addRoute('/sequence/{start}/{limit}', function($request) {
            $controller = new SequenceController();
            return $controller->process($request);
        })->value('start', 0)->value('limit', $config->get('app.list.page.amount'));

        $this->addRoute('/tag/{ActiveRecordType}/{ActiveRecordOID}', function($request) {
            $controller = new TagController();
            return $controller->process($request);
        });

        $this->addRoute('/view/{ActiveRecordType}/{ActiveRecordOID}', function($request) {
            $controller = new ViewController();
            return $controller->process($request);
        });

        $this->addRoute('/install', function($request) {
            $controller = new InstallController();
            return $controller->process($request);
        });

        $this->addRoute('/tk/{token}', function($request) {
        	$params = self::getDecodeQueryParams($request->getParam('token'));
        	
        	if (isset($params['act'])) {
        		$className = $params['act'];

        		if (class_exists($className)) {
        			$controller = new $className;
        			$request->addParams($params);
        			return $controller->process($request);
        		}
        	}
        	
        	self::$logger->warn('Bad params ['.print_r($params, true).'] provided on a /tk/ request');
        	return new Response(404, 'Resource not found');
        });

        $this->addRoute('/alpha/service', function($request) {
        	$controller = new LoginController();
        	$controller->setUnitOfWork(array('Alpha\Controller\LoginController', 'Alpha\Controller\ListActiveRecordsController'));
        	return $controller->process($request);
        });

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
	 * @deprecated
	 */
	private function decodeQuery()
	{
		$config = ConfigProvider::getInstance();

		$params = $this->request->getParams();

		if (!isset($params['token'])) {
			throw new SecurityException('No token provided for the front controller!');
		} else {
			// replace any troublesome characters from the URL with the original values
			$token = strtr($params['token'], '-_', '+/');
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
     * @return Alpha\Controller\Front\FrontController
     * @since 2.0
     */
    public function addRoute($URI, $callback)
    {
        if (is_callable($callback)) {
            $this->routes[$URI] = $callback;
            return $this;
        } else {
            throw new IllegalArguementException('Callback provided for route ['.$URI.'] is not callable');
        }
    }

    /**
     * Method to allow the setting of default request param values to be used when they are left off the request URI.
     *
     * @param string $param The param name (as defined on the route between {} braces)
     * @param mixed $defaultValue The value to use
     * @return Alpha\Controller\Front\FrontController
     * @since 2.0
     */
    public function value($param, $defaultValue)
    {
        $this->defaultParamValues[$param] = $defaultValue;
        return $this;
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
            $this->currentRoute = $URI;
            return $this->routes[$URI];
        } else { // we need to use a regex to match URIs with params

            // route URIs with params provided to callback
            foreach ($this->routes as $route => $callback) {
                $pattern = '#^'.$route.'$#s';
                $pattern = preg_replace('#\{\S+\}#', '\S+', $pattern);

                if (preg_match($pattern, $URI)) {
                    $this->currentRoute = $route;
                    return $callback;
                }
            }

             // route URIs with params missing (will attempt to layer on defaults later on in Request class)
            foreach ($this->routes as $route => $callback) {
                $pattern = '#^'.$route.'$#s';
                $pattern = preg_replace('#\/\{\S+\}#', '.*', $pattern);

                if (preg_match($pattern, $URI)) {
                    $this->currentRoute = $route;
                    return $callback;
                }
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

        if ($request->getURI() != $this->currentRoute)
            $request->parseParamsFromRoute($this->currentRoute, $this->defaultParamValues);

        try {
            $response = call_user_func($callback, $request);
        } catch (ResourceNotFoundException $rnfe) {
            self::$logger->info('ResourceNotFoundException throw, source message ['.$rnfe->getMessage().']');
            return new Response(404, $rnfe->getMessage());
        }

        if ($response instanceof Response) {
            return $response;
        } else {
            self::$logger->error('The callable defined for route ['.$request->getURI().'] does not return a Response object');
            throw new AlphaException('Unable to process request');
        }
    }
}

?>
