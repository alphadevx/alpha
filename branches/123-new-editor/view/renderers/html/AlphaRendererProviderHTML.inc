<?php

/**
 * HTML renderer.  Will invoke widgets from the alpha::view::widgets package
 * automatically for the correct data type.  Templates from ./templates/html
 * loaded by default, but these can be overridden on a per-DAO level in
 * the application when required (consider the default ones to be scaffolding).
 *
 * @package alpha::view::renderers
 * @since 1.2
 * @author John Collins <dev@alphaframework.org>
 * @version $Id: AlphaRendererProviderInterface.inc 1499 2012-02-16 19:29:16Z alphadev $
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2014, John Collins (founder of Alpha Framework).
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
		$fields['formSecurityFields'] = self::renderSecurityFields();

		self::$logger->debug('<<createView [HTML]');
		return AlphaView::loadTemplate($this->BO, 'create', $fields);
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

		$js = "if(window.jQuery) {
					BootstrapDialog.show({
						title: 'Confirmation',
			            message: 'Are you sure you wish to delete this item?',
			            buttons: [
			            	{
			            		icon: 'glyphicon glyphicon-remove',
				                label: 'Cancel',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself){
				                    dialogItself.close();
				                }
			            	},
			            	{
				                icon: 'glyphicon glyphicon-ok',
				                label: 'Okay',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself) {
				                	$('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
									$('#deleteForm').submit();
				                    dialogItself.close();
				                }
			            	}
			            ]
			        });
				}";
		$button = new Button($js, "Delete", "deleteBut");
		$fields['deleteButton'] = $button->render();

		$button = new Button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		$fields['cancelButton'] = $button->render();

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = self::renderSecurityFields();

		// OID will need to be posted for optimistic lock checking
		$fields['version_num'] = $this->BO->getVersionNumber();

		self::$logger->debug('<<editView [HTML]');
		return AlphaView::loadTemplate($this->BO, 'edit', $fields);
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
						$text = htmlentities($this->BO->get($propName), ENT_COMPAT, 'utf-8');
						if(mb_strlen($text) > 70)
							$html .= '	<td>&nbsp;'.mb_substr($text, 0, 70).'...</td>';
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
		if(mb_strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getOID())."';", 'View', 'view'.$this->BO->getOID().'But');
			$fields['viewButton'] = $button->render();
		}else{
			if($this->BO->hasAttribute('URL'))
				$button = new Button("document.location = '".$this->BO->get('URL')."';", 'View', 'view'.$this->BO->getOID().'But');
			else
				$button = new Button("document.location = '".$config->get('app.url')."Detail/bo/".get_class($this->BO)."/oid/".$this->BO->getOID()."';", 'View', 'view'.$this->BO->getOID().'But');

			$fields['viewButton'] = $button->render();
		}

		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			$html .= '&nbsp;&nbsp;';
			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getOID())."'", "Edit", "edit".$this->BO->getOID()."But");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';

			$js = "if(window.jQuery) {
					BootstrapDialog.show({
						title: 'Confirmation',
			            message: 'Are you sure you wish to delete this item?',
			            buttons: [
			            	{
			            		icon: 'glyphicon glyphicon-remove',
				                label: 'Cancel',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself){
				                    dialogItself.close();
				                }
			            	},
			            	{
				                icon: 'glyphicon glyphicon-ok',
				                label: 'Okay',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself) {
				                	$('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
									$('#deleteForm').submit();
				                    dialogItself.close();
				                }
			            	}
			            ]
			        });
				}";

			$button = new Button($js, "Delete", "delete".$this->BO->getOID()."But");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = self::renderSecurityFields();

		self::$logger->debug('<<listView [HTML]');
		return AlphaView::loadTemplate($this->BO, 'list', $fields);
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

			$button = new Button("document.location = '".FrontController::generateSecureURL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getOID())."'", "Edit", "editBut");
			$html .= $button->render();

			$js = "if(window.jQuery) {
					BootstrapDialog.show({
						title: 'Confirmation',
			            message: 'Are you sure you wish to delete this item?',
			            buttons: [
			            	{
			            		icon: 'glyphicon glyphicon-remove',
				                label: 'Cancel',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself){
				                    dialogItself.close();
				                }
			            	},
			            	{
				                icon: 'glyphicon glyphicon-ok',
				                label: 'Okay',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself) {
				                	$('[id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('deleteOID')) : 'deleteOID')."\"]').attr('value', '".$this->BO->getOID()."');
									$('#deleteForm').submit();
				                    dialogItself.close();
				                }
			            	}
			            ]
			        });
				}";

			$button = new Button($js, "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;

		self::$logger->debug('<<detailedView [HTML]');
		return AlphaView::loadTemplate($this->BO, 'detail', $fields);
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

		if($this->BO->getMaintainHistory())
			$fields['tableExists'] = ($this->BO->checkTableExists(true) ? '<span class="success">Yes</span>' : '<span class="warning">No history table</span>');

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
        	$fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('createTableBut')) : 'createTableBut');
        	$button = new Button('submit', 'Create Table', $fieldname);
            $html .= $button->render();
            // hidden field so that we know which class to create the table for
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('createTableClass')) : 'createTableClass');
            $html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
        }

		if ($html == '' && $this->BO->getMaintainHistory() && !$this->BO->checkTableExists(true)) {
			$fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('createHistoryTableBut')) : 'createHistoryTableBut');
        	$button = new Button('submit', 'Create History Table', $fieldname);
            $html .= $button->render();
            // hidden field so that we know which class to create the table for
            $fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('createTableClass')) : 'createTableClass');
            $html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
        }
        $fields['createTableButton'] = $html;

		// recreate and update table buttons (if required)
		$html = '';
		if ($this->BO->checkTableNeedsUpdate() && $this->BO->checkTableExists()) {

			$js = "if(window.jQuery) {
					BootstrapDialog.show({
						title: 'Confirmation',
			            message: 'Are you sure you wish to recreate this class table (all data will be lost)?',
			            buttons: [
			            	{
			            		icon: 'glyphicon glyphicon-remove',
				                label: 'Cancel',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself){
				                    dialogItself.close();
				                }
			            	},
			            	{
				                icon: 'glyphicon glyphicon-ok',
				                label: 'Okay',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself) {
				                	$('[Id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('admin_'.get_class($this->BO).'_button_pressed')) : 'admin_'.get_class($this->BO).'_button_pressed')."\"]').attr('value', 'recreateTableBut');
									$('#admin_".get_class($this->BO)."').submit();
				                    dialogItself.close();
				                }
			            	}
			            ]
			        });
				}";

			$button = new Button($js , "Recreate Table", "recreateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to recreate the table for
			$html .= '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
			$html .= '&nbsp;&nbsp;';

			$js = "if(window.jQuery) {
					BootstrapDialog.show({
						title: 'Confirmation',
			            message: 'Are you sure you wish to attempt to modify this class table by adding new attributes?',
			            buttons: [
			            	{
			            		icon: 'glyphicon glyphicon-remove',
				                label: 'Cancel',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself){
				                    dialogItself.close();
				                }
			            	},
			            	{
				                icon: 'glyphicon glyphicon-ok',
				                label: 'Okay',
				                cssClass: 'btn btn-default btn-xs',
				                action: function(dialogItself) {
				                	$('[Id=\"".($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('admin_'.get_class($this->BO).'_button_pressed')) : 'admin_'.get_class($this->BO).'_button_pressed')."\"]').attr('value', 'updateTableBut');
									$('#admin_".get_class($this->BO)."').submit();
				                    dialogItself.close();
				                }
			            	}
			            ]
			        });
				}";

			$button = new Button($js , "Update Table", "updateTableBut");
			$html .= $button->render();
			// hidden field so that we know which class to update the table for
			$fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('updateTableClass')) : 'updateTableClass');
			$html .= '<input type="hidden" name="'.$fieldname.'" value="'.get_class($this->BO).'"/>';
			// hidden field to tell us which button was pressed
			$fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('admin_'.get_class($this->BO).'_button_pressed')) : 'admin_'.get_class($this->BO).'_button_pressed');
			$html .= '<input type="hidden" id="'.$fieldname.'" name="'.$fieldname.'" value=""/>';
		}
		$fields['recreateOrUpdateButtons'] = $html;

		// buffer security fields to $formSecurityFields variable
		$fields['formSecurityFields'] = self::renderSecurityFields();

		self::$logger->debug('<<adminView [HTML]');
		return AlphaView::loadTemplate($this->BO, 'admin', $fields);
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageHead()
	 */
	public static function displayPageHead($controller) {
		if(self::$logger == null)
			self::$logger = new Logger('AlphaRendererProviderHTML');

		self::$logger->debug('>>displayPageHead(controller=['.var_export($controller, true).'])');

		global $config;

		if(!AlphaController::checkControllerDefExists(get_class($controller)))
			throw new IllegalArguementException('The controller provided ['.get_class($controller).'] is not defined anywhere!');

		$html = AlphaView::loadTemplateFragment('html', 'head.phtml', array('title' => $controller->getTitle(), 'description' => $controller->getDescription()));

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

		$html .= '<div class="container">';

		if(method_exists($controller, 'insert_CMSDisplayStandardHeader_callback'))
			$html.= $controller->insert_CMSDisplayStandardHeader_callback();


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


		$html = AlphaView::loadTemplateFragment('html', 'footer.phtml', array());

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

		$html = '<div class="alert alert-success">'.$message.'</div>';

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

		$html = '<div class="alert alert-danger">'.$message.'</div>';

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
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/lib/jquery/ui/themes/'.$config->get('app.css.theme').'/jquery.ui.all.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('app.url').'alpha/css/alpha.css">';
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

		global $config;

		$html = '<form action="'.$_SERVER['REQUEST_URI'].'" method="POST" id="deleteForm" accept-charset="UTF-8">';
		$fieldname = ($config->get('security.encrypt.http.fieldnames') ? base64_encode(AlphaSecurityUtils::encrypt('deleteOID')) : 'deleteOID');
		$html .= '<input type="hidden" name="'.$fieldname.'" id="'.$fieldname.'" value=""/>';
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

		global $config;

		$html = '';

		$fields = AlphaController::generateSecurityFields();

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt('var1'));
		else
			$fieldname = 'var1';

		$html .= '<input type="hidden" name="'.$fieldname.'" value="'.$fields[0].'"/>';

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt('var2'));
		else
			$fieldname = 'var2';

		$html .= '<input type="hidden" name="'.$fieldname.'" value="'.$fields[1].'"/>';

		self::$logger->debug('<<renderSecurityFields ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderIntegerField()
	 */
	public function renderIntegerField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderIntegerField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '';

		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';

				$html .= '<td>';
				$html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
			}
		}

		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';

				$html .= '<td>';
				$html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'.$value.'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" style="width:100%;" name="'.$fieldname.'" value="'.$value.'"/>';
			}
		}

		self::$logger->debug('<<renderIntegerField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDoubleField()
	 */
	public function renderDoubleField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderDoubleField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '';

		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';

				$html .= '<td>';
				$html .= '<input type="text" size="13" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="13" name="'.$fieldname.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/>';
			}
		}

		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';

				$html .= '<td>';
				$html .= '<input type="text" size="13" name="'.$fieldname.'" value="'.$value.'"/><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="13" name="'.$fieldname.'" value="'.$value.'"/>';
			}
		}

		self::$logger->debug('<<renderDoubleField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderBooleanField()
	 */
	public function renderBooleanField($name, $label, $mode, $value='') {
		self::$logger->debug('>>renderBooleanField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '<div class="checkbox">';
		$html .= '	<label>';

		if ($mode == 'create') {
			$html .= '		<input type="hidden" name="'.$fieldname.'" value="0">';
			$html .= '		<input type="checkbox" name="'.$fieldname.'" id="'.$fieldname.'">';
			$html .= '			'.$label;

		}

		if ($mode == 'edit') {
			$html .= '		<input type="hidden" name="'.$fieldname.'" value="0">';
			$html .= '		<input type="checkbox" name="'.$fieldname.'" id="'.$fieldname.'"'.($value == '1'? ' checked':'').' />';
			$html .= '			'.$label;
		}

		$html .= '	</label>';
		$html .= '</div>';

		self::$logger->debug('<<renderBooleanField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderEnumField()
	 */
	public function renderEnumField($name, $label, $mode, $options, $value='') {
		self::$logger->debug('>>renderEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '<div class="form-group">';
		$html .= '	<label for="'.$fieldname.'">'.$label.'</label>';

		if ($mode == 'create') {
			$html .= '	<select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
			foreach ($options as $val) {
				$html .= '		<option value="'.$val.'">'.$val.'</option>';
			}
			$html .= '	</select>';
		}

		if ($mode == 'edit') {
			$html .= '	<select name="'.$fieldname.'" id="'.$fieldname.'" class="form-control"/>';
			foreach ($options as $val) {
				if ($value == $val)
					$html .= '		<option value="'.$val.'" selected>'.$val.'</option>';
				else
					$html .= '		<option value="'.$val.'">'.$val.'</option>';
			}
			$html .= '	</select>';
		}

		$html .= '</div>';

		self::$logger->debug('<<renderEnumField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDEnumField()
	 */
	public function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		self::$logger->debug('>>renderDEnumField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '';

		if ($mode == 'create') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$fieldname.'"/>';
				foreach (array_keys($options) as $index) {
					$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$fieldname.'"/>';
				foreach (array_keys($options) as $index) {
					$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select>';
			}
		}

		if ($mode == 'edit') {
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $label;
				$html .= '</th>';
				$html .= '<td>';
				$html .= '<select name="'.$fieldname.'"/>';
				foreach (array_keys($options) as $index) {
					if ($value == $index)
						$html .= '<option value="'.$index.'" selected>'.$options[$index].'</option>';
					else
						$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select><br>';
				$html .= '</td></tr>';
			}else{
				$html .= '<select name="'.$fieldname.'"/>';
				foreach (array_keys($options) as $index) {
					if ($value == $index)
						$html .= '<option value="'.$index.'" selected>'.$options[$index].'</option>';
					else
						$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
				}
				$html .= '</select>';
			}
		}

		self::$logger->debug('<<renderDEnumField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDefaultField()
	 */
	public function renderDefaultField($name, $label, $mode, $value='') {
		self::$logger->debug('>>renderDefaultField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '';

		if ($mode == 'create') {
			$html .= '<textarea cols="100" rows="3" name="'.$fieldname.'">'. (isset ($_POST[$name]) ? $_POST[$name] : '').'</textarea>';
		}

		if ($mode == 'edit') {
			$html .= '<textarea cols="100" rows="3" name="'.$fieldname.'">'.$value.'</textarea>';
		}

		if ($mode == 'view') {
			$html .= '<p><strong>'.$label.':</strong> '.$value.'</p>';
		}

		self::$logger->debug('<<renderDefaultField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderTextField()
	 */
	public function renderTextField($name, $label, $mode, $value='', $tableTags=true) {
		self::$logger->debug('>>renderTextField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'])');

		global $config;

		if($config->get('security.encrypt.http.fieldnames'))
			$fieldname = base64_encode(AlphaSecurityUtils::encrypt($name));
		else
			$fieldname = $name;

		$html = '';

		if ($mode == 'create') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content')
				$text = new TextBox($this->BO->getPropObject($name), $label, $fieldname, 10);
			else
				$text = new TextBox($this->BO->getPropObject($name), $label, $fieldname);
			$html .= $text->render($tableTags);
		}

		if ($mode == 'edit') {
			// give 10 rows for content fields (other 5 by default)
			if($name == 'content') {
				$viewState = ViewState::getInstance();

				if($viewState->get('markdownTextBoxRows') == '')
					$text = new TextBox($this->BO->getPropObject($name), $label, $fieldname, 10);
				else
					$text = new TextBox($this->BO->getPropObject($name), $label, $fieldname, (integer)$viewState->get('markdownTextBoxRows'));

				$html .= $text->render($tableTags, true);
			}else{
				$text = new TextBox($this->BO->getPropObject($name), $label, $fieldname);
				$html .= $text->render($tableTags);
			}
		}

		if ($mode == 'view') {
			if($tableTags)
				$html .= '<tr><th>';

			$html .= $label;

			if($tableTags)
				$html .= '</th>';

			// filter ouput to prevent malicious injection
			$value = InputFilter::encode($value);

			// ensures that line returns are rendered
			$value = str_replace("\n", '<br>', $value);

			if($tableTags)
				$html .= '<td>&nbsp;';

			$html .= $value;

			if($tableTags)
				$html .= '</td></tr>';
		}

		self::$logger->debug('<<renderTextField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderStringField()
	 */
	public function renderStringField($name, $label, $mode, $value='') {
		self::$logger->debug('>>renderStringField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'])');

		global $config;

		$html = '';

		if ($mode == 'create' || $mode == 'edit') {
			$string = new StringBox($this->BO->getPropObject($name), $label, $name);
			$html .= $string->render();
		}

		if ($mode == 'view') {
			$html .= '<p><strong>'.$label.':</strong> '.$value.'</p>';
		}

		self::$logger->debug('<<renderStringField ['.$html.']');
		return $html;
	}

	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderRelationField()
	 */
	public function renderRelationField($name, $label, $mode, $value='', $tableTags=true, $expanded=false, $buttons=true) {
		self::$logger->debug('>>renderRelationField(name=['.$name.'], label=['.$label.'], mode=['.$mode.'], value=['.$value.'], tableTags=['.$tableTags.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');

		global $config;

		$html = '';

		$rel = $this->BO->getPropObject($name);

		if ($mode == 'create' || $mode == 'edit') {
			if($rel->getRelationType() == 'MANY-TO-MANY') {
				try{
					// check to see if the rel is on this class
					$rel->getSide(get_class($this->BO));
					$widget = new RecordSelector($rel, $label, $name, get_class($this->BO));
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}catch (IllegalArguementException $iae) {
					// the rel may be on a parent class
					$parentClassName = ucfirst($this->BO->getTableName()).'Object';
					$widget = new RecordSelector($rel, $label, $name, $parentClassName);
					$html .= $widget->render($tableTags, $expanded, $buttons);
				}
			}else{
				$rel = new RecordSelector($rel, $label, $name);
				$html .= $rel->render($tableTags, $expanded, $buttons);
			}
		}

		if ($mode == 'view') {
			if($rel->getRelationType() == 'MANY-TO-ONE') {
				$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue());
			}elseif($rel->getRelationType() == 'MANY-TO-MANY') {
				try{
					// check to see if the rel is on this class
					$rel->getSide(get_class($this->BO));
					$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue(get_class($this->BO)));
				}catch (IllegalArguementException $iae) {
					// the rel may be on a parent class
					$parentClassName = ucfirst($this->BO->getTableName()).'Object';
					$html .= $this->renderDefaultField($name, $label, 'view', $rel->getRelatedClassDisplayFieldValue($parentClassName));
				}
			}else{
				$rel = new RecordSelector($rel, $label, $name);
				$html .= $rel->render($tableTags, $expanded, $buttons);
			}
		}

		self::$logger->debug('<<renderRelationField ['.$html.']');
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

				switch (mb_strtoupper($propClass)) {
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
						$html .= $this->renderStringField($propName, $this->BO->getDataLabel($propName), $mode, $this->BO->get($propName));
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