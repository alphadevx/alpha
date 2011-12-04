<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/Logger.inc';
require_once $config->get('sysRoot').'alpha/model/PersonObject.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 *
 * Logout controller that removes the current user object from the session
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class Logout extends AlphaController implements AlphaControllerInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		self::$logger = new Logger('Logout');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Public');
		
		if(isset($_SESSION['currentUser']))
			$this->setBO($_SESSION['currentUser']);
		else
			self::$logger->warn('Logout controller called when no user is logged in');
		
		// set up the title and meta details
		$this->setTitle('Logged out successfully.');
		$this->setDescription('Logout page.');
		$this->setKeywords('Logout,logon');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle POST requests (adds $currentUser PersonObject to the session)
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 * @throws AlphaException
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		global $config;
		
		if($this->BO instanceof PersonObject)
			self::$logger->info('Logging out ['.$this->BO->get('email').'] at ['.date("Y-m-d H:i:s").']');
		
		$_SESSION = array();
		
		session_destroy();
		
		echo AlphaView::displayPageHead($this);
		
		echo AlphaView::displayUpdateMessage('You have successfully logged out of the system.');
		
		echo '<div align="center"><a href="'.$config->get('sysURL').'">Home Page</a></div>';
		
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