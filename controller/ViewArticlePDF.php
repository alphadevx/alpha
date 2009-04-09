<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/FPDFFacade.inc';
require_once $config->get('sysRoot').'alpha/util/AlphaMarkdown.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';

/**
 * 
 * Controller used to display PDF version of an article where the title is provided in GET vars
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id: ViewArticlePDF.php 238 2007-02-03 22:36:54Z john $
 * 
 */
class ViewArticlePDF extends Controller {								
	/**
	 * Constructor to set up the object
	 */
	public function __construct() {		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		global $config;
		
		try {
			// ensure that a title is provided
			if (isset($params['title'])) {
				$title = str_replace('_', ' ', $params['title']);
			}else{
				throw new IllegalArguementException('Could not load the article as a title was not supplied!');
			}
			
			$this->BO = new article_object();
			$this->BO->loadByAttribute('title', $title);
			
			$pdf = new FPDFFacade($this->BO);
			
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			exit;
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested article from the database!</p>';
		}		
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'ViewArticlePDF.php') {
	$controller = new ViewArticlePDF();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>