<?php

namespace Alpha\Util\Http\Filter;

use Alpha\Util\Logging\Logger;
use Alpha\Model\BlacklistedIP;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;

/**
 * Class for blocking requests from blacklisted IP addresses.
 *
 * @since 1.2
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
class IPBlacklistFilter implements FilterInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger;
     *
     * @since 1.2
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.2
     */
    public function __construct()
    {
        self::$logger = new Logger('IPBlacklistFilter');
    }

    /**
     * {@inheritdoc}
     */
    public function process($request)
    {
        $ip = $request->getIP();

        if (!empty($ip)) {
            $badIP = new BlacklistedIP();

            try {
                $badIP->loadByAttribute('IP', $ip);
            } catch (RecordNotFoundException $bonf) {
                // ip is not on the list!
                return;
            }

            // if we got this far then the IP is bad
            self::$logger->warn('The IP ['.$ip.'] was blocked from accessing the resource ['.$request->getURI().']');
            throw new ResourceNotAllowedException('Not allowed!');
        }
    }
}
