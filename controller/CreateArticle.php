<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaController.inc';
require_once $config->get('sysRoot').'alpha/view/AlphaView.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to create a new article in the database
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
class CreateArticle extends AlphaController implements AlphaControllerInterface {
	/**
	 * The new article to be created
	 * 
	 * @var article_object
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
		self::$logger = new Logger('CreateArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Standard');
		
		$this->BO = new article_object();
		
		// set up the title and meta details
		$this->setTitle('Create a new Article');
		$this->setDescription('Page to create a new article.');
		$this->setKeywords('create,new,article');
		
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
		
		echo AlphaView::displayPageHead($this);
		
		$view = AlphaView::getInstance($this->BO);
		
		echo $view->createView();		
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 * @throws SecurityException
	 * @since 1.0
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields())
				throw new SecurityException('This page cannot accept post data from remote servers!');
			
			$this->BO = new article_object();
		
			if (isset($params['createBut'])) {			
				// populate the transient object from post data
				$this->BO->populateFromPost();
					
				$this->BO->save();			
	
				try {
					if ($this->getNextJob() != '')					
						header('Location: '.$this->getNextJob());
					else					
						header('Location: '.FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID()));
				}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());
						echo '<p class="error"><br>Error creating the new article, check the log!</p>';
				}
			}
			
			if (isset($params['cancelBut'])) {
				header('Location: '.FrontController::generateSecureURL('act=ListBusinessObjects'));
			}
		}catch(SecurityException $e) {
			echo AlphaView::displayPageHead($this);
			echo '<p class="error"><br>'.$e->getMessage().'</p>';								
			self::$logger->warn($e->getMessage());
		}
		
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
			var previewURL = "'.FrontController::generateSecureURL('act=PreviewArticle&bo=article_object').'";
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
			        open: function() {
			        	//display correct dialog content
			        	$("#helpPage").load("'.$config->get('sysURL').'ViewArticleFile/file/Markdown_Help.text");
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
if(basename($_SERVER['PHP_SELF']) == 'CreateArticle.php') {
	$controller = new CreateArticle();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>