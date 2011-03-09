<?php

// load config
if(!isset($config)) {
	require_once 'AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/LogFile.inc';

/**
 * Log class used for debug and exception logging
 * 
 * @package alpha::util
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class Logger {
	/**
	 * The log file the log entries will be saved to
	 *
	 * @var LogFile
	 * @since 1.0
	 */
	private $logfile;
	
	/**
	 * The logging level applied accross the system.  Valid options are DEBUG, INFO, WARN, ERROR and FATAL
	 *
	 * @var string
	 * @since 1.0
	 */
	private $level;
	
	/**
	 * The name of the class that this Logger is logging for
	 *
	 * @var string
	 * @since 1.0
	 */
	private $classname;
	
	/**
	 * An array of class names that will be logged at debug level, regardless of the global Logger::level setting
	 * 
	 * @var array
	 * @since 1.0
	 */
	private $debugClasses = array();
	
	/**
	 * The constructor
	 *
	 * @param string $classname
	 * @since 1.0
	 */
	public function __construct($classname) {		
		global $config;
		
		$this->classname = $classname;
		$this->level = $config->get('sysTraceLevel');
		$this->debugClasses = explode(',', $config->get('sysTraceDebugClasses'));
		$this->logfile = new LogFile($config->get('sysLogFile'));
		$this->logfile->setMaxSize($config->get('sysLogFileMaxSize'));
	}
	
	/**
	 * Log a DEBUG message
	 *
	 * @param string $message
	 * @since 1.0
	 */
	public function debug($message) {		
		if($this->level == 'DEBUG' || in_array($this->classname, $this->debugClasses)) {
			$dateTime = date("Y-m-d H:i:s");
			$this->logfile->writeLine(array($dateTime, 'DEBUG', $this->classname, $message, (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
		}
	}
	
	/**
	 * Log an INFO message
	 *
	 * @param string $message
	 * @since 1.0
	 */
	public function info($message) {
		if($this->level == 'DEBUG' || $this->level == 'INFO' || in_array($this->classname, $this->debugClasses)) {
			$dateTime = date("Y-m-d H:i:s");
			$this->logfile->writeLine(array($dateTime, 'INFO', $this->classname, $message, (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
		}
	}
	
	/**
	 * Log a WARN message
	 *
	 * @param string $message
	 * @since 1.0
	 */
	public function warn($message) {
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || in_array($this->classname, $this->debugClasses)) {
			$dateTime = date("Y-m-d H:i:s");
			$this->logfile->writeLine(array($dateTime, 'WARN', $this->classname, $message, (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
		}
	}
	
	/**
	 * Log an ERROR message
	 *
	 * @param string $message
	 * @since 1.0
	 */
	public function error($message) {
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR' || in_array($this->classname, $this->debugClasses)) {
			$dateTime = date("Y-m-d H:i:s");		
			$this->logfile->writeLine(array($dateTime, 'ERROR', $this->classname, $message, (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
		}
	}
	
	/**
	 * Log a FATAL message
	 *
	 * @param string $message
	 * @since 1.0
	 */
	public function fatal($message) {
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR' || $this->level == 'FATAL' || in_array($this->classname, $this->debugClasses)) {
			$dateTime = date("Y-m-d H:i:s");
			$this->logfile->writeLine(array($dateTime, 'FATAL', $this->classname, $message, (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
		}
	}
	
	/**
	 * Notify the sys admin via email when a serious error occurs
	 * 
	 * @param string $message
	 * @since 1.0
	 */
	public function notifyAdmin($message) {
		global $config;
		
		// just making sure an email address has been set in the .ini file
		if($config->get('sysErrorMailAddress') != '') {
			$body = "The following error has occured:\n\n";
		
			$body .= "Class:-> ".$this->classname."\n\n";
			$body .= "Message:-> ".$message."\n\n";			
			
			$body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('sysURL');
			
			mail($config->get('sysErrorMailAddress'), "Error in class ".$this->classname." on site .".$config->get('sysTitle'), $body, "From: ".$config->get('sysReplyTo')."\r\nReply-To: ".$config->get('sysReplyTo')."\r\nX-Mailer: PHP/" . phpversion());
		}
	}
	
	/**
	 * Allows you to set the log file path to one other than the main application log.
	 * 
	 * @param string $filepath
	 * @since 1.0
	 */
	public function setLogFile($filepath) {
		global $config;
		
		$this->logfile = new LogFile($filepath);
		$this->logfile->setMaxSize($config->get('sysLogFileMaxSize'));
	}
}

?>