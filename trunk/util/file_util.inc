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
	
	function list_directory_contents($sourceDir, $fileCount=0) {
		try
		{
		    $dir = new DirectoryIterator($sourceDir);
		    $fileCount = 0;
		    
			echo '<ul>';
		    foreach ($dir as $file)
		    {
		    	if($file->isDir() && !$file->isDot()) {
		      		echo '<li><em>'.$file->getPath().'/'.$file->getFilename().'</em></li>';
		      		$fileCount += file_util::list_directory_contents($file->getPath().'/'.$file->getFilename(), $fileCount);
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
		
		}
	}
	
}
?>
