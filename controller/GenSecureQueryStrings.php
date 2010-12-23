<?php

// 

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/front/FrontController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 *
 * Controller used to generate secure URLs from the query strings provided
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 */
class GenSecureQueryStrings extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('CacheManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Generate Secure Query Strings');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		echo AlphaView::displayPageHead($this);
		
		$this->renderForm();
		
		echo AlphaView::displayPageFoot($this);
	}
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;

		echo AlphaView::displayPageHead($this);
		
		echo '<p style="width:90%; overflow:scroll;">';
		if(isset($params['QS']))
			echo $config->get('sysURL')."tk/".FrontController::encodeQuery($params['QS']);
		echo '</p>';
		
		$this->renderForm();
		
		echo AlphaView::displayPageFoot($this);
	}
	
	private function renderForm() {
		global $config;
		
		echo '<p>Use this form to generate secure (encrypted) URLs which make use of the Front Controller.  Always be sure to specify an action controller (act) at a minimum.</p>';
		echo '<p>Example 1: to generate a secure URL for viewing article object 00000000001, enter <em>act=ViewArticle&oid=00000000001</em></p>';
		echo '<p>Example 2: to generate a secure URL for viewing an Atom news feed of the articles, enter <em>act=ViewFeed&bo=article_object&type=Atom</em</p>';

		echo '<form action="'.$config->get('sysURL').'tk/'.$_GET['tk'].'" method="post">';
		echo '<input type="text" name="QS" size="100"/>';
		echo '<input type="submit" value="Generate"/>';
		echo '</form>';
	}
}

// now build the new controller if this file is called directly
if ('GenSecureQueryStrings.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new GenSecureQueryStrings();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_QUERY);
	}else{
		$controller->doGET($_GET);
	}
}

?>