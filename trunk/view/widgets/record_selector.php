<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
require_once $config->get('sysRoot').'alpha/model/DAO.inc';
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
		
		$this->relation_object = $object;		
		$this->label = $label;
		$this->name = $name;
		$this->accessingClassName = $accessingClassName;
	}
	
	function render($table_tags=true) {
		global $config;
		
		$html = '';
		
		// render text-box for many-to-one relations
		if($this->relation_object->getRelationType() == 'MANY-TO-ONE') {
			// value to appear in the text-box
			$inputBoxValue = $this->relation_object->getRelatedClassDisplayFieldValue();		
			
			if($table_tags) {
				$html .= '<tr><td style="width:25%;">';
				$html .= $this->label;
				$html .= '</td>';
				
				$html .= '<td>';			
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."').value+'&field=".$this->name."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value=".$this->relation_object->getValue()."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
			}
			
			// hidden field to store the actual value of the relation
			$html .= '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->relation_object->getValue().'"/>';
		}
		
		// render read-only list for one-to-many relations
		if($this->relation_object->getRelationType() == 'ONE-TO-MANY') {
			$objects = $this->relation_object->getRelatedObjects();			
			
			if(count($objects) > 0 && $table_tags) {
				$html .= '<tr><td colspan="2">';
				$html .= '<table cols="1" width="100%">';
				$html .= '<tr><td style="text-align:center;">';
				$html .= $this->label;
				$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = 'block';", "Display related objects", $this->name."DisBut", $config->get('sysURL')."/alpha/images/icons/arrow_down.png");
				$html .= $tmp->render();
				$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = 'none';", "Hide related objects", $this->name."HidBut", $config->get('sysURL')."/alpha/images/icons/arrow_up.png");
				$html .= $tmp->render();
				$html .= '</td></tr>';
				
				$html .= '<tr><td colspan="2">';
				$html .= '<div id="relation_field_'.$this->name.'" style="display:none;">';
				foreach($objects as $obj) {
					$html .= '<div class="bordered" style="margin:5px;">';
					$html .= '<p>'.$obj->getDataLabel('OID').': <em>'.$obj->getID().'</em></p>';
					$html .= '<p>'.$obj->getDataLabel($this->relation_object->getRelatedClassDisplayField()).': <em>'.$obj->get($this->relation_object->getRelatedClassDisplayField()).'</em></p>';
					$html .= '</div>';
				}
				$html .= '</div>';
				$html .= '</td></tr>';
				$html .= '</table>';
				$html .= '</td></tr>';				
			}
		}
		
		// render text-box for many-to-many relations
		if($this->relation_object->getRelationType() == 'MANY-TO-MANY') {
			// value to appear in the text-box
			$inputBoxValue = $this->relation_object->getRelatedClassDisplayFieldValue($this->accessingClassName);		
			
			if($table_tags) {
				$html .= '<tr><td style="width:25%;">';
				$html .= $this->label;
				$html .= '</td>';
				
				$html .= '<td>';			
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."_OID').value+'&field=".$this->name."&relatedClassLeft=".$this->relation_object->getRelatedClass('left')."&relatedClassLeftDisplayField=".$this->relation_object->getRelatedClassDisplayField('left')."&relatedClassRight=".$this->relation_object->getRelatedClass('right')."&relatedClassRightDisplayField=".$this->relation_object->getRelatedClassDisplayField('right')."&accessingClassName=".$this->accessingClassName."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				$html .= $tmp->render();
				$html .= '</td></tr>';
			}else{
				$html .= '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."_OID').value+'&field=".$this->name."&relatedClassLeft=".$this->relation_object->getRelatedClass('left')."&relatedClassLeftDisplayField=".$this->relation_object->getRelatedClassDisplayField('left')."&relatedClassRight=".$this->relation_object->getRelatedClass('right')."&relatedClassRightDisplayField=".$this->relation_object->getRelatedClassDisplayField('right')."&accessingClassName=".$this->accessingClassName."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
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
				$lookupOIDs = $this->relation_object->getLookup()->loadAllFieldValuesByAttribute('leftID', $this->relation_object->getValue(), 'rightID');
			}else{
				DAO::loadClassDef($classNameLeft);
				$tmpObject = new $classNameLeft;
				$fieldName = $this->relation_object->getRelatedClassDisplayField('left');
				$fieldLabel = $tmpObject->getDataLabel($fieldName);
				$oidLabel = $tmpObject->getDataLabel('OID');
				
				$objects = $tmpObject->loadAll();
				$lookupOIDs = $this->relation_object->getLookup()->loadAllFieldValuesByAttribute('rightID', $this->relation_object->getValue(), 'leftID');
			}
			
			echo '<table cols="3" width="100%" class="bordered">';
			echo '<tr>';		
			echo '<th>'.$oidLabel.'</th>';
			echo '<th>'.$fieldLabel.'</th>';
			echo '<th>Connect?</th>';		
			echo '</tr>';
			
			foreach($objects as $obj){
				echo '<tr>';
				echo '<td width="20%">';
				echo $obj->getID();
				echo '</td>';
				echo '<td width="60%">';
				echo $obj->get($fieldName);
				echo '</td>';			
				echo '<td width="20%">';
				
				if(in_array($obj->getID(), $lookupOIDs)) {					
					echo '<input name = "$lookupOIDs" type="checkbox" checked/>';
				}else{
					echo '<input name = "$lookupOIDs" type="checkbox"/>';
				}
				echo '</td>';
				echo '</tr>';
			}
		}else{			
			$className = $this->relation_object->getRelatedClass();
			
			DAO::loadClassDef($className);
			
			$tmpObject = new $className;		
			$label = $tmpObject->getDataLabel($this->relation_object->getRelatedClassDisplayField());
			$oidLabel = $tmpObject->getDataLabel('OID');
			
			$objects = $tmpObject->loadAll();
			
			echo '<table cols="3" width="100%" class="bordered">';
			echo '<tr>';		
			echo '<th>'.$oidLabel.'</th>';
			echo '<th>'.$label.'</th>';
			echo '<th>Connect?</th>';		
			echo '</tr>';
			
			foreach($objects as $obj){
				echo '<tr>';
				echo '<td width="20%">';
				echo $obj->getID();
				echo '</td>';
				echo '<td width="60%">';
				echo $obj->get($this->relation_object->getRelatedClassDisplayField());
				echo '</td>';			
				echo '<td width="20%">';
				if($obj->getID() == $this->relation_object->getValue()) {
					echo '<img src="'.$config->get('sysURL').'/alpha/images/icons/accept_ghost.png"/>';
				}else{
					$tmp = new button("window.opener.document.getElementById('".$_GET['field']."').value = '".$obj->getID()."'; window.opener.document.getElementById('".$_GET['field']."_display').value = '".$obj->get($this->relation_object->getRelatedClassDisplayField())."'; window.close();", "", "selBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
					echo $tmp->render();
				}
				echo '</td>';
				echo '</tr>';
			}
		}
		
		$this->display_page_foot();
	}
	
	function display_page_head() {
		global $config;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>Record Selector</title>';		
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$config->get('sysTheme').'.css.php">';
		
		echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		
		echo '</head>';
		echo '<body>';		
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