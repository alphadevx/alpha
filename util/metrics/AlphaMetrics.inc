<?php

try{
	AlphaAutoLoader::loadLib('Find.php');
}catch (LibraryNotInstalledException $e) {
	throw new LibraryNotInstalledException('Unable to generate code metrics as the PEAR::File_Find package is not installed!');
	exit;
}

/**
 * Utility class for calcualting some software metics related to a project
 *
 * @package alpha::util::metrics
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
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
class AlphaMetrics {
	/**
	 * The directory to begin the calculations from
	 *
	 * @var string
	 * @since 1.0
	 */
	private $rootDir;
	
	/**
	 * The file extensions of the file types to include in the calculations
	 *
	 * @var array
	 * @since 1.0
	 */
	private $includeFileTypes = array('.php', '.ini', '.html', '.phtml', '.inc', '.js', '.css', '.xml');
	
	/**
	 * Any sub-directories which you might want to exclude from the calculations
	 *
	 * @var array
	 * @since 1.0
	 */
	private $excludeSubDirectories = array('cache','lib','docs','attachments','dist');
	
	/**
	 * The Total Lines of Code (TLOC) for the project
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $TLOC = 0;
	
	/**
	 * The Total Lines of Code (TLOC) for the project, less comments defined in $comments
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $TLOCLessComments = 0;
	
	/**
	 * The count of the source code files in the project
	 *
	 * @var integer
	 * @since 1.0
	 */
	private $fileCount = 0;
	
	/**
	 * An array of fileName => lines of code to be populated by this class
	 *
	 * @var array
	 * @since 1.0
	 */
	private $filesLOCResult = array();
	
	/**
	 * An array of fileName => lines of code to be populated by this class, 
	 * excluding comment lines defined in the $comments array
	 *
	 * @var array
	 * @since 1.0
	 */
	private $filesLOCNoCommentsResult = array();
	
	/**
	 * An array of the source code file names in the project
	 *
	 * @var array
	 * @since 1.0
	 */
	private $files = array();
	
	/**
	 * An array of the directories in the project
	 *
	 * @var array
	 * @since 1.0
	 */
	private $directories = array();
	
	/**
	 * An array of the first characters of a comment line in source code
	 *
	 * @var array
	 * @since 1.0
	 */
	private $comments = array('/','*','#');
	
	/**
	 * Constructor, default $rootDir is .
	 *
	 * @param string $rootDir
	 * @since 1.0
	 */
	public function __construct($rootDir = '.') {
		$this->rootDir = $rootDir;
		// populate the file and directories arrays using the File_Find class
		list($this->directories, $this->files) = File_Find::maptree($rootDir);	
	}

	/**
	 * Calculates the Lines of Code (LOC)
	 * 
	 * @since 1.0
	 */
	public function calculateLOC() {
		foreach ($this->files as $file) {
			$file_type = substr($file, strrpos($file, '.'));
			if (in_array($file_type, $this->includeFileTypes)) {
				$exclude = false;
				foreach ($this->excludeSubDirectories as $dir) {
					if (preg_match("/".$dir."/i", $file)) {
						$exclude = true;
					}
				}
				
				if (!$exclude) {
					$current_file = file($file);

					$LOC = count($current_file);						
					$this->filesLOCResult[$file] = $LOC;
					$LOC_less_comments = $this->disregardCommentsLOC($file);
					$this->filesLOCNoCommentsResult[$file] = $LOC_less_comments;

					$this->TLOC += $LOC;
					$this->TLOCLessComments += $LOC_less_comments;
					$this->fileCount++;				
				}
			}
		}
	}
	
	/**
	 * Generates a HTML table containing the metrics results
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function resultsToHTML() {
		$count = 1;
		
		$html = '<table class="list_view"><tr>';
		$html .= '<th width="10%">File #:</th>';
		$html .= '<th width="50%">File name:</th>';
		$html .= '<th width="20%">Lines of Code (LOC):</th>';
		$html .= '<th width="20%">Lines of Code (less comments):</th>';
		$html .= '</tr>';
		foreach(array_keys($this->filesLOCResult) as $result) {
			$html .= "<tr><td>$count</td><td>$result</td><td>".$this->filesLOCResult[$result]."</td><td>".$this->filesLOCNoCommentsResult[$result]."</td></tr>";
			$count++;
		}		
		$html .= '</table>';
		
		$html .= "<p>Total files: ".number_format(count($this->files))."</p>";
		$html .= "<p>Total source code files: ".number_format($this->fileCount)."</p>";
		$html .= "<p>Total Lines of Code (TLOC): ".number_format($this->TLOC)."</p>";
		$html .= "<p>Total Lines of Code (TLOC), less comments: ".number_format($this->TLOCLessComments)."</p>";
		
		return $html;
	}
	
	/**
	 * Filters comments from LOC metric
	 *
	 * @param string $sourceFile
	 * @return integer
	 * @since 1.0
	 */
	private function disregardCommentsLOC($sourceFile) {
		$file = file($sourceFile);
		
		$LOC = 0;
		
		foreach ($file as $line) {
			$exclude = false;
			$line = ltrim($line);
			
			if(empty($line)) {
				$exclude = true;
			}else{			
				foreach ($this->comments as $comment) {
					if (substr($line, 0, 1) == $comment) {
						$exclude = true;
					}
				}
			}
				
			if (!$exclude) {
				$LOC++;			
			}
		}
		
		return $LOC;
	}
}

?>