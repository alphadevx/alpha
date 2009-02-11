<?php

// include the config file
if(!isset($config))
	require_once '../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/util/convertors/BO2Excel.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Controller for viewing Business Objects as Excel spreadsheets
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class ViewExcel extends Controller implements AlphaControllerInterface {
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
			self::$logger = new Logger('ViewExcel');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Loads the BO indicated in the GET request and handles the conversion to Excel 
	 *
	 * @param array $params
	 */
	public function doGet($params) {
		self::$logger->debug('>>doGet(params=['.print_r($params, true).'])');
		
		try {
			$BOname = $params['bo'];
			$OID = $params['oid'];
		}catch (Exception $e) {
			self::$logger->fatal('No BO and/or OID parameter available for ViewExcel controller!');
			self::$logger->debug('<<__doGet');
			exit;
		}
		
		try {
			DAO::loadClassDef($BOname);
			$BO = new $BOname();
			$BO->load($OID);
			
			$convertor = new BO2Excel($BO);
			$convertor->render();
		}catch (BONotFoundException $e) {
			self::$logger->fatal($e->getMessage());
			self::$logger->debug('<<__doGet');
			exit;
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
if ('ViewExcel.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ViewExcel();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>