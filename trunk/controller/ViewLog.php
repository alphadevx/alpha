<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/util/LogFile.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * 
 * Controller used to display a log file, the path for which must be supplied in GET vars
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 */
class ViewLog extends AlphaController implements AlphaControllerInterface{	
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
		
		echo AlphaView::displayPageHead($this);

		// load the business object (BO) definition
		if (isset($params['logPath'])) {
			$logPath = $params['logPath'];	
		}else{
			throw new IllegalArguementException('No log file path available to view!');
			return;
		}
		
		$this->logPath = $logPath;
		
		$log = new LogFile($this->logPath);
		if(preg_match("/alpha.*/", basename($this->logPath)))
			$log->renderLog(array('Date/time','Level','Class','Message','Client','IP'));
		if(preg_match("/search.*/", basename($this->logPath)))
			$log->renderLog(array('Search query','Search date','Client Application','Client IP'));
		if(preg_match("/feeds.*/", basename($this->logPath)))
			$log->renderLog(array('Business object','Feed type','Request date','Client Application','Client IP'));
		if(preg_match("/tasks.*/", basename($this->logPath)))
			$log->renderLog(array('Date/time','Level','Class','Message'));
		
		echo AlphaView::displayPageFoot($this);
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