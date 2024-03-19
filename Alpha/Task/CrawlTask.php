<?php

namespace Alpha\Task;

use Alpha\Exception\AlphaException;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Http\AlphaCrawler;
use Crwlr\Crawler\Steps\Html;
use Crwlr\Crawler\Steps\Dom;
use Crwlr\Crawler\Steps\Loading\Http;

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
        self::$logger->setLogProviderFile($config->get('app.file.store.dir').'logs/tasks.log');

        $seedfile = $config->get('app.root').'config/seed-urls.ini';

        if (file_exists($seedfile)) {
            $seedURLs = file($seedfile, FILE_IGNORE_NEW_LINES);
        } else {
            throw new AlphaException('Unable to find a seed-urls.ini file in the application!');
        }

        foreach ($seedURLs as $seedURL) {
            $crawler = new AlphaCrawler();

            $crawler->input($seedURL)
                ->addStep(Http::get())
                ->addStep(
                    Html::first('html')
                        ->extract([
                            'title' => 'title',
                            'content' => Dom::cssSelector('body')->formattedText(),
                            'links' => Dom::cssSelector('a')->attribute('href')
                        ])
                        ->addToResult()
                );

            foreach ($crawler->run() as $result) {
                /* TODO:
                    1. Check the DB for when this was last indexed
                    2. Re-index to Solr and the DB as required
                 */
                $result->set('url', $seedURL);
                $result->set('host', parse_url($seedURL, PHP_URL_HOST));
                print_r($result);
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
