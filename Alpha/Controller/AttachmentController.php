<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\File\FileUtils;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;

/**
 *
 * Controller used to view (download) an attachment file on an Article
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
class AttachmentController extends Controller implements ControllerInterface
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * The constructor
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('AttachmentController');
        self::$logger->debug('>>__construct()');

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param array $params
     * @since 1.0
     * @throws Alpha\Exception\ResourceNotFoundException
     */
    public function doGET($params)
    {
        self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

        $config = ConfigProvider::getInstance();

        try {
            if (isset($params['dir']) && isset($params['filename'])) {
                $filePath = $params['dir'].'/'.$params['filename'];

                if (file_exists($filePath)) {
                    self::$logger->info('Downloading the file ['.$params['filename'].'] from the folder ['.$params['dir'].']');

                    $pathParts = pathinfo($filePath);
                    $mimeType = FileUtils::getMIMETypeByExtension($pathParts['extension']);
                    header('Content-Type: '.$mimeType);
                    header('Content-Disposition: attachment; filename="'.$pathParts['basename'].'"');
                    header('Content-Length: '.filesize($filePath));

                    readfile($filePath);

                    self::$logger->debug('<<doGET');
                    exit;
                } else {
                    self::$logger->error('Could not access article attachment file ['.$filePath.'] as it does not exist!');
                    throw new IllegalArguementException('File not found');
                }
            } else {
                self::$logger->error('Could not access article attachment as dir and/or filename were not provided!');
                throw new IllegalArguementException('File not found');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }

        self::$logger->debug('<<doGET');
    }

    /**
     * Handle POST requests
     *
     * @param array $params
     */
    public function doPOST($params)
    {
        self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

        self::$logger->debug('<<doPOST');
    }
}

?>