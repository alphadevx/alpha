<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\File\FileUtils;
use Alpha\Util\Http\Response;
use Alpha\Util\Helper\Validator;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Article;
use Alpha\View\View;

/**
 * Controller used to view (download) an attachment file on an Article.
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
class AttachmentController extends Controller implements ControllerInterface
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
     * The constructor.
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
     * Handle GET requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        try {
            if (isset($params['articleID']) && isset($params['filename'])) {
                if (!Validator::isInteger($params['articleID'])) {
                    throw new IllegalArguementException('The articleID ['.$params['articleID'].'] provided is invalid');
                }

                $article = new Article();
                $article->setID($params['articleID']);
                $filePath = $article->getAttachmentsLocation().'/'.$params['filename'];

                if (file_exists($filePath)) {
                    self::$logger->info('Downloading the file ['.$params['filename'].'] from the folder ['.$article->getAttachmentsLocation().']');

                    $pathParts = pathinfo($filePath);
                    $mimeType = FileUtils::getMIMETypeByExtension($pathParts['extension']);

                    $response = new Response(200, file_get_contents($filePath));
                    $response->setHeader('Content-Type', $mimeType);
                    $response->setHeader('Content-Disposition', 'attachment; filename="'.$pathParts['basename'].'"');
                    $response->setHeader('Content-Length', filesize($filePath));

                    self::$logger->debug('<<doGET');

                    return $response;
                } else {
                    self::$logger->error('Could not access article attachment file ['.$filePath.'] as it does not exist!');
                    throw new IllegalArguementException('File not found');
                }
            } else {
                self::$logger->error('Could not access article attachment as articleID and/or filename were not provided!');
                throw new IllegalArguementException('File not found');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }
    }

    /**
     * Handle PUT requests.
     *
     * @param \Alpha\Util\Http\Request $request
     *
     * @since 3.0
     *
     * @throws \Alpha\Exception\ResourceNotFoundException
     */
    public function doPUT($request)
    {
        self::$logger->debug('>>doPUT($request=['.var_export($request, true).'])');

        $params = $request->getParams();

        $config = ConfigProvider::getInstance();

        try {
            if (isset($params['articleID'])) {
                $article = new Article();
                $article->load($params['articleID']);

                if (isset($params['uploadBut'])) {
                    $source = $request->getFile('userfile')['tmp_name'];
                    $dest = $article->getAttachmentsLocation().'/'.$request->getFile('userfile')['name'];

                    // upload the file to the attachments directory
                    FileUtils::copy($source, $dest);

                    if (!file_exists($dest)) {
                        throw new AlphaException('Could not move the uploaded file ['.$request->getFile('userfile')['name'].']');
                    }

                    // set read/write permissions on the file
                    $success = chmod($dest, 0666);

                    if (!$success) {
                        throw new AlphaException('Unable to set read/write permissions on the uploaded file ['.$dest.'].');
                    }

                    if ($success) {
                        self::$logger->action('File '.$source.' uploaded to '.$dest);
                        $this->setStatusMessage(View::displayUpdateMessage('File '.$source.' uploaded to '.$dest));
                    }
                } elseif (isset($params['deletefile']) && $params['deletefile'] != '') {
                    $success = unlink($article->getAttachmentsLocation().'/'.$params['deletefile']);

                    if (!$success) {
                        throw new AlphaException('Could not delete the file ['.$params['deletefile'].']');
                    }

                    if ($success) {
                        self::$logger->action('File '.$article->getAttachmentsLocation().'/'.$params['deletefile'].' deleted');
                        $this->setStatusMessage(View::displayUpdateMessage('File '.$article->getAttachmentsLocation().'/'.$params['deletefile'].' deleted'));
                    }
                }

                $response = new Response(301);

                if ($this->getNextJob() != '') {
                    $response->redirect($this->getNextJob());
                } else {
                    if ($this->request->isSecureURI()) {
                        $response->redirect(FrontController::generateSecureURL('act=Alpha\\Controller\\ActiveRecordController&ActiveRecordType=Alpha\Model\Article&ActiveRecordID='.$article->getID().'&view=edit'));
                    } else {
                        $title = str_replace(' ', $config->get('cms.url.title.separator'), $article->get('title'));
                        $response->redirect($config->get('app.url').'/a/'.$title.'/edit');
                    }
                }

                self::$logger->debug('<<doPUT');

                return $response;
            } else {
                self::$logger->error('Could not process article attachment as articleID was not provided!');
                throw new IllegalArguementException('File not found');
            }
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }
    }
}
