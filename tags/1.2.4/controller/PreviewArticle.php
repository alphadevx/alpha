<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();

	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 *
 * Article for previewing Markdown content in the markItUp TextBox widget
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
class PreviewArticle extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 *
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;

	/**
	 * Constructor to set up the object
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('PreviewArticle');
		self::$logger->debug('>>__construct()');

		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');

		// set up the title and meta details
		$this->setTitle('Preview');
		$this->setDescription('Preview page.');
		$this->setKeywords('Preview,page');

		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 *
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');

		self::$logger->debug('<<doGET');
	}

	/**
	 * Handle POST requests
	 *
	 * @param array $params
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');

		global $config;

		if(isset($params['data'])) {
			// allow the consumer to optionally indicate another BO than ArticleObject
			if(isset($params['bo'])) {
				AlphaDAO::loadClassDef($params['bo']);
				$temp = new $params['bo'];
			}else{
				$temp = new ArticleObject();
			}

			$temp->set('content', $params['data']);

			if(isset($params['oid']))
				$temp->set('OID', $params['oid']);

			$parser = new MarkdownFacade($temp, false);

			// render a simple HTML header
			echo '<html>';
			echo '<head>';
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/lib/jquery/ui/themes/'.$config->get('app.css.theme').'/jquery.ui.all.css">';
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/css/alpha.css">';
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'config/css/overrides.css">';
			echo '</head>';
			echo '<body>';

			// transform text using parser.
			echo $parser->getContent();

			echo '</body>';
			echo '</html>';
		}else{
			throw new IllegalArguementException('No Markdown data provided in the POST data!');
		}
		self::$logger->debug('<<doPOST');
	}
}

// now build the new controller if this file is called directly
if ('PreviewArticle.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new PreviewArticle();

	if(!empty($_POST)) {
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>