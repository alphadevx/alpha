<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
}

require_once $config->get('sysRoot').'alpha/controller/Edit.php';
require_once $config->get('sysRoot').'alpha/model/tag_object.inc';
require_once $config->get('sysRoot').'alpha/controller/AlphaControllerInterface.inc';
require_once $config->get('sysRoot').'alpha/exceptions/IllegalArguementException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/BONotFoundException.inc';
require_once $config->get('sysRoot').'alpha/exceptions/FailedSaveException.inc';

/**
 * 
 * Controller used to edit tag_objects related to the BO indicated in the supplied 
 * GET vars (bo and oid).
 * 
 * @package alpha::controller
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class EditTags extends Edit implements AlphaControllerInterface {
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
		if(self::$logger == null)
			self::$logger = new Logger('EditTags');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Editing Tags');
		$this->setDescription('Page to edit tags.');
		$this->setKeywords('edit,tags');
		
		$this->BO = new tag_object();
				
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.print_r($params, true).'])');
		
		global $config;
		
		echo ViewAlpha::displayPageHead($this);
		
		// ensure that a bo is provided
		if (isset($params['bo'])) {
			$BOName = $params['bo'];
		}else{
			throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
			return;
		}
		
		// ensure that a OID is provided
		if (isset($params['oid'])) {
			$BOoid = $params['oid'];
		}else{
			throw new IllegalArguementException('Could not load the tag objects as an oid was not supplied!');
			return;
		}
		
		try {
			AlphaDAO::loadClassDef($BOName);
			$this->BO = new $BOName;
			$this->BO->load($BOoid);
			
			$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
			echo '<table cols="3" class="edit_view">';
			echo '<form action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" method="POST">';
			echo '<tr><td colspan="3"><h3>The following tags were found:</h3></td></tr>';
			
			foreach($tags as $tag) {
				echo '<tr><td>';
				$labels = $tag->getDataLabels();
				echo $labels['content'];
				echo '</td><td>';
				
				$temp = new StringBox($tag->getPropObject('content'), $labels['content'], 'content_'.$tag->getID(), '');
				echo $temp->render(false);
				echo '</td><td>';
				
				$button = new button("if(confirm('Are you sure you wish to delete this tag?')) {document.getElementById('delete_oid').value = '".$tag->getID()."'; document.getElementById('delete_form').submit();}", "Delete", "deleteBut");
				echo $button->render().'</td></tr>';
			}
			
			echo '<tr><td colspan="3"><h3>Add a new tag:</h3></td></tr>';
			echo '<tr><td>';			
			echo 'New tag';
			echo '</td><td>';
			$temp = new StringBox(new String(), 'New tag', 'new_value', '');
			echo $temp->render(false);
			echo '</td><td></td></tr>';
		
			echo '<tr><td colspan="3">';
		
			$temp = new button('submit', 'Save', 'saveBut');
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.$params['bo'].'&oid='.$params['oid'])."'", 'Back to Object', 'cancelBut');
			echo $temp->render();
			echo '</td></tr>';

			echo AlphaView::renderSecurityFields();
		
			echo '</form></table>';
			
			echo AlphaView::renderDeleteForm();
			
		}catch(BONotFoundException $e) {
			self::$logger->error('Unable to load the BO of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
		}
		
		echo AlphaView::displayPageFoot($this);
		
		self::$logger->debug('<<doGET');		
	}
	
	/**
	 * Handle POST requests (adds $currentUser person_object to the session)
	 * 
	 * @param array $params
	 */
	public function doPOST($params) {
		self::$logger->debug('>>doPOST($params=['.print_r($params, true).'])');
		
		try {
			// check the hidden security fields before accepting the form POST data
			if(!$this->checkSecurityFields()) {
				throw new SecurityException('This page cannot accept post data from remote servers!');
				self::$logger->debug('<<doPOST');
			}
		
			// ensure that a bo is provided
			if (isset($params['bo'])) {
				$BOName = $params['bo'];
			}else{
				throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
				return;
			}
			
			// ensure that a OID is provided
			if (isset($params['oid'])) {
				$BOoid = $params['oid'];
			}else{
				throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
			}
			
			if (isset($params['saveBut'])) {
				try {
					AlphaDAO::loadClassDef($BOName);
					$this->BO = new $BOName;
					$this->BO->load($BOoid);
			
					$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
					AlphaDAO::begin();
					
					foreach ($tags as $tag) {
						$tag->set('content', tag_object::cleanTagContent($params['content_'.$tag->getID()]));
						$tag->save();
					}
	
					// handle new tag if posted
					if(isset($params['new_value']) && trim($params['new_value']) != '') {
						$newTag = new tag_object();
						$newTag->set('content', tag_object::cleanTagContent($params['new_value']));
						$newTag->set('taggedOID', $BOoid);
						$newTag->set('taggedClass', $BOName);
						$newTag->save();
					}
							
					AlphaDAO::commit();					
					
					$this->setStatusMessage('<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> Tags on '.get_class($this->BO).' '.$this->BO->getID().' saved successfully.</p>
						</div>');
										
					$this->doGET($params);
				}catch (ValidationException $e) {
					/*
					 * The unique key has most-likely been violated because this BO is already tagged with this
					 * value.
					 */
					AlphaDAO::rollback();
					
					$this->setStatusMessage('<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Error:</strong> Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved due to duplicate tag values, please try again.</p>
						</div>');
					
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error('Unable to save the tags of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
					AlphaDAO::rollback();
					
					$this->setStatusMessage('<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Error:</strong> Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved, please check the application logs.</p>
						</div>');
					
					$this->doGET($params);
				}
			}
			
			if (!empty($params['delete_oid'])) {					
				try {
					AlphaDAO::loadClassDef($BOName);
					$this->BO = new $BOName;
					$this->BO->load($BOoid);
					
					$tag = new tag_object();
					$tag->load($params['delete_oid']);
					$content = $tag->get('content');
					
					AlphaDAO::begin();
					
					$tag->delete();
								
					AlphaDAO::commit();
					
					$this->setStatusMessage('<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Update:</strong> Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' deleted successfully.</p>
						</div>');					
					
					$this->doGET($params);									
				}catch(AlphaException $e) {
					self::$logger->error('Unable to delete the tag of id ['.$params['delete_oid'].'], error was ['.$e->getMessage().']');
					AlphaDAO::rollback();
					
					$this->setStatusMessage('<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
						<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
						<strong>Error:</strong> Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' not deleted, please check the application logs.</p>
						</div>');
					
					$this->doGET($params);
				}
			}
		}catch(SecurityException $e) {
			
			$this->setStatusMessage('<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> '.$e->getMessage().'</p>
				</div>');
											
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			
			$this->setStatusMessage('<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>Error:</strong> Failed to load the requested item from the database!</p>
				</div>');
		}
				
		self::$logger->debug('<<doPOST');		
	}
	
	/**
	 * Using this callback to blank the new_value field when the page loads, regardless of anything being posted
	 * 
	 * @return string
	 */
	public function during_displayPageHead_callback() {
		$html = '';
		$html .= '<script language="javascript">';
		$html .= 'function clearNewField() {';
		$html .= '	document.getElementById("new_value").value = "";';
		$html .= '}';
		$html .= 'addOnloadEvent(clearNewField);';
		$html .= '</script>';
		return $html;
	}
}

// now build the new controller if this file is called directly
if ('EditTags.php' == basename($_SERVER['PHP_SELF'])) {
	$controller = new EditTags();
	
	if(!empty($_POST)) {			
		$controller->doPOST($_REQUEST);
	}else{
		$controller->doGET($_GET);
	}
}

?>