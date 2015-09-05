<?php

namespace Alpha\Util\Http\Filter;

use Alpha\Util\Logging\Logger;
use Alpha\Model\BlacklistedClient;
use Alpha\Exception\BONotFoundException;
use Alpha\Exception\ResourceNotAllowedException;

/**
 * Class for filtering requests from blacklisted HTTP clients.
 *
 * @since 1.0
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
class ClientBlacklistFilter implements FilterInterface
{
    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ClientBlacklistFilter');
    }

    /**
     * {@inheritdoc}
     */
    public function process($request)
    {
        $client = $request->getUserAgent();

        // if no user agent string is provided, we can't filter by it anyway to might as well skip
        if ($client == null) {
            return;
        }

        if (!empty($client)) {
            $badClient = new BlacklistedClient();
            try {
                $badClient->loadByAttribute('client', $client);
            } catch (BONotFoundException $bonf) {
                // client is not on the list!
                return;
            }
            // if we got this far then the client is bad
            self::$logger->warn('The client ['.$client.'] was blocked from accessing the resource ['.$request->getURI().']');
            throw new ResourceNotAllowedException('Not allowed!');
        }
    }
}
