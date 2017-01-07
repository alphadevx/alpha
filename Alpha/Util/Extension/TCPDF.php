<?php

namespace Alpha\Util\Extension;

use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Helper\Validator;
use Alpha\Controller\Front\FrontController;
use Alpha\View\Widget\Image;

/**
 * Custom version of the TCPDF library class, allowing for any required overrides.
 *
 * @since 1.0
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
class TCPDF extends \TCPDF
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Overrides the TCPDF::Image method to decrypt encrypted $file paths from the Image widget, then pass
     * them to the normal TCPDF::Image along with all of the other (unmodified) parameters.
     *
     * @param string $file    Name of the file containing the image.
     * @param float  $x       Abscissa of the upper-left corner.
     * @param float  $y       Ordinate of the upper-left corner.
     * @param float  $w       Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param float  $h       Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
     * @param string $type    Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library) and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If not specified, the type is inferred from the file extension.
     * @param mixed  $link    URL or identifier returned by AddLink().
     * @param string $align   Indicates the alignment of the pointer next to image insertion relative to image height. The value can be:<ul><li>T: top-right for LTR or top-left for RTL</li><li>M: middle-right for LTR or middle-left for RTL</li><li>B: bottom-right for LTR or bottom-left for RTL</li><li>N: next line</li></ul>
     * @param bool   $resize  If true resize (reduce) the image to fit $w and $h (requires GD library).
     * @param int    $dpi     dot-per-inch resolution used on resize
     * @param string $palign  Allows to center or align the image on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left for LTR or right for RTL</li></ul>
     * @param bool   $ismask  true if this image is a mask, false otherwise
     * @param mixed  $imgmask image object returned by this function or false
     * @param mixed  $border  Indicates if borders must be drawn around the image. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
     * @param $hidden (boolean) If true do not display the image.
     * @param $fitonpage (boolean) If true the image is resized to not exceed page dimensions.
     * @param $alt (boolean) If true the image will be added as alternative and not directly printed (the ID of the image will be returned).
     * @param $altimgs (array) Array of alternate images IDs. Each alternative image must be an array with two values: an integer representing the image ID (the value returned by the Image method) and a boolean value to indicate if the image is the default for printing.
     *
     * @since 1.0
     */
    public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array())
    {
        if (self::$logger == null) {
            self::$logger = new Logger('TCPDF');
        }

        $config = ConfigProvider::getInstance();

        self::$logger->debug('Processing image file URL ['.$file.']');

        try {
            if (mb_strpos($file, '/tk/') !== false) {
                $start = mb_strpos($file, '/tk/') + 3;
                $end = mb_strlen($file);
                $tk = mb_substr($file, $start + 1, $end - ($start + 1));
                $decoded = FrontController::getDecodeQueryParams($tk);

                parent::Image($decoded['source'], $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border);
            } else {
                // it has no query string, so threat as a regular image URL
                if (Validator::isURL($file)) {
                    parent::Image($config->get('app.root').'/'.Image::convertImageURLToPath($file), $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
                } else {
                    parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
                }
            }
        } catch (\Exception $e) {
            self::$logger->error('Error processing image file URL ['.$file.'], error ['.$e->getMessage().']');
            throw $e;
        }
    }
}
