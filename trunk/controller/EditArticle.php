<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/controller/Controller.inc';
require_once $config->get('sysRoot').'alpha/model/article_object.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';

/**
 * 
 * Controller used to edit an existing article
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class EditArticle extends Controller implements AlphaControllerInterface {
	/**
	 * The new article to be edited
	 * 
	 * @var article_object
	 */
	protected $BO;
								
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	/**
	 * constructor to set up the object
	 */
	public function __construct($visibility='Editor') {
		if(self::$logger == null)
			self::$logger = new Logger('EditArticle');
		self::$logger->debug('>>__construct()');
		
		global $config;
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct($visibility);
		
		$this->BO = new article_object();
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		try{
			// load the business object (BO) definition
			if (isset($params['oid'])) {				
				if(!Validator::isInteger($params['oid']))
					throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');
				
				$this->BO->load($params['oid']);
				
				$BOView = View::getInstance($this->BO);
				
				// set up the title and meta details
				$this->setTitle($this->BO->get('title').' (editing)');
				$this->setDescription('Page to edit '.$this->BO->get('title').'.');
				$this->setKeywords('edit,article');
				
				echo View::displayPageHead($this);
		
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
		
		echo View::renderDeleteForm();
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Method to handle POST requests
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		global $config;
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}

			if (isset($params['oid'])) {
				if(!Validator::isInteger($params['oid']))
					throw new IllegalArguementException('Article ID provided ['.$params['oid'].'] is not valid!');
									
				$this->BO->load($params['oid']);
				
				$BOView = View::getInstance($this->BO);
					
				// set up the title and meta details
				$this->setTitle($this->BO->get('title').' (editing)');
				$this->setDescription('Page to edit '.$this->BO->get('title').'.');
				$this->setKeywords('edit,article');
					
				echo View::displayPageHead($this);
		
				if (isset($params['saveBut'])) {					
					// populate the transient object from post data
					$this->BO->populateFromPost();
					
					try {
						$success = $this->BO->save();			
						echo '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
							<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
							<strong>Update:</strong> Article '.$this->BO->getID().' saved successfully.</p></div>';
					}catch (LockingException $e) {
						$this->BO->reload();						
						echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
							<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
							<strong>Error:</strong> '.$e->getMessage().'</p></div>';
					}
					// needed by markItUp so that it does not include \'s in text box after saving
					$this->BO->set('content', stripslashes($this->BO->get('content')));
					echo $BOView->editView();
				}
				
				if (!empty($params['delete_oid'])) {
					$this->BO->load($params['delete_oid']);
					
					try {
						$this->BO->delete();
								
						echo '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
							<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
							<strong>Update:</strong> Article '.$params['delete_oid'].' deleted successfully.</p></div>';
										
						echo '<center>';
						
						$temp = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'",'Back to List','cancelBut');
						echo $temp->render();
						
						echo '</center>';
					}catch(AlphaException $e) {
						self::$logger->error($e->getTraceAsString());						
						echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
							<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
							<strong>Error:</strong> Error deleting the article, check the log!</p></div>';
					}
				}
				
				if(isset($params['uploadBut'])) {							
					// upload the file to the attachments directory
					$success = move_uploaded_file($_FILES['userfile']['tmp_name'], $this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name']);
					
					if(!$success)
						throw new AlphaException('Could not move the uploaded file ['.$success.']');
					
					// set read/write permissions on the file
					$success = chmod($this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'], 0666);
					
					if (!$success)
						throw new AlphaException('Unable to set read/write permissions on the uploaded file ['.$this->BO->getAttachmentsLocation().'/'.$_FILES['userfile']['name'].'].');
					
					if($success) {						
						echo '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> File uploaded successfully.</p></div>';
					}
					
					$view = View::getInstance($this->BO);
				
					echo $view->editView();
				}
				
				if (!empty($params['file_to_delete'])) {							
					$success = unlink($this->BO->getAttachmentsLocation().'/'.$params['file_to_delete']);
					
					if(!$success)
						throw new AlphaException('Could not delete the file ['.$params['file_to_delete'].']');
					
					if($success) {						
						echo '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> '.$params['file_to_delete'].' deleted successfully.</p></div>';
					}
					
					$view = View::getInstance($this->BO);
				
					echo $view->editView();
				}
			}else{
				throw new IllegalArguementException('No valid article ID provided!');
			}
		}catch(SecurityException $e) {
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$e->getMessage().'</p></div>';
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			echo '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> Failed to load the requested article from the database!</p></div>';
		}
		
		echo View::renderDeleteForm();
		
		echo View::displayPageFoot($this);
	}
	
	/**
	 * Renders the Javascript required in the header by markItUp!
	 *
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		global $config;
		
		$html = '
			<script type="text/javascript">
			var previewURL = "'.$config->get('sysURL').'alpha/controller/PreviewArticle.php?oid='.$this->BO->getID().'&bo='.get_class($this->BO).'";
			</script>			
			<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/markitup/jquery.markitup.js"></script>
			<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/markitup/sets/markdown/set.js"></script>
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/markitup/skins/simple/style.css" />
			<link rel="stylesheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/markitup/sets/markdown/style.css" />
			<script type="text/javascript">
			$(document).ready(function() {
				$(\'#text_field_content_0\').markItUp(mySettings);
				
				var dialogCoords = [(screen.width/2)-400, (screen.height/2)-300];
				
				var dialogOpts = {
			        title: "Help Page",
			        modal: false,
			        autoOpen: false,
			        height: 400,
			        width: 800,
			        position: dialogCoords,
			        open: function() {
			        	//display correct dialog content
			        	$("#helpPage").load("'.$config->get('sysURL').'alpha/controller/ViewArticleFile.php?file=Markdown_Help.text");
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