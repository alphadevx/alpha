<?php

namespace Alpha\Util\Http\Session;

use Alpha\Util\Logging\Logger;

/**
 * Provides a session handle that stores session data in an array, useful for testing only.
 *
 * @since 2.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class SessionProviderArray implements SessionProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 2.0
     */
    private static $logger = null;

    /**
     * The hash array containing the session items.
     *
     * @var array
     *
     * @since 2.0
     */
    public static $sessionArray = array();

    /**
     * The current session ID.
     *
     * @var string
     *
     * @since 2.0
     */
    private $ID;

    /**
     * Constructor.
     *
     * @since 2.0
     */
    public function __construct()
    {
        self::$logger = new Logger('SessionProviderArray');
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->ID = uniqid();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy()
    {
        self::$sessionArray = array();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        self::$logger->debug('>>get(key=['.$key.'])');

        self::$logger->debug('Getting value for key ['.$key.']');

        if (array_key_exists($key, self::$sessionArray)) {
            return self::$sessionArray[$key];
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        self::$logger->debug('Setting value for key ['.$key.']');

        self::$sessionArray[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        self::$logger->debug('Removing value for key ['.$key.']');

        unset(self::$sessionArray[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getID()
    {
        return $this->ID;
    }
}
