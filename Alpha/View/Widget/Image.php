<?php

namespace Alpha\View\Widget;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Model\Type\Integer;
use Alpha\Model\Type\Enum;
use Alpha\Model\Type\Boolean;
use Alpha\Model\Type\Double;
use Alpha\Exception\IllegalArguementException;
use Alpha\Controller\Controller;
use Alpha\Controller\Front\FrontController;

/**
 * A widget that can generate an image which is scaled to the screen resolution of the
 * user, and can be made secured to prevent hot-linking from remote sites.  Note that by
 * default, a jpg file will be returned (the source file can be jpg, png, or gif).
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
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
 */
class Image
{
    /**
     * The title of the image for alt text (optional).
     *
     * @var string
     *
     * @since 1.0
     */
    private $title;

    /**
     * The absolute path to the source image.
     *
     * @var string
     *
     * @since 1.0
     */
    private $source;

    /**
     * The width of the image (can differ from the source file when scale=true).
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    private $width;

    /**
     * The height of the image (can differ from the source file when scale=true).
     *
     * @var \Alpha\Model\Type\Integer
     *
     * @since 1.0
     */
    private $height;

    /**
     * The file type of the source image (gif, jpg, or png supported).
     *
     * @var \Alpha\Model\Type\Enum
     *
     * @since 1.0
     */
    private $sourceType;

    /**
     * The quality of the jpg image generated (0.00 to 1.00, 0.75 by default).
     *
     * @var \Alpha\Model\Type\Double
     *
     * @since 1.0
     */
    private $quality;

    /**
     * Flag to determine if the image will scale to match the target resolution (false
     * by default).
     *
     * @var \Alpha\Model\Type\Boolean
     *
     * @since 1.0
     */
    private $scale;

    /**
     * Flag to determine if the link to the image will change every 24hrs, making hot-linking
     * to the image difficult (false by default).
     *
     * @var \Alpha\Model\Type\Boolean
     *
     * @since 1.0
     */
    private $secure;

    /**
     * The auto-generated name of the cache file for the image.
     *
     * @var string
     *
     * @since 1.0
     */
    private $filename;

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The constructor.
     *
     * @param string $source
     * @param int $width
     * @param int $height
     * @param string $sourceType
     * @param float $quality
     * @param bool $scale
     * @param bool $secure
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.0
     */
    public function __construct(string $source, int $width, int $height, string $sourceType, float $quality = 0.75, bool $scale = false, bool $secure = false)
    {
        self::$logger = new Logger('Image');
        self::$logger->debug('>>__construct(source=['.$source.'], width=['.$width.'], height=['.$height.'], sourceType=['.$sourceType.'], quality=['.$quality.'], scale=['.$scale.'], secure=['.$secure.'])');

        if (file_exists($source)) {
            $this->source = $source;
        } else {
            throw new IllegalArguementException('The source file for the Image widget ['.$source.'] cannot be found!');
        }

        $this->sourceType = new Enum();
        $this->sourceType->setOptions(array('jpg', 'png', 'gif'));
        $this->sourceType->setValue($sourceType);

        if ($quality < 0.0 || $quality > 1.0) {
            throw new IllegalArguementException('The quality setting of ['.$quality.'] is outside of the allowable range of 0.0 to 1.0');
        }

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
     * @param string $altText Set this value to render alternate text as part of the HTML link (defaults to no alternate text)
     *
     * @since 1.0
     */
    public function renderHTMLLink(string $altText = ''): string
    {
        $config = ConfigProvider::getInstance();

        $url = '';

        if ($this->secure->getBooleanValue()) {
            $params = Controller::generateSecurityFields();

            $url = FrontController::generateSecureURL('act=Alpha\Controller\ImageController&source='.$this->source.'&width='.$this->width->getValue().'&height='.$this->height->getValue().'&type='.$this->sourceType->getValue().'&quality='.$this->quality->getValue().'&scale='.$this->scale->getValue().'&secure='.$this->secure->getValue().'&var1='.$params[0].'&var2='.$params[1]);
        } else {
            $url = FrontController::generateSecureURL('act=Alpha\Controller\ImageController&source='.$this->source.'&width='.$this->width->getValue().'&height='.$this->height->getValue().'&type='.$this->sourceType->getValue().'&quality='.$this->quality->getValue().'&scale='.$this->scale->getValue().'&secure='.$this->secure->getValue());
        }

        return '<img src="'.$url.'"'.(empty($altText) ? '' : ' alt="'.$altText.'"').($config->get('cms.images.widget.bootstrap.responsive') ? ' class="img-responsive"' : '').'/>';
    }

    /**
     * Setter for the filename, which also creates a sub-directory under /cache for images when required.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\AlphaException
     */
    private function setFilename(): void
    {
        $config = ConfigProvider::getInstance();

        if (!strpos($this->source, 'attachments/article_')) {
            // checking to see if we will write a jpg or png to the cache
            if ($this->sourceType->getValue() == 'png' && $config->get('cms.images.perserve.png')) {
                $this->filename = $config->get('app.file.store.dir').'cache/images/'.basename($this->source, '.'.$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
            } else {
                $this->filename = $config->get('app.file.store.dir').'cache/images/'.basename($this->source, '.'.$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
            }
        } else {
            // make a cache dir for the article
            $cacheDir = $config->get('app.file.store.dir').'cache/images/article_'.mb_substr($this->source, mb_strpos($this->source, 'attachments/article_')+20, 11);
            if (!file_exists($cacheDir)) {
                $success = mkdir($cacheDir, 0777, true);

                if (!$success) {
                    throw new AlphaException('Unable to create the folder '.$cacheDir.' for the cache image, source file is '.$this->source);
                }

                if (!$success) {
                    throw new AlphaException('Unable to set write permissions on the folder ['.$cacheDir.'].');
                }
            }

            // now set the filename to include the new cache directory
            if ($this->sourceType->getValue() == 'png' && $config->get('cms.images.perserve.png')) {
                $this->filename = $cacheDir.'/'.basename($this->source, '.'.$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.png';
            } else {
                $this->filename = $cacheDir.'/'.basename($this->source, '.'.$this->sourceType->getValue()).'_'.$this->width->getValue().'x'.$this->height->getValue().'.jpg';
            }
        }

        self::$logger->debug('Image filename is ['.$this->filename.']');
    }

    /**
     * Gets the auto-generated filename for the image in the /cache directory.
     *
     * @since 1.0
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Renders the actual binary image using GD library calls.
     *
     * @since 1.0
     */
    public function renderImage(): void
    {
        $config = ConfigProvider::getInstance();

        // check the image cache first before we proceed
        if ($this->checkCache()) {
            $this->loadCache();
        } else {
            // now get the old image
            switch ($this->sourceType->getValue()) {
                case 'gif':
                    $oldImage = imagecreatefromgif($this->source);
                break;
                case 'jpg':
                    $oldImage = imagecreatefromjpeg($this->source);
                break;
                default:
                    $oldImage = imagecreatefrompng($this->source);
            }

            // the dimensions of the source image
            $oldWidth = imagesx($oldImage);
            $oldHeight = imagesy($oldImage);

            // now create the new image
            $newImage = imagecreatetruecolor($this->width->getValue(), $this->height->getValue());

            // set a transparent background for PNGs
            if ($this->sourceType->getValue() == 'png' && $config->get('cms.images.perserve.png')) {
                // Turn off transparency blending (temporarily)
                imagealphablending($newImage, false);

                // Create a new transparent color for image
                $color = imagecolorallocatealpha($newImage, 255, 0, 0, 0);

                // Completely fill the background of the new image with allocated color.
                imagefill($newImage, 0, 0, $color);

                // Restore transparency blending
                imagesavealpha($newImage, true);
            }
            // copy the old image to the new image (in memory, not the file!)
            imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $this->width->getValue(), $this->height->getValue(), $oldWidth, $oldHeight);

            if ($this->sourceType->getValue() == 'png' && $config->get('cms.images.perserve.png')) {
                imagepng($newImage);
            } else {
                imagejpeg($newImage, null, 100*$this->quality->getValue());
            }

            $this->cache($newImage);
            imagedestroy($oldImage);
            imagedestroy($newImage);
        }
    }

    /**
     * Caches the image to the cache directory.
     *
     * @param GdImage $image the binary GD image stream to save
     *
     * @since 1.0
     */
    private function cache(\GdImage $image): void
    {
        $config = ConfigProvider::getInstance();

        if ($this->sourceType->getValue() == 'png' && $config->get('cms.images.perserve.png')) {
            imagepng($image, $this->filename);
        } else {
            imagejpeg($image, $this->filename, 100*$this->quality->getValue());
        }
    }

    /**
     * Used to check the image cache for the image jpeg cache file.
     *
     * @since 1.0
     */
    private function checkCache(): bool
    {
        return file_exists($this->filename);
    }

    /**
     * Method to load the content of the image cache file to the standard output stream (the browser).
     *
     * @since 1.0
     */
    private function loadCache(): void
    {
        readfile($this->filename);
    }

    /**
     * Converts a URL for an image to a relative file system path for the image, assuming it is
     * hosted on the same server as the application. Returns the path of the image.
     *
     * @param string $imgURL
     *
     * @since 1.0
     */
    public static function convertImageURLToPath(string $imgURL): string
    {
        $config = ConfigProvider::getInstance();

        $imgPath = str_replace($config->get('app.url').'/', '', $imgURL);

        return $imgPath;
    }
}
