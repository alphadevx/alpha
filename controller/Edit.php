<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
	
	require_once $config->get('sysRoot').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * 
 * Controller used to edit BO, which must be supplied in GET vars
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
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
class Edit extends AlphaController implements AlphaControllerInterface {
	/**
	 * The business object to be edited
	 * 
	 * @var AlphaDAO
	 * @since 1.0
	 */
	protected $BO;
	
	/**
	 * The name of the BO
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $BOName;
	
	/**
	 * The OID of the BO to be edited
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $BOoid;
	
	/**
	 * The AlphaView object used for rendering the object to edit
	 * 
	 * @var AlphaView
	 * @since 1.0
	 */
	private $BOView;
								
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
	 * @param string $visibility The name of the rights group that can access this controller.
	 * @since 1.0
	 */
	public function __construct($visibility='Admin') {
		self::$logger = new Logger('Edit');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct($visibility);
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET(params=['.var_export($params, true).'])');
		
		try{
			// load the business object (BO) definition
			if (isset($params['bo']) && isset($params['oid'])) {
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				/*
				 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
				 */
				if($this->getCustomControllerName($BOName, 'edit') != null)
					$this->loadCustomController($BOName, 'edit');
				
				$this->BO = new $BOName();
				$this->BO->load($params['oid']);
				
				AlphaDAO::disconnect();
				
				$this->BOName = $BOName;
				
				$this->BOView = AlphaView::getInstance($this->BO);
				
				// set up the title and meta details
				if($this->title == '')
					$this->setTitle('Editing a '.$BOName);
				if($this->description == '')
					$this->setDescription('Page to edit a '.$BOName.'.');
				if($this->keywords == '')
					$this->setKeywords('edit,'.$BOName);
				
				echo AlphaView::displayPageHead($this);
		
				echo AlphaView::renderDeleteForm();
		
				echo $this->BOView->editView();		
			}else{
				throw new IllegalArguementException('No BO available to edit!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<p class="error"><br>Failed to load the requested item from the database!</p>';
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @param string $saveMessage Optional status message to display on successful save of the BO, otherwise default will be used
	 * @since 1.0
	 */
	public function doPOST($params, $saveMessage='') {
		self::$logger->debug('>>doPOST(params=['.var_export($params, true).'])');
		
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			// load the business object (BO) definition
			if (isset($params['bo']) && isset($params['oid'])) {
				$BOName = $params['bo'];
				AlphaDAO::loadClassDef($BOName);
				
				$this->BO = new $BOName();
				$this->BO->load($params['oid']);
				
				$this->BOView = AlphaView::getInstance($this->BO);
					
				// set up the title and meta details
				$this->setTitle('Editing a '.$BOName);
				$this->setDescription('Page to edit a '.$BOName.'.');
				$this->setKeywords('edit,'.$BOName);
					
				echo AlphaView::displayPageHead($this);
		
				if (isset($params['saveBut'])) {
					
					// populate the transient object from post data
					$this->BO->populateFromPost();
					
					try {
						$this->BO->save();
						if($saveMessage == '')
							echo AlphaView::displayUpdateMessage(get_class($this->BO).' '.$this->BO->getID().' saved successfully.');
						else
							echo AlphaView::displayUpdateMessage($saveMessage);
					}catch (LockingException $e) {
						$this->BO->reload();
						echo AlphaView::displayErrorMessage($e->getMessage());
					}
					
					AlphaDAO::disconnect();
					
					echo $this->BOView->editView();
				}
				
				if (!empty($params['deleteOID'])) {
					$temp = new $BOName();
					$temp->load($params['deleteOID']);
					
					try {
						$temp->delete();
						
						AlphaDAO::disconnect();
								
						echo AlphaView::displayUpdateMessage($this->BOName.' '.$params['deleteOID'].' deleted successfully.');
										
						echo '<center>';
						
						$temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
							'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getMessage());
						echo AlphaView::displayErrorMessage('Error deleting the OID ['.$params['deleteOID'].'], check the log!');
					}
				}
			}else{
				throw new IllegalArguementException('No BO available to edit!');
			}
		}catch(SecurityException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayErrorMessage('Failed to load the requested item from the database!');
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Use this callback to inject in the admin menu template fragment for admin users of
	 * the backend only.
	 * 
	 * @since 1.2
	 */
	public function after_displayPageHead_callback() {
		$menu = '';
		
		if (isset($_SESSION['currentUser']) && AlphaDAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$menu .= AlphaView::loadTemplateFragment('html', 'adminmenu.phtml', array());
		}
		
		return $menu;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Edit.php') {
	$controller = new Edit();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>