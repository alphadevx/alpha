<?php

namespace Alpha\Controller;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\ResourceNotAllowedException;
use Alpha\Exception\IllegalArguementException;
use Alpha\View\Widget\Image;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Model\Type\Boolean;

/**
 *
 * Controller for viewing an image rendered with the Image widget.
 *
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2015, John Collins (founder of Alpha Framework).
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
class ImageController extends Controller implements ControllerInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * Constructor
     *
     * @param string $visibility The name of the rights group that can access this controller.
     * @since 1.0
     */
    public function __construct($visibility='Public')
    {
        self::$logger = new Logger('ImageController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct($visibility);

        self::$logger->debug('<<__construct');
    }

    /**
     * Handles get requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @since 1.0
     * @throws Alpha\Exception\ResourceNotFoundException
     * @throws Alpha\Exception\ResourceNotAllowedException
     */
    public function doGet($request)
    {
        self::$logger->debug('>>doGet(request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        try {
            $imgSource = urldecode($params['source']);
            $imgWidth = $params['width'];
            $imgHeight = $params['height'];
            $imgType = $params['type'];
            $imgQuality = (double)$params['quality'];
            $imgScale = new Boolean($params['scale']);
            $imgSecure = new Boolean($params['secure']);
        } catch (\Exception $e) {
            self::$logger->error('Required param missing for ImageController controller['.$e->getMessage().']');
            throw new ResourceNotFoundException('File not found');
        }

        // handle secure tokens
        if ($imgSecure->getBooleanValue() && $config->get('cms.images.widget.secure')) {
            $valid = $this->checkSecurityFields();

            // if not valid, just return a blank black image of the same dimensions
            if (!$valid) {
                $im  = imagecreatetruecolor($imgWidth, $imgHeight);
                $bgc = imagecolorallocate($im, 0, 0, 0);
                imagefilledrectangle($im, 0, 0, $imgWidth, $imgHeight, $bgc);

                if ($imgSource == 'png' && $config->get('cms.images.perserve.png')) {
                    ob_start();
                    imagepng($im);
                    $body = ob_get_contents();
                    $contentType = 'image/png';
                    ob_end_clean();
                } else {
                    ob_start();
                    imagejpeg($im);
                    $body = ob_get_contents();
                    $contentType = 'image/jpeg';
                    ob_end_clean();
                }

                imagedestroy($im);

                self::$logger->warn('The client ['.$request->getUserAgent().'] was blocked from accessing the file ['.$imgSource.'] due to bad security tokens being provided');

                return new Response(200, $body, array('Content-Type' => $contentType));
            }
        }

        try {
            $image = new Image($imgSource, $imgWidth, $imgHeight, $imgType, $imgQuality, $imgScale->getBooleanValue(), $imgSecure->getBooleanValue());
            ob_start();
            $image->renderImage();
            $body = ob_get_contents();
            ob_end_clean();
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException('File not found');
        }

        self::$logger->debug('<<__doGet');

        if ($imgSource == 'png' && $config->get('cms.images.perserve.png')) {
            return new Response(200, $body, array('Content-Type' => 'image/png'));
        } else {
            return new Response(200, $body, array('Content-Type' => 'image/jpeg'));
        }
    }
}

?>