<?php

namespace Alpha\Util\Feed;

/**
 * Atom class for syndication
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
class Atom extends Feed
{
    /**
     * The XML namespace
     *
     * @var string
     * @since 1.0
     */
    protected $nameSpace = 'http://www.w3.org/2005/Atom';

    /**
     * The actual root tag used in each feed type
     *
     * @var string
     * @since 1.0
     */
    protected $rootTag = '<feed xmlns="http://www.w3.org/2005/Atom" />';

    /**
     * If the feed format has a channel or not
     *
     * @var boolean
     * @since 1.0
     */
    protected $hasChannel = false;

    /**
     * Maps the tags to the feed-specific tags
     *
     * @var array
     * @since 1.0
     */
    protected $tagMap = array(
        'item'=>'entry',
        'feeddesc'=>'subtitle',
        'itemdesc'=>'summary'
    );

    /**
     * {@inheritDoc}
     */
    protected function createLink($parent, $url)
    {
        $link = $this->rssDoc->createElementNS($this->nameSpace, 'link');
        $parent->appendChild($link);
        $link->setAttribute('href', $url);
    }

    /**
     * Constructor to create a new Atom feed
     *
     * @param string $title
     * @param string $url
     * @param string $description
     * @param string $pubDate
     * @param integer $id
     * @since 1.0
     */
    public function __construct($title, $url, $description, $pubDate = null, $id = null)
    {
        if (empty($id))
            $id = $url;
        if (empty($pubDate))
            $pubDate = date('Y-m-d');
        parent::__construct($title, $url, $description, $pubDate, $id);
    }

    /**
     * Adds an auther to a feed
     *
     * @param string $name The name of the author.
     * @since 1.0
     */
    public function addAuthor($name)
    {
        $author = $this->rssDoc->createElementNS($this->nameSpace, 'author');

        $this->docElement->appendChild($author);
        $namenode = $this->rssDoc->createElementNS($this->nameSpace, 'name', $name);
        $author->appendChild($namenode);
    }

    /**
     * {@inheritDoc}
     */
    protected function addItem($title, $link, $description=null, $pubDate = null, $id = null)
    {
        if (empty($id))
            $id = $link;
        if (empty($pubDate))
            $pubDate = date('Y-m-d');

        parent::addItem($title, $link, $description, $pubDate, $id);
    }
}

?>