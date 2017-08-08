<?php

namespace Alpha\Util\Feed;

use Alpha\Util\Logging\Logger;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\ActiveRecord;
use DOMDocument;
use DOMElement;

/**
 * Base feed class for generating syndication feeds.
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
abstract class Feed
{
    /**
     * The DOMDocument object used to create the feed.
     *
     * @var DOMDocument
     *
     * @since 1.0
     */
    protected $rssDoc;

    /**
     * The DOMElement object used to hold the item or entry elements.
     *
     * @var DOMElement
     *
     * @since 1.0
     */
    protected $docElement;

    /**
     * Holds the DOMElement to which metadata is added for the feed.
     *
     * @var DOMElement
     *
     * @since 1.0
     */
    protected $root;

    /**
     * The actual root tag used in each feed type.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $rootTag;

    /**
     * An array of feed items.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $items;

    /**
     * If the feed format has a channel or not.
     *
     * @var bool
     *
     * @since 1.0
     */
    protected $hasChannel = true;

    /**
     * Maps the tags to the feed-specific tags.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $tagMap = array('item' => 'item', 'feeddesc' => 'description', 'itemdesc' => 'description');

    /**
     * The Record which we will serve up in this feed.
     *
     * @var \Alpha\Model\ActiveRecord
     *
     * @since 1.0
     */
    private $Record;

    /**
     * An array containing Record field names -> RSS field name mappings.
     *
     * @var array
     *
     * @since 1.0
     */
    protected $fieldNameMappings;

    /**
     * The XML namespace to use in the generated feed.
     *
     * @var string
     */
    protected $nameSpace;

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
     * @param string $RecordName      The fully-qualifified classname of the Record to render a feed for.
     * @param string $title       The title of the feed.
     * @param string $url         The base URL for the feed.
     * @param string $description The description of the feed.
     * @param string $pubDate     The publish date, only used in Atom feeds.
     * @param int    $id          The feed id, only used in Atom feeds.
     * @param int    $limit       The amount of items to render in the feed.
     *
     * @throws IllegalArguementException
     *
     * @since 1.0
     */
    public function __construct($RecordName, $title, $url, $description, $pubDate = null, $id = null, $limit = 10)
    {
        self::$logger = new Logger('Feed');
        self::$logger->debug('>>__construct(RecordName=['.$RecordName.'], title=['.$title.'], url=['.$url.'], description=['.$description.'], pubDate=['.$pubDate.'], id=['.$id.'], limit=['.$limit.'])');

        $this->rssDoc = new DOMDocument();
        $this->rssDoc->loadXML($this->rootTag);
        $this->docElement = $this->rssDoc->documentElement;

        if (!class_exists($RecordName)) {
            throw new IllegalArguementException('Unable to load the class definition for the class ['.$RecordName.'] while trying to generate a feed!');
        }

        $this->record = new $RecordName();

        if ($this->hasChannel) {
            $root = $this->createFeedElement('channel');
            $this->root = $this->docElement->appendChild($root);
        } else {
            $this->root = $this->docElement;
        }

        $this->createRSSNode('feed', $this->root, $title, $url, $description, $pubDate, $id);

        self::$logger->debug('<<__construct');
    }

    /**
     * Method to load all of the Record items to the feed from the database, from the newest to the
     * $limit provided.
     *
     * @param int    $limit  The amount of items to render in the feed.
     * @param string $sortBy The name of the field to sort the feed by.
     *
     * @since 1.0
     */
    public function loadRecords($limit, $sortBy)
    {
        $Records = $this->record->loadAll(0, $limit, $sortBy, 'DESC');

        ActiveRecord::disconnect();

        foreach ($Records as $Record) {
            $this->addRecord($Record);
        }
    }

    /**
     * Method for adding a Record to the current feed.
     *
     * @param \Alpha\Model\ActiveRecord $Record
     */
    public function addRecord($Record)
    {
        $title = $Record->get($this->fieldNameMappings['title']);
        $url = $Record->get($this->fieldNameMappings['url']);

        if (isset($this->fieldNameMappings['description'])) {
            $description = $Record->get($this->fieldNameMappings['description']);
        } else {
            $description = '';
        }

        if (isset($this->fieldNameMappings['pubDate'])) {
            $dateTS = strtotime($Record->get($this->fieldNameMappings['pubDate']));
            $pubDate = date(DATE_ATOM, $dateTS);
        } else {
            $pubDate = '';
        }

        if (isset($this->fieldNameMappings['id'])) {
            $id = $Record->get($this->fieldNameMappings['id']);
        } else {
            $id = '';
        }

        $this->addItem($title, $url, $description, $pubDate, $id);
    }

    /**
     * Method for mapping Record fieldnames to feed field names.
     *
     * @param string $title       The title of the feed.
     * @param string $url         The base URL for the feed.
     * @param string $description The description of the feed.
     * @param string $pubDate     The publish date, only used in Atom feeds.
     * @param int    $id          The feed id, only used in Atom feeds.
     *
     * @since 1.0
     */
    public function setFieldMappings($title, $url, $description = null, $pubDate = null, $id = null)
    {
        $this->fieldNameMappings = array(
            'title' => $title,
            'url' => $url,
        );

        if (isset($description)) {
            $this->fieldNameMappings['description'] = $description;
        }

        if (isset($pubDate)) {
            $this->fieldNameMappings['pubDate'] = $pubDate;
        }

        if (isset($id)) {
            $this->fieldNameMappings['id'] = $id;
        }
    }

    /**
     * Method for creating a new feed element.
     *
     * @param string $name  The name of the element.
     * @param string $value The value of the element.
     *
     * @return DOMElement
     *
     * @since 1.0
     */
    protected function createFeedElement($name, $value = null)
    {
        $value = htmlspecialchars($value);

        if ($this->nameSpace == null) {
            return $this->rssDoc->createElement($name, $value);
        } else {
            return $this->rssDoc->createElementNS($this->nameSpace, $name, $value);
        }
    }

    /**
     * Method for creating link elements (note that Atom has a different format).
     *
     * @param DOMElement $parent The parent element.
     * @param string     $url    The URL for the link.
     *
     * @since 1.0
     */
    protected function createLink($parent, $url)
    {
        $link = $this->createFeedElement('link', $url);
        $parent->appendChild($link);
    }

    /**
     * Method for creating an RSS node with a title, url and description.
     *
     * @param int        $type        Can be either (item|feed) to indicate the type of node we are creating.
     * @param DOMElement $parent      The parent element.
     * @param string     $title       The title of the feed.
     * @param string     $url         The base URL for the feed.
     * @param string     $description The description of the feed.
     * @param string     $pubDate     The publish date, only used in Atom feeds.
     * @param int        $id          The feed id, only used in Atom feeds.
     *
     * @since 1.0
     *
     * @throws \Alpha\Exception\IllegalArguementException
     */
    protected function createRSSNode($type, $parent, $title, $url, $description, $pubDate = null, $id = null)
    {
        $this->createLink($parent, $url);
        $title = $this->createFeedElement('title', $title);
        $parent->appendChild($title);

        if ($type == 'item') {
            $titletag = $this->tagMap['itemdesc'];
        } elseif ($type == 'feed') {
            $titletag = $this->tagMap['feeddesc'];
        } else {
            throw new IllegalArguementException('The type paramater ['.$type.'] provided is invalid!');
        }

        $description = $this->createFeedElement($titletag, $description);
        $parent->appendChild($description);

        // id elements and updated elements are just for Atom!
        if ($id !== null) {
            $idnode = $this->createFeedElement('id', $id);
            $parent->appendChild($idnode);
        }

        if ($pubDate !== null) {
            $datenode = $this->createFeedElement('updated', $pubDate);
            $parent->appendChild($datenode);
        }
    }

    /**
     * Method for adding an item to a feed.
     *
     * @param string $title       The title of the feed.
     * @param string $url         The base URL for the feed.
     * @param string $description The description of the feed.
     * @param string $pubDate     The publish date, only used in Atom feeds.
     * @param int    $id          The feed id, only used in Atom feeds.
     *
     * @since 1.0
     */
    protected function addItem($title, $url, $description = null, $pubDate = null, $id = null)
    {
        $item = $this->createFeedElement($this->tagMap['item']);

        if ($this->docElement->appendChild($item)) {
            $this->createRSSNode('item', $item, $title, $url, $description, $pubDate, $id);
        }
    }

    /**
     * Returns the formatted XML for the feed as a string.
     *
     * @return string
     *
     * @since 1.0
     */
    public function render()
    {
        if ($this->rssDoc) {
            $this->rssDoc->formatOutput = true;

            return $this->rssDoc->saveXML();
        } else {
            return '';
        }
    }
}
