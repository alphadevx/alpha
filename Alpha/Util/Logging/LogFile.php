<?php

namespace Alpha\Util\Logging;

use Alpha\Util\Config\ConfigProvider;

/**
 *
 * Generic log file class to encapsulate common file I/O and rendering calls
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class LogFile
{
 	/**
 	 * The log file path
 	 *
 	 * @var string
 	 * @since 1.0
 	 */
 	private $path;

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
 	public function __construct($path)
 	{
 		$this->path = $path;
 	}

 	/**
 	 * Set the max log size in megabytes
 	 *
 	 * @param integer $MaxSize
 	 * @since 1.0
 	 */
 	public function setMaxSize($MaxSize)
 	{
 		$this->MaxSize = $MaxSize;
 	}

 	/**
 	 * Writes a line of data to the log file
 	 *
 	 * $param array $line
 	 * @since 1.0
 	 */
 	public function writeLine($line)
 	{
 		$config = ConfigProvider::getInstance();

 		try {
 			$fp = fopen($this->path, 'a+');
 			fputcsv($fp, $line, ',', '"', '\\');

 			if ($this->checkFileSize() >= $this->MaxSize)
				$this->backupFile();

 		} catch(\Exception $e) {
 			try {
	 			$logsDir = $config->get('app.file.store.dir').'logs';

				if (!file_exists($logsDir))
					mkdir($logsDir, 0766);

	 			$fp = fopen($this->path, 'a+');
 				fputcsv($fp, $line, ',', '"', '\\');

	 			if ($this->checkFileSize() >= $this->MaxSize)
					$this->backupFile();
			} catch(\Exception $e) {
				// TODO log to PHP system log file instead
	 			echo 'Unable to write to the log file ['.$this->path.'], error ['.$e->getMessage().']';
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
 	private function checkFileSize()
 	{
		$size = filesize($this->path);

		return ($size/1024)/1024;
 	}

 	/**
 	 * Creates a backup of the log file, which has the same file name and location as the
 	 * current file plus a timestamp appended
 	 *
 	 * @since 1.0
 	 */
 	private function backupFile()
 	{
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
 	 * @param array $cols The headings to use when rendering the log file
     * @return string
 	 * @since 1.0
 	 */
 	public function renderLog($cols)
 	{
 		// render the start of the table
 		$body = '<table class="table">';
 		$body .= '<tr>';
 		foreach ($cols as $heading)
 			$body .= '<th>'.$heading.'</th>';
 		$body .= '</tr>';

	 	$fp = fopen($this->path, 'r');

	 	while (($line = fgetcsv($fp)) !== FALSE) {

	 		$body .= '<tr>';

	 		for ($col = 0; $col < count($line); $col++) {

	 			// if it is an error log, render the error types field in different colours
	 			if ($col == 1 && $cols[1] == 'Level'){
	 				switch ($line[$col]) {
	 					case 'DEBUG':
	 						$body .= '<td class="debug">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'INFO':
	 						$body .= '<td class="info">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'WARN':
	 						$body .= '<td class="warn">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'ERROR':
	 						$body .= '<td class="error">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'FATAL':
	 						$body .= '<td class="fatal">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					case 'SQL':
	 						$body .= '<td class="sql">'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 					default:
	 						$body .= '<td>'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 					break;
	 				}
	 			} else {
	 				if ($cols[$col] == 'Message')
	 					$body .= '<td><pre>'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</pre></td>';
	 				else
	 					$body .= '<td>'.htmlentities($line[$col], ENT_COMPAT, 'utf-8').'</td>';
	 			}
	 		}

	 		$body .= '</tr>';
	 	}

 		$body .= '</table>';

        return $body;
 	}
}

?>