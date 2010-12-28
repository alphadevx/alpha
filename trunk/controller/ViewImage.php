<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/view/widgets/Image.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Controller for viewing an image rendered with the Image widget.
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ViewImage extends AlphaController implements AlphaControllerInterface {
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
		self::$logger = new Logger('ViewImage');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handles get requests
	 *
	 * @param array $params
	 */
	public function doGet($params) {
		self::$logger->debug('>>doGet(params=['.print_r($params, true).'])');
		
		try {
			$imgSource = $params['s'];
			$imgWidth = $params['w'];
			$imgHeight = $params['h'];
			$imgType = $params['t'];
			$imgQuality = $params['q'];
			$imgScale = $params['sc'];
			$imgSecure = $params['se'];
		}catch (Exception $e) {
			self::$logger->fatal('Required param missing for ViewImage controller['.$e->getMessage().']');
			self::$logger->debug('<<__doGet');
			exit;
		}
		
		try {
			$image = new Image($imgSource, $imgWidth, $imgHeight, $imgType, $imgQuality, $imgScale, $imgSecure);
			$image->renderImage();
		}catch (IllegalArguementException $e) {
			self::$logger->fatal($e->getMessage());
			self::$logger->debug('<<__doGet');
			exit;
		}
		self::$logger->debug('<<__doGet');
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
if ('ViewImage.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewImage();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>