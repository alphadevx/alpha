<?php

// include the config file
if(!isset($config)) {
	require_once '../../util/AlphaConfig.inc';
	$config = AlphaConfig::getInstance();
	
	require_once $config->get('sysRoot').'alpha/util/AlphaAutoLoader.inc';
}

/**
 * Record selection HTML widget.
 * 
 * @package alpha::view::widgets
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
class RecordSelector {
	/**
	 * The relation object that we are going to render a view for.
	 * 
	 * @var Relation
	 * @since 1.0
	 */
	private $relationObject = null;

	/**
	 * The label text to use where required.
	 *  
	 * @var string
	 * @since 1.0
	 */
	private $label;
	
	/**
	 * Used to indicate the reading side when accessing from MANY-TO-MANY relation 
	 * (leave blank for other relation types).
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $accessingClassName;
	
	/**
	 * Javascript to run when the widget opens in a new window.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $onloadJS = '';
	
	/**
	 * The name of the HTML input box for storing the hidden and display values.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $name;
	
	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * The constructor.
	 * 
	 * @param Relation $relation
	 * @param string $label
	 * @param string $name
	 * @param string $accessingClassName
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function __construct($relation, $label='', $name='', $accessingClassName='') {
		self::$logger = new Logger('RecordSelector');
		self::$logger->debug('>>__construct(relation=['.$relation.'], label=['.$label.'], name=['.$name.'], accessingClassName=['.$accessingClassName.'])');
		
		if(!$relation instanceof Relation)
			throw new IllegalArguementException('Invalid Relation object provided to the RecordSelector constructor!');
		
		$this->relationObject = $relation;		
		$this->label = $label;
		$this->name = $name;
		$this->accessingClassName = $accessingClassName;
		
		self::$logger->debug('<<__construct');
	}
	
	/**
	 * Renders the text boxes and buttons for the widget, that will appear in user forms.
	 * 
	 * @param bool $tableTags Include table tags and label (optional)
	 * @param bool $expanded Render the related fields in expanded format or not (optional)
	 * @param bool $buttons Render buttons for expanding/contacting the related fields (optional)
	 * @return string
	 * @since 1.0
	 */
	public function render($tableTags=true, $expanded=false, $buttons=true) {
		self::$logger->debug('>>render(tableTags=['.$tableTags.'], expanded=['.$expanded.'], buttons=['.$buttons.'])');
		
		global $config;
		
		$html = '';
		
		// render text-box for many-to-one relations
		if($this->relationObject->getRelationType() == 'MANY-TO-ONE') {
			// value to appear in the text-box
			$inputBoxValue = $this->relationObject->getRelatedClassDisplayFieldValue();		
				
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $this->label;
				$html .= '</th>';
					
				$html .= '<td>';			
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$js = "$('#recordSelector').dialog('open');
				 $('#recordSelector').load('".$config->get('sysURL')."ViewRecordSelector/value/'+document.getElementById('".$this->name."').value+'/field/".$this->name."/relatedClass/".$this->relationObject->getRelatedClass()."/relatedClassField/".$this->relationObject->getRelatedClassField()."/relatedClassDisplayField/".$this->relationObject->getRelatedClassDisplayField()."/relationType/".$this->relationObject->getRelationType()."');";
				$tmp = new Button($js, "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$js = "$('#recordSelector').dialog('open');
				 $('#recordSelector').load('".$config->get('sysURL')."ViewRecordSelector/value/'+document.getElementById('".$this->name."').value+'/field/".$this->name."/relatedClass/".$this->relationObject->getRelatedClass()."/relatedClassField/".$this->relationObject->getRelatedClassField()."/relatedClassDisplayField/".$this->relationObject->getRelatedClassDisplayField()."/relationType/".$this->relationObject->getRelationType()."');";
				$tmp = new Button($js, "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
			}
				
			// hidden field to store the actual value of the relation
			$html .= '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->relationObject->getValue().'"/>';
			
			if($this->relationObject->getRule() != '') {
				$html .= '<input type="hidden" id="'.$this->name.'_msg" value="'.$this->relationObject->getHelper().'"/>';
				$html .= '<input type="hidden" id="'.$this->name.'_rule" value="'.$this->relationObject->getRule().'"/>';
			}
		}
		
		// render read-only list for one-to-many relations
		if($this->relationObject->getRelationType() == 'ONE-TO-MANY') {
			$objects = $this->relationObject->getRelatedObjects();			
			
			if(count($objects) > 0 && $tableTags) {
				// render tags differently			
				if($this->name == 'tags' && $this->relationObject->getRelatedClass() == 'TagObject') {
					$html .= '<tr><th>'.$this->label.'</th><td>';
						
					foreach($objects as $tag) {
						$html .= ' <a href="'.$config->get('sysURL').'search/q/'.$tag->get('content').'">'.$tag->get('content').'</a>';
					}					
					
					$html .= '</td></tr>';
				}else{
					$html .= '<tr><th style="text-align:center;" colspan="2">';
					$html .= $this->label;
					if($buttons) {
						$tmp = new Button("document.getElementById('relation_field_".$this->name."').style.display = '';", "Display related objects", $this->name."DisBut", $config->get('sysURL')."/alpha/images/icons/arrow_down.png");
						$html .= $tmp->render();
						$tmp = new Button("document.getElementById('relation_field_".$this->name."').style.display = 'none';", "Hide related objects", $this->name."HidBut", $config->get('sysURL')."/alpha/images/icons/arrow_up.png");
						$html .= $tmp->render();
					}
					$html .= '</th></tr>';
					
					$html .= '<tr><td colspan="2">';				
					$html .= '<table id="relation_field_'.$this->name.'" style="width:100%; display:'.($expanded ? '' : 'none').';" class="relationTable">';
					
					$customViewControllerName = AlphaController::getCustomControllerName(get_class($objects[0]), 'view');
					$customEditControllerName = AlphaController::getCustomControllerName(get_class($objects[0]), 'edit');
					
					foreach($objects as $obj) {
						$html .= '<tr><td>';					
						// check to see if we are in the admin back-end
						if(strpos($_SERVER['REQUEST_URI'], '/tk/') !== false) {					
							$viewURL = FrontController::generateSecureURL('act=Detail&bo='.get_class($obj).'&oid='.$obj->getOID());
							$editURL = FrontController::generateSecureURL('act=Edit&bo='.get_class($obj).'&oid='.$obj->getOID());
						}else{						
							if(isset($customViewControllerName)) {
								if($config->get('sysUseModRewrite'))
									$viewURL = $config->get('sysURL').$customViewControllerName.'/oid/'.$obj->getOID();
								else
									$viewURL = $config->get('sysURL').'controller/'.$customViewControllerName.'.php?oid='.$obj->getOID();
							}else{
								$viewURL = $config->get('sysURL').'alpha/controller/Detail.php?bo='.get_class($obj).'&oid='.$obj->getOID();
							}
							if(isset($customEditControllerName)) {
								if($config->get('sysUseModRewrite'))
									$editURL = $config->get('sysURL').$customEditControllerName.'/oid/'.$obj->getOID();
								else
									$editURL = $config->get('sysURL').'controller/'.$customEditControllerName.'.php?oid='.$obj->getOID();
							}else{
								$editURL = $config->get('sysURL').'alpha/controller/Edit.php?bo='.get_class($obj).'&oid='.$obj->getOID();
							}
						}						
											
						/*
						 * If any display headers were set with setRelatedClassHeaderFields, use them otherwise
						 * use the OID of the related class as the only header.
						 */ 
						$headerFields = $this->relationObject->getRelatedClassHeaderFields();
						if(count($headerFields) > 0) {
							foreach($headerFields as $field) {
								$label = $obj->getDataLabel($field);
								$value = $obj->get($field);
								
								if($field == 'created_by' || $field == 'updated_by') {
									$person = new PersonObject();
									$person->load($value);
									$value = $person->getDisplayName();
								}
								
								$html .= '<em>'.$label.': </em>'.$value.'&nbsp;&nbsp;&nbsp;&nbsp;';
							}
							// if the related BO has been updated, render the update time
							if($obj->getCreateTS() != $obj->getUpdateTS()) {
								try {
									$html .= '<em>'.$obj->getDataLabel('updated_ts').': </em>'.$obj->get('updated_ts');
								}catch(IllegalArguementException $e) {
									$html .= '<em>Updated: </em>'.$obj->get('updated_ts');
								}
							}
						}else{
							$html .= '<em>'.$obj->getDataLabel('OID').': </em>'.$obj->get('OID');
						}
						// ensures that line returns are rendered
						$value = str_replace("\n", '<br>', $obj->get($this->relationObject->getRelatedClassDisplayField()));
						$html .= '<p>'.$value.'</p>';
						
						$html .= '<div align="center">';
						$html .= '<a href="'.$viewURL.'">View</a>';
						// if the current user owns it, they get the edit link
						if(isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getOID() == $obj->getCreatorId())
							$html .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$editURL.'">Edit</a></div>';
						$html .= '</div>';					
					}				
					$html .= '</table>';				
					
					$html .= '</td></tr>';
				}
			}
		}
		
		// render text-box for many-to-many relations
		if($this->relationObject->getRelationType() == 'MANY-TO-MANY') {
			// value to appear in the text-box
			$inputBoxValue = $this->relationObject->getRelatedClassDisplayFieldValue($this->accessingClassName);		
			// replace commas with line returns
			$inputBoxValue = str_replace(",", "\n", $inputBoxValue);
			
			if($tableTags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $this->label;
				$html .= '</th>';
				
				$html .= '<td>';			
				$html .= '<textarea id="'.$this->name.'_display" style="width:100%;" rows="4" readonly>';
				$html .= $inputBoxValue;
				$html .= '</textarea>';
				$html .= '<div align="center">';
				$js = "$('#recordSelector').dialog('open');
				 $('#recordSelector').load('".$config->get('sysURL')."ViewRecordSelector/lookupOIDs/'+document.getElementById('".$this->name."').value+'/value/'+document.getElementById('".$this->name."_OID').value+'/field/".$this->name."/relatedClassLeft/".$this->relationObject->getRelatedClass('left')."/relatedClassLeftDisplayField/".$this->relationObject->getRelatedClassDisplayField('left')."/relatedClassRight/".$this->relationObject->getRelatedClass('right')."/relatedClassRightDisplayField/".$this->relationObject->getRelatedClassDisplayField('right')."/accessingClassName/".$this->accessingClassName."/relationType/".$this->relationObject->getRelationType()."');";
				$tmp = new Button($js, "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</div>';
				$html .= '</td></tr>';
			}else{
				$html .= '<textarea id="'.$this->name.'_display" style="width:95%;" rows="5" readonly>';
				$html .= $inputBoxValue;
				$html .= '</textarea>';
				$js = "$('#recordSelector').dialog('open');
				 $('#recordSelector').load('".$config->get('sysURL')."ViewRecordSelector/lookupOIDs/'+document.getElementById('".$this->name."').value+'/value/'+document.getElementById('".$this->name."_OID').value+'/field/".$this->name."/relatedClassLeft/".$this->relationObject->getRelatedClass('left')."/relatedClassLeftDisplayField/".$this->relationObject->getRelatedClassDisplayField('left')."/relatedClassRight/".$this->relationObject->getRelatedClass('right')."/relatedClassRightDisplayField/".$this->relationObject->getRelatedClassDisplayField('right')."/accessingClassName/".$this->accessingClassName."/relationType/".$this->relationObject->getRelationType()."');";
				$tmp = new Button($js, "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
			}
			
			// hidden field to store the OID of the current BO
			$html .= '<input type="hidden" name="'.$this->name.'_OID" id="'.$this->name.'_OID" value="'.$this->relationObject->getValue().'"/>';
			
			// hidden field to store the OIDs of the related BOs on the other side of the rel (this is what we check for when saving)
			if($this->relationObject->getSide($this->accessingClassName) == 'left')
				$lookupOIDs = $this->relationObject->getLookup()->loadAllFieldValuesByAttribute('leftID', $this->relationObject->getValue(), 'rightID', 'DESC');
			else
				$lookupOIDs = $this->relationObject->getLookup()->loadAllFieldValuesByAttribute('rightID', $this->relationObject->getValue(), 'leftID', 'DESC');
			$html .= '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.implode(',', $lookupOIDs).'"/>';
		}
		
		self::$logger->debug('<<__render [html]');
		return $html;
	}
	
	/**
	 * Returns the HTML for the record selector that will appear in a pop-up window.
	 * 
	 * @param array $lookupOIDs An optional array of related look-up OIDs, only required for rendering MANY-TO-MANY rels
	 * @since 1.0
	 * @return string
	 */
	public function renderSelector($lookupOIDs=array()) {
		self::$logger->debug('>>renderSelector(lookupOIDs=['.var_export($lookupOIDs, true).'])');
		
		global $config;
		
		$html = $this->displayPageHead();
		
		if($this->relationObject->getRelationType() == 'MANY-TO-MANY') {
			
			$classNameLeft = $this->relationObject->getRelatedClass('left');
			$classNameRight = $this->relationObject->getRelatedClass('right');
			
			if($this->accessingClassName == $classNameLeft) {
				AlphaDAO::loadClassDef($classNameRight);
				$tmpObject = new $classNameRight;
				$fieldName = $this->relationObject->getRelatedClassDisplayField('right');		
				$fieldLabel = $tmpObject->getDataLabel($fieldName);
				$oidLabel = $tmpObject->getDataLabel('OID');
				
				$objects = $tmpObject->loadAll(0, 0, 'OID', 'ASC', true);
				
				self::$logger->debug('['.count($objects).'] related ['.$classNameLeft.'] objects loaded');
			}else{
				AlphaDAO::loadClassDef($classNameLeft);
				$tmpObject = new $classNameLeft;
				$fieldName = $this->relationObject->getRelatedClassDisplayField('left');
				$fieldLabel = $tmpObject->getDataLabel($fieldName);
				$oidLabel = $tmpObject->getDataLabel('OID');
				
				$objects = $tmpObject->loadAll(0, 0, 'OID', 'ASC', true);
				
				self::$logger->debug('['.count($objects).'] related ['.$classNameLeft.'] objects loaded');
			}
			
			$html .= '<table cols="3" width="100%" class="bordered">';
			$html .= '<tr>';		
			$html .= '<th>'.$oidLabel.'</th>';
			$html .= '<th>'.$fieldLabel.'</th>';
			$html .= '<th>Connect?</th>';		
			$html .= '</tr>';
			
			foreach($objects as $obj){
				$html .= '<tr>';
				$html .= '<td width="20%">';
				$html .= $obj->getOID();
				$html .= '</td>';
				$html .= '<td width="60%">';
				$html .= $obj->get($fieldName);
				$html .= '</td>';			
				$html .= '<td width="20%">';
				
				if(in_array($obj->getOID(), $lookupOIDs)) {
					$this->onloadJS .= 'toggelOID(\''.$obj->getOID().'\',\''.$obj->get($fieldName).'\',true);';
					$html .= '<input name = "'.$obj->getOID().'" type="checkbox" checked onclick="toggelOID(\''.$obj->getOID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
				}else{
					$html .= '<input name = "'.$obj->getOID().'" type="checkbox" onclick="toggelOID(\''.$obj->getOID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
				}
				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
			
			$html .= '<div align="center" style="padding:10px;">';
			$tmp = new Button("$('#recordSelector').dialog('close');", "Cancel", "cancelBut", $config->get('sysURL')."/alpha/images/icons/cancel.png");
			$html .= $tmp->render();
			$html .= '&nbsp;&nbsp;&nbsp;';		
			$tmp = new Button("setParentFieldValues(); $('#".$_GET['field']."_display').blur(); $('#recordSelector').dialog('close');", "Accept", "acceptBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
			$html .= $tmp->render();
			$html .= '</div>';
		}else{			
			$className = $this->relationObject->getRelatedClass();
			
			AlphaDAO::loadClassDef($className);
			
			$tmpObject = new $className;		
			$label = $tmpObject->getDataLabel($this->relationObject->getRelatedClassDisplayField());
			$oidLabel = $tmpObject->getDataLabel('OID');
			
			$objects = $tmpObject->loadAll(0, 0, 'OID', 'DESC');
			
			$html = '<table cols="3" width="100%" class="bordered">';
			$html .= '<tr>';		
			$html .= '<th>'.$oidLabel.'</th>';
			$html .= '<th>'.$label.'</th>';
			$html .= '<th>Connect?</th>';		
			$html .= '</tr>';
			
			foreach($objects as $obj){
				$html .= '<tr>';
				$html .= '<td width="20%">';
				$html .= $obj->getOID();
				$html .= '</td>';
				$html .= '<td width="60%">';
				$html .= $obj->get($this->relationObject->getRelatedClassDisplayField());
				$html .= '</td>';			
				$html .= '<td width="20%">';
				if($obj->getOID() == $this->relationObject->getValue()) {
					$html .= '<img src="'.$config->get('sysURL').'/alpha/images/icons/accept_ghost.png"/>';
				}else{
					$tmp = new Button("document.getElementById('".$_GET['field']."').value = '".$obj->getOID()."'; document.getElementById('".$_GET['field']."_display').value = '".$obj->get($this->relationObject->getRelatedClassDisplayField())."'; $('#".$_GET['field']."_display').blur(); $('#recordSelector').dialog('close');", "", "selBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
					$html .= $tmp->render();
				}
				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}
		
		$html .= '<body>';
		$html .= '<script type="text/javascript">'.
				'$(document).ready(function() {';
			
		$html .= $this->onloadJS;
			
		$html .= '});</script>';
		
		$html .= $this->displayPageFoot();
		
		self::$logger->debug('<<renderSelector[html]');
		return $html;
	}
	
	/**
	 * Renders the header HTML and JS for the record selector pop-up page.
	 * 
	 * @since 1.0
	 * @return string
	 */
	private function displayPageHead() {
		self::$logger->debug('>>displayPageHead()');
		
		global $config;		
		
		$html = '<html>';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		$html .= '<title>Record Selector</title>';		
		
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/ui.all.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/css/alpha.css">';
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		
		$html .= '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		$html .= '<script language="JavaScript">
			var selectedOIDs = new Object();
			
			function toggelOID(oid, displayValue, isSelected) {			
				if(isSelected)
					selectedOIDs[oid] = displayValue;
				else
					delete selectedOIDs[oid];
			}
			
			function setParentFieldValues() {			
				var OIDs;
				var displayValues;
				
				for(key in selectedOIDs) {
					if(OIDs == null)
						OIDs = key;
					else
						OIDs = OIDs + \',\' + key;
						
					if(displayValues == null)
						displayValues = selectedOIDs[key];
					else
						displayValues = displayValues + \'\\n\' + selectedOIDs[key];
				}
				
				if(OIDs == null) {
					document.getElementById(\''.$_GET['field'].'\').value = "00000000000";
					document.getElementById(\''.$_GET['field'].'_display\').value = "";
				}else{
					document.getElementById(\''.$_GET['field'].'\').value = OIDs;
					document.getElementById(\''.$_GET['field'].'_display\').value = displayValues;
				}
			}
			
			</script>';
		
		$html .= '</head>';
		
		self::$logger->debug('<<displayPageHead [html]');
		return $html;
	}
	
	/**
	 * Renders the footer HTML and JS for the record selector pop-up page.
	 * 
	 * @since 1.0
	 * @return string
	 */
	private function displayPageFoot() {
		$html = '</body>';
		$html .= '</html>';
		
		return $html;
	}
}


?>