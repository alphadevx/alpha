<?php

namespace Alpha\Util\Http;

/**
 * A utility class for carrying out various tasks on HTTP user agent strings.
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
class AgentUtils
{
    /**
     * An array of partial user agent strings belonging to well known web spider bots.
     *
     * @var array
     *
     * @since 1.0
     */
    private static $bots = array(
        'ia_archiver',
        'Scooter/',
        'Ask Jeeves',
        'Baiduspider+(',
        'bingbot/',
        'Disqus/',
        'Exabot/',
        'FAST Enterprise Crawler',
        'FAST-WebCrawler/',
        'http://www.neomo.de/',
        'Gigabot/',
        'Mediapartners-Google',
        'Google Desktop',
        'Feedfetcher-Google',
        'Googlebot',
        'heise-IT-Markt-Crawler',
        'heritrix/1.',
        'ibm.com/cs/crawler',
        'ICCrawler - ICjobs',
        'ichiro/2',
        'MJ12bot/',
        'MetagerBot/',
        'msnbot-NewsBlogs/',
        'msnbot/',
        'msnbot-media/',
        'NG-Search/',
        'http://lucene.apache.org/nutch/',
        'NutchCVS/',
        'OmniExplorer_Bot/',
        'online link validator',
        'psbot/0',
        'Seekbot/',
        'Sensis Web Crawler',
        'SEO search Crawler/',
        'Seoma [SEO Crawler]',
        'SEOsearch/',
        'Snappy/1.1 ( http://www.urltrends.com/ )',
        'http://www.tkl.iis.u-tokyo.ac.jp/~crawler/',
        'SynooBot/',
        'crawleradmin.t-info@telekom.de',
        'TurnitinBot/',
        'voyager/1.0',
        'W3 SiteSearch Crawler',
        'W3C-checklink/',
        'W3C_*Validator',
        'http://www.WISEnutbot.com',
        'yacybot',
        'Yahoo-MMCrawler/',
        'Yahoo! DE Slurp',
        'Yahoo! Slurp',
        'YahooSeeker/',
        );

    /**
     * Static method to check if the provided user agent string matches any of the known user
     * agent strings in the $bots array on this class.
     *
     * @param string $userAgent The user agent string that we want to check.
     *
     * @return bool
     *
     * @since 1.0
     */
    public static function isBot($userAgent)
    {
        $isBot = false;

        foreach (self::$bots as $botName) {
            if (stristr($userAgent, $botName) == true) {
                $isBot = true;
                break;
            }
        }

        return $isBot;
    }
}
