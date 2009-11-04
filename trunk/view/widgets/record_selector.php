<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/model/DAO.inc';
require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/model/types/Relation.inc';


/**
* Record selction HTML widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
*  
*/

class record_selector
{
	var $relation_object = null;	
	var $label;
	var $accessingClassName;
	var $onloadJS;
	
	/**
	 * the name of the HTML input box
	 * @var string
	 */
	var $name;
	
	/**
	 * the constructor
	 * @param Relation $object the Relation that will be edited
	 * @param string $label the data label for the Relation object
	 * @param string $name the name of the HTML input box	 
	 * @param bool $table_tags determines if table tags are also rendered
	 * @param string $accessingClassName Used to indicate the reading side when accessing from MANY-TO-MANY relation (leave blank for other relation types)
	 */
	function record_selector($object, $label="", $name="", $table_tags=true, $accessingClassName='') {
		if(!isset($_SESSION))
			session_start();
		
		$this->relation_object = $object;		
		$this->label = $label;
		$this->name = $name;
		$this->accessingClassName = $accessingClassName;
	}
	
	/**
	 * 
	 * @param bool $table_tags Include table tags and label (optional)
	 * @param bool $expanded Render the related fields in expanded format or not (optional)
	 * @param bool $buttons Render buttons for expanding/contacting the related fields (optional)
	 * @return string
	 */
	function render($table_tags=true, $expanded=false, $buttons=true) {
		global $config;
		
		$html = '';
		
		// render text-box for many-to-one relations
		if($this->relation_object->getRelationType() == 'MANY-TO-ONE') {
			// value to appear in the text-box
			$inputBoxValue = $this->relation_object->getRelatedClassDisplayFieldValue();		
				
			if($table_tags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $this->label;
				$html .= '</th>';
					
				$html .= '<td>';			
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."').value+'&field=".$this->name."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."').value+'&field=".$this->name."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
			}
				
			// hidden field to store the actual value of the relation
			$html .= '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->relation_object->getValue().'"/>';
		}
		
		// render read-only list for one-to-many relations
		if($this->relation_object->getRelationType() == 'ONE-TO-MANY') {
			$objects = $this->relation_object->getRelatedObjects();			
			
			if(count($objects) > 0 && $table_tags) {
				// render tags differently			
				if($this->name == 'tags' && $this->relation_object->getRelatedClass() == 'tag_object') {
					$html .= '<tr><td colspan="2">'.$this->label.': ';
						
					foreach($objects as $tag) {
						$html .= ' <a href="'.$config->get('sysURL').'alpha/controller/Search.php?q='.$tag->get('content').'">'.$tag->get('content').'</a>';
					}					
					
					$html .= '</td></tr>';
				}else{
					$html .= '<tr><th style="text-align:center;" colspan="2">';
					$html .= $this->label;
					if($buttons) {
						$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = '';", "Display related objects", $this->name."DisBut", $config->get('sysURL')."/alpha/images/icons/arrow_down.png");
						$html .= $tmp->render();
						$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = 'none';", "Hide related objects", $this->name."HidBut", $config->get('sysURL')."/alpha/images/icons/arrow_up.png");
						$html .= $tmp->render();
					}
					$html .= '</th></tr>';
					
					$html .= '<tr><td colspan="2">';				
					$html .= '<table id="relation_field_'.$this->name.'" style="width:100%; display:'.($expanded ? '' : 'none').';" class="relationTable">';
					
					$customViewControllerName = Controller::getCustomControllerName(get_class($objects[0]), 'view');
					$customEditControllerName = Controller::getCustomControllerName(get_class($objects[0]), 'edit');
					
					foreach($objects as $obj) {
						$html .= '<tr><td>';					
						// check to see if we are in the admin back-end
						if(strpos($_SERVER['REQUEST_URI'], 'FC.php') !== false) {					
							$viewURL = FrontController::generateSecureURL('act=Detail&bo='.get_class($obj).'&oid='.$obj->getID());
							$editURL = FrontController::generateSecureURL('act=Edit&bo='.get_class($obj).'&oid='.$obj->getID());
						}else{						
							if(isset($customViewControllerName)) {
								if($config->get('sysUseModRewrite'))
									$viewURL = $config->get('sysURL').$customViewControllerName.'/oid/'.$obj->getID();
								else
									$viewURL = $config->get('sysURL').'controller/'.$customViewControllerName.'.php?oid='.$obj->getID();
							}else{
								$viewURL = $config->get('sysURL').'alpha/controller/Detail.php?bo='.get_class($obj).'&oid='.$obj->getID();
							}
							if(isset($customEditControllerName)) {
								if($config->get('sysUseModRewrite'))
									$editURL = $config->get('sysURL').$customEditControllerName.'/oid/'.$obj->getID();
								else
									$editURL = $config->get('sysURL').'controller/'.$customEditControllerName.'.php?oid='.$obj->getID();
							}else{
								$editURL = $config->get('sysURL').'alpha/controller/Edit.php?bo='.get_class($obj).'&oid='.$obj->getID();
							}
						}						
											
						/*
						 * If any display headers were set with setRelatedClassHeaderFields, use them otherwise
						 * use the OID of the related class as the only header.
						 */ 
						$headerFields = $this->relation_object->getRelatedClassHeaderFields();
						if(count($headerFields) > 0) {
							foreach($headerFields as $field) {
								$label = $obj->getDataLabel($field);
								$value = $obj->get($field);
								
								if($field == 'created_by' || $field == 'updated_by') {
									$person = new person_object();
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
						$value = str_replace("\n", '<br>', $obj->get($this->relation_object->getRelatedClassDisplayField()));
						$html .= '<p>'.$value.'</p>';
						
						$html .= '<div align="center">';
						$html .= '<a href="'.$viewURL.'">View</a>';
						// if the current user owns it, they get the edit link
						if(isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getID() == $obj->getCreatorId())
							$html .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$editURL.'">Edit</a></div>';
						$html .= '</div>';					
					}				
					$html .= '</table>';				
					
					$html .= '</td></tr>';
				}
			}
		}
		
		// render text-box for many-to-many relations
		if($this->relation_object->getRelationType() == 'MANY-TO-MANY') {
			// value to appear in the text-box
			$inputBoxValue = $this->relation_object->getRelatedClassDisplayFieldValue($this->accessingClassName);		
			// replace commas with line returns
			$inputBoxValue = str_replace(",", "\n", $inputBoxValue);
			
			if($table_tags) {
				$html .= '<tr><th style="width:25%;">';
				$html .= $this->label;
				$html .= '</th>';
				
				$html .= '<td>';			
				$html .= '<textarea id="'.$this->name.'_display" style="width:100%;" rows="4" readonly>';
				$html .= $inputBoxValue;
				$html .= '</textarea>';
				$html .= '<div align="center">';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?lookupOIDs='+document.getElementById('".$this->name."').value+'&value='+document.getElementById('".$this->name."_OID').value+'&field=".$this->name."&relatedClassLeft=".$this->relation_object->getRelatedClass('left')."&relatedClassLeftDisplayField=".$this->relation_object->getRelatedClassDisplayField('left')."&relatedClassRight=".$this->relation_object->getRelatedClass('right')."&relatedClassRightDisplayField=".$this->relation_object->getRelatedClassDisplayField('right')."&accessingClassName=".$this->accessingClassName."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</div>';
				$html .= '</td></tr>';
			}else{
				$html .= '<textarea id="'.$this->name.'_display" style="width:95%;" rows="5" readonly>';
				$html .= $inputBoxValue;
				$html .= '</textarea>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?lookupOIDs='+document.getElementById('".$this->name."').value+'&value='+document.getElementById('".$this->name."_OID').value+'&field=".$this->name."&relatedClassLeft=".$this->relation_object->getRelatedClass('left')."&relatedClassLeftDisplayField=".$this->relation_object->getRelatedClassDisplayField('left')."&relatedClassRight=".$this->relation_object->getRelatedClass('right')."&relatedClassRightDisplayField=".$this->relation_object->getRelatedClassDisplayField('right')."&accessingClassName=".$this->accessingClassName."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
			}
			
			// hidden field to store the OID of the current BO
			$html .= '<input type="hidden" name="'.$this->name.'_OID" id="'.$this->name.'_OID" value="'.$this->relation_object->getValue().'"/>';
			
			// hidden field to store the OIDs of the related BOs on the other side of the rel (this is what we check for when saving)
			if($this->relation_object->getSide($this->accessingClassName) == 'left')
				$lookupOIDs = $this->relation_object->getLookup()->loadAllFieldValuesByAttribute('leftID', $this->relation_object->getValue(), 'rightID');
			else
				$lookupOIDs = $this->relation_object->getLookup()->loadAllFieldValuesByAttribute('rightID', $this->relation_object->getValue(), 'leftID');
			$html .= '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.implode(',', $lookupOIDs).'"/>';
		}
		
		return $html;
	}
	
	/**
	 * renders the HTML for the record selector
	 */
	function render_selector() {
		global $config;
		
		$this->display_page_head();
		
		if($this->relation_object->getRelationType() == 'MANY-TO-MANY') {
			
			$classNameLeft = $this->relation_object->getRelatedClass('left');
			$classNameRight = $this->relation_object->getRelatedClass('right');
			
			if($this->accessingClassName == $classNameLeft) {
				DAO::loadClassDef($classNameRight);
				$tmpObject = new $classNameRight;
				$fieldName = $this->relation_object->getRelatedClassDisplayField('right');		
				$fieldLabel = $tmpObject->getDataLabel($fieldName);
				$oidLabel = $tmpObject->getDataLabel('OID');
				
				$objects = $tmpObject->loadAll();
			}else{
				DAO::loadClassDef($classNameLeft);
				$tmpObject = new $classNameLeft;
				$fieldName = $this->relation_object->getRelatedClassDisplayField('left');
				$fieldLabel = $tmpObject->getDataLabel($fieldName);
				$oidLabel = $tmpObject->getDataLabel('OID');
				
				$objects = $tmpObject->loadAll();
			}
			
			$lookupOIDs = explode(',',$_GET['lookupOIDs']);
			
			$html = '<table cols="3" width="100%" class="bordered">';
			$html .= '<tr>';		
			$html .= '<th>'.$oidLabel.'</th>';
			$html .= '<th>'.$fieldLabel.'</th>';
			$html .= '<th>Connect?</th>';		
			$html .= '</tr>';
			
			foreach($objects as $obj){
				$html .= '<tr>';
				$html .= '<td width="20%">';
				$html .= $obj->getID();
				$html .= '</td>';
				$html .= '<td width="60%">';
				$html .= $obj->get($fieldName);
				$html .= '</td>';			
				$html .= '<td width="20%">';
				
				if(in_array($obj->getID(), $lookupOIDs)) {
					$this->onloadJS .= 'toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',true);'; 				
					$html .= '<input name = "'.$obj->getID().'" type="checkbox" checked onclick="toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
				}else{
					$html .= '<input name = "'.$obj->getID().'" type="checkbox" onclick="toggelOID(\''.$obj->getID().'\',\''.$obj->get($fieldName).'\',this.checked);"/>';
				}
				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
			
			$html .= '<div align="center" style="padding:10px;">';
			$tmp = new button("window.close();", "Cancel", "cancelBut", $config->get('sysURL')."/alpha/images/icons/cancel.png");
			$html .= $tmp->render();
			$html .= '&nbsp;&nbsp;&nbsp;';		
			$tmp = new button("setParentFieldValues(); window.close();", "Accept", "acceptBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
			$html .= $tmp->render();
			$html .= '</div>';
		}else{			
			$className = $this->relation_object->getRelatedClass();
			
			DAO::loadClassDef($className);
			
			$tmpObject = new $className;		
			$label = $tmpObject->getDataLabel($this->relation_object->getRelatedClassDisplayField());
			$oidLabel = $tmpObject->getDataLabel('OID');
			
			$objects = $tmpObject->loadAll();
			
			$html = '<table cols="3" width="100%" class="bordered">';
			$html .= '<tr>';		
			$html .= '<th>'.$oidLabel.'</th>';
			$html .= '<th>'.$label.'</th>';
			$html .= '<th>Connect?</th>';		
			$html .= '</tr>';
			
			foreach($objects as $obj){
				$html .= '<tr>';
				$html .= '<td width="20%">';
				$html .= $obj->getID();
				$html .= '</td>';
				$html .= '<td width="60%">';
				$html .= $obj->get($this->relation_object->getRelatedClassDisplayField());
				$html .= '</td>';			
				$html .= '<td width="20%">';
				if($obj->getID() == $this->relation_object->getValue()) {
					$html .= '<img src="'.$config->get('sysURL').'/alpha/images/icons/accept_ghost.png"/>';
				}else{
					$tmp = new button("window.opener.document.getElementById('".$_GET['field']."').value = '".$obj->getID()."'; window.opener.document.getElementById('".$_GET['field']."_display').value = '".$obj->get($this->relation_object->getRelatedClassDisplayField())."'; window.close();", "", "selBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
					$html .= $tmp->render();
				}
				$html .= '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}
		
		echo '<body onload="'.$this->onloadJS.'">';
		
		echo $html;
		
		$this->display_page_foot();
	}
	
	function display_page_head() {
		global $config;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>Record Selector</title>';		
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/lib/jquery/ui/themes/'.$config->get('sysTheme').'/ui.all.css">';
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'alpha/alpha.css">';
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'config/css/overrides.css">';
		
		echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		
		echo '<script language="JavaScript">
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
				
				window.opener.document.getElementById(\''.$_GET['field'].'\').value = OIDs;
				window.opener.document.getElementById(\''.$_GET['field'].'_display\').value = displayValues;
			}
			
			</script>';
		
		echo '</head>';
	}
	
	function display_page_foot() {
		echo '</body>';
		echo '</html>';
	}
}

// checking to see if the record_selector has been accessed directly via a pop-up
if(basename($_SERVER["PHP_SELF"]) == "record_selector.php") {
	$relation_object = new Relation();

	if($_GET['relationType'] == 'MANY-TO-MANY') {
		$relation_object->setRelatedClass($_GET['relatedClassLeft'], 'left');
		$relation_object->setRelatedClassDisplayField($_GET['relatedClassLeftDisplayField'], 'left');
		$relation_object->setRelatedClass($_GET['relatedClassRight'], 'right');
		$relation_object->setRelatedClassDisplayField($_GET['relatedClassRightDisplayField'], 'right');
		$relation_object->setRelationType($_GET['relationType']);
		$relation_object->setValue($_GET['value']);
		
		$recSelector = new record_selector($relation_object,'',$_GET['field'],true,$_GET['accessingClassName']);
		$recSelector->render_selector();
	}else{
		$relation_object->setRelatedClass($_GET['relatedClass']);
		$relation_object->setRelatedClassField($_GET['relatedClassField']);
		$relation_object->setRelatedClassDisplayField($_GET['relatedClassDisplayField']);
		$relation_object->setRelationType($_GET['relationType']);
		$relation_object->setValue($_GET['value']);
		
		$recSelector = new record_selector($relation_object);
		$recSelector->render_selector();
	}
}

?>