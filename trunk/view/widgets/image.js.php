<?php

// $Id$

if (!isset($sysRoot))
	$sysRoot = '../../../';

require_once $sysRoot.'config/config.conf';

require_once $sysRoot.'alpha/util/handle_error.inc';

require_once $sysRoot.'alpha/model/types/String.inc';
require_once $sysRoot.'alpha/model/types/Integer.inc';
require_once $sysRoot.'alpha/model/types/Double.inc';
require_once $sysRoot.'alpha/model/types/Enum.inc';
require_once $sysRoot.'alpha/model/types/Boolean.inc';

/**
* Scalable image custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
* @todo cache, check_cache and load_cache methods
*
*/
class image
{
	/**
	 * the title of the image for alt text
	 * @var string
	 */
	var $title;
	
	/**
	 * the path to the source image
	 * @var string
	 */
	var $source;
	
	/**
	 * the width of the image (can also be a javascript function)
	 * @var String
	 */
	var $width;
	
	/**
	 * the height of the image (can also be a javascript function)
	 * @var String
	 */
	var $height;
	
	/**
	 * the file type of the source image
	 * @var Enum
	 */
	var $sourceType;
	
	/**
	 * the quality of the image generated (0.00 to 1.00)
	 * @var Double
	 */
	var $quality;	
	
	/**
	 * the auto-generated name of the cache file for the image
	 * @var string
	 */
	var $filename;
	
	/**
	 * the constructor (the parameters for this can also be set in GET vars
	 * @param string $source the path to the source image
	 * @param string $width
	 * @param string $height
	 * @param string $sourceType
	 * @param double $quality
	 */
	function image($source="", $width=0, $height=0, $sourceType="png", $quality=0.50) {
		global $sysRoot;
		
		$this->source = $source;
		$this->width = new String($width);
		$this->height = new String($height);
		$this->sourceType = new Enum(array("gif",
										"jpg",
										"png"));
		$this->sourceType->set_value($sourceType);
		$this->quality = new Double($quality);		
					
		if (isset($_GET["source"])) $this->source = $_GET["source"];
		if (isset($_GET["width"])) $this->width->set_value($_GET["width"]);
		if (isset($_GET["height"])) $this->height->set_value($_GET["height"]);
		if (isset($_GET["sourceType"])) $this->sourceType->set_value($_GET["sourceType"]);
		if (isset($_GET["quality"])) $this->quality->set_value($_GET["quality"]);
		
		$this->filename = $sysRoot.'cache/images/'.basename($this->source, ".".$this->sourceType->get_value()).'_'.$this->width->get_value().'x'.$this->height->get_value().'.jpg';
	
		// if GET vars where provided, then render the image, otherwise render the JavaScript call for the image creation
		if (isset($_GET["source"]))
			$this->render_image();
		else
			$this->render();
	}
	
	/**
	 * renders the actual image using GD library calls	 
	 */
	function render_image() {
		global $sysRoot;
		
		// check the image cache first before we proceed
		if ($this->check_cache()) {
			$this->load_cache();			
		}else{
			// now get the old image
			switch ($this->sourceType->get_value()) {
				case "gif":
					$old_image = imagecreatefromgif($sysRoot.$this->source);
				break;
				case "jpg":
					$old_image = imagecreatefromjpeg($sysRoot.$this->source);
				break;
				case "png":
					$old_image = imagecreatefrompng($sysRoot.$this->source);
				break;
			}
			
			if (!$old_image) { /* See if it failed */ 
			    $im  = imagecreatetruecolor($this->width->get_value(), $this->height->get_value()); /* Create a blank image */ 
			    $bgc = imagecolorallocate($im, 255, 255, 255); 
			    $tc  = imagecolorallocate($im, 0, 0, 0); 
			    imagefilledrectangle($im, 0, 0, $this->width->get_value(), $this->height->get_value(), $bgc); 
			    
			    imagestring($im, 1, 5, 5, "Error loading $this->source", $tc);
			    header("Content-Type: image/jpeg");
				imagejpeg($im);
				imagedestroy($im);
		    }else{
				// the dimensions of the source image
				$oldWidth = imagesx($old_image);
				$oldHeight = imagesy($old_image);
			
				// now create the new image
				$new_image = imagecreatetruecolor($this->width->get_value(), $this->height->get_value());
			
				// copy the old image to the new image (in memory, not the file!)	
				imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $this->width->get_value(), $this->height->get_value(), $oldWidth, $oldHeight);	
					
				header("Content-Type: image/jpeg");
				imagejpeg($new_image, '', 100*$this->quality->get_value());
				$this->cache($new_image);
				imagedestroy($old_image);
				imagedestroy($new_image);
			}
		}
	}
	
	/**
	 * caches the image to the cache directory
	 * @param image $image the binary GD image stream to save
	 */
	function cache($image) {		
		imagejpeg($image, $this->filename, 100*$this->quality->get_value());
	}
	
	/**
	 * used to check the image cache for the image jpeg cache file	 
	 * @return bool true if the file exists, false otherwise
	 */
	function check_cache() {
		return file_exists($this->filename);
	}
	
	/**
	 * method to load the content of the image cache file to the standard output stream (the browser)	 
	 */
	function load_cache() {		
		readfile($this->filename);		
	}
	
	/**
	 * renders the JavaScript for the image	 
	 */
	function render() {
		echo '<script language="javascript">';
		echo 'insertImage(\''.$this->source.'\','.$this->width->get_value().','.$this->height->get_value().',\''.$this->sourceType->get_value().'\', '.$this->quality->get_value().');';
		echo '</script>';
	}
	
	/**
	 * renders the Javascript to control the behaviour of the button
	 */
	function render_javascript() {
		global $sysURL;
		
		header("Content-type: application/x-javascript");
		
		// begining of javascript
		// ----------------------
		echo <<<EOS
				
		function insertImage(source, width, height, sourceType, quality) {
			/* returns a HTML image tag to the PHP file that draws in a PNG image of the resulting re-scaled image
			
			Parameters:
			source: the file location of the source images
			width: the width of the outputted image
			height: the height of the images
			sourceType: the type of image that that source is (options are "jpeg","gif", and "png")

			*/
			
			// default quality setting if not provided
			quality = (quality == null || quality == "") ? 0.75 : quality;

			// first make the scalable image units (based on the current resolution, compared to a default of 1024x768)
			var xu = screen.width/1024;
			var yu = screen.height/768;

			// now we determine the size of that the image will be scaled to
			var new_width = parseInt(width*xu);
			var new_height = parseInt(height*xu);
EOS;
// end of javascript
// -----------------
			
		echo 'document.write(\'<img src="'.$sysURL.'/view/widgets/image.js.php?source=\'+source+\'&width=\'+new_width+\'&height=\'+new_height+\'&sourceType=\'+sourceType+\'&quality=\'+quality+\'" width="\'+new_width+\'" height="\'+new_height+\'" border="0"/>\')';
		echo '}';

	} 
	
	/**
	 * setter for title
	 * @param string $title
	 */
	function set_title($title)
	{
		$this->title->set_value($title);
	}

	/**
	 * getter for title
	 * @return string title
	 */
	function get_title() {
		return $this->title->get_value();
	}	
}

// if the GET variables are set for the image, then we can construct a new image object safe in the knowledge that the constructor will pick up on all of the
// image properties from GET.
if (isset($_GET["source"])) {
	$temp = new image();
}else{
	// if called from a Javascript link, render the Javascript code, else render the HTML
	// link to the Javascript code contained here.
	if (isset($_GET["render_javascript"]))
		image::render_javascript();
	else
		echo '<script language="JavaScript" src="'.$sysURL.'/alpha/view/widgets/image.js.php?render_javascript"></script>';
}
	
?>