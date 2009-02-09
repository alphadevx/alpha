<?php

require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 *
 * The rendering class for the DEnum class
 * 
 * @package alpha::view
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class DEnumView extends View {
	/**
	 * Custom list view
	 * 
	 * @return string
	 */
	public function listView() {
		global $config;

		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		$labels = $this->BO->getDataLabels();
		$colCount = 1;

		$html = '<table class="list_view">';
		$html .= '<form action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" method="POST">';
		// first render all of the table headers
		$html .= '<tr>';
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != 'Text') {
					$colCount ++;
					$html .= '	<th>'.$labels[$prop].'</th>';
				}
			}
			if ($prop == 'OID')
				$html .= '	<th>'.$labels[$prop].'</th>';			
		}
		// render the count
		$html .= '	<th>Item count</th>';
		
		$html .= '</tr><tr>';

		// and now the values
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != 'Text') {
					$html .= '	<td>&nbsp;'.$this->BO->get($prop).'</td>';
				}
			}
			if ($prop == 'OID')
				$html .= '	<td>&nbsp;'.$this->BO->getID().'</td>';
		}
		// render the count
		$html .= '	<td>&nbsp;'.$this->BO->getItemCount().'</td>';
		
		$html .= '</tr>';

		$html .= '<tr><td colspan="'.($colCount+1).'" align="center">';
		// render edit buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->getAccessLevel() == 'Admin') {
			$html .= '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/EditDEnum.php?oid=".$this->BO->getID()."'", 'Edit', 'editBut');
			$html .= $temp->render();
		}
		$html .= '</td></tr>';

		$html .= '</form>';
		$html .= '</table>';
		
		return $html;
	}
	
	/**
	 * draws a form to enable object editing
	 */
	function edit_view() {
		if(method_exists($this, 'before_edit_view_callback'))
			$this->before_edit_view_callback();
		
		global $config;

		$properties = get_object_vars($this->BO);
		$labels = $this->BO->getDataLabels();
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		
		$temp = new stringBox($this->BO->getPropObject("name"), $labels["name"], "name", "", 0, true, true);
		
		echo '<tr><td colspan="2"><h3>DEnum display values:</h3></td></tr>';
		
		// now get all of the options for the enum and render
		$denum = $this->BO;
		$tmp = new DEnumItem();
		$denumItems = $tmp->loadItems($denum->getID());						
		
		foreach ($denumItems as $item) {
			$labels = $item->getDataLabels();
			$temp = new stringBox($item->getPropObject("value"), $labels["value"], "value_".$item->getID(), "");
			echo $temp->render();
		}
		
		echo '<input type="hidden" name="version_num" value="'.$this->BO->getVersion().'"/>';
		
		echo '<tr><td colspan="2"><h3>Add a new value to the DEnum dropdown list:</h3></td></tr>';
		
		$temp = new stringBox(new String(), $labels["value"], "new_value", "");
		echo $temp->render();
		
		echo '<tr><td colspan="2">';
		
		$temp = new button("submit", "Save", "saveBut");
		echo $temp->render();
		echo '&nbsp;&nbsp;';
		$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/ListDEnums.php'", "Back to List", "cancelBut");
		echo $temp->render();
		echo '</td></tr>';

		echo $this->renderSecurityFields();
		
		echo '</form></table>';
		
		if(method_exists($this, 'after_edit_view_callback'))
			$this->after_edit_view_callback();
	}
}

?>