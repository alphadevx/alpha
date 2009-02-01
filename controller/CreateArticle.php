<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to create a new article in the database
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * @todo Validation must include checking the size of the uploaded file
 *
 */
class CreateArticle extends Controller implements AlphaControllerInterface {
	/**
	 * The new article to be created
	 * 
	 * @var article_object
	 */
	protected $BO;
								
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
								
	/**
	 * constructor to set up the object
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('CreateArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Editor');
		
		$this->BO = new article_object();
		
		// set up the title and meta details
		$this->setTitle('Create a new Article');
		$this->setDescription('Page to create a new article.');
		$this->setKeywords('create,new,article');
		
		self::$logger->debug('<<__construct');
	}

	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		echo View::displayPageHead($this);
		
		$view = View::getInstance($this->BO);
		
		echo $view->createView();		
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		echo View::displayPageHead($this);
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			$this->BO = new article_object();
		
			if (isset($params['createBut'])) {			
				// populate the transient object from post data
				$this->BO->populateFromPost();
					
				$this->BO->save();			
	
				try {
					if ($this->getNextJob() != '')					
						header('Location: '.$this->getNextJob());
					else					
						header('Location: '.Front_Controller::generate_secure_URL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
				}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error creating the new article, check the log!</p>';
				}
			}
			
			if (isset($params['cancelBut'])) {
				header('Location: '.Front_Controller::generate_secure_URL('act=ListBusinessObjects'));
			}
		}catch(SecurityException $e) {
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}
	}
	
	/**
	 * Renders the Javascript required in the header by markItUp!
	 *
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		global $config;
		
		$html = '
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/jquery/jquery.pack.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/markitup/jquery.markitup.pack.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'/alpha/lib/markitup/sets/markdown/set.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'/alpha/lib/markitup/skins/simple/style.css" />
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'/alpha/lib/markitup/sets/markdown/style.css" />
			<script type="text/javascript">
			$(document).ready(function() {
				$(\'#text_field_content_0\').markItUp(mySettings);
			});
			</script>';
		
		return $html;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'CreateArticle.php') {
	$controller = new CreateArticle();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>