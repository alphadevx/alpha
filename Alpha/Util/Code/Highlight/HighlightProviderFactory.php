<?php

namespace Alpha\Util\Code\Highlight;

use Alpha\Util\Logging\Logger;
use Alpha\Exception\IllegalArguementException;

/**
 * A factory for generating instances of the AlphaCodeHighlighterInterface,
 * based on the cms.highlight.provider.name setting.
 *
 * @since 1.2
 *
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
 */
class HighlightProviderFactory
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.2
     */
    private static $logger = null;

    /**
     * A static method that attempts to return a HighlightProviderInterface instance
     * based on the name of the provider class supplied.
     *
     * @param $providerName The fully-qualified class name of the provider class, should implement Alpha\Util\Code\Highlight\HighlightProviderInterface
     *
     * @throws Alpha\Exception\IllegalArguementException
     *
     * @return Alpha\Util\Code\Highlight\HighlightProviderInterface
     *
     * @since 1.2
     */
    public static function getInstance($providerName)
    {
        if (self::$logger == null) {
            self::$logger = new Logger('HighlightProviderFactory');
        }

        self::$logger->debug('>>getInstance(providerName=['.$providerName.'])');

        if (class_exists($providerName)) {
            $instance = new $providerName();

            if (!$instance instanceof HighlightProviderInterface) {
                throw new IllegalArguementException('The class ['.$providerName.'] does not implement the expected HighlightProviderInterface interface!');
            }

            self::$logger->debug('<<getInstance: [Object '.$providerName.']');

            return $instance;
        } else {
            throw new IllegalArguementException('The class ['.$providerName.'] is not defined anywhere!');
        }

        self::$logger->debug('<<getInstance');
    }
}
