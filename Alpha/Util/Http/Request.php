<?php

namespace Alpha\Util\Http;

use Alpha\Exception\IllegalArguementException;

/**
 * A class to encapsulate a HTTP request
 *
 * @since 2.0
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

class Request
{
    /**
     * Array of supported HTTP methods
     *
     * @var array
     * @since 2.0
     */
    private $HTTPMethods = array('HEAD','GET','POST','PUT','PATCH','DELETE','OPTIONS');

    /**
     * The HTTP method of this request (must be in HTTPMethods array)
     *
     * @var string
     * @since 2.0
     */
    private $method;

    /**
     * An array of HTTP headers on this request
     *
     * @var array
     * @since 2.0
     */
    private $headers;

    /**
     * An array of HTTP cookies on this request
     *
     * @var array
     * @since 2.0
     */
    private $cookies;

    /**
     * The HTTP params (form data and query string) on this request
     *
     * @var array
     * @since 2.0
     */
    private $params;

    /**
     * The request body if one was provided
     *
     * @var string
     * @since 2.0
     */
    private $body;

    /**
     * Builds up the request based on available PHP super globals, in addition to
     * any overrides provided (useful for testing).
     *
     * @param array $overrides Hash array of PHP super globals to override
     * @throws Alpha\Exception\IllegalArguementException
     * @since 2.0
     * @todo
     */
    public function __construct($overrides = array())
    {
        // set HTTP method
        if (isset($overrides['method']) && in_array($overrides['method'], $this->HTTPMethods))
            $this->method = $overrides['method'];
        elseif (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], $this->HTTPMethods))
            $this->method = $_SERVER['REQUEST_METHOD'];

        if ($this->method == '')
            throw new IllegalArguementException('No valid HTTP method provided when creating new Request object');

        // set HTTP headers
        if (isset($overrides['headers']) && is_array($overrides['headers']))
            $this->headers = $overrides['headers'];
        else
            $this->headers = $this->getGlobalHeaders();

        // set HTTP cookies
        if (isset($overrides['cookies']) && is_array($overrides['cookies']))
            $this->cookies = $overrides['cookies'];
        elseif (isset($_COOKIE))
            $this->cookies = $_COOKIE;
        else
            $this->cookies = array();

        // set HTTP params
        if (isset($overrides['params']) && is_array($overrides['params'])) {
            $this->params = $overrides['params'];
        } else {
            $this->params = array();

            if (isset($_GET))
                $this->params = array_merge($this->params, $_GET);

            if (isset($_POST))
                $this->params = array_merge($this->params, $_POST);
        }
    }

    /**
     * Get the HTTP method of this request
     *
     * @return string
     * @since 2.0
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return all headers on this request
     *
     * @return array
     * @since 2.0
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Get the header matching the key provided
     *
     * @param string $key The key to search for
     * @param mixed $default If key is not found, return this instead
     * @return mixed
     * @since 2.0
     */
    public function getHeader($key, $default = null)
    {
        if (array_key_exists($key, $this->headers))
            return $this->headers[$key];
        else
            return $default;
    }

    /**
     * Tries to get the current HTTP request headers from supoer globals
     *
     * @return array
     * @since 2.0
     */
    private function getGlobalHeaders()
    {
        if (!function_exists('getallheaders')) {
            $headers = array();
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_')
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                if ($name == 'CONTENT_TYPE')
                    $headers['Content-Type'] = $value;
                if ($name == 'CONTENT_LENGTH')
                    $headers['Content-Length'] = $value;
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }

    /**
     * Return all cookies on this request
     *
     * @return array
     * @since 2.0
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Get the cookie matching the key provided
     *
     * @param string $key The key to search for
     * @param mixed $default If key is not found, return this instead
     * @return mixed
     * @since 2.0
     */
    public function getCookie($key, $default = null)
    {
        if (array_key_exists($key, $this->cookies))
            return $this->cookies[$key];
        else
            return $default;
    }

    /**
     * Return all params on this request
     *
     * @return array
     * @since 2.0
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the param matching the key provided
     *
     * @param string $key The key to search for
     * @param mixed $default If key is not found, return this instead
     * @return mixed
     * @since 2.0
     */
    public function getParam($key, $default = null)
    {
        if (array_key_exists($key, $this->params))
            return $this->params[$key];
        else
            return $default;
    }

    /**
     * Get the request body if one was provided
     *
     * @return string
     * @since 2.0
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the Content-Type header of the request
     *
     * @return string
     * @since 2.0
     */
    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Get the Content-Length header of the request
     *
     * @return string
     * @since 2.0
     */
    public function getContentLength()
    {
        return $this->getHeader('Content-Length');
    }

    /**
     * Get the host name of the client that sent the request
     *
     * @return string
     * @since 2.0
     * @todo
     */
    public function getHost()
    {

    }

    /**
     * Get the URL that was requested
     *
     * @return string
     * @since 2.0
     * @todo
     */
    public function getURL()
    {

    }

    /**
     * Get the IP address of the client that sent the request
     *
     * @return string
     * @since 2.0
     * @todo
     */
    public function getIP()
    {

    }

    /**
     * Get the Referrer header of the request
     *
     * @return string
     * @since 2.0
     */
    public function getReferrer()
    {
        return $this->getHeader('Referrer');
    }

    /**
     * Get the User-Agent header of the request
     *
     * @return string
     * @since 2.0
     */
    public function getUserAgent()
    {
        return $this->getHeader('User-Agent');
    }
}

?>