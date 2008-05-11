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
	 * The error type (validation, warning, php, framework, other)
	 * The error type (AlphaValidationException, AlphaWarningException, AlphaPHPException,
	 * AlphaFrameworkException, AlphaException)
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * The constructor
	 *
	 * @param string $message	 
	 */
	public function __construct($message) {
		parent::__construct($message);
		
		$this->type = $type;
		$this->display_error();
	}
	
	/**
	 * Displays the error message in HTML format
	 */
	protected function display_error() {
		global $config;
		
		switch (get_class($this)) {
			case "AlphaValidationException":
				if($config->get('sysErrorValidationDisplay'))
					echo '<p class="error"><br>Validation error: '.$this->getMessage().'</p>';
				if($config->get('sysErrorValidationLog'))
					$this->log_error_to_file();
			break;
			case "AlphaWarningException":
				if($config->get('sysErrorWarningDisplay'))
					echo '<p class="error"><br>Warning: '.$this->getMessage().'</p>';
				if($config->get('sysErrorWarningLog'))
					$this->log_error_to_file();
			break;
			case "AlphaPHPException":
				if($config->get('sysErrorPhpDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorPhpLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
			case "AlphaFrameworkException":
				if($config->get('sysErrorFrameworkDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorFrameworkLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
			case "other":
				if($config->get('sysErrorOtherDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->getFile().'], message: <br>The exception generated the following error message: ['.$this->getMessage().']</p>';	
				if($config->get('sysErrorOtherLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
		}
	}
	
	/**
	 * Method to log an error to a text file
	 */
	protected function log_error_to_file() {
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
	protected function notify_admin() {
		global $config;
		
		// just making sure an email address has been set in the .ini file
		if($config->get('sysErrorMailAddress') != "") {
			$body = "The following error has occured:\n\n";
		
			$body .= "File:-> ".$this->sys_file."\n\n";
			$body .= "Message:-> ".$this->message."\n\n";
			$body .= "Method:-> ".$this->method."\n\n";
			$body .= "Type:-> ".$this->type."\n\n";
			
			$body .= "\n\nKind regards,\n\nAdministrator\n--\n".$config->get('sysURL');
			
			mail($config->get('sysErrorMailAddress'), "Error in file ".$this->sys_file." on site .".$config->get('sysTitle'), $body, "From: ".$config->get('sysReplyTo')."\r\nReply-To: ".$config->get('sysReplyTo')."\r\nX-Mailer: PHP/" . phpversion());
		}
	}
}

?>