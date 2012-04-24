<?php

require_once $config->get('sysRoot').'alpha/util/helpers/AlphaValidator.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';

/**
 *
 * A Key Performance Indicator (KPI) logging class
 * 
 * @package alpha::util
 * @since 1.1
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class AlphaKPI {
	/**
	 * The date/time of the KPI event
	 * 
	 * @var Timestamp
	 * @since 1.1
	 */
	private $timeStamp;
	
	/**
	 * The name of the KPI
	 * 
	 * @var String
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
	 * @throws IllegalArguementException
	 * @since 1.1
	 */
	public function __construct($name) {
		$this->name = new String();
		$this->name->setRule(AlphaValidator::REQUIRED_ALPHA_NUMBEIC);
		$this->name->setHelper('The KPI name can only contain letters and numbers');
		
		$this->name->setValue($name);
		
		$this->timeStamp = new Timestamp(date('Y-m-d H:i:s'));
		
		$this->startTime = microtime(true);
		
		if(!isset($_SESSION))
	 		session_start();
	 	
	 	// a startTime value may have been passed from a previous request
	 	if(isset($_SESSION[$name.'-startTime'])) {
	 		$this->startTime = $_SESSION[$name.'-startTime'];
	 		$_SESSION[$name.'-startTime'] = null;
	 	}
	 		
	 	$this->sessionID = session_id();
	}
	
	/**
	 * Stores the current startTime for the KPI in the session, useful for multi-request KPI tracking.
	 * 
	 * @since 1.0
	 */
	public function storeStartTimeInSession() {
		$_SESSION[$this->name->getValue().'-startTime'] = $this->startTime;
	}
	
	/**
	 * Writes the KPI event to a log file named logs/kpi-'.$this->name->getValue().'.csv, which will be created if it does
	 * not exist.
	 * 
	 * @since 1.1
	 */
	public function log() {
		global $config;
		
		$this->endTime = microtime(true);
		
		$this->duration = $this->endTime - $this->startTime;
		
		$logfile = new LogFile($config->get('sysRoot').'logs/kpi-'.$this->name->getValue().'.csv');
		
		$logfile->setMaxSize($config->get('sysLogFileMaxSize'));
		$logfile->setSeperator(',');
		
		$logfile->writeLine(array($this->timeStamp, $this->name->getValue(), $this->sessionID, $this->startTime, $this->endTime, 
			$this->duration));
	}
	
	/**
	 * Writes a step in the KPI event to a log file named logs/kpi-'.$this->name->getValue().'.csv, which will be created if it does
	 * not exist.
	 * 
	 * @since 1.1
	 */
	public function logStep($stepName) {
		global $config;
		
		$this->endTime = microtime(true);
		
		$this->duration = $this->endTime - $this->startTime;
		
		$logfile = new LogFile($config->get('sysRoot').'logs/kpi-'.$this->name->getValue().'.csv');
		
		$logfile->setMaxSize($config->get('sysLogFileMaxSize'));
		$logfile->setSeperator(',');
		
		$logfile->writeLine(array($this->timeStamp, $this->name->getValue().' ['.$stepName.']', $this->sessionID, $this->startTime, 
			$this->endTime, $this->duration));
	}
}

?>