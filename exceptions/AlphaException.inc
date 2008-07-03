<?php

// load config
if(!isset($config))
	require_once '../utils/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/log_file.inc';

/**
 * The parent exception class for Alpha
 * 
 * @package Alpha Core Exceptions
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class AlphaException extends Exception {
	/**
	 * The level of the error. Can be sysErrorValidation, sysErrorWarning, sysErrorPhp,
	 * sysErrorFramework, sysErrorOther
	 *
	 * @var unknown_type
	 */
	protected $errorLevel;
	
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {
		parent::__construct($message);		
		
		$this->displayError();
	}
	
	/**
	 * Displays the error message in HTML format
	 */
	public function displayError() {
		global $config;
		
		switch ($this->errorLevel) {
			case 'sysErrorValidation':
				if($config->get('sysErrorValidationDisplay'))
					echo '<p class="error"><br>Validation error: '.$this->getMessage().'</p>';
				if($config->get('sysErrorValidationLog'))
					$this->logErrorToFile();
			break;
			case 'sysErrorWarning':
				if($config->get('sysErrorWarningDisplay'))
					echo '<p class="error"><br>Warning: '.$this->getMessage().'</p>';
				if($config->get('sysErrorWarningLog'))
					$this->logErrorToFile();
			break;
			case 'sysErrorPhp':
				if($config->get('sysErrorPhpDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorPhpLog'))
					$this->logErrorToFile();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notifyAdmin();
			break;
			case 'sysErrorFramework':
				if($config->get('sysErrorFrameworkDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorFrameworkLog'))
					$this->logErrorToFile();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notifyAdmin();
			break;
			case 'sysErrorOther':
				if($config->get('sysErrorOtherDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorOtherLog'))
					$this->logErrorToFile();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notifyAdmin();
			break;
		}
	}
	
	/**
	 * Method to log an error to a text file
	 */
	protected function logErrorToFile() {
		global $config;

		//sets up the date and time variables		
		$dateTime = date("Y-m-d H:i:s");		
		$ip = $_SERVER["REMOTE_ADDR"];
		$host = gethostbyaddr($ip);
		$browser = $_SERVER["HTTP_USER_AGENT"];
		
		$sys_file = $this->getFile();
		$trace = $this->getTraceAsString();
		
		// before writting the error to the log file, will will strip out any line returns from the message
		$message = str_replace("\n", "", $this->getMessage());
		
		$error_log = new log_file($config->get('sysErrorLogFile'));
		$error_log->set_max_size($config->get('sysErrorLogFileMaxEntires'));	
		$error_log->write_line(array($dateTime, get_class($this), $sys_file, $trace, $this->getMessage(), $ip, $host, $browser));
	}
	
	/**
	 * Notify the sys admin when a serious error occurs
	 */
	protected function notifyAdmin() {
		global $config;
		
		// just making sure an email address has been set in the .ini file
		if($config->get('sysErrorMailAddress') != "") {
			$body = "The following error has occured:\n\n";
		
			$body .= "File:-> ".$this->getFile()."\n\n";
			$body .= "Message:-> ".$this->getMessage()."\n\n";
			$body .= "Stack trace:-> ".$this->getTraceAsString()."\n\n";
			$body .= "Type:-> ".get_class($this)."\n\n";
			
			$body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('sysURL');
			
			mail($config->get('sysErrorMailAddress'), "Error in file ".$this->getFile()." on site .".$config->get('sysTitle'), $body, "From: ".$config->get('sysReplyTo')."\r\nReply-To: ".$config->get('sysReplyTo')."\r\nX-Mailer: PHP/" . phpversion());
		}
	}
	
	/**
	 * Set the message for the exception
	 *
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}
	
	/**
	 * Set the filename for the exception
	 *
	 * @param string $file
	 */
	public function setFile($file) {
		$this->file = $file;
	}
}

?>