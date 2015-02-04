<?php

namespace Alpha\Util\Http;

use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;

/**
 * A class to encapsulate a HTTP Response
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
class Response
{
    /**
     * The body of the response
     *
     * @var string
     * @since 2.0
     */
    private $body;

    /**
     * The status code of the response
     *
     * @var int
     * @since 2.0
     */
    private $status;

    /**
     * Array of supported HTTP response codes
     *
     * @var array
     * @since 2.0
     */
    private $HTTPStatusCodes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    );

    /**
     * An associative array of headers for the response
     *
     * @var array
     * @since 2.0
     */
    private $headers;

    /**
     * An associative array of HTTP cookies on this response
     *
     * @var array
     * @since 2.0
     */
    private $cookies;

    /**
     * Build the response
     *
     * @param int $status The HTTP status code of the response.
     * @param string $body The body of the response (optional).
     * @param array $headers The headers to set on the response (optional).
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function __construct($status, $body = null, $headers = array())
    {
        $this->headers = $headers;

        if (isset($body))
            $this->body = $body;

        if (array_key_exists($status, $this->HTTPStatusCodes))
            $this->status = $status;
        else
            throw new IllegalArguementException('The status code provided ['.$status.'] is invalid');
    }

    /**
     * Get the response body
     *
     * @return string|null
     * @since 2.0
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the response body
     *
     * @param string $body The response body.
     * @since 2.0
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get the status code of the response
     *
     * @return int
     * @since 2.0
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the status message of the response
     *
     * @return string
     * @todo
     */
    public function getStatusMessage()
    {
        return $this->HTTPStatusCodes[$this->status];
    }

    /**
     * Set the status code of the response
     *
     * @param int $status The response code.
     * @throws Alpha\Exception\IllegalArguementException
     * @since 2.0
     */
    public function setStatus($status)
    {
        if (array_key_exists($status, $this->HTTPStatusCodes))
            $this->status = $status;
        else
            throw new IllegalArguementException('The status code provided ['.$status.'] is invalid');
    }

    /**
     * Set a header key/value tuple for the response
     *
     * @param string $header The header key name.
     * @param string $value The header value.
     * @since 2.0
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Get all of the headers for the response
     *
     * @return array
     * @since 2.0
     */
    public function getHeaders()
    {
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
     * Set a cookie key/value tuple for the response
     *
     * @param string $cookie The cookie key name.
     * @param string $value The cookie value.
     * @since 2.0
     * @todo
     */
    public function setCookie($cookie, $value)
    {

    }

    /**
     * Get all of the cookies for the response
     *
     * @return array
     * @since 2.0
     * @todo
     */
    public function getCookies()
    {

    }

    /**
     * Get the content length of the response
     *
     * @return int
     * @since 2.0
     * @todo
     */
    public function getContentLength()
    {

    }

    /**
     * Send a redirect response to the client
     *
     * @param string $URL The URL to redirect the client to.
     * @param int $status The HTTP response code to use for the request (should be valid per HTTP spec).
     * @since 2.0
     * @todo
     */
    public function redirect($URL, $status)
    {

    }

    /**
     * Sends the current response to standard output
     *
     * @since 2.0
     * @todo
     */
    public function send()
    {

    }
}

?>