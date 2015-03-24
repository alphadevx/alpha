<?php

namespace Alpha\Controller;

use Alpha\Util\Logging\Logger;
use Alpha\Util\Logging\LogFile;
use Alpha\Util\Feed\RSS2;
use Alpha\Util\Feed\RSS;
use Alpha\Util\Feed\Atom;
use Alpha\Util\Http\Request;
use Alpha\Util\Http\Response;
use Alpha\Util\Config\ConfigProvider;
use Alpha\Exception\ResourceNotFoundException;
use Alpha\Exception\IllegalArguementException;
use Alpha\Model\Article;

/**
 * Controller for viewing news feeds
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
class FeedController extends Controller implements ControllerInterface
{
    /**
     * The name of the BO to render as a feed
     *
     * @var string
     * @since 1.0
     */
    private $ActiveRecordType;

    /**
     * The type of feed to render (RSS, RSS2 or Atom)
     *
     * @var string
     * @since 1.0
     */
    private $type;

    /**
     * The title of the feed
     *
     * @var string
     * @since 1.0
     */
    protected $title;

    /**
     * The description of the feed
     *
     * @var string
     * @since 1.0
     */
    protected $description;

    /**
     * The BO to feed field mappings
     *
     * @var array
     * @since 1.0
     */
    protected $fieldMappings;

    /**
     * The BO field name to sort the feed by (descending), default is OID
     *
     * @var string
     * @since 1.0
     */
    private $sortBy = 'OID';

    /**
     * Trace logger
     *
     * @var Alpha\Util\Logging\Logger
     * @since 1.0
     */
    private static $logger = null;

    /**
     * constructor to set up the object
     *
     * @since 1.0
     */
    public function __construct()
    {
        self::$logger = new Logger('FeedController');
        self::$logger->debug('>>__construct()');

        $config = ConfigProvider::getInstance();

        // ensure that the super class constructor is called, indicating the rights group
        parent::__construct('Public');

        self::$logger->debug('<<__construct');
    }

    /**
     * Handle GET requests
     *
     * @param Alpha\Util\Http\Request $request
     * @return Alpha\Util\Http\Response
     * @since 1.0
     * @throws Alpha\Exception\ResourceNotFoundException
     */
    public function doGET($request)
    {
        self::$logger->debug('>>doGET($request=['.var_export($request, true).'])');

        $config = ConfigProvider::getInstance();

        $params = $request->getParams();

        $response = new Response(200);

        try {
            if (isset($params['ActiveRecordType'])) {
                $ActiveRecordType = $params['ActiveRecordType'];
            } else {
                throw new IllegalArguementException('ActiveRecordType not specified to generate feed!');
            }

            if (isset($params['type'])) {
                $type = $params['type'];
            } else {
                throw new IllegalArguementException('No feed type specified to generate feed!');
            }

            $className = "Alpha\\Model\\$ActiveRecordType";
            if (class_exists($className))
                $this->ActiveRecordType = $className;
            else
                throw new IllegalArguementException('No ActiveRecord available to render!');
            $this->type = $type;

            $this->setup();

            switch ($type) {
                case 'RSS2':
                    $feed = new RSS2($className, $this->title, str_replace('&', '&amp;', $request->getURI()), $this->description);
                    $feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
                    $response->setHeader('Content-Type', 'application/rss+xml');
                break;
                case 'RSS':
                    $feed = new RSS($className, $this->title, str_replace('&', '&amp;', $request->getURI()), $this->description);
                    $feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
                    $response->setHeader('Content-Type', 'application/rss+xml');
                break;
                case 'Atom':
                    $feed = new Atom($className, $this->title, str_replace('&', '&amp;', $request->getURI()), $this->description);
                    $feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3],
                        $this->fieldMappings[4]);
                    if ($config->get('feeds.atom.author') != '')
                        $feed->addAuthor($config->get('feeds.atom.author'));
                    $response->setHeader('Content-Type', 'application/atom+xml');
                break;
            }

            // now add the twenty last items (from newest to oldest) to the feed, and render
            $feed->loadBOs(20, $this->sortBy);
            $response->setBody($feed->render());

            // log the request for this news feed
            $feedLog = new LogFile($config->get('app.file.store.dir').'logs/feeds.log');
            $feedLog->writeLine(array($this->ActiveRecordType, $this->type, date("Y-m-d H:i:s"), $request->getUserAgent(), $request->getIP()));
        } catch (IllegalArguementException $e) {
            self::$logger->error($e->getMessage());
            throw new ResourceNotFoundException($e->getMessage());
        }

        self::$logger->debug('<<doGet');
        return $response;
    }

    /**
     * setup the feed title, field mappings and description based on common BO types
     */
    protected function setup()
    {
        self::$logger->debug('>>setup()');

        $config = ConfigProvider::getInstance();

        $bo = new $this->ActiveRecordType;

        if ($bo instanceof Article) {
            $this->title = 'Latest articles from '.$config->get('app.title');
            $this->description = 'News feed containing all of the details on the latest articles published on '.$config->get('app.title').'.';
            $this->fieldMappings = array('title', 'URL', 'description', 'created_ts', 'OID');
            $this->sortBy = 'created_ts';
        }

        self::$logger->debug('<<setup');
    }
}

?>