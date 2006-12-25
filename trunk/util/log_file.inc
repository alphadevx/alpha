<?php

// $Id$

/**
 *
 * Generic log_file class to encapsulate common file I/O calls 
 * 
 * @package Alpha Core Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 */
 
class log_file {
 	/**
 	 * the log file path
 	 * @var string
 	 */
 	var $path; 	
 	/**
 	 * an array of column headers use when rendering the log file
 	 * @var array
 	 */
 	var $column_headers;
 	/**
 	 * the value-seperator to use in the file (default is a comma)
 	 * @var string
 	 */
 	var $seperator = ",";
 	/**
 	 * the maximum amount of records accepting in the log file before a backup is created and a new file is started
 	 * var int
 	 */
 	var $max_size = 800;
 	
 	/**
 	 * the constructor
 	 * @param string $path
 	 */
 	function log_file($path) {
 		$this->path = $path; 		
 	}
 	
 	/**
 	 * change the max log size from the default of 800
 	 * @param int $max_size
 	 */
 	function set_max_size($max_size) {
 		$this->max_size = $max_size;
 	}
 	
 	/**
 	 * change the value seperator from the default of a comma
 	 * @param int $max_size
 	 */
 	function set_seperator($seperator) {
 		$this->seperator = $seperator;
 	}
 	
 	/**
 	 * writes a line of data to the log file
 	 * $param array $line the line of data to write
 	 */
 	function write_line($line) {
 		$fp=fopen($this->path,"a+");
 		
 		flock($fp,2); // locks the file for writting
 		
 		$col_count = count($line); 		
 		
 		// we need to ensure that the seperator is not in the value anywhere, as it
 		// would cause problems later when reading the log
 		$values = str_replace($this->seperator, "", $line);
 		
 		for($i = 0; $i < $col_count; $i++) {
			fwrite($fp,$values[$i]);
			if ($i == ($col_count-1))
				fwrite($fp,"\n");
			else
				fwrite($fp, $this->seperator);
 		}
 		
 		flock($fp,3); // unlocks the file
		fclose($fp); //closes the file
		
		if($this->check_file_line_count() >= $this->max_size) {
			$this->backup_file();	
		}
 	}
 	
 	/**
 	 * returns a count of the amount of lines in the log file on disc
 	 * @return int the amount of lines in the file
 	 */
 	function check_file_line_count() {
 		//counts the number of entries in the logfile
		$numOfRecords = 0;
		
		$fileArray = file($this->path);
		for ($k = 0; $k < count($fileArray); $k++) {
			$numOfRecords++;
		}
		return $numOfRecords;
 	}
 	
 	/**
 	 * creates a backup of the log file, which is the same file name and location plus a timestamp
 	 */
 	function backup_file() {
 		// generate the name of the backup file name to contain a timestampe
 		$backName = str_replace(".log", "-backup-".date("y-m-d H.i.s").".log", $this->path);
 		
 		//renames the logfile as the value of $backName
		rename($this->path, $backName);
		//creates a new log file, and sets it's permission for writting!
		$fp = fopen($this->path, "a+"); // remember set directory permissons to allow creation!
		fclose($fp);
		//sets the new permission to rw+:rw+:rw+
		chmod($this->path, 0666);		
 	}
}
?>
