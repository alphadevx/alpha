<?php

// $Id$

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
		global $sysRoot;
		
		$logfile = $sysErrorLogFile;

		$maxEntries = $sysErrorLogFileMaxEntires;

		//sets up the date and time variables		
		$dateTime = date("d/m/y H:i:s");		
		$ip = $_SERVER["REMOTE_ADDR"];
		$host = gethostbyaddr($ip);
		$browser = $_SERVER["HTTP_USER_AGENT"];
		
		
		//searches the $browser variable for extra comas, and removes them.
		$browser = str_replace(",","",$browser);
		
		//writes in the data to the log file
		$fp=fopen("$logfile","a+");
		  flock($fp,2); // locks the file for writting		    
		    fwrite($fp,'"'.$dateTime.'",'); // date and time
		    fwrite($fp,'"'.$this->sys_file.'",'); // error type
		    fwrite($fp,'"'.$this->method.'",'); // method causing the error
		    fwrite($fp,'"'.$this->message.'",'); // error message
			fwrite($fp,'"'.$this->type.'",'); // error type			
			fwrite($fp,'"'.$ip.'",'); // IP
		    fwrite($fp,'"'.$host.'",');// server!
			fwrite($fp,'"'.$browser.'"'."\r\n");  // browser			
		  flock($fp,3); // unlocks the file
		fclose($fp); //closes the file
		
		//counts the number of entries in the logfile
		$numOfRecords = 0;
		
		$fileArray = file("$logfile");
		for ($k = 0; $k < count($fileArray); $k++) {
			$numOfRecords++;
		}
		
		//sets the name of the back-up file
		$backName = $sysRoot."util/logs/error_log_bak_".date("d-m-y").".log";		
		
		//checks to see if the maximum entries amount has been met
		if ($numOfRecords >= $maxEntries) {			
			//renames the logfile as the value of $backName
			rename("$logfile", "$backName");
			//creates a new log file, and sets it's permission for writting!
			$fp = fopen($logfile, "a+"); // set directory permissons to allow creation!
			fclose($fp);
			//sets the new permission to rw+:rw+:rw+
			chmod($logfile, 0666);
		}
	}	
}
 
?>
