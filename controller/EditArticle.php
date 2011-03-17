<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/view/ViewState.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/model/ArticleObject.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to edit an existing article
 * 
 * @package alpha::controller
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
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
class EditArticle extends AlphaController implements AlphaControllerInterface {
	/**
	 * The new article to be edited
	 * 
	 * @var ArticleObject
	 * @since 1.0
	 */
	protected $BO;
								
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
		self::$logger = new Logger('EditArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Standard');
		
		$this->BO = new ArticleObject();
		
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
			if (isset($params['oid'])) {				
				if(!AlphaValidator::isInteger($params['oid']))
					throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');
				
				$this->BO->load($params['oid']);
				
				AlphaDAO::disconnect();
				
				$BOView = AlphaView::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle($this->BO->get('title').' (editing)');
				$this->setDescription('Page to edit '.$this->BO->get('title').'.');
				$this->setKeywords('edit,article');
				
				echo AlphaView::displayPageHead($this);
		
				echo $BOView->editView();
			}else{
				throw new IllegalArguementException('No valid article ID provided!');
			}
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> Failed to load the requested article from the database!</p></div>';
		}
		
		echo AlphaView::renderDeleteForm();
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST(params=['.var_export($params, true).'])');
		
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
			
			if(isset($params['markdownTextBoxRows']) && $params['markdownTextBoxRows'] != '') {
				$viewState = ViewState::getInstance();
				$viewState->set('markdownTextBoxRows', $params['markdownTextBoxRows']);
			}

			if (isset($params['oid'])) {
				if(!AlphaValidator::isInteger($params['oid']))
					throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');
									
				$this->BO->load($params['oid']);
				
				$BOView = AlphaView::getInstance($this->BO);
					
				// set up the title and meta details
				$this->setTitle($this->BO->get('title').' (editing)');
				$this->setDescription('Page to edit '.$this->BO->get('title').'.');
				$this->setKeywords('edit,article');
				
				echo AlphaView::displayPageHead($this);
		
				if (isset($params['saveBut'])) {
										
					// populate the transient object from post data
					$this->BO->populateFromPost();
					
					try {
						$success = $this->BO->save();			
						echo AlphaView::displayUpdateMessage('Article '.$this->BO->getID().' saved successfully.');
					}catch (LockingException $e) {
						$this->BO->reload();						
						echo AlphaView::displayErrorMessage($e->getMessage());
					}

					AlphaDAO::disconnect();
					echo $BOView->editView();
				}
				
				if (!empty($params['deleteOID'])) {
					
					$this->BO->load($params['deleteOID']);
					
					try {
						$this->BO->delete();
						
						AlphaDAO::disconnect();
								
						echo AlphaView::displayUpdateMessage('Article '.$params['deleteOID'].' deleted successfully.');
										
						echo '<center>';
						
						$temp = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",
							'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());						
						echo AlphaView::displayErrorMessage('Error deleting the article, check the log!');
					}
				}
				
				if(isset($params['uploadBut'])) {
												
					// upload the file to the attachments directory
					$success = move_uploaded_file($_FILES['userfile']['tmp_name'], $this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name']);
					
					if(!$success)
						throw new AlphaException('Could not move the uploaded file ['.$_FILES['userfile']['name'].']');
					
					// set read/write permissions on the file
					$success = chmod($this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'], 0666);
					
					if (!$success)
						throw new AlphaException('Unable to set read/write permissions on the uploaded file ['.$this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'].'].');
					
					if($success) {						
						echo AlphaView::displayUpdateMessage('File uploaded successfully.');
					}
					
					$view = AlphaView::getInstance($this->BO);
				
					echo $view->editView();
				}
				
				if (!empty($params['file_to_delete'])) {
												
					$success = unlink($this->BO->getAttachmentsLocation().'/'.$params['file_to_delete']);
					
					if(!$success)
						throw new AlphaException('Could not delete the file ['.$params['file_to_delete'].']');
					
					if($success) {						
						echo AlphaView::displayUpdateMessage($params['file_to_delete'].' deleted successfully.');
					}
					
					$view = AlphaView::getInstance($this->BO);
				
					echo $view->editView();
				}
			}else{
				throw new IllegalArguementException('No valid article ID provided!');
			}
		}catch(SecurityException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo AlphaView::displayErrorMessage('Failed to load the requested article from the database!');
		}catch(AlphaException $e) {
			echo AlphaView::displayErrorMessage($e->getMessage());
			self::$logger->error($e->getMessage());
		}
		
		echo AlphaView::renderDeleteForm();
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Renders the Javascript required in the header by markItUp!
	 *
	 * @return string
	 * @since 1.0
	 */
	public function during_displayPageHead_callback() {
		global $config;
		
		$html = '
			<script type="text/javascript">
			var previewURL = "'.FrontController::generateSecureURL('act=PreviewArticle&bo=ArticleObject&oid='.$this->BO->getOID()).'";
			</script>			
			<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/markitup/jquery.markitup.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/markitup/sets/markdown/set.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/markitup/skins/simple/style.css" />
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/markitup/sets/markdown/style.css" />
			<script type="text/javascript">
			$(document).ready(function() {
				$("#text_field_content_0").markItUp(mySettings);
				
				var dialogCoords = [(screen.width/2)-400, (screen.height/2)-300];
				
				var dialogOpts = {
			        title: "Help Page",
			        modal: true,
			        resizable: false,
			        draggable: false,
			        autoOpen: false,
			        height: 400,
			        width: 800,
			        position: dialogCoords,
			        buttons: {},
			        open: function() {
			        	//display correct dialog content
			        	$("#helpPage").load("'.FrontController::generateSecureURL('act=ViewArticleFile&file=Markdown_Help.text').'");
					},
					close: function() {
					
						$("#helpPage").dialog(dialogOpts);
						
						$(".markItUpButton15").click(
			        		function (){
			            		$("#helpPage").dialog("open");
			            		return false;
			        		}
			    		);
			    	}
			    };
			        
			    $("#helpPage").dialog(dialogOpts);
    
			    $(".markItUpButton15").click(
			        function (){
			            $("#helpPage").dialog("open");
			            return false;
			        }
			    );
			});
			</script>';
		
		return $html;
	}
}

// now build the new controller
if(basename($_SERVER['PHP_SELF']) == 'EditArticle.php') {
	$controller = new EditArticle();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>