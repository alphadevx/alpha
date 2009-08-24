<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/MarkdownFacade.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';

/**
 *
 * Article for previewing Markdown content in the markItUp TextBox widget
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class PreviewArticle extends Controller implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
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
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doGET');
	}	
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		global $config;
		
		if(isset($params['data'])) {
			$temp = new article_object();
			$temp->set('content', $params['data']);
			if(isset($params['oid']))
				$temp->set('OID', $params['oid']);
			
			$parser = new MarkdownFacade($temp, false);
		
			echo '<html>';
			echo '<head>';
					
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/ui.all.css">';
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/alpha.css">';
			echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
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