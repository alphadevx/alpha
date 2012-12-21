<?php

// include the config file
if(!isset($config)) {
	require_once '../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
	
	require_once $config->get('app.root').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * 
 * Controller used to edit TagObjects related to the BO indicated in the supplied 
 * GET vars (bo and oid).
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
class EditTags extends Edit implements AlphaControllerInterface {
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
		self::$logger = new Logger('EditTags');
		self::$logger->debug('>>__construct()');
		
		// ensure that the super class constructor is called, indicating the rights group
		parent::__construct('Admin');
		
		// set up the title and meta details
		$this->setTitle('Editing Tags');
		$this->setDescription('Page to edit tags.');
		$this->setKeywords('edit,tags');
		
		$this->BO = new TagObject();
				
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Handle GET requests
	 * 
	 * @param array $params
	 * @throws IllegalArguementException
	 * @since 1.0
	 * @throws FileNotFoundException
	 */
	public function doGET($params) {
		self::$logger->debug('>>doGET($params=['.var_export($params, true).'])');
		
		global $config;
		
		echo AlphaView::displayPageHead($this);
		
		// ensure that a bo is provided
		if (isset($params['bo']))
			$BOName = $params['bo'];
		else
			throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
		
		// ensure that a OID is provided
		if (isset($params['oid']))
			$BOoid = $params['oid'];
		else
			throw new IllegalArguementException('Could not load the tag objects as an oid was not supplied!');
		
		try {
			AlphaDAO::loadClassDef($BOName);
			$this->BO = new $BOName;
			$this->BO->load($BOoid);
			
			$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
			AlphaDAO::disconnect();
			
			echo '<table cols="3" class="edit_view">';
			echo '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST">';
			echo '<tr><td colspan="3"><h3>The following tags were found:</h3></td></tr>';
			
			foreach($tags as $tag) {
				echo '<tr><td>';
				$labels = $tag->getDataLabels();
				echo $labels['content'];
				echo '</td><td>';
				
				$temp = new StringBox($tag->getPropObject('content'), $labels['content'], 'content_'.$tag->getID(), '');
				echo $temp->render(false);
				echo '</td><td>';
				
				$js = "$('#dialogDiv').text('Are you sure you wish to delete this tag?');
							$('#dialogDiv').dialog({
							buttons: {
								'OK': function(event, ui) {						
									$('#deleteOID').attr('value', '".$tag->getID()."');
									$('#deleteForm').submit();
								},
								'Cancel': function(event, ui) {
									$(this).dialog('close');
								}
							}
						})
						$('#dialogDiv').dialog('open');
						return false;";
				$button = new Button($js, "Delete", "delete".$tag->getID()."But");
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
		
			$temp = new Button('submit', 'Save', 'saveBut');
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.$params['bo'].'&oid='.$params['oid'])."'", 'Back to Object', 'cancelBut');
			echo $temp->render();
			echo '</td></tr>';

			echo AlphaView::renderSecurityFields();
		
			echo '</form></table>';
			
			echo AlphaView::renderDeleteForm();
			
		}catch(BONotFoundException $e) {
			$msg = 'Unable to load the BO of id ['.$params['oid'].'], error was ['.$e->getMessage().']';
			self::$logger->error($msg);
			throw new FileNotFoundException($msg);
		}
		
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
		
			// ensure that a bo is provided
			if (isset($params['bo']))
				$BOName = $params['bo'];
			else
				throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
			
			// ensure that a OID is provided
			if (isset($params['oid']))
				$BOoid = $params['oid'];
			else
				throw new IllegalArguementException('Could not load the tag objects as a bo was not supplied!');
			
			if (isset($params['saveBut'])) {
				try {
					AlphaDAO::loadClassDef($BOName);
					$this->BO = new $BOName;
					$this->BO->load($BOoid);
			
					$tags = $this->BO->getPropObject('tags')->getRelatedObjects();
			
					AlphaDAO::begin();
					
					foreach ($tags as $tag) {
						$tag->set('content', TagObject::cleanTagContent($params['content_'.$tag->getID()]));
						$tag->save();
					}
	
					// handle new tag if posted
					if(isset($params['new_value']) && trim($params['new_value']) != '') {
						$newTag = new TagObject();
						$newTag->set('content', TagObject::cleanTagContent($params['new_value']));
						$newTag->set('taggedOID', $BOoid);
						$newTag->set('taggedClass', $BOName);
						$newTag->save();
					}
							
					AlphaDAO::commit();
					
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' saved successfully.'));
										
					$this->doGET($params);
				}catch (ValidationException $e) {
					/*
					 * The unique key has most-likely been violated because this BO is already tagged with this
					 * value.
					 */
					AlphaDAO::rollback();
					
					$this->setStatusMessage(AlphaView::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved due to duplicate tag values, please try again.'));
					
					$this->doGET($params);
				}catch (FailedSaveException $e) {
					self::$logger->error('Unable to save the tags of id ['.$params['oid'].'], error was ['.$e->getMessage().']');
					AlphaDAO::rollback();
					
					$this->setStatusMessage(AlphaView::displayErrorMessage('Tags on '.get_class($this->BO).' '.$this->BO->getID().' not saved, please check the application logs.'));
					
					$this->doGET($params);
				}
				
				AlphaDAO::disconnect();
			}
			
			if (!empty($params['deleteOID'])) {					
				try {
					AlphaDAO::loadClassDef($BOName);
					$this->BO = new $BOName;
					$this->BO->load($BOoid);
					
					$tag = new TagObject();
					$tag->load($params['deleteOID']);
					$content = $tag->get('content');
					
					AlphaDAO::begin();
					
					$tag->delete();
								
					AlphaDAO::commit();
					
					$this->setStatusMessage(AlphaView::displayUpdateMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' deleted successfully.'));					
					
					$this->doGET($params);									
				}catch(AlphaException $e) {
					self::$logger->error('Unable to delete the tag of id ['.$params['deleteOID'].'], error was ['.$e->getMessage().']');
					AlphaDAO::rollback();
					
					$this->setStatusMessage(AlphaView::displayErrorMessage('Tag <em>'.$content.'</em> on '.get_class($this->BO).' '.$this->BO->getID().' not deleted, please check the application logs.'));
					
					$this->doGET($params);
				}
				
				AlphaDAO::disconnect();
			}
		}catch(SecurityException $e) {
			
			$this->setStatusMessage(AlphaView::displayErrorMessage($e->getMessage()));
											
			self::$logger->warn($e->getMessage());
		}catch(IllegalArguementException $e) {
			self::$logger->error($e->getMessage());
		}catch(BONotFoundException $e) {
			self::$logger->warn($e->getMessage());
			
			$this->setStatusMessage(AlphaView::displayErrorMessage('Failed to load the requested item from the database!'));
		}
				
		self::$logger->debug('<<doPOST');
	}
	
	/**
	 * Using this callback to blank the new_value field when the page loads, regardless of anything being posted
	 * 
	 * @return string
	 * @since 1.0
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