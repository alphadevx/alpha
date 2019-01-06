<?php

namespace Alpha\Util\Search;

use Alpha\Exception\RecordNotFoundException;
use Alpha\Exception\ValidationException;
use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Service\ServiceFactory;
use Alpha\Model\Tag;

/**
 * Uses the Tag business oject to store searchable tags in the main
 * application database.
 *
 * @since 1.2.3
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2018, John Collins (founder of Alpha Framework).
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
class SearchProviderTags implements SearchProviderInterface
{
    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 1.2.3
     */
    private static $logger;

    /**
     * The number of matches found for the current search.
     *
     * @var int
     *
     * @since 1.2.3
     */
    private $numberFound = 0;

    /**
     * constructor to set up the object.
     *
     * @since 1.2.3
     */
    public function __construct()
    {
        self::$logger = new Logger('SearchProviderTags');
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $returnType = 'all', $start = 0, $limit = 10, $createdBy = 0)
    {
        $config = ConfigProvider::getInstance();

        // explode the user's query into a set of tokenized transient Tags
        $queryTags = Tag::tokenize($query, '', '', false);
        $matchingTags = array();

        // load Tags from the DB where content equals the content of one of our transient Tags
        foreach ($queryTags as $queryTag) {
            if ($createdBy == 0) {
                $tags = $queryTag->loadAllByAttribute('content', $queryTag->get('content'));
            } else {
                $tags = $queryTag->loadAllByAttributes(array('content', 'created_by'), array($queryTag->get('content'), $createdBy));
            }
            $matchingTags = array_merge($matchingTags, $tags);
        }

        // the result objects
        $results = array();
        // matching tags with weights
        $matches = array();

        if ($config->get('cache.provider.name') != '' && count($queryTags) == 1) { // for now, we are only caching on single tags
            $key = $queryTags[0]->get('content');
            $matches = $this->loadFromCache($key);
        }

        if (count($matches) == 0) {
            /*
             * Build an array of records for the matching tags from the DB:
             * array key = Record ID
             * array value = weight (the amount of tags matching the record)
             */
            foreach ($matchingTags as $tag) {
                if ($returnType == 'all' || $tag->get('taggedClass') == $returnType) {
                    $key = $tag->get('taggedClass').'-'.$tag->get('taggedID');

                    if (isset($matches[$key])) {
                        // increment the weight if the same Record is tagged more than once
                        $weight = intval($matches[$key])+1;
                        $matches[$key] = $weight;
                    } else {
                        $matches[$key] = 1;
                    }
                }
            }

            if ($config->get('cache.provider.name') != '' && count($queryTags) == 1) { // for now, we are only caching on single tags
                $key = $queryTags[0]->get('content');
                $this->addToCache($key, $matches);
            }
        }

        // sort the matches based on tag frequency weight
        arsort($matches);

        $this->numberFound = count($matches);

        // now paginate
        $matches = array_slice($matches, $start, $limit+5); // the +5 is just some padding in case of orphans

        // now load each object
        foreach ($matches as $key => $weight) {
            if (count($results) < $limit) {
                $parts = explode('-', $key);

                try {
                    $Record = new $parts[0]();
                    $Record->load($parts[1]);

                    $results[] = $Record;
                } catch (RecordNotFoundException $e) {
                    self::$logger->warn('Orpaned Tag detected pointing to a non-existant Record of ID ['.$parts[1].'] and type ['.$parts[0].'].');
                }
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelated($sourceObject, $returnType = 'all', $start = 0, $limit = 10, $distinct = '')
    {
        $config = ConfigProvider::getInstance();

        // the result objects
        $results = array();
        // matching tags with weights
        $matches = array();
        // only used in conjunction with distinct param
        $distinctValues = array();

        if ($config->get('cache.provider.name') != '') {
            $key = get_class($sourceObject).'-'.$sourceObject->getID().'-related'.($distinct == '' ? '' : '-distinct');
            $matches = $this->loadFromCache($key);
        }

        if (count($matches) == 0) {
            // all the tags on the source object for comparison
            $tags = $sourceObject->getPropObject('tags')->getRelated();

            foreach ($tags as $tag) {
                $Tag = new Tag();

                if ($distinct == '') {
                    $matchingTags = $Tag->query('SELECT * FROM '.$Tag->getTableName()." WHERE 
                        content='".$tag->get('content')."' AND NOT 
                        (taggedID = '".$sourceObject->getID()."' AND taggedClass = '".get_class($sourceObject)."');");
                } else {
                    // filter out results where the source object field is identical to distinct param
                    $matchingTags = $Tag->query('SELECT * FROM '.$Tag->getTableName()." WHERE 
                        content='".$tag->get('content')."' AND NOT 
                        (taggedID = '".$sourceObject->getID()."' AND taggedClass = '".get_class($sourceObject)."')
                        AND taggedID IN (SELECT ID FROM ".$sourceObject->getTableName().' WHERE '.$distinct." != '".addslashes($sourceObject->get($distinct))."');");
                }

                foreach ($matchingTags as $matchingTag) {
                    if ($returnType == 'all' || $tag->get('taggedClass') == $returnType) {
                        $key = $matchingTag['taggedClass'].'-'.$matchingTag['taggedID'];

                        // matches on the distinct if defined need to be skipped
                        if ($distinct != '') {
                            try {
                                $Record = new $matchingTag['taggedClass']();
                                $Record->load($matchingTag['taggedID']);

                                // skip where the source object field is identical
                                if ($sourceObject->get($distinct) == $Record->get($distinct)) {
                                    continue;
                                }

                                if (!in_array($Record->get($distinct), $distinctValues)) {
                                    $distinctValues[] = $Record->get($distinct);
                                } else {
                                    continue;
                                }
                            } catch (RecordNotFoundException $e) {
                                self::$logger->warn('Error loading object ['.$matchingTag['taggedID'].'] of type ['.$matchingTag['taggedClass'].'], probable orphan');
                            }
                        }

                        if (isset($matches[$key])) {
                            // increment the weight if the same Record is tagged more than once
                            $weight = intval($matches[$key])+1;
                            $matches[$key] = $weight;
                        } else {
                            $matches[$key] = 1;
                        }
                    }
                }

                if ($config->get('cache.provider.name') != '') {
                    $key = get_class($sourceObject).'-'.$sourceObject->getID().'-related'.($distinct == '' ? '' : '-distinct');
                    $this->addToCache($key, $matches);
                }
            }
        }

        // sort the matches based on tag frequency weight
        arsort($matches);

        $this->numberFound = count($matches);

        // now paginate
        $matches = array_slice($matches, $start, $limit);

        // now load each object
        foreach ($matches as $key => $weight) {
            $parts = explode('-', $key);

            $Record = new $parts[0]();
            $Record->load($parts[1]);

            $results[] = $Record;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function index($sourceObject)
    {
        $taggedAttributes = $sourceObject->getTaggedAttributes();

        foreach ($taggedAttributes as $tagged) {
            $tags = Tag::tokenize($sourceObject->get($tagged), get_class($sourceObject), $sourceObject->getID());

            foreach ($tags as $tag) {
                try {
                    $tag->save();
                } catch (ValidationException $e) {
                    /*
                     * The unique key has most-likely been violated because this Record is already tagged with this
                     * value, so we can ignore in this case.
                     */
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($sourceObject)
    {
        $tags = $sourceObject->getPropObject('tags')->getRelated();

        foreach ($tags as $tag) {
            $tag->delete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberFound()
    {
        return $this->numberFound;
    }

    /**
     * Load the tag search matches from the cache.
     *
     * @since 1.2.4
     */
    private function loadFromCache($key)
    {
        $config = ConfigProvider::getInstance();

        try {
            $cache = ServiceFactory::getInstance($config->get('cache.provider.name'), 'Alpha\Util\Cache\CacheProviderInterface');
            $matches = $cache->get($key);

            if (!$matches) {
                self::$logger->debug('Cache miss on key ['.$key.']');

                return array();
            } else {
                self::$logger->debug('Cache hit on key ['.$key.']');

                return $matches;
            }
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to load a search result from ['.$config->get('cache.provider.name').'] 
             instance: ['.$e->getMessage().']');

            return array();
        }
    }

    /**
     * Add the tag search matches to the cache.
     *
     * @since 1.2.4
     */
    public function addToCache($key, $matches)
    {
        $config = ConfigProvider::getInstance();

        try {
            $cache = ServiceFactory::getInstance($config->get('cache.provider.name'), 'Alpha\Util\Cache\CacheProviderInterface');
            $cache->set($key, $matches, 86400); // cache search matches for a day
        } catch (\Exception $e) {
            self::$logger->error('Error while attempting to store a search matches array to the ['.$config->get('cache.provider.name').'] 
                instance: ['.$e->getMessage().']');
        }
    }
}
