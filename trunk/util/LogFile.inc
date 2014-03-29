<?php

/**
 *
 * Generic log file class to encapsulate common file I/O and rendering calls
 *
 * @package alpha::util
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2014, John Collins (founder of Alpha Framework).
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
class LogFile {
 	/**
 	 * The log file path
 	 *
 	 * @var string
 	 * @since 1.0
 	 */
 	private $path;

 	/**
 	 * An array of column headers use when rendering the log file
 	 *
 	 * @var array
 	 * @since 1.0
 	 */
 	private $columnHeadings;

 	/**
 	 * The value seperator to use in the file (default is a pipe)
 	 *
 	 * @var string
 	 * @since 1.0
 	 */
 	private $seperator = '|';

 	/**
 	 * The maximum size of the log file in megabytes before a backup is created and a
 	 * new file is created, default is 5
 	 *
 	 * @var integer
 	 * @since 1.0
 	 */
 	private $MaxSize = 5;

 	/**
 	 * The constructor
 	 *
 	 * @param string $path
 	 * @since 1.0
 	 */
 	public function __construct($path) {
 		$this->path = $path;
 	}

 	/**
 	 * Set the max log size in megabytes
 	 *
 	 * @param integer $MaxSize
 	 * @since 1.0
 	 */
 	public function setMaxSize($MaxSize) {
 		$this->MaxSize = $MaxSize;
 	}

 	/**
 	 * Set the value seperator
 	 *
 	 * @param string $seperator
 	 * @since 1.0
 	 */
 	public function setSeperator($seperator) {
 		$this->seperator = $seperator;
 	}

 	/**
 	 * Writes a line of data to the log file
 	 *
 	 * $param array $line
 	 * @since 1.0
 	 */
 	public function writeLine($line) {
 		global $config;

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
 			try {
	 			$logsDir = $config->get('app.file.store.dir').'logs';

				if(!file_exists($logsDir))
					mkdir($logsDir, 0766);

	 			file_put_contents($this->path, $mergedLine, FILE_APPEND|LOCK_EX);

	 			if($this->checkFileSize() >= $this->MaxSize) {
					$this->backupFile();
				}
			}catch(Exception $e) {
	 			echo '<p class="error"><br>Unable to write to the log file ['.$this->path.'], error ['.$e->getMessage().']</p>';
	 			exit;
			}
 		}
 	}

 	/**
 	 * Returns the size in megabytes of the log file on disc
 	 *
 	 * @return float
 	 * @since 1.0
 	 */
 	private function checkFileSize() {
		$size = filesize($this->path);

		return ($size/1024)/1024;
 	}

 	/**
 	 * Creates a backup of the log file, which has the same file name and location as the
 	 * current file plus a timestamp appended
 	 *
 	 * @since 1.0
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
 	 * @since 1.0
 	 */
 	public function renderLog($cols) {
 		// render the start of the table
 		echo '<table class="table">';
 		echo '<tr>';
 		foreach($cols as $heading)
 			echo '<th>'.$heading.'</th>';
 		echo '</tr>';

 		// now read the file and render the data
 		$LogFile = file_get_contents($this->path);
 		$fields = explode($this->seperator, $LogFile);
		$totalLines = (count($fields)-1)/count($cols);

 		for($line = 0; $line < $totalLines; $line++) {
 			$count = count($cols);

	 		for($col = 0; $col < $count; $col++) {
	 			$index = ($line*count($cols))+$col;

	 			// if it is an error log, render the error types field in different colours
	 			if($col == 1 && $cols[1] == 'Level'){
	 				switch($fields[$index]) {
	 					case 'DEBUG':
	 						echo '<td class="debug">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'INFO':
	 						echo '<td class="info">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'WARN':
	 						echo '<td class="warn">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'ERROR':
	 						echo '<td class="error">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'FATAL':
	 						echo '<td class="fatal">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'SQL':
	 						echo '<td class="sql">'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					default:
	 						echo '<td>'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 				}
	 			}else{
	 				if($cols[$col] == 'Message')
	 					echo '<td><pre>'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</pre></td>';
	 				else
	 					echo '<td>'.htmlentities($fields[$index], ENT_COMPAT, 'utf-8').'</td>';
	 			}
	 		}

 			echo '</tr>';
 		}

 		echo '</table>';
 	}
}
?>