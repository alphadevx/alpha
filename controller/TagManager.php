<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/util/AlphaFileUtil.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';

/**
 * 
 * Controller used to allow an admin to manage tags in the database
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
class TagManager extends AlphaController implements AlphaControllerInterface {	
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
		self::$logger = new Logger('TagManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Tag Manager');		
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		echo AlphaView::displayPageHead($this);
		
		echo '<h2>Listing business objects which are tagged</h2>';
		
		$BOs = AlphaDAO::getBOClassNames();
		
		foreach ($BOs as $BO) {
			AlphaDAO::loadClassDef($BO);
			$temp = new $BO;
			if($temp->isTagged()) {
				$tag = new TagObject();
				$count = count($tag->loadAllByAttribute('taggedClass', $BO));
				echo '<h3>'.$temp->getFriendlyClassName().' object is tagged ('.$count.' tags found)</h3>';
				
				$js = "$('#dialogDiv').text('Are you sure you want to delete all tags attached to the ".$temp->getFriendlyClassName()." class, and have them re-created?');
						$('#dialogDiv').dialog({
						buttons: {
							'OK': function(event, ui) {						
								$('#clearTaggedClass').attr('value', '".$BO."');
								$('#clearForm').submit();
							},
							'Cancel': function(event, ui) {
								$(this).dialog('close');
							}
						}
					})
					$('#dialogDiv').dialog('open');
					return false;";
				$button = new Button($js, "Re-create tags", "clearBut");
				
   				echo $button->render();
			}
		}

		AlphaDAO::disconnect();
		
   		echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="clearForm">';
   		echo '<input type="hidden" name="clearTaggedClass" id="clearTaggedClass"/>';
   		echo AlphaView::renderSecurityFields();
   		echo '</form>';
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 * @throws ResourceNotAllowedException
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			if (isset($params['clearTaggedClass']) && $params['clearTaggedClass'] != '') {
				try {
					self::$logger->info('About to start rebuilding the tags for the class ['.$params['clearTaggedClass'].']');
					$startTime = microtime(true);
					
					AlphaDAO::loadClassDef($params['clearTaggedClass']);
					$temp = new $params['clearTaggedClass'];
					$BOs = $temp->loadAll();
					
					self::$logger->info('Loaded all of the BOs (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					AlphaDAO::begin();
					
					$tag = new TagObject();
					$tag->deleteAllByAttribute('taggedClass', $params['clearTaggedClass']);
					
					self::$logger->info('Deleted all of the old tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					foreach ($BOs as $BO) {
						foreach($BO->get('taggedAttributes') as $tagged) {
							$tags = TagObject::tokenize($BO->get($tagged), get_class($BO), $BO->getOID());
							foreach($tags as $tag) {
								try {
									$tag->save();
								}catch(ValidationException $e){
									/*
									 * The unique key has most-likely been violated because this BO is already tagged with this
									 * value, so we can ignore in this case.
									 */
								}
							}
						}
					}

					self::$logger->info('Saved all of the new tags (elapsed time ['.round(microtime(true)-$startTime, 5).'] seconds)');
					
					AlphaDAO::commit();
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Tags recreated on the '.$temp->getFriendlyClassName().' class.'));
					
					self::$logger->info('Tags recreated on the ['.$params['clearTaggedClass'].'] class (time taken ['.round(microtime(true)-$startTime, 5).'] seconds).');
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
					AlphaDAO::rollback();
				}
				
				AlphaDAO::disconnect();
			}
			
			$this->doGET($params);
		}catch(SecurityException $e) {
			self::$logger->warn($e->getMessage());
			throw new ResourceNotAllowedException($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			throw new ResourceNotFoundException($e->getMessage());
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
}

// now build the new controller if this file is called directly
if ('TagManager.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new TagManager();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>