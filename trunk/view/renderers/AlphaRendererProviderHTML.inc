<?php

/**
 * HTML renderer.  Will invoke widgets from the alpha::view::widgets package
 * automatically for the correct data type.  Templates from ./templates/html
 * loaded by default, but these can be overridden on a per-DAO level in
 * the application when required (consider the default ones to be scaffolding).
 * 
 * 
 * @package alpha::view::renderers
 * @since 1.2
 * @author John Collins <dev@alphaframework.org>
 * @version $Id: AlphaRendererProviderInterface.inc 1499 2012-02-16 19:29:16Z alphadev $
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
class AlphaRendererProviderHTML implements AlphaRendererProviderInterface {
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.2
	 */
	private static $logger = null;

	/**
	 * The business object that we are renderering
	 * 
	 * @var AlphaDAO
	 * @since 1.2
	 */
	private $BO;
	
	/**
	 * The constructor
	 * 
	 * @since 1.2
	 */
	public function __construct() {
		self::$logger = new Logger('AlphaRendererProviderHTML');
		self::$logger->debug('>>__construct()');
		
		self::$logger->debug('<<__construct');
	}

	/**
  	 * (non-PHPdoc)
  	 * @see alpha/view/renderers/AlphaRendererProvider::setBO()
  	 */
	public function setBO($BO) {
		$this->BO = $BO;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::createView()
	 */
	public function createView($fields=array()) {
		self::$logger->debug('>>createView(fields=['.var_export($fields, true).'])');

		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getOID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('create');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new Button('submit', 'Create', 'createBut');
		$fields['createButton'] = $button->render();
		
		$button = new Button("document.location.replace('".FrontController::generateSecureURL('act=ListBusinessObjects')."')", 'Cancel', 'cancelBut');
		$fields['cancelButton'] = $button->render();		
		
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();		

		self::$logger->debug('<<createView [HTML]');
		return $this->loadTemplate($this->BO, 'create', $fields);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::editView()
	 */
	public function editView($fields=array()) {
		self::$logger->debug('>>editView(fields=['.var_export($fields, true).'])');
		
		global $config;

		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the form ID
		$fields['formID'] = get_class($this->BO).'_'.$this->BO->getOID();
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('edit');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new Button('submit', 'Save', 'saveBut');
		$fields['saveButton'] = $button->render();
		
		$js = "$('#dialogDiv').text('Are you sure you wish to delete this item?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {						
						$('#deleteOID').attr('value', '".$this->BO->getOID()."');
						$('#deleteForm').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
		$button = new Button($js, "Delete", "deleteBut");
		$fields['deleteButton'] = $button->render();
		
		$button = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		$fields['cancelButton'] = $button->render();		
				
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		// OID will need to be posted for optimistic lock checking
		$fields['version_num'] = $this->BO->getVersionNumber();

		self::$logger->debug('<<editView [HTML]');
		return $this->loadTemplate($this->BO, 'edit', $fields);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::listView()
	 */
	public function listView($fields=array()) {
		self::$logger->debug('>>listView(fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// work out how many columns will be in the table
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = array_keys($reflection->getDefaultProperties());		
		$fields['colCount'] = 1+count(array_diff($properties, $this->BO->getDefaultAttributes(), $this->BO->getTransientAttributes()));
		
		// get the class attributes
		$properties = $reflection->getProperties();
		
		$html = '';
		
		$html .= '<tr>';
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			// skip over password fields
			$property = $this->BO->getPropObject($propName);
			if(!($property instanceof String && $property->checkIsPassword())) {			
				if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
					$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';				
				}
				if ($propName == 'OID')
					$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';
			}else{
				$fields['colCount'] = $fields['colCount']-1;
			}
		}
		$html .= '</tr><tr>';
		
		$fields['formHeadings'] = $html;
		
		$html = '';

		// and now the values
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			$property = $this->BO->getPropObject($propName);
			if(!($property instanceof String && $property->checkIsPassword())) {
				if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
					$propClass = get_class($this->BO->getPropObject($propName));
					
					if ($propClass == 'Text') {
						$text = htmlentities($this->BO->get($propName));
						if(strlen($text) > 70)
							$html .= '	<td>&nbsp;'.substr($text, 0, 70).'...</td>';
						else
							$html .= '	<td>&nbsp;'.$text.'</td>';
					}elseif($propClass == 'DEnum') {
						$html .= '	<td>&nbsp;'.$this->BO->getPropObject($propName)->getDisplayValue().'</td>';
					}else{
						$html .= '	<td>&nbsp;'.$this->BO->get($propName).'</td>';
					}
				}
				if ($propName == 'OID')
					$html .= '	<td>&nbsp;'.$this->BO->getOID().'</td>';
			}
		}
		$html .= '</tr>';
		
		$fields['formFields'] = $html;
		
		// View button
		if(strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getOID())."';", 'View', 'view'.$this->BO->getOID().'But');
			$fields['viewButton'] = $button->render();
		}else{
			if($this->BO->hasAttribute('URL'))
				$button = new Button("document.location = '".$this->BO->get('URL')."';", 'View', 'view'.$this->BO->getOID().'But');
			else
				$button = new Button("document.location = '".$config->get('sysURL')."Detail/bo/".get_class($this->BO)."/oid/".$this->BO->getOID()."';", 'View', 'view'.$this->BO->getOID().'But');
			
			$fields['viewButton'] = $button->render();
		}

		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getOID())."'", "Edit", "edit".$this->BO->getOID()."But");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$js = "$('#dialogDiv').text('Are you sure you wish to delete this item?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {						
						$('#deleteOID').attr('value', '".$this->BO->getOID()."');
						$('#deleteForm').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
			$button = new Button($js, "Delete", "delete".$this->BO->getOID()."But");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = $this->renderSecurityFields();

		self::$logger->debug('<<listView [HTML]');
		return $this->loadTemplate($this->BO, 'list', $fields);				
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::detailedView()
	 */
	public function detailedView($fields=array()) {
		self::$logger->debug('>>detailedView(fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// we may want to display the OID regardless of class
		$fields['OIDLabel'] = $this->BO->getDataLabel('OID');		
		$fields['OID'] = $this->BO->getOID();		
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('view');
		
		// Back button
		$button = new Button('history.back()', 'Back', 'backBut');
		$fields['backButton'] = $button->render();
		
		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getOID())."'", "Edit", "editBut");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$js = "$('#dialogDiv').text('Are you sure you wish to delete this item?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {						
						$('#deleteOID').attr('value', '".$this->BO->getOID()."');
						$('#deleteForm').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
			$button = new Button($js, "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;
		
		self::$logger->debug('<<detailedView [HTML]');
		return $this->loadTemplate($this->BO, 'detail', $fields);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::adminView()
	 */
	public function adminView($fields=array()) {
		self::$logger->debug('>>adminView(fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// the form action
		$fields['formAction'] = $_SERVER['REQUEST_URI'];
		
		// the class name of the BO
		$fields['className'] = get_class($this->BO);
		
		// the table name in the DB for the BO
		$fields['tableName'] = $this->BO->getTableName();
		
		// record count for the BO in the DB
		$fields['count'] = ($this->BO->checkTableExists() ? $this->BO->getCount() : '<span class="warning">unavailable</span>');

		// table exists in the DB?
		$fields['tableExists'] = ($this->BO->checkTableExists() ? '<span class="success">Yes</span>' : '<span class="warning">No</span>');
		
		// table schema needs to be updated in the DB?
		$fields['tableNeedsUpdate'] = ($this->BO->checkTableNeedsUpdate() ? '<span class="warning">Yes</span>' : '<span class="success">No</span>');

		// create button
		if($this->BO->checkTableExists()) {
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Create&bo='.get_class($this->BO))."'", "Create New", "create".get_class($this->BO)."But");
			$fields['createButton'] = $button->render();
		}else{
			$fields['createButton'] = '';
		}
		
		// list all button
		if($this->BO->checkTableExists()) {
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "List All", "list".get_class($this->BO)."But");
			$fields['listButton'] = $button->render();
		}else{
			$fields['listButton'] = '';
		}
		
		// the create table button (if required)
		$html = '';
		if (!$this->BO->checkTableExists()) {			
			$button = new Button("submit", "Create Table", "createTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to create the table for
			$html .= '<input type="hidden" name="createTableClass" value="'.get_class($this->BO).'"/>';
		}
		$fields['createTableButton'] = $html;
		
		// recreate and update table buttons (if required)
		$html = '';
		if ($this->BO->checkTableNeedsUpdate() && $this->BO->checkTableExists()) {			
			$js = "$('#dialogDiv').text('Are you sure you wish to recreate this class table (all data will be lost)?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {
						$('#admin_".get_class($this->BO)."_button_pressed').attr('value', 'recreateTableBut');
						$('#admin_".get_class($this->BO)."').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
			$button = new Button($js , "Recreate Table", "recreateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to recreate the table for
			$html .= '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
			$html .= '&nbsp;&nbsp;';
			$js = "$('#dialogDiv').text('Are you sure you wish to attempt to modify this class table by adding new attributes?');
				$('#dialogDiv').dialog({
				buttons: {
					'OK': function(event, ui) {
						$('#admin_".get_class($this->BO)."_button_pressed').attr('value', 'updateTableBut');
						$('#admin_".get_class($this->BO)."').submit();
					},
					'Cancel': function(event, ui) {
						$(this).dialog('close');
					}
				}
			})
			$('#dialogDiv').dialog('open');
			return false;";
			$button = new Button($js , "Update Table", "updateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to update the table for
			$html .= '<input type="hidden" name="updateTableClass" value="'.get_class($this->BO).'"/>';
			// hidden field to tell us which button was pressed
			$html .= '<input type="hidden" id="admin_'.get_class($this->BO).'_button_pressed" name="admin_'.get_class($this->BO).'_button_pressed" value=""/>';
		}
		$fields['recreateOrUpdateButtons'] = $html;
		
		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = $this->renderSecurityFields();
		
		self::$logger->debug('<<adminView [HTML]');
		return $this->loadTemplate($this->BO, 'admin', $fields);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageHead()
	 */
	public static function displayPageHead($controller) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');

		self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'], renderMenu=['.$renderMenu.'], renderStatus=['.$renderStatus.'])');

		if(method_exists($controller, 'before_displayPageHead_callback'))
			$controller->before_displayPageHead_callback();
		
		global $config;
		
		if(!AlphaController::checkControllerDefExists(get_class($controller)))
			throw new IllegalArguementException('The controller provided ['.get_class($controller).'] is not defined anywhere!');
		
		$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
		
		$html.= '<html>';
		$html.= '<head>';
		$html.= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		$html.= '<title>'.$controller->getTitle().'</title>';
		if($controller->getKeywords() != '')
			$html.= '<meta name="Keywords" content="'.$controller->getKeywords().'">';
		if($controller->getDescription() != '')
			$html.= '<meta name="Description" content="'.$controller->getDescription().'">';
		$html.= '<meta name="title" content="'.$controller->getTitle().'">';
		$html.= '<meta name="robots" content="index,follow,NOODP">';
		if ($controller->getBO() != null && $controller->getBO()->hasAttribute('URL')) {
			$URL = $controller->getBO()->get('URL');
			if($URL != '')
				$html.= '<link rel="canonical" href="'.$URL.'" />';
		}
		$html.= '<meta http-equiv="imagetoolbar" content="no">';

		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/jquery.ui.all.css">';
		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/css/alpha.css">';
		$html.= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/jquery-1.5.1.min.js"></script>';
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/scripts/validation.js"></script>';
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/contextMenu/jquery.contextMenu.js"></script>';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/jquery-ui-1.8.11.custom.min.js"></script>';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/jquery.ui.potato.menu.js"></script>';
		
		$html.= '<script type="text/javascript" src="'.$config->get('sysURL').'alpha/lib/jquery/ui/jquery.ui.checkbox.js"></script>';
		
		// handle force-frames
		if($config->get('sysForceFrame')) {
			// if no-forceframe=true or we are in the admin backend, don't force frame
			if(!isset($_GET['no-forceframe']) && strpos($_SERVER['REQUEST_URI'], '/tk/') === false) {
				$html.= '<script type="text/javascript">';
				$html.= 'pageLoc = self.location;';
				$html.= 'pageAdd = top.location;';
		 
				$html.= 'if (pageLoc == pageAdd) {';
				$html.= '    contentSrc = escape(pageLoc);';
				$html.= '    contPage = \''.$config->get('sysURL').'?\' + contentSrc;';
				$html.= '    top.location.href = contPage;';
				$html.= '}';
				$html.= '</script>';
			}
		}
		
		if (isset($_SESSION['currentUser']) && AlphaDAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$html.= '<script type="text/javascript">';
			$html.= '	(function($) {';
			$html.= '		$(document).ready(function(){';
			$html.= '		$("#adminmenu").ptMenu();';
			$html.= '	});';
			$html.= '})(jQuery);';
			$html.= '</script>';
		}
		
		// general dialog defaults
		$html.= '<script type="text/javascript">';
		$html.= '	(function($) {';
		$html.= '		var dialogCoords = [(screen.width/2)-200, (screen.height/2)-200];';
		$html.= '		$(document).ready(function(){';
		$html.= '			$.extend($.ui.dialog.prototype.options, {';
		$html.= '				modal: true,';
		$html.= '	        	resizable: false,';
		$html.= '	        	draggable: false,';
		$html.= '	        	autoOpen: false,';
		$html.= '	        	height: 200,';
		$html.= '	        	width: 400,';
		$html.= '	        	position: dialogCoords,';
		$html.= '	        	buttons: {';
		$html.= '	        		"Cancel": function() {';
		$html.= '						$(this).dialog("close");';
		$html.= '					}';
		$html.= '				}';
		$html.= '			});';
		$html.= '		});';
		$html.= '	})(jQuery);';
		$html.= '</script>';
		
		// record selector JS
		$html.= '<script type="text/javascript">';
		$html.= '	(function($) {';
		$html.= '		$(document).ready(function(){';
		$html.= '			var dialogOpts = {';
		$html.= '			    title: "Record selector",';
		$html.= '			    modal: true,';
		$html.= '			    resizable: false,';
		$html.= '			    draggable: false,';
		$html.= '			    autoOpen: false,';
		$html.= '			    height: 300,';
		$html.= '			    width: 600';
		$html.= '			};';
		$html.= '			$("#recordSelector").dialog(dialogOpts);';
		$html.= '		});';
		$html.= '	})(jQuery);';
		$html.= '</script>';
		
		// checkbox JS
		$html.= '<script type="text/javascript">';
		//$html .= '$(function() {';
		$html.= '	(function($) {';
		$html.= '		$(document).ready(function(){';
		$html .= '			$(":checkbox").checkbox();';
		//$html .= '});';
		$html.= '		});';
		$html.= '	})(jQuery);';
		$html.= '</script>';
		
		
		if(method_exists($controller, 'during_displayPageHead_callback'))
			$html.= $controller->during_displayPageHead_callback();

		$html.= '</head>';
		try {
			if($controller->getBO() != null)
				$html.= '<body'.($controller->getBO()->get('bodyOnload') != '' ? ' onload="'.$controller->getBO()->get('bodyOnload').'"' : '').'>';
			else
				$html.= '<body>';
		} catch (AlphaException $e) {
			$html.= '<body>';
		}
		
		if(method_exists($controller, 'insert_CMSDisplayStandardHeader_callback'))
			$html.= $controller->insert_CMSDisplayStandardHeader_callback();
		
		$html .= '<div id="recordSelector"></div>';
			
		//if($controller->getTitle() != '')
			//$html.= '<h1>'.$controller->getTitle().'</h1>';
		
		if ($renderMenu && isset($_SESSION['currentUser']) && AlphaDAO::isInstalled() && $_SESSION['currentUser']->inGroup('Admin') && strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$html .= '<ul id="adminmenu" class="potato-menu">
						<li><a href="'.$config->get('sysURL').'">Home</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ListBusinessObjects').'">Admin Home</a></li>
						<li><a href="#">Logs &raquo;</a>			
							<ul>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysLogFile')).'">System Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/search.log').'">Search Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/feeds.log').'">Feed Log</a></li>
								<li><a href="'.FrontController::generateSecureURL('act=ViewLog&logPath='.$config->get('sysRoot').'logs/tasks.log').'">Cron Tasks Log</a></li>
							</ul>
						</li>
						<li><a href="'.FrontController::generateSecureURL('act=GenSecureQueryStrings').'">Generate Secure URL</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ViewMetrics').'">Software Metrics</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=CacheManager').'">Manage Cache</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=TagManager').'">Manage Tags</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ListDEnums').'">Manage DEnums</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ListSequences').'">Manage Sequences</a></li>
						<li><a href="'.FrontController::generateSecureURL('act=ViewTestResults').'">Unit Tests</a></li>
					</ul>';
		}
		
		if(method_exists($controller, 'after_displayPageHead_callback'))
			$html.= $controller->after_displayPageHead_callback();

		if($renderStatus) {
			// render a status message if there is any
			$message = $controller->getStatusMessage();
			if(!empty($message))
				$html .= $message;
		}
			
		self::$logger->debug('<<displayPageHead [HTML]');
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageFoot()
	 */
	public static function displayPageFoot($controller) {
		if(self::$logger == null)		
			self::$logger = new Logger('AlphaRendererProviderHTML');
		
		self::$logger->debug('>>displayPageFoot(controller=['.get_class($controller).'])');

		$html = '';
		
		if(method_exists($controller, 'before_displayPageFoot_callback'))
			$html .= $controller->before_displayPageFoot_callback();
		
		$html .= '<div id="dialogDiv"></div>';
		$html .= '</body>';
		$html .= '</html>';
		
		if(method_exists($controller, 'after_displayPageFoot_callback'))
			$controller->after_displayPageFoot_callback();
			
		self::$logger->debug('<<displayPageFoot ['.$html.']');
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayUpdateMessage()
	 */
	public static function displayUpdateMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');
		self::$logger->debug('>>displayUpdateMessage(message=['.$message.'])');
		
		$html = '<div class="ui-state-highlight ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span> 
				'.$message.'</p></div>';
		
		self::$logger->debug('<<displayUpdateMessage ['.$html.']');
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayErrorMessage()
	 */
	public static function displayErrorMessage($message) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');
		self::$logger->debug('>>displayErrorMessage(message=['.$message.'])');
		
		$html = '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				'.$message.'</p></div>';
		
		self::$logger->debug('<<displayErrorMessage ['.$html.']');
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderErrorPage()
	 */
	public static function renderErrorPage($code, $message) {
		global $config;		
		
		$html = '<html><head>';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/jquery.ui.all.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/css/alpha.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		$html .= '<title>'.$code.' - '.$message.'</title></head>';
		$html .= '<body>';
		$html .= '<div class="ui-state-error ui-corner-all" style="padding: 0pt 0.7em;"> 
				<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> 
				<strong>'.$code.':</strong> '.$message.'</p>
				</div>';
		$html .= '</body></html>';
		
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDeleteForm()
	 */
	public static function renderDeleteForm() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');
		self::$logger->debug('>>renderDeleteForm()');
		
		$html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="deleteForm">';		
		$html .= '<input type="hidden" name="deleteOID" id="deleteOID" value=""/>';
		$html .= self::renderSecurityFields();
		$html .= '</form>';
		
		self::$logger->debug('<<renderDeleteForm ['.$html.']');
		return $html;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderSecurityFields()
	 */
	public static function renderSecurityFields() {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');

		self::$logger->debug('>>renderSecurityFields()');
		
		$html = '';
		
		$fields = AlphaController::generateSecurityFields();
		
		$html .= '<input type="hidden" name="var1" value="'.$fields[0].'"/>';
		$html .= '<input type="hidden" name="var2" value="'.$fields[1].'"/>';
		
		self::$logger->debug('<<renderSecurityFields ['.$html.']');
		return $html;
	}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderIntegerField()
	 */
	public function renderIntegerField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDoubleField()
	 */
	public function renderDoubleField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderBooleanField()
	 */
	public function renderBooleanField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderEnumField()
	 */
	public function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDEnumField()
	 */
	public function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDefaultField()
	 */
	public function renderDefaultField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderTextField()
	 */
	public function renderTextField($name, $label, $mode, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderRelationField()
	 */
	public function renderRelationField($name, $label, $mode, $value='', $tableTags=true, $expanded=false, $buttons=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}

	/**
	 * Loads a .phtml template for the BO specified if one exists.  Lower level custom templates
	 * take precedence.
	 * 
	 * @param AlphaDAO $BO
	 * @param string $mode
	 * @param array $fields
	 * @return string The rendered HTML.
	 * @since 1.2
	 * @throws IllegalArguementException
	 */
	private function loadTemplate($BO, $mode, $fields) {
		self::$logger->debug('>>loadTemplate(BO=['.var_export($BO, true).'], mode=['.$mode.'], fields=['.var_export($fields, true).'])');
		
		global $config;
		
		// for each BO property, create a local variable holding its value		
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if($propName != 'logger' && !$propObj->isPrivate()) {
				$prop = $BO->getPropObject($propName);
				if($prop instanceof DEnum) {					
					${$propName} = $BO->getPropObject($propName)->getDisplayValue();
				}else{
					${$propName} = $BO->get($propName);
				}
			}
		}
		
		// loop over the $fields array and create a local variable for each key value
		foreach (array_keys($fields) as $fieldName)
			${$fieldName} = $fields[$fieldName];
			
		$filename = $mode.'.phtml';
		$classTemplateDir = get_class($BO);

		$customPath = $config->get('sysRoot').'view/templates/html/'.$classTemplateDir.'/'.$filename;
		$defaultPath1 = $config->get('sysRoot').'alpha/view/renderers/templates/html/'.$classTemplateDir.'/'.$filename;
		$defaultPath2 = $config->get('sysRoot').'alpha/view/renderers/templates/html/'.$filename;

		$html = '';

		// Check to see if a custom template exists for this BO, and if it does load that		
		if (file_exists($customPath)) {
			self::$logger->debug('Loading template ['.$customPath.']');
			ob_start();
			require $customPath;
			$html = ob_get_clean();
		}elseif (file_exists($defaultPath1)) {
			self::$logger->debug('Loading template ['.$defaultPath1.']');
			ob_start();
			require $defaultPath1;
			$html = ob_get_clean();
		}elseif (file_exists($defaultPath2)) {
			self::$logger->debug('Loading template ['.$defaultPath2.']');
			ob_start();
			require $defaultPath2;
			$html = ob_get_clean();
		}else{
			throw new IllegalArguementException('No ['.$mode.'] HTML template found for class ['.get_class($BO).']');
		}
		
		self::$logger->debug('<<loadTemplate [HTML]');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderAllFields()
	 */
	public function renderAllFields($mode, $filterFields=array(), $readOnlyFields=array()) {
		self::$logger->debug('>>renderAllFields(mode=['.$mode.'], filterFields=['.var_export($filterFields, true).'], readOnlyFields=['.var_export($readOnlyFields, true).'])');
		
		$html = '';
				
		// get the class attributes		
		$properties = array_keys($this->BO->getDataLabels());
		
		$orignalMode = $mode;
		
		foreach($properties as $propName) {			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $filterFields)) {
				// render readonly fields in the supplied array
				if(in_array($propName, $readOnlyFields))
					$mode = 'view';
				else
					$mode = $orignalMode;
					
				if(!is_object($this->BO->getPropObject($propName)))
					continue;
					
				$propClass = get_class($this->BO->getPropObject($propName));
				
				// exclude non-Relation transient attributes from create and edit screens
				if($propClass != 'Relation' && ($mode == 'edit' || $mode == 'create') && in_array($propName, $this->BO->getTransientAttributes())) {
					continue;
				}
				
				switch (strtoupper($propClass)) {
					case 'INTEGER' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderIntegerField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}				
					break;
					case 'DOUBLE' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderDoubleField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}
					break;
					case 'DATE' :
						if($mode == 'view') {
							$value = $this->BO->get($propName);
							if ($value == '0000-00-00')
								$value = '';
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
						}else{
							$date = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $date->render();
						}
					break;
					case 'TIMESTAMP' :
						if($mode == 'view') {
							$value = $this->BO->get($propName);
							if ($value == '0000-00-00 00:00:00')
								$value = '';
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $value);
						}else{
							$timestamp = new DateBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $timestamp->render();
						}
					break;
					case 'STRING' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$string = new StringBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $string->render();
						}
					break;
					case 'TEXT' :
						$html .= $this->renderTextField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
					case 'BOOLEAN' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderBooleanField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
						}
					break;
					case 'ENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$enum = $this->BO->getPropObject($propName);
							$html .= $this->renderEnumField($propName, $this->BO->getDataLabel($propName), $mode, $enum->getOptions(), $this->BO->get($propName));
						}
					break;
					case 'DENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->getPropObject($propName)->getDisplayValue());
						}else{
							$denum = $this->BO->getPropObject($propName);
							$html .= $this->renderDEnumField($propName, $this->BO->getDataLabel($propName), $mode, $denum->getOptions(), $this->BO->get($propName));
						}
					break;
					case 'RELATION' :
						$html .= $this->renderRelationField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
					default :
						$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
					break;
				}
			}
		}
		
		self::$logger->debug('<<renderAllFields ['.$html.']');
		return $html;
	}
} 

?>
