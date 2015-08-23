<?php

namespace Alpha\Util\Logging;

use Alpha\Model\Type\Timestamp;
use Alpha\Model\Type\String;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\Session\SessionProviderFactory;

/**
 *
 * A Key Performance Indicator (KPI) logging class
 *
 * @since 1.1
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
class KPI
{
    /**
     * The date/time of the KPI event
     *
     * @var Alpha\Model\Type\Timestamp
     * @since 1.1
     */
    private $timeStamp;

    /**
     * The name of the KPI
     *
     * @var Alpha\Model\Type\String
     * @since 1.1
     */
    private $name;

    /**
     * The session ID of the current HTTP session
     *
     * @var string
     * @since 1.1
     */
    private $sessionID;

    /**
     * The start time of the KPI event (UNIX timestamp in seconds)
     *
     * @var float
     * @since 1.1
     */
    private $startTime;

    /**
     * The end time of the KPI event (UNIX timestamp in seconds)
     *
     * @var float
     * @since 1.1
     */
    private $endTime;

    /**
     * The duration in seconds
     *
     * @var float
     * @since 1.1
     */
    private $duration;

    /**
     * Constructor
     *
     * @param string $name The name of the KPI which is used in the log files, must only be letters and/or numbers.
     * @throws Alpha\Exception\IllegalArguementException
     * @since 1.1
     */
    public function __construct($name)
    {
        $config = ConfigProvider::getInstance();

        $this->name = new String();
        $this->name->setRule(Validator::REQUIRED_ALPHA_NUMERIC);
        $this->name->setHelper('The KPI name can only contain letters and numbers');

        $this->name->setValue($name);

        $this->timeStamp = new Timestamp(date('Y-m-d H:i:s'));

        $this->startTime = microtime(true);

        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        // a startTime value may have been passed from a previous request
        if ($session->get($name.'-startTime') !== false) {
            $this->startTime = $session->get($name.'-startTime');
            $session->delete($name.'-startTime');
        }

        $this->sessionID = $session->getID();
    }

    /**
     * Stores the current startTime for the KPI in the session, useful for multi-request KPI tracking.
     *
     * @since 1.0
     */
    public function storeStartTimeInSession()
    {
        $config = ConfigProvider::getInstance();
        $sessionProvider = $config->get('session.provider.name');
        $session = SessionProviderFactory::getInstance($sessionProvider);

        $session->set($this->name->getValue().'-startTime', $this->startTime);
    }

    /**
     * Writes the KPI event to a log file named logs/kpi-'.$this->name->getValue().'.csv, which will be created if it does
     * not exist.
     *
     * @since 1.1
     */
    public function log()
    {
        $config = ConfigProvider::getInstance();

        $this->endTime = microtime(true);

        $this->duration = $this->endTime - $this->startTime;

        $logfile = new LogFile($config->get('app.file.store.dir').'logs/kpi-'.$this->name->getValue().'.csv');

        $logfile->setMaxSize($config->get('app.log.file.max.size'));

        $logfile->writeLine(array($this->timeStamp, $this->name->getValue(), $this->sessionID, $this->startTime, $this->endTime, $this->duration));
    }

    /**
     * Writes a step in the KPI event to a log file named logs/kpi-'.$this->name->getValue().'.csv, which will be created if it does
     * not exist.
     *
     * @since 1.1
     */
    public function logStep($stepName)
    {
        $config = ConfigProvider::getInstance();

        $this->endTime = microtime(true);

        $this->duration = $this->endTime - $this->startTime;

        $logfile = new LogFile($config->get('app.file.store.dir').'logs/kpi-'.$this->name->getValue().'.csv');

        $logfile->setMaxSize($config->get('app.log.file.max.size'));

        $logfile->writeLine(array($this->timeStamp, $this->name->getValue().' ['.$stepName.']', $this->sessionID, $this->startTime, $this->endTime, $this->duration));
    }
}

?>