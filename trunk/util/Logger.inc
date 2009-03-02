<?php

// load config
require_once 'configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/LogFile.inc';

class Logger {
	private $logfile;
	private $level;
	private $classname;
	
	public function __construct($classname) {
		global $config;
		
		$this->classname = $classname;
		$this->level = $config->get('sysTraceLevel');
		$this->logfile = new LogFile($config->get('sysLogFile'));
		$this->logfile->setMaxSize($config->get('sysLogFileMaxSize'));
	}
	
	public function debug($message) {		
		$dateTime = date("Y-m-d H:i:s");		
		
		if($this->level == 'DEBUG')
			$this->logfile->writeLine(array($dateTime, 'DEBUG', $this->classname, $message));
	}
	
	public function info($message) {
		$dateTime = date("Y-m-d H:i:s");		
		
		if($this->level == 'DEBUG' || $this->level == 'INFO')
			$this->logfile->writeLine(array($dateTime, 'INFO', $this->classname, $message));
	}
	
	public function warn($message) {
		$dateTime = date("Y-m-d H:i:s");		
		
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN')
			$this->logfile->writeLine(array($dateTime, 'WARN', $this->classname, $message));
	}
	
	public function error($message) {
		$dateTime = date("Y-m-d H:i:s");		
		
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR')
			$this->logfile->writeLine(array($dateTime, 'ERROR', $this->classname, $message));
	}
	
	public function fatal($message) {
		$dateTime = date("Y-m-d H:i:s");		
		
		if($this->level == 'DEBUG' || $this->level == 'INFO' || $this->level == 'WARN' || $this->level == 'ERROR' || $this->level == 'FATAL')
			$this->logfile->writeLine(array($dateTime, 'FATAL', $this->classname, $message));
	}
	
	/**
	 * Notify the sys admin when a serious error occurs
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
}

?>