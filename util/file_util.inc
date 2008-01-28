<?php

// $Id$

/**
* 
* Util class with static file I/O methods
* 
* @author John Collins <john@design-ireland.net>
* @package Alpha Util
* @copyright 2008 John Collins
*
*/

class file_util {
	
	/**
	 * Renders the contents of the directory as a HTML list
	 * @param string $sourceDir The path to the source directory
	 * @param integer $fileCount The current file count (used in recursive calls)
	 * @return integer The current filecount for the directory
	 */
	function list_directory_contents($sourceDir, $fileCount=0) {
		try
		{
		    $dir = new DirectoryIterator($sourceDir);
		    $fileCount = 0;
		    
			echo '<ul>';
		    foreach ($dir as $file)
		    {
		    	if($file->isDir() && !$file->isDot()) {
		      		echo '<li><em>'.$file->getPathname().'</em></li>';
		      		$fileCount += file_util::list_directory_contents($file->getPathname(), $fileCount);
		      	}elseif(!$file->isDot()){
			        $fileName = $file->getFilename();
			        $fileCount++;
			        echo '<li>'.$fileName.'</li>';
		      	}		      	
		    }
			echo '</ul>';
			
			return $fileCount;			
		}
		catch (Exception $ex)
		{
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed list files in the ['.$sourceDir.'] directory','list_directory_contents()','framework');
		}
	}
	
	/**
	 * Recursively deletes the contents of the directory indicated
	 * @param string $sourceDir The path to the source directory	
	 */
	function delete_directory_contents($sourceDir) {
		$success = false;
		
		try
		{
		    $dir = new DirectoryIterator($sourceDir);	    
			
		    foreach ($dir as $file)
		    {
		    	if($file->isDir() && !$file->isDot()) {
		      		file_util::delete_directory_contents($file->getPathname());
		      	}elseif(!$file->isDot()){
			        $success = unlink($file->getPathname());
		      	}		      	
		    }
		    
		    return $success;
		}
		catch (Exception $ex)
		{
			$error = new handle_error($_SERVER["PHP_SELF"],'Failed to delete files in the ['.$sourceDir.'] directory','delete_directory_contents()','framework');
		}
	}	
}
?>
