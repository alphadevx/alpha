<?php

namespace Alpha\Util\Http;

use Alpha\Model\IndexedPage;
use Alpha\Model\Type\Timestamp;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\LockingException;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Logging\Logger;
use Crwlr\Crawler\HttpCrawler;
use Crwlr\Crawler\Loader\LoaderInterface;
use Crwlr\Crawler\Loader\Http\HttpLoader;
use Crwlr\Crawler\UserAgents\BotUserAgent;
use Crwlr\Crawler\UserAgents\UserAgentInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Solarium\Client;
use Solarium\Exception\HttpException;
use Exception;
use Error;

/**
 * A web crawler HTTP client.
 *
 * @since 4.1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2024, John Collins (founder of Alpha Framework).
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
class AlphaCrawler extends HttpCrawler
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     */
    private static $alphaLogger = null;

    protected function userAgent(): UserAgentInterface
    {
        $config = ConfigProvider::getInstance();

        return BotUserAgent::make($config->get('search.indexer.user.agent'));
    }

    public function loader(UserAgentInterface $userAgent, LoggerInterface $logger): LoaderInterface
    {
        $loader = new HttpLoader($userAgent, logger: $logger, defaultGuzzleClientConfig: [
                'connect_timeout' => 5,
                'timeout' => 5,
            ]);

        $loader->onError(function (RequestInterface $request, Exception|Error|ResponseInterface $exceptionOrResponse, $logger) {

            $config = ConfigProvider::getInstance();

            self::$alphaLogger = new Logger('AlphaCrawler');
            self::$alphaLogger->setLogProviderFile($config->get('app.file.store.dir').'logs/crawl.log');

            $adapter = new Curl();
            $eventDispatcher = new EventDispatcher();

            $solrConfig = array(
                'endpoint' => array(
                    'localhost' => array(
                        'host' => $config->get('search.solr.host'),
                        'port' => $config->get('search.solr.port'),
                        'path' => $config->get('search.solr.path'),
                        'core' => $config->get('search.solr.core'),
                        'username' => $config->get('search.solr.username'),
                        'password' => $config->get('search.solr.password')
                    )
                )
            );

            // Solr client
            $client = new Client($adapter, $eventDispatcher, $solrConfig);

            $logMessage = 'Failed to load ' . $request->getUri()->__toString() . ': ';

            if ($exceptionOrResponse instanceof ResponseInterface) {
                $logMessage .= 'got response ' . $exceptionOrResponse->getStatusCode() . ' - ' .
                    $exceptionOrResponse->getReasonPhrase();
            } else {
                $logMessage .= $exceptionOrResponse->getMessage();
            }

            self::$alphaLogger->error($logMessage);

            if (method_exists($exceptionOrResponse, 'getStatusCode')) {
                /*$page = new IndexedPage();
                try {
                    $page->loadByAttribute('url', $request->getUri()->__toString());
                } catch (RecordNotFoundException $e) {
                }

                $host = parse_url($request->getUri()->__toString(), PHP_URL_HOST);
                $page->set('url', $request->getUri()->__toString());
                $page->set('host', $host);
                $page->set('tstamp', new Timestamp());
                $page->set('responseCode', $exceptionOrResponse->getStatusCode());

                try {
                    $page->save();
                } catch (LockingException $e) {
                }*/
                self::$alphaLogger->info('Deleting the URL ['.$request->getUri()->__toString().'] due to an error response ['.$exceptionOrResponse->getStatusCode().']');

                // delete from the database
                $page = new IndexedPage();
                try {
                    $page->loadByAttribute('url', $request->getUri()->__toString());
                    $page->delete();
                } catch (RecordNotFoundException $e) {
                }

                // delete from Solr
                $update = $client->createUpdate();
                $update->addDeleteById($request->getUri()->__toString());
                $update->addCommit();

                try {
                    $solrResult = $client->update($update);
                } catch (HttpException $e) {
                    self::$alphaLogger->error('[worker '.getmypid().'] '.$e->getMessage());
                }
            }
        });

        return $loader;
    }
}
