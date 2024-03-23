<?php

namespace Alpha\Model;

use Alpha\Model\Type\SmallText;
use Alpha\Model\Type\Timestamp;
use Alpha\Util\Logging\Logger;

/**
 * Used to track a URL that has been indexed for search.
 *
 * @since 4.1.0
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
class IndexedPage extends ActiveRecord
{
    /**
     * The absolute URL for the page.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 4.1.0
     */
    protected $url;

    /**
     * The last time this page was updated in the search index.
     *
     * @var \Alpha\Model\Type\Timestamp
     *
     * @since 4.1.0
     */
    protected $tstamp;

    /**
     * The hostname for the website hosting this page.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 4.1.0
     */
    protected $host;

    /**
     * The optional filename for the screenshot of the page.
     *
     * @var \Alpha\Model\Type\SmallText
     *
     * @since 4.1.0
     */
    protected $screenshot;

    /**
     * An array of data display labels for the class properties.
     *
     * @var array
     *
     * @since 4.1.0
     */
    protected $dataLabels = array('ID' => 'Indexed Page ID#', 'url' => 'URL', 'tstamp' => 'Last index update', 'host' => 'Host', 'screenshot' => 'Screenshot');

    /**
     * The name of the database table for the class.
     *
     * @var string
     *
     * @since 4.1.0
     */
    public const TABLE_NAME = 'IndexedPage';

    /**
     * Trace logger.
     *
     * @var \Alpha\Util\Logging\Logger
     *
     * @since 4.1.0
     */
    private static $logger = null;

    /**
     * Constructor.
     *
     * @since 4.1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('IndexedPage');

        // ensure to call the parent constructor
        parent::__construct();

        $this->url = new SmallText();
        $this->tstamp = new Timestamp();
        $this->host = new SmallText();
        $this->screenshot = new SmallText();
    }
}
