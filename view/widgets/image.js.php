<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';

if($config->get('sysCMSImagesWidgetSecure')) {
	require_once $config->get('sysRoot').'alpha/view/View.inc';
	require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
}

/**
* Scalable image custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2007 John Collins
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
	 * the quality of the image generated (0.00 to 1.00, 0.75 by default)
	 * @var Double
	 */
	var $quality;
	
	/**
	 * flag to determine if the image will scale to match resolution (0 by default)
	 * a default resoultion of 1024x768 is assumed for scalable images.
	 * @var Boolean 
	 */
	var $scale;
	
	/**
	 * flag if you want only create the image in the cache but not render it to the browser
	 * @var Boolean
	 */
	var $cache_only;
	
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
	 * @param Boolean $scale;
	 * @param Boolean $render_image;
	 */
	function image($source="", $width=0, $height=0, $sourceType="png", $quality=0.75, $scale=0, $cache_only=0) {
		global $config;
		
		$this->source = $source;
		$this->width = new String($width);
		$this->height = new String($height);
		$this->sourceType = new Enum(array("gif",
										"jpg",
										"png"));
		$this->sourceType->setValue($sourceType);
		$this->quality = new Double($quality);
		$this->scale = new Boolean($scale);	
		$this->cache_only = new Boolean($cache_only);
					
		if (isset($_GET["source"])) $this->source = $_GET["source"];
		if (isset($_GET["width"])) $this->width->setValue($_GET["width"]);
		if (isset($_GET["height"])) $this->height->setValue($_GET["height"]);
		if (isset($_GET["sourceType"])) $this->sourceType->setValue($_GET["sourceType"]);
		if (isset($_GET["quality"])) $this->quality->setValue($_GET["quality"]);
		
		$this->set_filename($this->source);
		
		// if GET vars where provided, then render the image, otherwise render the JavaScript call for the image creation
		if (isset($_GET["source"]) || $this->cache_only->getValue())
			$this->render_image();
		else
			$this->render();
	}
	
	/**
	 * setter for the file name, creates a sub-directory under /cache for attachment images when required
	 */
	function set_filename($source) {
		global $config;
		
		if(!strpos($source, 'attachments/article_')) {
			if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
				$this->filename = $config->get('sysRoot').'cache/images/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
			else
				$this->filename = $config->get('sysRoot').'cache/images/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
		}else{
			// make a cache dir for the article
			$cache_dir = $config->get('sysRoot').'cache/images/article_'.substr($source, strpos($source, 'attachments/article_')+20, 11);
			if(!file_exists($cache_dir)) {
				$success = mkdir($cache_dir);
			
				if (!$success) {
					$error = new handle_error($_SERVER["PHP_SELF"],'Unable to create the folder '.$cache_dir.' for the cache image, source file is '.$source.'.' ,'set_filename()','framework');
				}
				
				// ...and set write permissions on the folder
				$success = chmod($cache_dir, 0777);
					
				if (!$success) {
					$error = new handle_error($_SERVER["PHP_SELF"],'Unable to set write permissions on the folder '.$cache_dir.'.','set_filename()','framework');
				}
			}
			
			// now set the filename to include the new cache directory
			if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
				$this->filename = $cache_dir.'/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
			else
				$this->filename = $cache_dir.'/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
		}
	}
	
	/**
	 * renders the actual image using GD library calls	 
	 */
	function render_image() {
		global $config;
		
		/*
		 * Handle secure image validation (if enabled in config).
		 * Not required for the view_article_pdf.php controller.
		 */
		if($config->get('sysCMSImagesWidgetSecure') && basename($_SERVER["PHP_SELF"]) != 'view_article_pdf.php') {
			$valid = Controller::checkSecurityFields();			
			
			// if not valid, just return a blank black image of the same dimensions
			if(!$valid) {
				$im  = imagecreatetruecolor($this->width->getValue(), $this->height->getValue()); /* Create a blank image */ 
			    $bgc = imagecolorallocate($im, 0, 0, 0); 
			    //$tc  = imagecolorallocate($im, 0, 0, 0); 
			    imagefilledrectangle($im, 0, 0, $this->width->getValue(), $this->height->getValue(), $bgc); 
			    
			    //imagestring($im, 5, 5, 5, "Error loading $this->source", $tc);
			    if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
			    	header("Content-Type: image/png");
					imagepng($im);
			    }else{
				    header("Content-Type: image/jpeg");
					imagejpeg($im);
			    }
				imagedestroy($im);
			}
		}
		
		// check the image cache first before we proceed
		if ($this->check_cache()) {
			$this->load_cache();			
		}else{
			// now get the old image
			switch ($this->sourceType->getValue()) {
				case "gif":
					$old_image = imagecreatefromgif($config->get('sysRoot').$this->source);
				break;
				case "jpg":
					$old_image = imagecreatefromjpeg($config->get('sysRoot').$this->source);
				break;
				case "png":
					$old_image = imagecreatefrompng($config->get('sysRoot').$this->source);
				break;
			}
			
			if (!$old_image) { /* See if it failed */ 
			    $im  = imagecreatetruecolor($this->width->getValue(), $this->height->getValue()); /* Create a blank image */ 
			    $bgc = imagecolorallocate($im, 255, 255, 255); 
			    $tc  = imagecolorallocate($im, 0, 0, 0); 
			    imagefilledrectangle($im, 0, 0, $this->width->getValue(), $this->height->getValue(), $bgc); 
			    
			    imagestring($im, 1, 5, 5, "Error loading $this->source", $tc);
			    if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
			    	header("Content-Type: image/png");
					imagepng($im);
			    }else{
				    header("Content-Type: image/jpeg");
					imagejpeg($im);
			    }
				imagedestroy($im);
		    }else{
				// the dimensions of the source image
				$oldWidth = imagesx($old_image);
				$oldHeight = imagesy($old_image);
			
				// now create the new image
				$new_image = imagecreatetruecolor($this->width->getValue(), $this->height->getValue());
			
				// set a transparent background for PNGs
				if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
					// Turn off transparency blending (temporarily)
			        imagealphablending($new_image, false);
			
			        // Create a new transparent color for image
			        $color = imagecolorallocatealpha($new_image, 255, 0, 0, 0);
			
			        // Completely fill the background of the new image with allocated color.
			        imagefill($new_image, 0, 0, $color);
			
			        // Restore transparency blending
			        imagesavealpha($new_image, true);
				}
		        // copy the old image to the new image (in memory, not the file!)
		        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $this->width->getValue(), $this->height->getValue(), $oldWidth, $oldHeight);	
				
				// just making sure that we are not running in cache-only mode before sending output
				if(!$this->cache_only->getValue()) {
					if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
						header("Content-Type: image/png");
						imagepng($new_image);
					}else{
						header("Content-Type: image/jpeg");
						imagejpeg($new_image, '', 100*$this->quality->getValue());
					}
				}
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
		global $config;
		
		if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
			imagepng($image, $this->filename);
		else
			imagejpeg($image, $this->filename, 100*$this->quality->getValue());
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
		// just making sure that we are not running in cache-only mode
		if(!$this->cache_only->getValue())
			readfile($this->filename);		
	}
	
	/**
	 * renders the JavaScript for the image	 
	 */
	function render() {
		echo '<script language="javascript">';
		echo 'insertImage(\''.$this->source.'\','.$this->width->getValue().','.$this->height->getValue().',\''.$this->sourceType->getValue().'\', '.$this->quality->getValue().');';
		echo '</script>';
	}
	
	/**
	 * renders the Javascript to control the behaviour of the button
	 */
	function render_javascript() {
		global $config;
		
		header("Content-type: application/x-javascript");
		
		// begining of javascript
		// ----------------------
		echo <<<EOS
				
		function insertImage(source, width, height, sourceType, quality, scale) {
			/* returns a HTML image tag to the PHP file that draws in a PNG image of the resulting re-scaled image
			
			Parameters:
			source: the file location of the source images
			width: the width of the outputted image
			height: the height of the images
			sourceType: the type of image that that source is (options are "jpeg","gif", and "png")
			quality: the quality of the jpeg to be returned
			scale: flag to determine if the image will scale to match resolution (0 by default)
			
			*/
			
			// default quality setting if not provided
			quality = (quality == null || quality == "") ? 0.75 : quality;
			
			// default scale setting if not provided
			scale = (scale == null || scale == "") ? 0 : scale;
			
			// first make the scalable image units (based on the current resolution, compared to a default of 1024x768)
			var xu = (scale == 1) ? screen.width/1024 : 1;
			var yu = (scale == 1) ? screen.height/768 : 1;

			// now we determine the size of that the image will be scaled to
			var new_width = parseInt(width*xu);
			var new_height = parseInt(height*xu);
EOS;
// end of javascript
// -----------------
		if($config->get('sysCMSImagesWidgetSecure')) {
			$secureFields = View::generateSecurityFields();
			echo 'document.write(\'<img src="'.$config->get('sysURL').'/alpha/view/widgets/image.js.php?source=\'+source+\'&width=\'+new_width+\'&height=\'+new_height+\'&sourceType=\'+sourceType+\'&quality=\'+quality+\'&scale=\'+scale+\'&var1='.$secureFields[0].'&var2='.$secureFields[1].'" width="\'+new_width+\'" height="\'+new_height+\'" border="0"/>\')';
		}else{
			echo 'document.write(\'<img src="'.$config->get('sysURL').'/alpha/view/widgets/image.js.php?source=\'+source+\'&width=\'+new_width+\'&height=\'+new_height+\'&sourceType=\'+sourceType+\'&quality=\'+quality+\'&scale=\'+scale+\'" width="\'+new_width+\'" height="\'+new_height+\'" border="0"/>\')';
		}
		echo '}';

	} 
	
	/**
	 * setter for title
	 * @param string $title
	 */
	function set_title($title)
	{
		$this->title->setValue($title);
	}

	/**
	 * getter for title
	 * @return string title
	 */
	function get_title() {
		return $this->title->getValue();
	}
	
	/**
	 * converts a URL for an image to a relative path for the image, assuming it is
	 * hosted on the same server as the application
	 * @param string $imgURL
	 * @return string the path of the image
	 */
	function convertImageURLToPath($imgURL) {
		global $config;
		
		$imgPath = str_replace($config->get('sysURL'), '', $imgURL);
		
		return $imgPath;
	}
}

// if the GET variables are set for the image, then we can construct a new image object safe in the knowledge that the constructor will pick up on all of the
// image properties from GET.
if (isset($_GET["source"])) {
	$temp = new image();
}else{
	// check to make sure that this file is not being included from the PDF controller	
	if(basename($_SERVER["PHP_SELF"]) != 'view_article_pdf.php') {
		// if called from a Javascript link, render the Javascript code, else render the HTML
		// link to the Javascript code contained here.
		if (isset($_GET["render_javascript"]))
			image::render_javascript();
		else
			echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/view/widgets/image.js.php?render_javascript"></script>';
	}
}
	
?>