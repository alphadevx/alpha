<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * Controller for viewing news feeds
 *
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2013, John Collins (founder of Alpha Framework).
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
class ViewFeed extends AlphaController implements AlphaControllerInterface {
	/**
	 * The name of the BO to render as a feed
	 *
	 * @var string
	 * @since 1.0
	 */
	private $BOName;

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
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * constructor to set up the object
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('ViewFeed');
		self::$logger->debug('>>__construct()');

		global $config;

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 *
	 * @param array $params
	 * @since 1.0
	 * @throws ResourceNotFoundException
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

		global $config;

		try {
			if (isset($params['bo'])) {
				$BOName = $params['bo'];
			}else{
				throw new IllegalArguementException('BO not specified to generate feed!');
			}

			if (isset($params['type'])) {
				$type = $params['type'];
			}else{
				throw new IllegalArguementException('No feed type specified to generate feed!');
			}

			$this->BOName = $BOName;
			$this->type = $type;

			$this->setup();

			switch($type) {
				case 'RSS2':
					$feed = new RSS2($BOName, $this->title, str_replace('&', '&amp;', $_SERVER['REQUEST_URI']), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
				break;
				case 'RSS':
					$feed = new RSS($BOName, $this->title, str_replace('&', '&amp;', $_SERVER['REQUEST_URI']), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3]);
				break;
				case 'Atom':
					$feed = new Atom($BOName, $this->title, str_replace('&', '&amp;', $_SERVER['REQUEST_URI']), $this->description);
					$feed->setFieldMappings($this->fieldMappings[0], $this->fieldMappings[1], $this->fieldMappings[2], $this->fieldMappings[3],
						$this->fieldMappings[4]);
					if($config->get('feeds.atom.author') != '')
						$feed->addAuthor($config->get('feeds.atom.author'));
				break;
			}

			// now add the twenty last items (from newest to oldest) to the feed, and render
			$feed->loadBOs(20, $this->sortBy);
			echo $feed->render();

			// log the request for this news feed
			$feedLog = new LogFile($config->get('app.file.store.dir').'logs/feeds.log');
			$feedLog->writeLine(array($this->BOName, $this->type, date("Y-m-d H:i:s"), $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']));
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}

		self::$logger->debug('<<doGet');
	}

	/**
	 * Method to handle POST requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

		self::$logger->debug('<<doPOST');
	}

	/**
	 * setup the feed title, field mappings and description based on common BO types
	 */
	protected function setup() {
		self::$logger->debug('>>setup()');

		global $config;

		$bo = new $this->BOName;

		if($bo instanceof ArticleObject) {
			$this->title = 'Latest articles from '.$config->get('app.title');
			$this->description = 'News feed containing all of the details on the latest articles published on '.$config->get('app.title').'.';
			$this->fieldMappings = array('title', 'URL', 'description', 'created_ts', 'OID');
			$this->sortBy = 'created_ts';
		}

		self::$logger->debug('<<setup');
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewFeed.php') {
	$controller = new ViewFeed();

	if(!empty($_POST)) {
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>