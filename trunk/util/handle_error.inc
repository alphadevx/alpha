<?php

// $Id$

require_once $sysRoot.'alpha/util/log_file.inc';

/**
 *
 * Generic error handling class used to display error messages and log errors
 * 
 * @package Alpha Core Error
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins 
 * @todo Implement database logging
 * 
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
	 * the error type
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
		global $sysErrorValidationDisplay;
		global $sysErrorValidationLog;
		
		global $sysErrorWarningDisplay;
		global $sysErrorWarningLog;
		
		global $sysErrorPhpDisplay;
		global $sysErrorPhpLog;
		
		global $sysErrorFrameworkDisplay;
		global $sysErrorFrameworkLog;
		
		global $sysErrorOtherDisplay;
		global $sysErrorOtherLog;
		
		switch ($this->type) {
			case "validation":
				if($sysErrorValidationDisplay)
					echo '<p class="error"><br>Validation error: '.$this->message.'</p>';
				if($sysErrorValidationLog)
					$this->log_error_to_file();
			break;
			case "warning":
				if($sysErrorWarningDisplay)
					echo '<p class="error"><br>Warning: '.$this->message.'</p>';
				if($sysErrorWarningLog)
					$this->log_error_to_file();
			break;
			case "php":
				if($sysErrorPhpDisplay)
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($sysErrorPhpLog)
					$this->log_error_to_file();
			break;
			case "framework":
				if($sysErrorFrameworkDisplay)
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($sysErrorFrameworkLog)
					$this->log_error_to_file();
			break;
			case "other":
				if($sysErrorOtherDisplay)
					echo '<p class="error">An error has occured in file: ['.$this->sys_file.'], message: <br>The method ['.$this->method.'] has generated the following error message: ['.$this->message.']</p>';	
				if($sysErrorOtherLog)
					$this->log_error_to_file();
			break;
		}
	}
	
	/**
	 * method to log an error to a text file
	 */
	function log_error_to_file() {
		global $sysErrorLogFile;
		global $sysErrorLogFileMaxEntires;		

		//sets up the date and time variables		
		$dateTime = date("d/m/y H:i:s");		
		$ip = $_SERVER["REMOTE_ADDR"];
		$host = gethostbyaddr($ip);
		$browser = $_SERVER["HTTP_USER_AGENT"];
		
		// before writting the error to the log file, will will strip out any line returns from the message
		$this->message = str_replace("\n", "", $this->message);
		
		$error_log = new log_file($sysErrorLogFile);
		$error_log->set_max_size($sysErrorLogFileMaxEntires);	
		$error_log->write_line(array($dateTime, $this->sys_file, $this->method, $this->message, $this->type, $ip, $host, $browser));
	}	
}
 
?>
