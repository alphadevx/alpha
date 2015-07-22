<?php

namespace Alpha\View\Widget;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Util\Cache\CacheProviderFactory;
use Alpha\Model\Tag;

/**
 * A widget for rendering a tag cloud, based off the Tag instances in the
 * database.
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
class TagCloud
{
    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * A hash array of popular tags
     *
     * @var array
     * @since 1.0
     */
    private $popTags = array();

    /**
     * Constructor
     *
     * @param $limit The maximum amount of tags to include in the cloud.
     * @param $cacheKey Set this optional value to attempt to store the tag cloud array in the available cache for 24hrs (cache.provider.name).
     * @since 1.0
     */
    public function __construct($limit, $cacheKey = '')
    {
        $config = ConfigProvider::getInstance();

        self::$logger = new Logger('TagCloud');

        if ($cacheKey != '' && $config->get('cache.provider.name') != '') {
            $cache = CacheProviderFactory::getInstance($config->get('cache.provider.name'));
            $this->popTags = $cache->get($cacheKey);

            // cache look-up failed, so add it for the next time
            if (!$this->popTags) {
                self::$logger->debug('Cache lookup on the key ['.$cacheKey.'] failed, regenerating popular tags...');

                $this->popTags = Tag::getPopularTagsArray($limit);

                $cache->set($cacheKey, $this->popTags, 86400);
            } else {
                $this->popTags = array_slice($this->popTags, 0, $limit);
                self::$logger->debug('Cache lookup on the key ['.$cacheKey.'] succeeded');
            }
        } else {
            $this->popTags = Tag::getPopularTagsArray($limit);
        }
    }

    /**
     * Render the tag cloud and return all of the HTML links in a single paragraph.
     *
     * @param $minLinkSize The minimum font size for any tag link, in points.
     * @param $maxLinkSize The maximum font size for any tag link, in points.
     * @param $target The target attribute for the links
     * @return string
     * @since 1.0
     */
    public function render($minLinkSize=8, $maxLinkSize=20, $target='')
    {
        $config = ConfigProvider::getInstance();
        $html = '<p>';

        foreach (array_keys($this->popTags) as $key) {
            $linkSize = $this->popTags[$key];
            if ($linkSize < $minLinkSize)
                $linkSize = $minLinkSize;
            if ($linkSize > $maxLinkSize)
                $linkSize = $maxLinkSize;
            $html .= '<a href="'.$config->get('app.url').'search/'.$key.'" style="font-size:'.$linkSize.'pt;"'.(empty($target) ? '' : ' target="'.$target.'"').' rel="tag">'.$key.'</a> ';
        }

        return $html.'</p>';
    }
}

?>