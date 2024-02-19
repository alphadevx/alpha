<?php

namespace Alpha\Util\Service;

use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Logging\Logger;

/**
 * A factory for creating service instances that match the provider name and interface provided
 *
 * @since 3.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
class ServiceFactory
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 3.0
     */
    private static $logger = null;

    /**
     * A static array of service singletons in case any service needs to be accessed as a single instance.
     *
     * @var array
     *
     * @since 3.0
     */
    private static $singletons = array();

    /**
     * A static method that attempts to return a service provider instance
     * based on the name of the provider class supplied.  If the instance does not
     * implement the desired interface, an exception is thrown.
     *
     * @param string $serviceName The class name of the service class (fully qualified).
     * @param string $serviceInterface The interface name of the service class returned (fully qualified).
     * @param bool   $isSingleton Set to true if the service instance requested is a singleton, default is false (you get a new instance each time).
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 3.0
     */
    public static function getInstance(string $serviceName, string $serviceInterface, bool $isSingleton = false): mixed
    {
        // as the LogProviderInterface is itself a service, we don't call it's constructor again during instantiation
        if (self::$logger === null && $serviceInterface != 'Alpha\Util\Logging\LogProviderInterface') {
            self::$logger = new Logger('ServiceFactory');
        }

        if (self::$logger !== null) {
            self::$logger->debug('>>getInstance(serviceName=['.$serviceName.'], serviceInterface=['.$serviceInterface.'], isSingleton=['.$isSingleton.'])');
        }

        if (class_exists($serviceName)) {
            if ($isSingleton && in_array($serviceName, self::$singletons, true)) {
                return self::$singletons[$serviceName];
            }

            $instance = new $serviceName();

            if (!$instance instanceof $serviceInterface) {
                throw new IllegalArguementException('The class ['.$serviceName.'] does not implement the expected ['.$serviceInterface.'] interface!');
            }

            if ($isSingleton && !in_array($serviceName, self::$singletons, true)) {
                self::$singletons[$serviceName] = $instance;
            }

            if (self::$logger !== null) {
                self::$logger->debug('<<getInstance: [Object '.$serviceName.']');
            }

            return $instance;
        } else {
            if (self::$logger !== null) {
                self::$logger->debug('<<getInstance');
            }

            throw new IllegalArguementException('The class ['.$serviceName.'] is not defined anywhere!');
        }
    }
}
