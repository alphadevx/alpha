<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';
require_once $config->get('sysRoot').'alpha/util/db_connect.inc';
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
	
	/**
	 * the name of the HTML input box
	 * @var string
	 */
	var $name;
	
	/**
	 * the constructor
	 * @param object $object the date or timestamp object that will be edited by this calender
	 * @param string $label the data label for the date object
	 * @param string $name the name of the HTML input box	 
	 * @param bool $table_tags determines if table tags are also rendered for the calender
	 */
	function record_selector($object, $label="", $name="", $table_tags=true) {
		
		$this->relation_object = $object;		
		$this->label = $label;
		$this->name = $name;
				
		// if its in a form render the input tags and calender button, else render the month for the pop-up window
		if(!empty($label)) {
			$this->render($table_tags);
		}else{
			$this->display_page_head();
			$this->render_selector();
			$this->display_page_foot();
		}
	}
	
	function render($table_tags) {
		global $config;
		
		// render text-box for many-to-one relations
		if($this->relation_object->getRelationType() == 'MANY-TO-ONE') {
			// value to appear in the text-box
			$inputBoxValue = $this->relation_object->getRelatedClassDisplayFieldValue();		
			
			if($table_tags) {
				echo '<tr><td style="width:25%;">';
				echo $this->label;
				echo '</td>';
				
				echo '<td>';			
				echo '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value='+document.getElementById('".$this->name."').value+'&field=".$this->name."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
				echo '</td></tr>';
			}else{
				echo '<input type="text" size="70" class="readonly" name="'.$this->name.'_display" id="'.$this->name.'_display" value="'.$inputBoxValue.'" readonly/>';
				$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/record_selector.php?value=".$this->relation_object->getValue()."&relatedClass=".$this->relation_object->getRelatedClass()."&relatedClassField=".$this->relation_object->getRelatedClassField()."&relatedClassDisplayField=".$this->relation_object->getRelatedClassDisplayField()."&relationType=".$this->relation_object->getRelationType()."','relWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=500,height=50,left='+(event.screenX-250)+',top='+event.screenY+'');", "Insert record link", "relBut", $config->get('sysURL')."/alpha/images/icons/application_link.png");
			}
			
			// hidden field to store the actual value of the relation
			echo '<input type="hidden" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->relation_object->getValue().'"/>';
		}
		
		// render read-only list for one-to-many relations
		if($this->relation_object->getRelationType() == 'ONE-TO-MANY') {
			$objects = $this->relation_object->getRelatedObjects();			
			
			if(count($objects) > 0 && $table_tags) {
				echo '<tr><td colspan="2">';
				echo '<table cols="1" width="100%">';
				echo '<tr><td style="text-align:center;">';
				echo $this->label;
				$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = 'block';", "Display related objects", $this->name."DisBut", $config->get('sysURL')."/alpha/images/icons/arrow_down.png");
				$tmp = new button("document.getElementById('relation_field_".$this->name."').style.display = 'none';", "Hide related objects", $this->name."HidBut", $config->get('sysURL')."/alpha/images/icons/arrow_up.png");
				echo '</td></tr>';
				
				echo '<tr><td colspan="2">';
				echo '<div id="relation_field_'.$this->name.'" style="display:none;">';
				foreach($objects as $obj) {
					echo '<div class="bordered" style="margin:5px;">';
					echo '<p>'.$obj->data_labels['OID'].': <em>'.$obj->get_ID().'</em></p>';
					echo '<p>'.$obj->data_labels[$this->relation_object->getRelatedClassDisplayField()].': <em>'.$obj->get($this->relation_object->getRelatedClassDisplayField()).'</em></p>';
					echo '</div>';
				}
				echo '</div>';
				echo '</td></tr>';
				echo '</table>';
				echo '</td></tr>';				
			}
		}
	}
	
	/**
	 * renders the HTML for the record selector
	 */
	function render_selector() {
		global $config;
		
		$className = $this->relation_object->getRelatedClass();
		
		if (file_exists($config->get('sysRoot').'alpha/model/'.$className.'.inc')) {
			require_once $config->get('sysRoot').'alpha/model/'.$className.'.inc';
		}elseif (file_exists($config->get('sysRoot').'model/'.$className.'.inc')) {
			require_once $config->get('sysRoot').'model/'.$className.'.inc';
		}else{
			$error = new handle_error('record_selector.php','Could not load the defination for the BO class '.$this->relatedClass,'framework');
			exit;
		}
		
		$tmpObject = new $className;		
		$label = $tmpObject->data_labels[$this->relation_object->getRelatedClassDisplayField()];
		$oidLabel = $tmpObject->data_labels['OID'];
		
		$objects = $tmpObject->load_all();
		
		echo '<table cols="3" width="100%" class="bordered">';
		echo '<tr>';		
		echo '<th>'.$oidLabel.'</th>';
		echo '<th>'.$label.'</th>';
		echo '<th>Connect?</th>';		
		echo '</tr>';
		
		foreach($objects as $obj){
			echo '<tr>';
			echo '<td width="20%">';
			echo $obj->get_ID();
			echo '</td>';
			echo '<td width="60%">';
			echo $obj->get($this->relation_object->getRelatedClassDisplayField());
			echo '</td>';			
			echo '<td width="20%">';
			if($obj->get_ID() == $this->relation_object->getValue())
				echo '<img src="'.$config->get('sysURL').'/alpha/images/icons/accept_ghost.png"/>';
			else
				$tmp = new button("window.opener.document.getElementById('".$_GET['field']."').value = '".$obj->get_ID()."'; window.opener.document.getElementById('".$_GET['field']."_display').value = '".$obj->get($this->relation_object->getRelatedClassDisplayField())."'; window.close();", "", "selBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
			echo '</td>';
			echo '</tr>';
		}		
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
	$relation_object->setRelatedClass($_GET['relatedClass']);
	$relation_object->setRelatedClassField($_GET['relatedClassField']);
	$relation_object->setRelatedClassDisplayField($_GET['relatedClassDisplayField']);
	$relation_object->setRelationType($_GET['relationType']);
	$relation_object->setValue($_GET['value']);
	
	$recSelector = new record_selector($relation_object);
}

?>