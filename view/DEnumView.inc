<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/types/DEnum.inc';
require_once $config->get('sysRoot').'alpha/model/types/DEnumItem.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 *
 * The rendering class for the DEnum class
 * 
 * @package Alpha Scafolding
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * 
 * 
 */
class DEnumView extends View
{
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * constructor for the DEnum	
	 * @param DEnum $DEnum the DEnum object for rendering
	 */
	function DEnumView($DEnum)
	{
		$this->View($DEnum);
	}	
	
	/**
	 * Custom list view with no password field present.
	 */
	function list_view() {
		global $config;

		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		$labels = $this->BO->getDataLabels();
		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		// first render all of the table headers
		echo '<tr>';
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != "Text") {
					$col_count ++;
					echo '	<th>'.$labels[$prop].'</th>';
				}
			}
			if ($prop == "OID")
				echo '	<th>'.$labels[$prop].'</th>';			
		}
		// render the count
		echo '	<th>Item count</th>';
		
		echo '</tr><tr>';

		// and now the values
		foreach($properties as $propObj) {
			$prop = $propObj->name;
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {
				if (get_class($this->BO->getPropObject($prop)) != "Text") {
					echo '	<td>&nbsp;'.$this->BO->get($prop).'</td>';
				}
			}
			if ($prop == "OID")
				echo '	<td>&nbsp;'.$this->BO->getID().'</td>';
		}
		// render the count
		echo '	<td>&nbsp;'.$this->BO->getItemCount().'</td>';
		
		echo '</tr>';

		echo '<tr><td colspan="'.($col_count+1).'" align="center">';
		// render edit buttons for admins only
		if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->getAccessLevel() == "Administrator") {
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/EditDEnum.php?oid=".$this->BO->getID()."'", "Edit", "editBut");
		}
		echo '</td></tr>';

		echo '</form>';
		echo '</table>';
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
		
		$temp = new string_box($this->BO->getPropObject("name"), $labels["name"], "name", "", 0, true, true);
		
		echo '<tr><td colspan="2"><h3>DEnum display values:</h3></td></tr>';
		
		// now get all of the options for the enum and render
		$denum = $this->BO;
		$tmp = new DEnumItem();
		$denumItems = $tmp->loadItems($denum->getID());						
		
		foreach ($denumItems as $item) {
			$labels = $item->getDataLabels();
			$temp = new string_box($item->getPropObject("value"), $labels["value"], "value_".$item->getID(), "");
		}
		
		echo '<input type="hidden" name="version_num" value="'.$this->BO->getVersion().'"/>';
		
		echo '<tr><td colspan="2"><h3>Add a new value to the DEnum dropdown list:</h3></td></tr>';
		
		$temp = new string_box(new String(), $labels["value"], "new_value", "");
		
		echo '<tr><td colspan="2">';
		
		$temp = new button("submit", "Save", "saveBut");
		echo '&nbsp;&nbsp;';
		$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/ListDEnums.php'", "Back to List", "cancelBut");
		
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form></table>';
		
		if(method_exists($this, 'after_edit_view_callback'))
			$this->after_edit_view_callback();
	}
}

?>