<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/log_file.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 * 
 * Controller used to display a log file, the path for which must be supplied in GET vars
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 */
class ViewLog extends Controller implements AlphaControllerInterface{	
	/**
	 * The path to the log that we are displaying
	 * 
	 * @var string
	 */
	private $logPath;
	
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
			self::$logger = new Logger('ViewLog');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Displaying the requested log');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		echo View::displayPageHead($this);

		// load the business object (BO) definition
		if (isset($params['logPath'])) {
			$logPath = $params['logPath'];	
		}else{
			throw new IllegalArguementException('No log file path available to view!');
			return;
		}
		
		$this->logPath = $logPath;
		
		$log = new log_file($this->logPath);
		if(preg_match("/alpha.*/", basename($this->logPath)))
			$log->render_log(array('Date/time','Level','Class','Message'));
		if(preg_match("/search_log.*/", basename($this->logPath)))
			$log->render_log(array("Search query","Search date","Client Application","Client IP"));
		if(preg_match("/feed_log.*/", basename($this->logPath)))
			$log->render_log(array("Business object","Feed type","Request date","Client Application","Client IP"));		
		
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
if ('ViewLog.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewLog();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>