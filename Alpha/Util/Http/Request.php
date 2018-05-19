<?php

namespace Alpha\Util\Http;

use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;

/**
 * A class to encapsulate a HTTP request.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
 */
class Request
{
    /**
     * Array of supported HTTP methods.
     *
     * @var array
     *
     * @since 2.0
     */
    private $HTTPMethods = array('HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE');

    /**
     * The HTTP method of this request (must be in HTTPMethods array).
     *
     * @var string
     *
     * @since 2.0
     */
    private $method;

    /**
     * An associative array of HTTP headers on this request.
     *
     * @var array
     *
     * @since 2.0
     */
    private $headers;

    /**
     * An associative array of HTTP cookies on this request.
     *
     * @var array
     *
     * @since 2.0
     */
    private $cookies;

    /**
     * The HTTP params (form data and query string) on this request.
     *
     * @var array
     *
     * @since 2.0
     */
    private $params;

    /**
     * An associative 3D array of uploaded files.
     *
     * @var array
     *
     * @since 2.0
     */
    private $files;

    /**
     * The request body if one was provided.
     *
     * @var string
     *
     * @since 2.0
     */
    private $body;

    /**
     * The host header provided on the request.
     *
     * @var string
     *
     * @since 2.0
     */
    private $host;

    /**
     * The IP of the client making the request.
     *
     * @var string
     *
     * @since 2.0
     */
    private $IP;

    /**
     * The URI requested.
     *
     * @var string
     *
     * @since 2.0
     */
    private $URI;

    /**
     * The query string provided on the request (if any).
     *
     * @var string
     *
     * @since 2.0
     */
    private $queryString;

    /**
     * Builds up the request based on available PHP super globals, in addition to
     * any overrides provided (useful for testing).
     *
     * @param array $overrides Hash array of PHP super globals to override
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 2.0
     */
    public function __construct($overrides = array())
    {
        // set HTTP headers
        if (isset($overrides['headers']) && is_array($overrides['headers'])) {
            $this->headers = $overrides['headers'];
        } else {
            $this->headers = $this->getGlobalHeaders();
        }

        // set HTTP method
        if (isset($overrides['method']) && in_array($overrides['method'], $this->HTTPMethods)) {
            $this->method = $overrides['method'];
        } else {
            $method = $this->getGlobalServerValue('REQUEST_METHOD');
            if (in_array($method, $this->HTTPMethods)) {
                $this->method = $method;
            }
        }

        // allow the POST param _METHOD to override the HTTP method
        if (isset($_POST['_METHOD']) && in_array($_POST['_METHOD'], $this->HTTPMethods)) {
            $this->method = $_POST['_METHOD'];
        }

        // allow the POST param X-HTTP-Method-Override to override the HTTP method
        if (isset($this->headers['X-HTTP-Method-Override']) && in_array($this->headers['X-HTTP-Method-Override'], $this->HTTPMethods)) {
            $this->method = $this->headers['X-HTTP-Method-Override'];
        }

        if ($this->method == '') {
            throw new IllegalArguementException('No valid HTTP method provided when creating new Request object');
        }

        // set HTTP cookies
        if (isset($overrides['cookies']) && is_array($overrides['cookies'])) {
            $this->cookies = $overrides['cookies'];
        } elseif (isset($_COOKIE)) {
            $this->cookies = $_COOKIE;
        } else {
            $this->cookies = array();
        }

        // set HTTP params
        if (isset($overrides['params']) && is_array($overrides['params'])) {
            $this->params = $overrides['params'];
        } else {
            $this->params = array();

            if (isset($_GET)) {
                $this->params = array_merge($this->params, $_GET);
            }

            if (isset($_POST)) {
                $this->params = array_merge($this->params, $_POST);
            }
        }

        // set HTTP body
        if (isset($overrides['body'])) {
            $this->body = $overrides['body'];
        } else {
            $this->body = $this->getGlobalBody();
        }

        // set HTTP host
        if (isset($overrides['host'])) {
            $this->host = $overrides['host'];
        } else {
            $this->host = $this->getGlobalServerValue('HTTP_HOST');
        }

        // set IP of the client
        if (isset($overrides['IP'])) {
            $this->IP = $overrides['IP'];
        } else {
            $this->IP = $this->getGlobalServerValue('REMOTE_ADDR');
        }

        // set requested URI
        if (isset($overrides['URI'])) {
            $this->URI = $overrides['URI'];
        } else {
            $this->URI = $this->getGlobalServerValue('REQUEST_URI');
        }

        // set uploaded files (if any)
        if (isset($overrides['files'])) {
            $this->files = $overrides['files'];
        } elseif (isset($_FILES)) {
            $this->files = $_FILES;
        }
    }

    /**
     * Tries to get the requested param from the $_SERVER super global, otherwise returns an
     * empty string.
     *
     * @param string $param
     * @return string
     *
     * @since 3.0
     */
    private function getGlobalServerValue($param)
    {
        $server = $_SERVER;

        if (isset($server[$param])) {
            return $server[$param];
        } else {
            return '';
        }
    }

    /**
     * Get the HTTP method of this request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the HTTP method of this request.
     *
     * @param string $method
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 2.0
     */
    public function setMethod($method)
    {
        if (in_array($method, $this->HTTPMethods)) {
            $this->method = $method;
        } else {
            throw new IllegalArguementException('The method provided ['.$method.'] is not valid!');
        }
    }

    /**
     * Return all headers on this request.
     *
     * @return array
     *
     * @since 2.0
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the header matching the key provided.
     *
     * @param string $key     The key to search for
     * @param mixed  $default If key is not found, return this instead
     *
     * @return string
     *
     * @since 2.0
     */
    public function getHeader($key, $default = null)
    {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        } else {
            return $default;
        }
    }

    /**
     * Tries to get the current HTTP request headers from super globals.
     *
     * @return array
     *
     * @since 2.0
     */
    private function getGlobalHeaders()
    {
        if (!function_exists('getallheaders')) {
            $headers = array();
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
                if ($name == 'CONTENT_TYPE') {
                    $headers['Content-Type'] = $value;
                }
                if ($name == 'CONTENT_LENGTH') {
                    $headers['Content-Length'] = $value;
                }
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }

    /**
     * Return all cookies on this request.
     *
     * @return array
     *
     * @since 2.0
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Get the cookie matching the key provided.
     *
     * @param string $key     The key to search for
     * @param mixed  $default If key is not found, return this instead
     *
     * @return mixed
     *
     * @since 2.0
     */
    public function getCookie($key, $default = null)
    {
        if (array_key_exists($key, $this->cookies)) {
            return $this->cookies[$key];
        } else {
            return $default;
        }
    }

    /**
     * Return all params on this request.
     *
     * @return array
     *
     * @since 2.0
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the param matching the key provided.
     *
     * @param string $key     The key to search for
     * @param mixed  $default If key is not found, return this instead
     *
     * @return string
     *
     * @since 2.0
     */
    public function getParam($key, $default = null)
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        } else {
            return $default;
        }
    }

    /**
     * Append the hash array provided to the params for this request.
     *
     * @param array A hash array of values to add to the request params
     *
     * @since 2.0
     */
    public function addParams($params)
    {
        if (is_array($params)) {
            $this->params = array_merge($this->params, $params);
        }
    }

    /**
     * Set the params array.
     *
     * @param array A hash array of values to set as the request params
     *
     * @since 2.0
     */
    public function setParams($params)
    {
        if (is_array($params)) {
            $this->params = $params;
        }
    }

    /**
     * Return all files on this request.
     *
     * @return array
     *
     * @since 2.0
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the file matching the key provided.
     *
     * @param string $key     The key to search for
     * @param mixed  $default If key is not found, return this instead
     *
     * @return mixed
     *
     * @since 2.0
     */
    public function getFile($key, $default = null)
    {
        if (array_key_exists($key, $this->files)) {
            return $this->files[$key];
        } else {
            return $default;
        }
    }

    /**
     * Get the request body if one was provided.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Attempts to get the raw body of the current request from super globals.
     *
     * @return string
     *
     * @since 2.0
     */
    private function getGlobalBody()
    {
        if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return $GLOBALS['HTTP_RAW_POST_DATA'];
        } else {
            return file_get_contents('php://input');
        }
    }

    /**
     * Get the Accept header of the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getAccept()
    {
        return $this->getHeader('Accept');
    }

    /**
     * Get the Content-Type header of the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Get the Content-Length header of the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getContentLength()
    {
        return $this->getHeader('Content-Length');
    }

    /**
     * Get the host name of the client that sent the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the URI that was requested.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getURI()
    {
        return $this->URI;
    }

    /**
     * Get the URL that was requested.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getURL()
    {
        $config = ConfigProvider::getInstance();

        return $config->get('app.url').$this->getURI();
    }

    /**
     * Get the IP address of the client that sent the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getIP()
    {
        return $this->IP;
    }

    /**
     * Get the Referrer header of the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getReferrer()
    {
        return $this->getHeader('Referrer');
    }

    /**
     * Get the User-Agent header of the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getUserAgent()
    {
        return $this->getHeader('User-Agent');
    }

    /**
     * Get the query string provided on the request.
     *
     * @return string
     *
     * @since 2.0
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * Parses the route provided to extract matching params of the route from this request's URI.
     *
     * @param string $route         The route with parameter names, e.g. /user/{username}
     * @param array  $defaultParams Optional hash array of default request param values to use if they are missing from URI
     *
     * @since 2.0
     */
    public function parseParamsFromRoute($route, $defaultParams = array())
    {
        // if the URI has a query-string, we will ignore it for now
        if (mb_strpos($this->URI, '?') !== false) {
            $URI = mb_substr($this->URI, 0, mb_strpos($this->URI, '?'));

            // let's take this opportunity to pass query string params to $this->params
            $queryString = mb_substr($this->URI, (mb_strpos($this->URI, '?')+1));
            $this->queryString = $queryString;
            parse_str($queryString, $this->params);
        } else {
            $URI = $this->URI;
        }

        $paramNames = explode('/', $route);
        $paramValues = explode('/', $URI);

        for ($i = 0; $i < count($paramNames); ++$i) {
            $name = $paramNames[$i];

            if (!isset($this->params[trim($name, '{}')])) {
                if (isset($paramValues[$i]) && substr($name, 0, 1) == '{' && substr($name, strlen($name)-1, 1) == '}') {
                    $this->params[trim($name, '{}')] = $paramValues[$i];
                }
                if (!isset($paramValues[$i]) && isset($defaultParams[trim($name, '{}')])) {
                    $this->params[trim($name, '{}')] = $defaultParams[trim($name, '{}')];
                }
            }
        }
    }

    /**
     * Checks to see if the request contains a secure/encrypted token.
     *
     * @return bool
     *
     * @since 2.0
     */
    public function isSecureURI()
    {
        if (isset($this->params['act']) && mb_strpos($this->URI, '/tk/') !== false) {
            return true;
        } else {
            return false;
        }
    }
}
