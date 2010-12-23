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
require_once $config->get('sysRoot').'alpha/util/LOC/AlphaMetrics.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * 
 * Controller used to display the software metrics for the application
 * 
 * @author John Collins <john@design-ireland.net>
 * @package alpha::controller
 * @copyright 2009 John Collins
 * @version $Id$
 */
class ViewMetrics extends AlphaController implements AlphaControllerInterface{
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
		
		echo AlphaView::displayPageHead($this);

		$dir = $config->get('sysRoot');
		
		$metrics = new AlphaMetrics($dir);
		$metrics->calculateLOC();
		echo $metrics->resultsToHTML();
		
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
	
	/**
	 * Renders the JQuery code to do zebra-style table colouring
	 *
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		global $config;
		
		$html = '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/jquery-1.3.2.min.js"></script>'; 
		
		$html .= '<script type="text/javascript">'.
			'$(document).ready(function(){'.
			'	$(".list_view tr:even").addClass("zebraAlt");'.
			'	$(".list_view tr").mouseover(function(){'.
			'		$(this).addClass("zebraOver");'.
			'	});'.
			'	$(".list_view tr").mouseout(function(){'.
			'		$(this).removeClass("zebraOver");'.
			'	});'.
			'});</script>';
		
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