<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/AlphaFileUtil.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * 
 * Controller used to view (download) an attachment file on an article_object
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2010 John Collins
 * @version $Id$
 */
class ViewAttachment extends AlphaController implements AlphaControllerInterface{	
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
			self::$logger = new Logger('ViewAttachment');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
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
		
		if(isset($params['dir']) && isset($params['filename'])) {
			$filePath = $params['dir'].'/'.$params['filename'];
			
			if(file_exists($filePath)) {
				self::$logger->info('Downloading the file ['.$params['filename'].'] from the folder ['.$params['dir'].']');
				
				$pathParts = pathinfo($filePath);
				$mimeType = AlphaFileUtil::getMIMETypeByExtension($pathParts['extension']);
				header('Content-Type: '.$mimeType);
				header('Content-Disposition: attachment; filename="'.$pathParts['basename'].'"');
				header("Content-Length: ".filesize($filePath));
				
				readfile($filePath);
				
				self::$logger->debug('<<doGET');
				exit;
			}else{
				self::$logger->fatal('Could not access article attachment file ['.$filePath.'] as it does not exist!');
				throw new IllegalArguementException('File not found');
			}
		}else{
			self::$logger->fatal('Could not access article attachment as dir and/or filename were not provided!');
			throw new IllegalArguementException('File not found');
		}
		
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
if ('ViewAttachment.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewAttachment();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>