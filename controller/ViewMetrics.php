<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/LOC/metrics.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 * 
 * Controller used to display the software metrics for the application
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
 */
class ViewMetrics extends Controller implements AlphaControllerInterface{
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * The constructor
	 */
	public function __construct() {
		if(self::$logger == null)
			self::$logger = new Logger('ViewMetrics');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Application Metrics');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		echo View::displayPageHead($this);

		$dir = $config->get('sysRoot');
		
		$metrics = new metrics($dir);
		$metrics->calculate_LOC();
		$metrics->results_to_HTML();
		
		echo View::displayPageFoot($this);
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Renders an administration home page link after the page header is rendered
	 * 
	 * @return string
	 */
	public function after_displayPageHead_callback() {
		global $config;
		
		$html = '<p align="center"><a href="'.FrontController::generateSecureURL('act=ListBusinessObjects').'">Administration Home Page</a></p>';
		
		return $html;
	}
}

// now build the new controller if this file is called directly
if ('ViewMetrics.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewMetrics();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>