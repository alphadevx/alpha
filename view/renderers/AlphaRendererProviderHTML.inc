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
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::detailedView()
	 */
	public function detailedView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::adminView()
	 */
	public function adminView($fields=array()) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageHead()
	 */
	public static function displayPageHead($controller) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayPageFoot()
	 */
	public static function displayPageFoot($controller) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayUpdateMessage()
	 */
	public static function displayUpdateMessage($message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::displayErrorMessage()
	 */
	public static function displayErrorMessage($message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderErrorPage()
	 */
	public static function renderErrorPage($code, $message) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDeleteForm()
	 */
	public static function renderDeleteForm() {
		// TODO
		self::$logger->warn('Not implemented yet!');
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
	public static function renderDoubleField($name, $label, $mode, $value='', $tableTags=true) {
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
	public static function renderEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
		// TODO
		self::$logger->warn('Not implemented yet!');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see alpha/view/renderers/AlphaRendererProviderInterface::renderDEnumField()
	 */
	public static function renderDEnumField($name, $label, $mode, $options, $value='', $tableTags=true) {
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
