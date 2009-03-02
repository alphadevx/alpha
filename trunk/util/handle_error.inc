<?php

// $Id$

// load config
require_once 'configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/LogFile.inc';

/**
 *
 * Generic error handling class used to display error messages and log errors
 * 
 * @package Alpha Core Error
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins 
 * @todo Implement database logging
 * @deprecated Use exceptions instead!
 */
 class handle_error {
	/**
	 * the name of the file that caused the error
	 * @var string
	 */
	var $sys_file;
	/**
	 * the error message passed
	 * @var string
	 */
	var $message;
	/**
	 * the name of the method that caused the error
	 * @var string
	 */
	var $method;
	/**
	 * the error type (validation, warning, php, framework, other)
	 * @var string
	 */
	var $type;
	
	/**
	 * a constructor for the class that takes the error message, and calls the display_error method
	 * @param string $sys_file The file causing the error
	 * @param string $message The error message
	 * @param string $method The method causing the error
	 * @param string $type The type of error, options are: validation, warning, php, framework, other
	 */
	function handle_error($sys_file, $message, $method, $type="other") {
		$this->sys_file = $sys_file;
		$this->message = $message;		
		$this->method = $method;
		$this->type = $type;
		
		$this->display_error();		
	}
	
	/**
	 * displays the error message in HTML format
	 */
	function display_error() {
		global $config;
		
		switch ($this->type) {
			case "validation":
				if($config->get('sysErrorValidationDisplay'))
					echo '<p class="error"><br>Validation error: '.$this->message.'</p>';
				if($config->get('sysErrorValidationLog'))
					$this->log_error_to_file();
			break;
			case "warning":
				if($config->get('sysErrorWarningDisplay'))
					echo '<p class="error"><br>Warning: '.$this->message.'</p>';
				if($config->get('sysErrorWarningLog'))
					$this->log_error_to_file();
			break;
			case "php":
				if($config->get('sysErrorPhpDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($config->get('sysErrorPhpLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
			case "framework":
				if($config->get('sysErrorFrameworkDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($config->get('sysErrorFrameworkLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
			case "other":
				if($config->get('sysErrorOtherDisplay'))
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($config->get('sysErrorOtherLog'))
					$this->log_error_to_file();
				if($config->get('sysErrorMailSysAdmin'))
					$this->notify_admin();
			break;
		}
	}
	
	/**
	 * method to log an error to a text file
	 */
	function log_error_to_file() {
		global $config;

		//sets up the date and time variables		
		$dateTime = date("Y-m-d H:i:s");		
		$ip = $_SERVER["REMOTE_ADDR"];
		$host = gethostbyaddr($ip);
		$browser = $_SERVER["HTTP_USER_AGENT"];
		
		// before writting the error to the log file, will will strip out any line returns from the message
		$this->message = str_replace("\n", "", $this->message);
		
		$error_log = new LogFile($config->get('sysErrorLogFile'));
		$error_log->setMaxSize($config->get('sysErrorLogFileMaxEntires'));	
		$error_log->writeLine(array($dateTime, $this->sys_file, $this->method, $this->message, $this->type, $ip, $host, $browser));
	}
	
	/**
	 * Notify the sys admin when a serious error occurs
	 */
	function notify_admin() {
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
