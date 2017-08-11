<?php

namespace Alpha\Util\Image;

use Alpha\Exception\IllegalArguementException;

/**
 * A utility class for carrying out various image file tasks.
 *
 * @since 1.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
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
class ImageUtils
{
    /**
     * Generates a perfectly square thumbnail from the supplied original image file.
     *
     * @param string $original   The path to the original file
     * @param string $thumbnail  The path to the new thumbnail file to generate
     * @param int    $dimensions The width/height of the generated thumbnail
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.1
     */
    public static function generateSquareThumbnail($original, $thumbnail, $dimensions)
    {
        if ($dimensions <= 0) {
            throw new IllegalArguementException('Illegal dimensions value provided ['.$dimensions.'], should be greater than zero');
        }

        $newImage = imagecreatetruecolor($dimensions, $dimensions);
        $imageInfo = getimagesize($original);
        $originalX = 0;

        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $type = 'jpg';
                $originalImage = imagecreatefromjpeg($original);
            break;
            case 'image/gif':
                $type = 'gif';
                $originalImage = imagecreatefromgif($original);
            break;
            case 'image/png':
                $type = 'png';
                $originalImage = imagecreatefrompng($original);
            break;
            default:
                throw new IllegalArguementException('Unsupported image format ['.$imageInfo['mime'].']');
        }

        // in case the destination type is different from the source...
        $pathParts = pathinfo($thumbnail);
        if (!isset($pathParts['extension'])) {
            $type = $pathParts['extension'];
        }

        list($originalWidth, $originalHeight) = $imageInfo;

        if ($originalWidth > $originalHeight) {
            $originalX = floor(($originalWidth-$originalHeight)/2);
            $sourceWidth = $sourceHeight = $originalHeight;
        } else {
            $sourceWidth = $sourceHeight = $originalWidth;
        }

        imagecopyresampled($newImage, $originalImage, 0, 0, $originalX, 0, $dimensions, $dimensions, $sourceWidth, $sourceHeight);

        return self::saveImage($newImage, $type, $thumbnail);
    }

    /**
     * Saves the GD image resource to the file path indicated.
     *
     * @param image  $imageResource The GD image resource to save
     * @param string $type          The image type (jpg, png, or gif)
     * @param string $destination   The desination file path of the image file to create
     *
     * @throws \Alpha\Exception\IllegalArguementException
     *
     * @since 1.1
     */
    public static function saveImage($imageResource, $type, $destination)
    {
        if (!in_array($type, array('jpg', 'png', 'gif'))) {
            throw new IllegalArguementException('Illegal image type ['.$type.'], cannot create file');
        }

        if (($type == 'jpg')) {
            imagejpeg($imageResource, $destination);
        } else {
            $function = 'image'.$type;

            $function($imageResource, $destination);
        }

        // free up memory
        imagedestroy($imageResource);
    }
}
