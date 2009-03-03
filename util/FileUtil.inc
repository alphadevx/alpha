<?php

require_once $config->get('sysRoot').'alpha/exceptions/AlphaException.inc';

/**
 * 
 * Util class with static file I/O methods for interacting with directories and files
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::util
 * @copyright 2009 John Collins
 * @version $Id$
 *
 */
class FileUtil {	
	/**
	 * Renders the contents of the directory as a HTML list
	 * 
	 * @param string $sourceDir The path to the source directory
	 * @param integer $fileCount The current file count (used in recursive calls)
	 * @return integer The current filecount for the directory
	 */
	public static function listDirectoryContents($sourceDir, $fileCount=0) {
		try
		{
		    $dir = new DirectoryIterator($sourceDir);
		    $fileCount = 0;
		    
			echo '<ul>';
		    foreach ($dir as $file) {
		    	if($file->isDir() && !$file->isDot()) {
		      		echo '<li><em>'.$file->getPathname().'</em></li>';
		      		$fileCount += FileUtil::listDirectoryContents($file->getPathname(), $fileCount);
		      	}elseif(!$file->isDot()){
			        $fileName = $file->getFilename();
			        $fileCount++;
			        echo '<li>'.$fileName.'</li>';
		      	}		      	
		    }
			echo '</ul>';
			
			return $fileCount;			
		}catch (Exception $e) {
			throw new AlphaException('Failed list files in the ['.$sourceDir.'] directory, error is ['.$e->getMessage().']');
		}
	}
	
	/**
	 * Recursively deletes the contents of the directory indicated
	 * 
	 * @param string $sourceDir The path to the source directory	
	 */
	public static function deleteDirectoryContents($sourceDir) {
				
		try	{
		    $dir = new DirectoryIterator($sourceDir);	    
			
		    foreach ($dir as $file) {
		    	if($file->isDir() && !$file->isDot()) {
		      		FileUtil::deleteDirectoryContents($file->getPathname());
		      	}elseif(!$file->isDot()){
			        unlink($file->getPathname());
		      	}		      	
		    }
		}catch (Exception $e) {
			throw new AlphaException('Failed to delete files files in the ['.$sourceDir.'] directory, error is ['.$e->getMessage().']');
		}
	}	
}

?>