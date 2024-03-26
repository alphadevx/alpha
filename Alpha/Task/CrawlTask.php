<?php

namespace Alpha\Task;

use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\IndexedPage;
use Alpha\Model\Type\Timestamp;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\AlphaCrawler;
use Crwlr\Crawler\Steps\Html;
use Crwlr\Crawler\Steps\Dom;
use Crwlr\Crawler\Steps\Loading\Http;
use Crwlr\CrawlerExtBrowser\Steps\Screenshot;
use Crwlr\Url\Url;
use Crwlr\Url\Exceptions\InvalidUrlException
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Solarium\Client;
use Solarium\Exception\HttpException;

/**
 * A persistent task for crawing the webpages defined in config/seed-urls.ini and indexing
 * the pages found to the defined search engine.
 *
 * @since 1.1
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
class CrawlTask implements TaskInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     */
    private static $logger = null;

    /**
     * {@inheritdoc}
     */
    public function doTask(): void
    {
        $config = ConfigProvider::getInstance();

        self::$logger = new Logger('CrawlTask');
        self::$logger->setLogProviderFile($config->get('app.file.store.dir').'logs/crawl.log');

        $seedfile = $config->get('app.root').'config/seed-urls.ini';

        if (file_exists($seedfile)) {
            $seedURLs = file($seedfile, FILE_IGNORE_NEW_LINES);
            // random-sort the initial seed URLs for running this task in mulitple threads
            shuffle($seedURLs);
            self::$logger->debug('Read ['.count($seedURLs).'] seed URLs from the file ['.$seedfile.']');
        } else {
            throw new AlphaException('Unable to find a seed-urls.ini file in the application!');
        }

        $adapter = new Curl();
        $eventDispatcher = new EventDispatcher();

        $solrConfig = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $config->get('solr.host'),
                    'port' => $config->get('solr.port'),
                    'path' => $config->get('solr.path'),
                    'core' => $config->get('solr.core'),
                    'username' => $config->get('solr.username'),
                    'password' => $config->get('solr.password')
                )
            )
        );

        // create a client instance
        $client = new Client($adapter, $eventDispatcher, $solrConfig);

        while (true) {
            foreach ($seedURLs as $seedURL) {
                $crawler = new AlphaCrawler();

                self::$logger->info('Crawling URL ['.$seedURL.']');

                $crawler = new AlphaCrawler();

                // TODO if the crawler returns a 404, this URL should be deleted from the index
                $crawler->input($seedURL)
                    /*->addStep( // TODO wrap screenshot feature in config
                        Screenshot::loadAndTake($config->get('app.file.store.dir').'cache/images/screenshots')
                        ->addToResult(['url', 'screenshotPath'])
                    )*/
                    ->addStep(Http::get())
                    ->addStep(
                        Html::first('html')
                            ->extract([
                                'title' => 'title',
                                'content' => Dom::cssSelector('body')->text(),
                                'links' => Dom::cssSelector('a')->attribute('href')
                            ])
                            ->addToResult()
                    );

                foreach ($crawler->run() as $result) {

                    $host = parse_url($seedURL, PHP_URL_HOST);

                    $result->set('url', $seedURL);
                    $result->set('host', $host);

                    /* TODO:
                        1. Check the DB for when this was last indexed
                        2. Re-index to Solr and the DB as required
                        3. Add links found on the page to seedURLs array for the next iteration
                     */

                    // 1. Check the DB for when this page was last indexed
                    $page = new IndexedPage();

                    try {
                        $page->loadByAttribute('url', $seedURL);
                    } catch (RecordNotFoundException $e) {
                        $page->set('url', $seedURL);
                        $page->set('host', $host);
                    }

                    $page->set('tstamp', new Timestamp());
                    // TODO wrap screenshot feature in config
                    //$page->set('screenshot', $result->get('screenshotPath')); // TODO: delete old screenshot
                    $page->save();

                    // 2. Re-index to Solr and the DB as required
                    $update = $client->createUpdate();
                    $doc = $update->createDocument();
                    $doc->id = $seedURL;
                    $doc->url = $seedURL;
                    $doc->host = $host;
                    $doc->title = $result->get('title');
                    $doc->content = $result->get('content');
                    $doc->tstamp = gmdate("Y-m-d\TH:i:s\Z");
                    $update->addDocuments(array($doc));
                    $update->addCommit();

                    try {
                        $solrResult = $client->update($update);
                    } catch (HttpException $e) {
                        self::$logger->error($e->getMessage());
                    }

                    // 3. Add links found on the page to seedURLs array for the next iteration
                    self::$logger->debug('url ['.$seedURL.'] from host ['.$host.'] returned ['.(is_array($result->get('links')) ? count($result->get('links')) : '0').'] child links to add to the seedURL list');

                    if (is_array($result->get('links'))) {
                        $newURLs = $result->get('links');
                        $pageURL = Url::parse($seedURL);

                        $absoluteLinks = array_map(function ($newURL) use ($pageURL) {
                            try {
                                return $pageURL->resolve($newURL)->toString();
                            } catch (InvalidUrlException $e) {
                                self::$logger->error($e->getMessage());
                            }
                        }, $newURLs);

                        $seedURLs = array_merge($seedURLs, $absoluteLinks);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRunTime(): int
    {
        return 600;
    }
}
