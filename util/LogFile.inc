<?php

/**
 *
 * Generic log file class to encapsulate common file I/O and rendering calls 
 * 
 * @package alpha::util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class LogFile {
 	/**
 	 * The log file path
 	 * 
 	 * @var string
 	 */
 	private $path;
 	 	
 	/**
 	 * An array of column headers use when rendering the log file
 	 * 
 	 * @var array
 	 */
 	private $columnHeadings;
 	
 	/**
 	 * The value seperator to use in the file (default is a pipe)
 	 * 
 	 * @var string
 	 */
 	private $seperator = '|';
 	
 	/**
 	 * The maximum size of the log file in megabytes before a backup is created and a 
 	 * new file is created, default is 5
 	 * 
 	 * @var int
 	 */
 	private $MaxSize = 5;
 	
 	/**
 	 * The constructor
 	 * 
 	 * @param string $path
 	 */
 	public function __construct($path) {
 		$this->path = $path; 		
 	}
 	
 	/**
 	 * Set the max log size in megabytes
 	 * 
 	 * @param int $MaxSize
 	 */
 	public function setMaxSize($MaxSize) {
 		$this->MaxSize = $MaxSize;
 	}
 	
 	/**
 	 * Set the value seperator
 	 * 
 	 * @param string $seperator
 	 */
 	public function setSeperator($seperator) {
 		$this->seperator = $seperator;
 	}
 	
 	/**
 	 * Writes a line of data to the log file
 	 * 
 	 * $param array $line
 	 */
 	public function writeLine($line) {
 		$mergedLine = '';
 		
 		$colCount = count($line); 		
 		
 		for($i = 0; $i < $colCount; $i++) {
 			/*
 			 * we need to ensure that the seperator is not in the value anywhere, as it
 			 * would cause problems later when reading the log
 			 */
 			$value = str_replace($this->seperator, '', $line[$i]);
			if ($i == ($colCount-1))
				$mergedLine .= $value.$this->seperator."\n";				
			else
				$mergedLine .= $value.$this->seperator;				
 		}
 		
 		try{
 			file_put_contents($this->path, $mergedLine, FILE_APPEND|LOCK_EX);
 			
 			if($this->checkFileSize() >= $this->MaxSize) {			
				$this->backupFile();	
			}
 		}catch(Exception $e) {
 			echo '<p class="error"><br>Unable to write to the log file ['.$this->path.'], error ['.$e->getMessage().']</p>';
 			exit;	
 		}
 	}
 	
 	/**
 	 * Returns the size in megabytes of the log file on disc
 	 * 
 	 * @return float
 	 */
 	private function checkFileSize() {		
		$size = filesize($this->path);
		
		return ($size/1024)/1024;
 	}
 	
 	/**
 	 * Creates a backup of the log file, which has the same file name and location as the 
 	 * current file plus a timestamp appended
 	 */
 	private function backupFile() {
 		// generate the name of the backup file name to contain a timestampe
 		$backName = str_replace('.log', '-backup-'.date("y-m-d H.i.s").'.log', $this->path);
 		
 		// renames the logfile as the value of $backName
		rename($this->path, $backName);
		//creates a new log file, and sets it's permission for writting!
		$fp = fopen($this->path, 'a+'); // remember set directory permissons to allow creation!
		fclose($fp);
		//sets the new permission to rw+:rw+:rw+
		chmod($this->path, 0666);		
 	}
 	
 	/**
 	 * Renders a log file as a HTML table
 	 * 
 	 * $param array $cols The headings to use when rendering the log file
 	 */
 	public function renderLog($cols) {
 		// render the start of the table
 		echo '<table class="log_file">';
 		echo '<tr>';
 		foreach($cols as $heading)
 			echo '<th>'.$heading.'</th>';
 		echo '</tr>';
 		
 		// now read the file and render the data
 		$LogFile = file_get_contents($this->path); 		
 		$fields = explode($this->seperator, $LogFile); 		
		$totalLines = (count($fields)-1)/count($cols);
 		
 		for($line = 0; $line < $totalLines; $line++) {
	 		for($col = 0; $col < count($cols); $col++) {
	 			$index = ($line*count($cols))+$col;
	 			
	 			// if it is an error log, render the error types field in different colours
	 			if($col == 1 && $cols[1] == 'Level'){
	 				switch($fields[$index]) {
	 					case 'DEBUG':
	 						echo '<td class="validation"><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 					case 'INFO':
	 						echo '<td class="validation"><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 					case 'WARN':
	 						echo '<td class="warning"><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 					case 'ERROR':
	 						echo '<td class="framework"><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 					case 'FATAL':
	 						echo '<td class="other"><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 					default:
	 						echo '<td><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 					break;
	 				}
	 			}else{
	 				echo '<td><pre>'.htmlentities($fields[$index]).'</pre></td>';
	 			}
	 		}
 			
 			echo '</tr>';
 		}
 		
 		echo '</table>';
 	}
}
?>