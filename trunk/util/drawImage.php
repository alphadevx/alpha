<?php

// $Id$

/**
 * contains the drawImage function for outputting scalable images
 * 
 * @package Alpha Core Datatype
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 *
 */

// include the config file
require_once '../../config/config.conf';

// first get the variables from get vars, then call the function to return the image
$source = $_GET["source"];
$width = $_GET["width"];
$height = $_GET["height"];
$sourceType = $_GET["sourceType"];
$quality = $_GET["quality"];

function drawImage($source, $width, $height, $sourceType, $quality) {
	/* returns a PNG images to the web browser
	Parameters:
	$source: the file location of the source images
	$width: the width of the outputted image
	$height: the height of the images
	$sourceType: the type of image that that source is (options are "jpeg","gif", and "png")

	*/

	global $sysRoot;	

	// now get the old image
	switch ($sourceType) {
		case "gif":
			$old_image = imagecreatefromgif($sysRoot.$source);
		break;
		case "jpg":
			$old_image = imagecreatefromjpeg($sysRoot.$source);
		break;
		case "png":
			$old_image = imagecreatefrompng($sysRoot.$source);
		break;
	}
	
	if (!$old_image) { /* See if it failed */ 
	    $im  = imagecreatetruecolor($width,$height); /* Create a blank image */ 
	    $bgc = imagecolorallocate($im, 255, 255, 255); 
	    $tc  = imagecolorallocate($im, 0, 0, 0); 
	    imagefilledrectangle($im, 0, 0, $width, $height, $bgc); 
	    
	    imagestring($im, 1, 5, 5, "Error loading $source", $tc);
	    header("Content-Type: image/jpeg");
		imagejpeg($im);
		imagedestroy($im);
    }else{
		// the dimensions of the source image
		$oldWidth = imagesx($old_image);
		$oldHeight = imagesy($old_image);
	
		// now create the new image
		$new_image = imagecreatetruecolor($width,$height);
	
		// copy the old image to the new image (in memory, not the file!)	
		imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $width, $height, $oldWidth, $oldHeight);	
			
		header("Content-Type: image/jpeg");
		imagejpeg($new_image, '', $quality);
		imagedestroy($old_image);
		imagedestroy($new_image);
	}
}

drawImage($source, $width, $height, $sourceType, $quality);

?>