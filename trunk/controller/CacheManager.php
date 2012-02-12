<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
	
	require_once $config->get('sysRoot').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * 
 * Controller used to clear out the CMS cache when required
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
class CacheManager extends AlphaController implements AlphaControllerInterface {
	/**
	 * The root of the cache directory
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $dataDir;
	
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
		self::$logger = new Logger('CacheManager');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		$this->setTitle('Cache Manager');
		$this->dataDir  = $config->get('sysRoot').'cache/';
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		global $config;
		
		if(!is_array($params))
			throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doGET method!');
		
		
		echo AlphaView::displayPageHead($this);
		
		echo '<h2>Listing contents of cache directory: '.$this->dataDir.'</h2>';
		
   		$fileCount = AlphaFileUtils::listDirectoryContents($this->dataDir);
   		
   		echo '<h2>Total of '.$fileCount.' files in the cache.</h2>';
   		
   		echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="clearForm" id="clearForm">';
   		echo '<input type="hidden" name="clearCache" id="clearCache" value="false"/>';
   		$js = "$('#dialogDiv').text('Are you sure you want to delete all files in the cache?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {						
						$('#clearCache').attr('value', 'true');
						$('#clearForm').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
		$button = new Button($js, "Clear cache", "clearBut");
   		echo $button->render();
   		
   		echo AlphaView::renderSecurityFields();
   		echo '</form>';
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Handle POST requests
	 * 
	 * @param array $params
	 * @throws SecurityException
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.var_export($params, true).'])');
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			if(!is_array($params))
				throw new IllegalArguementException('Bad $params ['.var_export($params, true).'] passed to doPOST method!');

			if (isset($params['clearCache']) && $params['clearCache'] == 'true') {
				try {
					AlphaFileUtils::deleteDirectoryContents($this->dataDir);
							
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Cache contents deleted successfully.'));
					
					self::$logger->info('Cache contents deleted successfully by user ['.$_SESSION['currentUser']->get('displayName').'].');
				}catch (AlphaException $e) {
					self::$logger->error($e->getMessage());
					$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
				}				
			}
			
			$this->doGET($params);
		}catch(SecurityException $e) {
			$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
			
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
			$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
		}
		
		echo AlphaView::displayPageFoot($this);
		self::$logger->debug('<<doPOST');
	}
}

// now build the new controller if this file is called directly
if ('CacheManager.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new CacheManager();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_QUERY);
	}else{
		$controller->doGET($_GET);
	}
}

?>