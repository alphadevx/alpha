<?php

namespace Alpha\Model;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\IllegalArguementException;

/**
 * A factory for creating active record provider implementations that implement the
 * ActiveRecordProviderInterface interface.
 *
 * @since 1.1
 *
 * @author John Collins <dev@alphaframework.org>
 *
 * @version $Id: ActiveRecordProviderFactory.php 1842 2014-11-12 22:27:01Z alphadevx $
 *
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
class ActiveRecordProviderFactory
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.1
     */
    private static $logger = null;

    /**
     * A static method that attempts to return a ActiveRecordProviderInterface instance
     * based on the name of the provider class supplied.
     *
     * @param string $providerName The fully-qualified class name of the provider class.
     * @param ActiveRecord $Record The (optional) active record instance to pass to the persistance provider for mapping.
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @return \Alpha\Model\ActiveRecordProviderInterface
     *
     * @since 1.1
     */
    public static function getInstance($providerName, $Record = null)
    {
        if (self::$logger == null) {
            self::$logger = new Logger('ActiveRecordProviderFactory');
        }

        self::$logger->debug('>>getInstance(providerName=['.$providerName.'], Record=['.print_r($Record, true).'])');

        if (class_exists($providerName)) {
            $instance = new $providerName();

            if (!$instance instanceof ActiveRecordProviderInterface) {
                throw new IllegalArguementException('The class ['.$providerName.'] does not implement the expected ActiveRecordProviderInterface interface!');
            }

            if ($Record instanceof ActiveRecord) {
                $instance->setRecord($Record);
            }

            self::$logger->debug('<<getInstance: [Object '.$providerName.']');

            return $instance;
        } else {
            self::$logger->debug('<<getInstance');
            throw new IllegalArguementException('The class ['.$providerName.'] is not defined anywhere!');
        }
    }
}
