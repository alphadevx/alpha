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
 	var $seperator = "|";
 	/**
 	 * the maximum size of the log file before a backup is created and a new file is started
 	 * var int
 	 */
 	var $max_size = 5;
 	
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
 		$mergedLine = '';
 		
 		$col_count = count($line); 		
 		
 		for($i = 0; $i < $col_count; $i++) {
 			// we need to ensure that the seperator is not in the value anywhere, as it
 			// would cause problems later when reading the log
 			$value = str_replace($this->seperator, "", $line[$i]);
			if ($i == ($col_count-1))
				$mergedLine .= $value."\n";				
			else
				$mergedLine .= $value.$this->seperator;				
 		}
 		
 		file_put_contents($this->path, $mergedLine, FILE_APPEND|LOCK_EX);
 		
		if($this->check_file_size() >= $this->max_size) {			
			$this->backup_file();	
		}
 	}
 	
 	/**
 	 * returns the size in megabytes of the log file on disc
 	 * @return float
 	 */
 	function check_file_size() {		
		$size = filesize($this->path);
		
		return ($size/1024)/1024;
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
 	
 	/**
 	 * renders a log file as a HTML table
 	 * $param array $cols the headings to use when rendering the log file
 	 */
 	function render_log($cols) {
 		// render the start of the table
 		echo '<table class="log_file">';
 		echo '<tr>';
 		foreach($cols as $heading)
 			echo '<th>'.$heading.'</th>';
 		echo '</tr>';
 		
 		// now read the file and render the data
 		$log_file = file($this->path);
 		
 		foreach($log_file as $line) {
 			echo '<tr>';
 			$fields = split($this->seperator, $line);
 			for($i = 0; $i < count($fields); $i++) {
 				// if it is an error log, render the error types field in different colours
 				if($i == 4 && preg_match("/error_log.*/", basename($this->path))){
 					switch($fields[$i]) {
 						case "validation":
 							echo '<td class="validation">'.$fields[$i].'</td>';
 						break;
 						case "warning":
 							echo '<td class="warning">'.$fields[$i].'</td>';
 						break;
 						case "php":
 							echo '<td class="php">'.$fields[$i].'</td>';
 						break;
 						case "framework":
 							echo '<td class="framework">'.$fields[$i].'</td>';
 						break;
 						case "other":
 							echo '<td class="other">'.$fields[$i].'</td>';
 						break;
 						default:
 							echo '<td>'.$fields[$i].'</td>';
 						break;
 					}
 				}else{
 					echo '<td>'.$fields[$i].'</td>';
 				}
 			}
 			echo '</tr>';
 		}
 		
 		echo '</table>';
 	}
}
?>
