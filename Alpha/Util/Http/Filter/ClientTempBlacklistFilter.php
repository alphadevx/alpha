<?php

namespace Alpha\Util\Http\Filter;

use Alpha\Util\Logging\Logger;
use Alpha\Model\BadRequest;
use Alpha\Exception\BONotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Util\Config\ConfigProvider;

/**
 * Class for filtering requests from temporariy blacklisted HTTP clients
 *
 * @since 1.0
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
class ClientTempBlacklistFilter implements FilterInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ClientTempBlacklistFilter');
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $config = ConfigProvider::getInstance();

        // if no user agent string or IP are provided, we can't filter by these anyway to might as well skip
        if(!isset($_SERVER['HTTP_USER_AGENT']) || !isset($_SERVER['REMOTE_ADDR']))
            return;

        $client = $_SERVER['HTTP_USER_AGENT'];
        $IP = $_SERVER['REMOTE_ADDR'];

        if (!empty($client) && !empty($IP)) {
            $request = new BadRequest();
            $request->set('client', $client);
            $request->set('IP', $IP);
            $badRequestCount = $request->getBadRequestCount();

            if ($badRequestCount >= $config->get('security.client.temp.blacklist.filter.limit')) {
                // if we got this far then the client is bad
                self::$logger->warn('The client ['.$client.'] was blocked from accessing the resource ['.$_SERVER['REQUEST_URI'].'] on a temporary basis');
                throw new ResourceNotAllowedException('Not allowed!');
            }
        }
    }
}

?>