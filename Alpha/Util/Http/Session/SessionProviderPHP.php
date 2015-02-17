<?php

namespace Alpha\Util\Http\Session;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;

/**
 *
 * Provides a session handle that stores session data in $_SESSION, the default PHP implementation.
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
class SessionProviderPHP implements SessionProviderInterface
{
	/**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 2.0
     */
    private static $logger = null;

    /**
     * Constructor
     *
     * @since 2.0
     */
    public function __construct()
    {
        self::$logger = new Logger('SessionProviderPHP');
    }

	/**
	 * {@inheritDoc}
	 */
	public function init()
	{
		if (session_id() == '' && !headers_sent()) {
            $config = ConfigProvider::getInstance();
    		$url = parse_url($config->get('app.url'));
    	 	$hostname = $url['host'];
    	 	session_set_cookie_params(0, '/', $hostname, false, true);
    	 	session_start();
        }
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy()
	{
		$_SESSION = array();
		session_destroy();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($key)
	{
		self::$logger->debug('>>get(key=['.$key.'])');

        self::$logger->debug('Getting value for key ['.$key.']');

        if (array_key_exists($key, $_SESSION))
            return $_SESSION[$key];
        else
            return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value)
	{
		self::$logger->debug('Setting value for key ['.$key.']');

        $_SESSION[$key] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($key)
	{
		self::$logger->debug('Removing value for key ['.$key.']');

        unset($_SESSION[$key]);
	}

    /**
     * {@inheritDoc}
     */
    public function getID()
    {
        return session_id();
    }
}

?>