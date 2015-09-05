<?php

namespace Alpha\Util\Feed;

/**
 * RSS 1.0 class for synication.
 *
 * @since 1.0
 *
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
 */
class RSS extends Feed
{
    /**
     * The XML namespace.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $nameSpace = 'http://purl.org/rss/1.0/';

    /**
     * The RDF namespace.
     *
     * @var string
     *
     * @since 1.0
     */
    private $rdfns = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    /**
     * The actual root tag used in each feed type.
     *
     * @var string
     *
     * @since 1.0
     */
    protected $rootTag = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" />';

    /**
     * Add a URL to feed item.
     *
     * @param $url
     *
     * @since 1.0
     */
    private function addToItems($url)
    {
        if ($this->items == null) {
            $container = $this->createFeedElement('items');
            $this->root->appendChild($container);
            $this->items = $this->rssDoc->createElementNS($this->rdfns, 'Seq');
            $container->appendChild($this->items);
        }

        $item = $this->rssDoc->createElementNS($this->rdfns, 'li');
        $this->items->appendChild($item);
        $item->setAttribute('resource', $url);
    }

    /**
     * {@inheritdoc}
     */
    protected function addItem($title, $link, $description = null, $pubDate = null, $id = null)
    {
        parent::addItem($title, $link, $description, $pubDate, $id);
        $this->addToItems($link);
    }

    /**
     * {@inheritdoc}
     */
    protected function createRSSNode($type, $parent, $title, $url, $description, $pubDate = null)
    {
        $parent->setAttributeNS($this->rdfns, 'rdf:about', $url);
        parent::createRSSNode($type, $parent, $title, $url, $description, $pubDate);
    }
}
