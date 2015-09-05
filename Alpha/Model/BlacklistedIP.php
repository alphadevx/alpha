<?php

namespace Alpha\Model;

use Alpha\Model\Type\String;
use Alpha\Util\Logging\Logger;

/**
 * An IP address that is blacklisted from accessing this application.
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
class BlacklistedIP extends ActiveRecord
{
    /**
     * The (unique) IP address that is blocked.
     *
     * @var Alpha\Model\Type\String
     *
     * @since 1.2
     */
    protected $IP;

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.2
     */
    protected $dataLabels = array('OID' => 'Blacklisted IP ID#','IP' => 'IP Address');

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.2
     */
    const TABLE_NAME = 'BlacklistedIP';

    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.2
     */
    private static $logger = null;

    /**
     * Constructor for the class.
     *
     * @since 1.2
     */
    public function __construct()
    {
        self::$logger = new Logger('BlacklistedIP');

        // ensure to call the parent constructor
        parent::__construct();

        $this->IP = new String();
        $this->markUnique('IP');
    }
}
