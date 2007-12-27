<?php

require_once $config->get('sysRoot')."alpha/lib/PEAR/File_Find/Find.php";

/**
 *	utility class for calcualting some software metics related to a project
 *
 * @package Alpha Util
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 */

class metrics {
	// the directory to begin the calculations from
	var $root_dir;
	// the file extensions of the file types to include in the calculations
	var $include_file_types = array(".php", ".conf", ".html", ".inc", ".js", ".css",".schema",".before");
	// any sub-directories which you might want to exclude from the calculations
	var $exclude_sub_directories = array("cache","lib","docs");
	// the Total Lines of Code (TLOC) for the project
	var $TLOC = 0;
	// the Total Lines of Code (TLOC) for the project, less comments defined in $comments
	var $TLOC_less_comments = 0;
	// the count of the source code files in the project
	var $file_count = 0;
	// an array of file_name => lines of code to be populated by this class
	var $files_LOC_result = array();
	// an array of file_name => lines of code to be populated by this class, excluding comment lines defined in the $comments array
	var $files_LOC_no_comments_result = array();
	// an array of the source code file names in the project
	var $files = array();
	// an array of the directories in the project
	var $directories = array();
	// an array of the first characters of a comment line in source code
	var $comments = array('/','*','#');
	
	function metrics($root_dir=".") {
		$this->root_dir = $root_dir;
		// populate the file and directories arrays using the File_Find class
		list($directories, $this->files) = File_Find::maptree($root_dir);	
	}

	function calculate_LOC() {
		foreach ($this->files as $file) {
			$file_type = substr($file, strrpos($file, '.'));
			if (in_array($file_type, $this->include_file_types)) {
				$exclude = false;
				foreach ($this->exclude_sub_directories as $dir) {
					if (preg_match("/".$dir."/i", $file)) {
						$exclude = true;
					}
				}
				
				if (!$exclude) {
					$current_file = file($file);

					$LOC = count($current_file);						
					$this->files_LOC_result[$file] = $LOC;
					$LOC_less_comments = $this->disregard_comments_LOC($file);
					$this->files_LOC_no_comments_result[$file] = $LOC_less_comments;

					$this->TLOC += $LOC;
					$this->TLOC_less_comments += $LOC_less_comments;
					$this->file_count++;
				
				}
			}
		}
	}
	
	function results_to_console() {
		$count = 1;
		foreach(array_keys($this->files_LOC_result) as $result) {
			echo "$count. File: $result has [".$this->files_LOC_result[$result]."] lines of code (".$this->files_LOC_no_comments_result[$result]." excluding comments).\n";
			$count++;
		}
		
		echo "\n\nTotal files: ".number_format(count($this->files));
		echo "\nTotal source code files: ".number_format($this->file_count);
		echo "\nTotal Lines of Code (TLOC): ".number_format($this->TLOC)."\n";
		echo "\nTotal Lines of Code (TLOC), less comments: ".number_format($this->TLOC_less_comments)."\n";
	}
	
	function results_to_HTML() {
		$count = 1;
		echo '<table border="1"><tr>';
		echo '<th>File #:</th>';
		echo '<th>File name:</th>';
		echo '<th>Lines of Code (LOC):</th>';
		echo '<th>Lines of Code (less comments):</th>';
		echo '</tr>';
		foreach(array_keys($this->files_LOC_result) as $result) {
			echo "<tr><td>$count</td><td>$result</td><td>".$this->files_LOC_result[$result]."</td><td>".$this->files_LOC_no_comments_result[$result]."</td></tr>";
			$count++;
		}		
		echo '</table>';
		
		echo "<p>Total files: ".number_format(count($this->files))."</p>";
		echo "<p>Total source code files: ".number_format($this->file_count)."</p>";
		echo "<p>Total Lines of Code (TLOC): ".number_format($this->TLOC)."</p>";
		echo "<p>Total Lines of Code (TLOC), less comments: ".number_format($this->TLOC_less_comments)."</p>";
	}
	
	function disregard_comments_LOC($source_file) {
		$file = file($source_file);
		
		$LOC = 0;
		
		foreach ($file as $line) {
			$line = ltrim($line);
			
			$exclude = false;
			foreach ($this->comments as $comment) {
				if (substr($line, 0, 1) == $comment) {
					$exclude = true;
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