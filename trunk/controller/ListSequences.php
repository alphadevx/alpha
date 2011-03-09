<?php

// include the config file
if(!isset($config)) {
	require_once '../util/configLoader.inc';
	$config = configLoader::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/ListAll.php';
require_once $config->get('sysRoot').'alpha/model/types/Sequence.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to list all Sequences
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id: ListBusinessObjects.php 1249 2010-12-31 16:04:04Z johnc $
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
class ListSequences extends ListAll implements AlphaControllerInterface {
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
		self::$logger = new Logger('ListSequences');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$BO = new Sequence();
		
		// make sure that the Sequence tables exist
		if(!$BO->checkTableExists()) {
			echo AlphaView::displayErrorMessage('Warning! The Sequence table do not exist, attempting to create it now...');
			$BO->makeTable();
		}
		
		// set up the title and meta details
		$this->setTitle('Listing all Sequences');
		$this->setDescription('Page to list all Sequences.');
		$this->setKeywords('list,all,Sequences');
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		echo AlphaView::displayPageHead($this);
		
		// get all of the BOs and invoke the list_view on each one
		$temp = new Sequence();
		// set the start point for the list pagination
		if (isset($params['start']) ? $this->startPoint = $params['start']: $this->startPoint = 1);
			
		$objects = $temp->loadAll($this->startPoint);
		
		AlphaDAO::disconnect();
		
		$BO = new Sequence();
		$this->BOCount = $BO->getCount();
		
		echo AlphaView::renderDeleteForm();
		
		foreach($objects as $object) {
			$temp = AlphaView::getInstance($object);
			echo $temp->listView();
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');		
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		self::$logger->debug('<<doPOST');		
	}
}

// now build the new controller if this file is called directly
if ('ListSequences.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new ListSequences();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_POST);
	}else{
		$controller->doGET($_GET);
	}
}

?>