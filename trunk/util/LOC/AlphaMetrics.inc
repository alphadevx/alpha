<?php

require_once $config->get('sysRoot').'alpha/lib/PEAR/File_Find/Find.php';

/**
 * Utility class for calcualting some software metics related to a project
 *
 * @package alpha::util::LOC
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 */
class AlphaMetrics {
	/**
	 * The directory to begin the calculations from
	 *
	 * @var string
	 */
	private $rootDir;
	
	/**
	 * The file extensions of the file types to include in the calculations
	 *
	 * @var array
	 */
	private $includeFileTypes = array('.php', '.ini', '.html', '.phtml', '.inc', '.js', '.css', '.xml');
	
	/**
	 * Any sub-directories which you might want to exclude from the calculations
	 *
	 * @var array
	 */
	private $excludeSubDirectories = array('cache','lib','docs','attachments','dist');
	
	/**
	 * The Total Lines of Code (TLOC) for the project
	 *
	 * @var int
	 */
	private $TLOC = 0;
	
	/**
	 * The Total Lines of Code (TLOC) for the project, less comments defined in $comments
	 *
	 * @var int
	 */
	private $TLOCLessComments = 0;
	
	/**
	 * The count of the source code files in the project
	 *
	 * @var int
	 */
	private $fileCount = 0;
	
	/**
	 * An array of fileName => lines of code to be populated by this class
	 *
	 * @var array
	 */
	private $filesLOCResult = array();
	
	/**
	 * An array of fileName => lines of code to be populated by this class, 
	 * excluding comment lines defined in the $comments array
	 *
	 * @var array
	 */
	private $filesLOCNoCommentsResult = array();
	
	/**
	 * An array of the source code file names in the project
	 *
	 * @var array
	 */
	private $files = array();
	
	/**
	 * An array of the directories in the project
	 *
	 * @var array
	 */
	private $directories = array();
	
	/**
	 * An array of the first characters of a comment line in source code
	 *
	 * @var array
	 */
	private $comments = array('/','*','#');
	
	/**
	 * Constructor, default $rootDir is .
	 *
	 * @param string $rootDir
	 */
	public function __construct($rootDir='.') {
		$this->rootDir = $rootDir;
		// populate the file and directories arrays using the File_Find class
		list($this->directories, $this->files) = File_Find::maptree($rootDir);	
	}

	/**
	 * Calculates the Lines of Code (LOC)
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
	 * @return int
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