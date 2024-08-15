<?php

namespace Alpha\Task;

use Alpha\Exception\AlphaException;
use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\LockingException;
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
use Crwlr\Url\Exceptions\InvalidUrlException;
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
            self::$logger->info('[worker '.getmypid().'] Read ['.count($seedURLs).'] seed URLs from the file ['.$seedfile.']');
        } else {
            throw new AlphaException('[worker '.getmypid().'] Unable to find a seed-urls.ini file in the application!');
        }

        $adapter = new Curl();
        $adapter->setTimeout(10);
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

        AlphaCrawler::setMemoryLimit('512m');

        $iteration = 1;

        while (true) {
            self::$logger->info('[worker '.getmypid().'] starting iteration ['.$iteration.'] with ['.count($seedURLs).'] URLs');
            self::$logger->info('[worker '.getmypid().'] memory usage is ['.memory_get_usage().'] bytes');

            $somethingIndexed = false;

            foreach ($seedURLs as $seedURL) {

                self::$logger->debug('[worker '.getmypid().'] Crawling URL ['.$seedURL.']');

                // if it's a .pdf/.xml, remove it and skip to next iteration
                $path = parse_url($seedURL, PHP_URL_PATH);
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if ($ext == '.pdf' || $ext == '.xml') {
                    self::$logger->debug('[worker '.getmypid().'] Skipping .pdf/.xml file ['.$seedURL.']');
                    unset($seedURLs[$seedURL]);
                    continue;
                }

                // if it's an anchor URL, remove it and skip to next iteration
                if (strpos($seedURL, '#') !== false) {
                    self::$logger->debug('[worker '.getmypid().'] Skipping anchor link ['.$seedURL.'] and removing it from the index');

                    // delete from the database
                    $page = new IndexedPage();
                    try {
                        $page->loadByAttribute('url', $seedURL);
                        $page->delete();
                    } catch (RecordNotFoundException $e) {
                    }

                    // delete from Solr
                    $update = $client->createUpdate();
                    $update->addDeleteById($seedURL);
                    $update->addCommit();

                    try {
                        $solrResult = $client->update($update);
                    } catch (HttpException $e) {
                        self::$logger->error('[worker '.getmypid().'] '.$e->getMessage());
                    }

                    // skip
                    continue;
                }

                $page = new IndexedPage();
                $host = parse_url($seedURL, PHP_URL_HOST);

                try {
                    $page->loadByAttribute('url', $seedURL);

                    $ts = $page->getPropObject('tstamp');

                    if (time() - $ts->getUnixValue() < 3601) {
                        self::$logger->debug('[worker '.getmypid().'] Skipping recently ['.$ts->getValue().'] indexed URL ['.$seedURL.']');
                        unset($seedURLs[$seedURL]);
                        continue;
                    }
                } catch (RecordNotFoundException $e) {
                    $page->set('url', $seedURL);
                    $page->set('host', $host);
                }

                // web crawler client
                $crawler = new AlphaCrawler();

                // TODO if the crawler returns a 404, this URL should be deleted from the index
                if ($config->get('search.indexer.take.screenshot') == true) {
                    $crawler->input($seedURL)
                    ->addStep( // screenshot capture feature is enabled in config
                        Screenshot::loadAndTake($config->get('app.file.store.dir').'cache/images/screenshots')
                        ->addToResult(['url', 'screenshotPath'])
                    )
                    ->addStep(Http::get()->addToResult(['status']))
                    ->addStep(
                        Html::first('html')
                            ->extract([
                                'title' => 'title',
                                'content' => Dom::cssSelector('body')->text(),
                                'links' => Dom::cssSelector('a')->attribute('href'),
                                'imageUrl' => Dom::cssSelector('img.mw-file-element')->attribute('src')->first()->toAbsoluteUrl()
                            ])
                            ->addToResult()
                    );
                } else {
                    $crawler->input($seedURL)
                    ->addStep(Http::get()->addToResult(['status']))
                    ->addStep(
                        Html::first('html')
                            ->extract([
                                'title' => 'title',
                                'content' => Dom::cssSelector('body')->text(),
                                'links' => Dom::cssSelector('a')->attribute('href'),
                                'imageUrl' => Dom::cssSelector('img.mw-file-element')->attribute('src')->first()->toAbsoluteUrl()
                            ])
                            ->addToResult()
                    );
                }


                foreach ($crawler->run() as $result) {

                    $result->set('url', $seedURL);
                    $result->set('host', $host);

                    $page->set('tstamp', new Timestamp());
                    $page->set('responseCode', $result->get('status'));
                    if ($config->get('search.indexer.take.screenshot') == true) {
                        $page->set('screenshot', $result->get('screenshotPath')); // TODO: delete old screenshot
                        if ($result->get('imageUrl') != '') {
                            $page->set('imageUrl', $result->get('imageUrl'));
                        }
                    }

                    try {
                        $page->save();
                    } catch (LockingException $e) {
                        self::$logger->error('[worker '.getmypid().'] '.$e->getMessage());
                    }

                    // Re-index to Solr and the DB as required
                    $update = $client->createUpdate();
                    $doc = $update->createDocument();
                    $doc->id = $seedURL;
                    $doc->url = $seedURL;
                    $doc->host = $host;
                    $doc->title = $result->get('title');
                    if (is_string($result->get('content'))) {
                        $doc->content = $this->stripHTML($result->get('content'));
                    } else {
                        // let's not index pages with no boby content
                        unset($seedURLs[$seedURL]);
                        continue;
                    }
                    $doc->tstamp = gmdate("Y-m-d\TH:i:s\Z");
                    $update->addDocuments(array($doc));
                    $update->addCommit();

                    try {
                        $solrResult = $client->update($update);
                        $somethingIndexed = true;
                    } catch (HttpException $e) {
                        self::$logger->error('[worker '.getmypid().'] '.$e->getMessage());
                    }

                    // Add links found on the page to seedURLs array for the next iteration
                    self::$logger->debug('[worker '.getmypid().'] url ['.$seedURL.'] from host ['.$host.'] returned ['.(is_array($result->get('links')) ? count($result->get('links')) : '0').'] child links to add to the seedURL list');

                    if (is_array($result->get('links'))) {
                        $newURLs = $result->get('links');
                        $pageURL = Url::parse($seedURL);

                        $absoluteLinks = array_map(function ($newURL) use ($pageURL) {
                            try {
                                return $pageURL->resolve($newURL)->toString();
                            } catch (InvalidUrlException $e) {
                                self::$logger->error('[worker '.getmypid().'] '.$e->getMessage());
                            }
                        }, $newURLs);

                        $seedURLs = array_merge($seedURLs, $absoluteLinks);
                    }

                    // remove the current link from the seed list as it's already indexed in this run.
                    unset($seedURLs[$seedURL]);
                }
            }

            $iteration++;

            /* If we made it this far without anything new being indexed, then
            load the oldest 300 URLs from the database and re-index them
            in the next iteration. */
            if ($somethingIndexed === false) {
                $page = new IndexedPage();
                $pages = $page->query('select * from IndexedPage order by tstamp asc limit 300');

                foreach ($pages as $page) {
                    $seedURLs[] = $page['url'];
                }

                self::$logger->info('[worker '.getmypid().'] added 300 old URLs to the seed list as the previous iteration indexed nothing new');
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

    /**
     * Apply some rules to the HTML crawled to strip it of code before indexing it
     *
     * @since 4.1.0
     */
    private function stripHTML(string $content): string
    {
        // remove tags
        $content = strip_tags($content);
        // remove CDATA
        $content = preg_replace('/<!\[CDATA\[.*?\]\]>/', '', $content);
        // remove any remaining /* */ code blocks
        $content = preg_replace('/\/\*.*?\*\//', '', $content);
        // remove any remaining {} code blocks
        $content = preg_replace('/{.*?}/', '', $content);

        return $content;
    }
}
