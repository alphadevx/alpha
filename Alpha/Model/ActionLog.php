<?php

namespace Alpha\Model;

use Alpha\Model\Type\String;
use Alpha\Model\Type\Relation;
use Alpha\Util\Logging\Logger;

/**
 * An action carried out be a person using the system can be logged using this class.  Best
 * to call via Logger::action() method rather than directly here.
 *
 * @since 1.2.2
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
class ActionLog extends ActiveRecord
{
    /**
     * The HTTP user-agent client string.
     *
     * @var Alpha\Model\Type\String
     *
     * @since 1.2.2
     */
    protected $client;

    /**
     * The IP of the client.
     *
     * @var Alpha\Model\Type\String
     *
     * @since 1.2.2
     */
    protected $IP;

    /**
     * The action carried out by the person should be described here.
     *
     * @var Alpha\Model\Type\String
     *
     * @since 1.2.2
     */
    protected $message;

    /**
     * The person who carried out the action.
     *
     * @var Alpha\Model\Person
     *
     * @since 2.0
     */
    protected $personOID;

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 1.2.2
     */
    protected $dataLabels = array('OID' => 'Action Log ID#','client' => 'Client string','IP' => 'IP address','message' => 'Message','personOID' => 'Owner');

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 1.2.2
     */
    const TABLE_NAME = 'ActionLog';

    /**
     * Trace logger.
     *
     * @var Alpha\Util\Logging\Logger
     *
     * @since 1.2.2
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('ActionLog');

        // ensure to call the parent constructor
        parent::__construct();

        $this->client = new String();
        $this->IP = new String();
        $this->message = new String();

        $this->personOID = new Relation();
        $this->personOID->setRelatedClass('Alpha\Model\Person');
        $this->personOID->setRelatedClassField('OID');
        $this->personOID->setRelatedClassDisplayField('displayName');
        $this->personOID->setRelationType('MANY-TO-ONE');
        $this->personOID->setValue($this->created_by->getValue());
    }
}
