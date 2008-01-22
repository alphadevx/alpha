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

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		// first render all of the table headers
		echo '<tr>';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->default_attributes) && !in_array($prop, $this->BO->transient_attributes)) {
				if (get_class($properties[$prop]) != "Text") {
					$col_count ++;
					echo '	<th>'.$this->BO->data_labels[$prop].'</th>';
				}
			}
			if ($prop == "OID")
				echo '	<th>'.$this->BO->data_labels[$prop].'</th>';
		}
		echo '</tr><tr>';

		// and now the values
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->default_attributes) && !in_array($prop, $this->BO->transient_attributes)) {
				if (get_class($properties[$prop]) != "Text") {
					echo '	<td>&nbsp;'.$this->BO->$prop->get_value().'</td>';
				}
			}
			if ($prop == "OID")
				echo '	<td>&nbsp;'.$this->BO->get_ID().'</td>';
		}
		echo '</tr>';

		echo '<tr><td colspan="'.$col_count.'" align="center">';
		// render edit buttons for admins only
		if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/EditDEnum.php?oid=".$this->BO->get_ID()."'", "Edit", "editBut");
		}
		echo '</td></tr>';

		echo '</form>';
		echo '</table>';
	}
}

?>