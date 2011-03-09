<?php

require_once $config->get('sysRoot').'alpha/util/AlphaErrorHandlers.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * A widget that can generate an image which is scaled to the screen resolution of the
 * user, and can be made secured to prevent hot-linking from remote sites.  Note that by
 * default, a jpg file will be returned (the source file can be jpg, png, or gif).
 * 
 * @package alpha::view::widgets
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class Image {
	/**
	 * The title of the image for alt text (optional).
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $title;
	
	/**
	 * The absolute path to the source image.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $source;
	
	/**
	 * The width of the image (can differ from the source file when scale=true).
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	private $width;
	
	/**
	 * The height of the image (can differ from the source file when scale=true).
	 * 
	 * @var Integer
	 * @since 1.0
	 */
	private $height;
	
	/**
	 * The file type of the source image (gif, jpg, or png supported)
	 * 
	 * @var Enum
	 * @since 1.0
	 */
	private $sourceType;
	
	/**
	 * The quality of the jpg image generated (0.00 to 1.00, 0.75 by default).
	 * 
	 * @var Double
	 * @since 1.0
	 */
	private $quality;
	
	/**
	 * Flag to determine if the image will scale to match the target resolution (false
	 * by default).
	 * 
	 * @var Boolean
	 * @since 1.0
	 */
	private $scale;
	
	/**
	 * Flag to determine if the link to the image will change every 24hrs, making hot-linking
	 * to the image difficult (false by default).
	 * 
	 * @var Boolean
	 * @since 1.0
	 */
	private $secure;
	
	/**
	 * The auto-generated name of the cache file for the image.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $filename;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * The constructor.
	 * 
	 * @param $source
	 * @param $width
	 * @param $height
	 * @param $sourceType
	 * @param $quality
	 * @param $scale
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function __construct($source, $width, $height, $sourceType, $quality=0.75, $scale=false, $secure=false) {
		self::$logger = new Logger('Image');
		self::$logger->debug('>>__construct(source=['.$source.'], width=['.$width.'], height=['.$height.'], sourceType=['.$sourceType.'], quality=['.$quality.'], scale=['.$scale.'], secure=['.$secure.'])');
			
		global $config;
			
		if(file_exists($source))
			$this->source = $source;
		else
			throw new IllegalArguementException('The source file for the Image widget ['.$source.'] cannot be found!');
		
		$this->sourceType = new Enum();
		$this->sourceType->setOptions(array('jpg','png','gif'));
		$this->sourceType->setValue($sourceType);
		
		if($quality < 0.0 || $quality > 1.0)
			throw new IllegalArguementException('The quality setting of ['.$quality.'] is outside of the allowable range of 0.0 to 1.0');
		
		$this->quality = new Double($quality);
		$this->scale = new Boolean($scale);
		$this->secure = new Boolean($secure);
		
		$this->width = new Integer($width);
		$this->height = new Integer($height);
		
		$this->setFilename();
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Renders the HTML <img> tag to the ViewImage controller, with all of the correct params to render the source
	 * image in the desired resolution.
	 * 
	 * @param $altText Set this value to render alternate text as part of the HTML link (defaults to no alternate text)
	 * @return string
	 * @since 1.0
	 */
	public function renderHTMLLink($altText='') {
		global $config;
		
		if($this->secure->getBooleanValue()) {
			$params = AlphaController::generateSecurityFields();
			return '<img src="'.FrontController::generateSecureURL('act=ViewImage&s='.$this->source.'&w='.$this->width->getValue().'&h='.$this->height->getValue().'&t='.$this->sourceType->getValue().'&q='.$this->quality->getValue().'&sc='.$this->scale->getValue().'&se='.$this->secure->getValue().'&var1='.$params[0].'&var2='.$params[1]).'"'.(empty($altText) ? '' : ' alt="'.$altText.'"').'/>';
		}else{
			return '<img src="'.FrontController::generateSecureURL('act=ViewImage&s='.$this->source.'&w='.$this->width->getValue().'&h='.$this->height->getValue().'&t='.$this->sourceType->getValue().'&q='.$this->quality->getValue().'&sc='.$this->scale->getValue().'&se='.$this->secure->getValue()).'"'.(empty($altText) ? '' : ' alt="'.$altText.'"').'/>';
		}
	}
	
	/**
	 * Setter for the filename, which also creates a sub-directory under /cache for images when required
	 * 
	 * @since 1.0
	 * @throws AlphaException
	 */
	private function setFilename() {
		global $config;
		
		if(!strpos($this->source, 'attachments/article_')) {
			// checking to see if we will write a jpg or png to the cache
			if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
				$this->filename = $config->get('sysRoot').'cache/images/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
			else
				$this->filename = $config->get('sysRoot').'cache/images/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
		}else{
			// make a cache dir for the article
			$cacheDir = $config->get('sysRoot').'cache/images/article_'.substr($this->source, strpos($this->source, 'attachments/article_')+20, 11);
			if(!file_exists($cacheDir)) {
				$success = mkdir($cacheDir);
			
				if (!$success) {
					throw new AlphaException('Unable to create the folder '.$cacheDir.' for the cache image, source file is '.$this->source);
				}
				
				// ...and set write permissions on the folder
				$success = chmod($cacheDir, 0777);
					
				if (!$success) {
					throw new AlphaException('Unable to set write permissions on the folder ['.$cacheDir.'].');
				}
			}
			
			// now set the filename to include the new cache directory
			if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
				$this->filename = $cacheDir.'/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
			else
				$this->filename = $cacheDir.'/'.basename($this->source, ".".$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
		}
		
		self::$logger->debug('Image filename is ['.$this->filename.']');
	}
	
	/**
	 * Gets the auto-generated filename for the image in the /cache directory
	 * 
	 * @since 1.0
	 */
	public function getFilename() {
		return $this->filename;
	}
	
	/**
	 * Renders the actual binary image using GD library calls.
	 * 
	 *  @since 1.0
	 */
	public function renderImage() {
		global $config;
		
		/*
		 * Handle secure image validation (if enabled in config).
		 * Not required for the view_article_pdf.php controller.
		 */
		if($config->get('sysCMSImagesWidgetSecure') && basename($_SERVER["PHP_SELF"]) != 'ViewArticlePDF.php') {
			$valid = AlphaController::checkSecurityFields();			
			
			// if not valid, just return a blank black image of the same dimensions
			if(!$valid) {
				$im  = imagecreatetruecolor($this->width->getValue(), $this->height->getValue()); /* Create a blank image */ 
			    $bgc = imagecolorallocate($im, 0, 0, 0);			    
			    imagefilledrectangle($im, 0, 0, $this->width->getValue(), $this->height->getValue(), $bgc); 
			    
			    if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
			    	header("Content-Type: image/png");
					imagepng($im);
			    }else{
				    header("Content-Type: image/jpeg");
					imagejpeg($im);
			    }
			    
				imagedestroy($im);
				
				self::$logger->warn('The client ['.$_SERVER['HTTP_USER_AGENT'].'] was blocked from accessing the file ['.$this->filename.'] due to bad security tokens being provided');
			}
		}
		
		// if scaled, we need to compute the target image size
		if($this->scale->getBooleanValue() && isset($_COOKIE['screenSize'])) {
			$originalScreenResolution = explode('x', $config->get('sysCMSImagesWidgetScreenResolution'));
			$originalScreenX = $originalScreenResolution[0];
			$originalScreenY = $originalScreenResolution[1];
				
			$targetScreenResolution = explode('x', $_COOKIE['screenSize']);
			$targetScreenX = $targetScreenResolution[0];
			$targetScreenY = $targetScreenResolution[1];
				
			// calculate the new units we will scale by 
			$xu = $targetScreenX/$originalScreenX;
			$yu = $targetScreenY/$originalScreenY;
				
			$this->width = new Integer(intval($this->width->getValue()*$xu));
			$this->height = new Integer(intval($this->height->getValue()*$yu));
			
			// need to update the cache filename as the dimensions have changed
			$this->setFilename();
		}
		
		// check the image cache first before we proceed
		if ($this->checkCache()) {
			$this->loadCache();			
		}else{
			// now get the old image
			switch ($this->sourceType->getValue()) {
				case "gif":
					$old_image = imagecreatefromgif($this->source);
				break;
				case "jpg":
					$old_image = imagecreatefromjpeg($this->source);
				break;
				case "png":
					$old_image = imagecreatefrompng($this->source);
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
				
				if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG')) {
					header("Content-Type: image/png");
					imagepng($new_image);
				}else{
					header("Content-Type: image/jpeg");
					imagejpeg($new_image, '', 100*$this->quality->getValue());
				}
				
				$this->cache($new_image);
				imagedestroy($old_image);
				imagedestroy($new_image);
			}
		}
	}
	
	/**
	 * Caches the image to the cache directory
	 * 
	 * @param image $image the binary GD image stream to save
	 * @since 1.0
	 */
	private function cache($image) {
		global $config;
		
		if($this->sourceType->getValue() == 'png' && $config->get('sysImagesPerservePNG'))
			imagepng($image, $this->filename);
		else
			imagejpeg($image, $this->filename, 100*$this->quality->getValue());
	}
	
	/**
	 * Used to check the image cache for the image jpeg cache file.
	 * 	 
	 * @return bool
	 * @since 1.0
	 */
	private function checkCache() {
		return file_exists($this->filename);
	}
	
	/**
	 * Method to load the content of the image cache file to the standard output stream (the browser)
	 * 
	 * @since 1.0
	 */
	private function loadCache() {		
		readfile($this->filename);		
	}
	
	/**
	 * Converts a URL for an image to a relative file system path for the image, assuming it is
	 * hosted on the same server as the application.
	 * 
	 * @param string $imgURL
	 * @return string the path of the image
	 * @since 1.0
	 */
	public static function convertImageURLToPath($imgURL) {
		global $config;
		
		$imgPath = str_replace($config->get('sysURL'), '', $imgURL);

		return $imgPath;
	}
}

?>