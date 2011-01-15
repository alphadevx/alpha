<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to create a new BO, whose classname must be supplied in GET vars
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class Create extends AlphaController implements AlphaControllerInterface {
	/**
	 * The name of the BO
	 * 
	 * @var string
	 * @since 1.0
	 */
	protected $BOname;
	
	/**
	 * The new BO to be created
	 * 
	 * @var AlphaDAO
	 * @since 1.0
	 */
	protected $BO;
	
	/**
	 * The AlphaView object used for rendering the objects to create
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
	 * Constructor to set up the object
	 * 
	 * @param string $visibility
	 * @since 1.0
	 */
	public function __construct($visibility='Admin') {
		self::$logger = new Logger('Create');
		self::$logger->debug('>>__construct(visibility=['.$visibility.'])');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct($visibility);
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @throws IllegalArguementException
	 * @throws ResourceNotFoundException
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		try{
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to create!');
			}
			
			AlphaDAO::loadClassDef($BOname);
		
			/*
			 *  check and see if a custom create controller exists for this BO, and if it does use it otherwise continue
			 */
			if($this->getCustomControllerName($BOname, 'create') != null)
				$this->loadCustomController($BOname, 'create');
		
			$this->BO = new $BOname();
				
			$this->BOView = AlphaView::getInstance($this->BO);
				
			// set up the title and meta details
			if(!isset($this->title))
				$this->setTitle('Create a new '.$BOname);
			if(!isset($this->description))
				$this->setDescription('Page to create a new '.$BOname.'.');
			if(!isset($this->keywords))
				$this->setKeywords('create,new,'.$BOname);				
						
			echo AlphaView::displayPageHead($this);
				
			echo $this->BOView->createView();
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 * @throws ResourceNotAllowedException
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			// load the business object (BO) definition
			if (isset($params['bo'])) {
				$BOname = $params['bo'];
				$this->BOname = $BOname;
			}elseif(isset($this->BOname)) {
				$BOname = $this->BOname;
			}else{
				throw new IllegalArguementException('No BO available to create!');
			}
			
			AlphaDAO::loadClassDef($BOname);
				
			$this->BO = new $BOname();
		
			if (isset($params['createBut'])) {			
				// populate the transient object from post data
				$this->BO->populateFromPost();
							
				$this->BO->save();
	
				AlphaDAO::disconnect();
				
				try {
					if ($this->getNextJob() != '')					
						header('Location: '.$this->getNextJob());
					else					
						header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
				}catch(AlphaException $e) {
					echo AlphaView::displayPageHead($this);
					self::$logger->error($e->getTraceAsString());
					echo AlphaView::displayErrorMessage('Error creating the new ['.$BOname.'], check the log!');
				}
			}
			
			if (isset($params['cancelBut'])) {
				header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
			}
		}catch(SecurityException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayPageHead($this);
			throw new ResourceNotAllowedException($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayPageHead($this);
			throw new ResourceNotFoundException('The file that you have requested cannot be found!');
		}catch(ValidationException $e) {
			self::$logger->warn($e->getMessage().', query ['.$this->BO->getLastQuery().']');
			$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
			$this->doGET($params);
		}
		
		self::$logger->debug('<<doPOST');
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'Create.php') {
	$controller = new Create();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>