<?php

namespace Alpha\Controller;

use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\View\Widgets\Image;
use Alpha\Util\Logging\Logger;

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
     * @param array $params
     * @since 1.0
     * @throws Alpha\Exception\ResourceNotFoundException
     */
    public function doGet($params)
    {
        self::$logger->debug('>>doGet(params=['.var_export($params, true).'])');

        try {
            $imgSource = $params['s'];
            $imgWidth = $params['w'];
            $imgHeight = $params['h'];
            $imgType = $params['t'];
            $imgQuality = (double)$params['q'];
            $imgScale = (boolean)$params['sc'];
            $imgSecure = (boolean)$params['se'];
        } catch (\Exception $e) {
            self::$logger->error('Required param missing for ImageController controller['.$e->getMessage().']');
            throw new ResourceNotFoundException('File not found');
        }

        try {
            $image = new Image($imgSource, $imgWidth, $imgHeight, $imgType, $imgQuality, $imgScale, $imgSecure);
            $image->renderImage();
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException('File not found');
        }

        self::$logger->debug('<<__doGet');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     * @since 1.0
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        self::$logger->debug('<<doPOST');
    }
}

?>