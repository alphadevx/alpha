<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/person.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Logout controller that removes the current user object to the session
 * 
 * @package Alpha Admin
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * @todo logging of user Logout times
 * @version $Id$
 * 
 */
class Logout extends AlphaController implements AlphaControllerInterface {
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
		self::$logger = new Logger('Logout');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		$this->setBO($_SESSION['currentUser']);
		
		// set up the title and meta details
		$this->setTitle('Logged out successfully.');
		$this->setDescription('Logout page.');
		$this->setKeywords('Logout,logon');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		self::$logger->info('Logging out ['.$this->BO->get('email').'] at ['.date("Y-m-d H:i:s").']');
		
		$_SESSION = array();
		
		session_destroy();
		
		echo AlphaView::displayPageHead($this);
		
		echo '<center><p class="success">You have successfully logged out of the system.</p><br>';
		
		echo '<a href="'.$config->get('sysURL').'">Home Page</a></center>';
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');		
	}
}

// now build the new controller if this file is called directly
if ('Logout.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new Logout();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>