<?php

namespace Alpha\Task;

use Alpha\Exception\RecordNotFoundException;
use Alpha\Model\IndexedPage;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Client;
use Solarium\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use LanguageDetection\Language;

/**
 * A persistent task for removing unwanted content from the search index.
 *
 * @since 4.1
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2026, John Collins (founder of Alpha Framework).
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
class CleanIndexTask implements TaskInterface
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

        self::$logger = new Logger('CleanIndexTask');
        self::$logger->setLogProviderFile($config->get('app.file.store.dir').'logs/cleanindex.log');

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

        $ld = new Language(['en']);
        $batchSize = 100;
        $start = 0;

        while (true) {
            // Create Select Query
            $query = $client->createSelect();
            $query->setQuery('*:*');
            $query->setStart($start);
            $query->setRows($batchSize);
            $query->setFields(['id', 'content']);
            $query->addSort('id', $query::SORT_ASC); // Sort ensures we don't skip docs

            $resultset = $client->execute($query);

            // Break if we've reached the end of the index
            if ($resultset->getNumFound() == 0 || $start >= $resultset->getNumFound()) {
                break;
            }

            $toDelete = [];

            foreach ($resultset as $document) {
                $content = $document->content[0];//print_r($content);

                // Ensure we have enough text for a reliable detection
                if (!empty($content)) {
                    $detection = $ld->detect($content)->bestResults()->close();

                    // If the top result isn't 'en', mark for deletion
                    // Note: bestResults() returns an array where the key is the lang code
                    if (!isset($detection['en'])) {
                        $toDelete[] = $document->id;
                        // delete from the database
                        $page = new IndexedPage();
                        try {
                            $page->loadByAttribute('url', $document->id);
                            $page->delete();
                        } catch (RecordNotFoundException $e) {
                        }
                        self::$logger->info("The following content is **not** English [".$content."]");
                    } else {
                        self::$logger->debug("The following content is English [".$content."]");
                    }
                } else {
                    // also delete documents with no content
                    $toDelete[] = $document->id;
                    // delete from the database
                    $page = new IndexedPage();
                    try {
                        $page->loadByAttribute('url', $document->id);
                        $page->delete();
                    } catch (RecordNotFoundException $e) {
                    }
                    self::$logger->info("The following content is empty [".$content."]");
                }
            }

            // Execute Batch Delete
            if (!empty($toDelete)) {
                $update = $client->createUpdate();
                $update->addDeleteByIds($toDelete);
                $update->addCommit();
                $client->execute($update);

                self::$logger->info("Deleted ".count($toDelete)." non-English or documents in this batch.");

                // Since we deleted items, we don't increment $start as much
                // as the next set of items will "shift" up into the current range.
                $start += ($batchSize - count($toDelete));
            } else {
                $start += $batchSize;
            }

            self::$logger->info("Processed up to record: $start");
        }

        self::$logger->info("Purge complete!");
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRunTime(): int
    {
        return 600;
    }
}
