<?php


// $Id$

require_once $sysRoot.'alpha/util/catch_error.inc';

require_once $sysRoot."alpha/model/types/Date.inc";
require_once $sysRoot."alpha/model/types/Double.inc";
require_once $sysRoot."alpha/model/types/Integer.inc";
require_once $sysRoot."alpha/model/types/String.inc";
require_once $sysRoot."alpha/model/types/Text.inc";
require_once $sysRoot."alpha/model/types/Enum.inc";
require_once $sysRoot."alpha/model/types/Boolean.inc";

/**
 *
 * The master rendering view class for the framework
 * 
 * @package Alpha Core
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * @todo ensure that all buttons are present when not using widgets
 * @todo move factory logic in constructor to a dedicated method (get_instance())
 * 
 */
class View {
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * the business object that will be rendered
	 * @var object
	 */
	var $BO = null;

	/**
	 * constructor for the View
	 * @param object $BO the main business object that this view is going to render
	 */
	function View($BO) {		
		$this->BO = $BO;
	}
	
	/**
	 * static methid which returns a View object or a custom child view for the BO specified if one exists
	 * @param object $BO the main business object that this view is going to render
	 * @param bool $return_parent flag to enforce the return of this object instead of a child (defaults to false)
	 * @return object returns a View object, or a child view object from the /view directory if one exists for this BO 
	 */
	function get_instance($BO, $return_parent=false) {
		global $sysRoot;
		
		$filename = get_class($BO);
		$underscore_pos = strpos($filename, '_');
		$filename = substr($filename, 0, $underscore_pos);		

		// Check to see if a custom view exists for this BO, and if it does return that view instead		
		if (!$return_parent) {
			if (file_exists($sysRoot.'/view/'.$filename.'.inc')) {
				require_once $sysRoot.'/view/'.$filename.'.inc';			
				return new $filename($BO);
			}elseif (file_exists($sysRoot.'/alpha/view/'.$filename.'.inc')) {
				require_once $sysRoot.'/alpha/view/'.$filename.'.inc';			
				return new $filename($BO);
			}else{
				return new View($BO, true);
			}
		}
	}

	/**
	 * simple setter for the view business object
	 * @param object $BO the main business object that this view is going to render
	 */
	function set_BO($BO) {
		$this->BO = $BO;
	}

	/**
	 * renders the default create view
	 */
	function create_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="create_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {

				$obj_type = get_class($properties[$prop]);

				switch (strtoupper($obj_type)) {
					case "INTEGER" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="11" name="'.$prop.'" value="'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'"/><br>';
						echo '</td></tr>';
						break;
					case "DOUBLE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="13" name="'.$prop.'" value="'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'"/><br>';
						echo '</td></tr>';
						break;
					case "DATE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="16" name="'.$prop.'" value="'. (isset ($_POST[$prop]) ? $_POST[$prop] : date("Y-m-d H:i:s")).'"/><br>';
						echo '</td></tr>';
						break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("string_box")) {
							$temp = new string_box($this->BO-> $prop, $this->BO->data_labels[$prop], $prop, "");
						} else {
							echo '<tr><td style="width:25%;">';
							echo $this->BO->data_labels[$prop];
							echo '</td>';

							echo '<td>';
							echo '<input type="text" size="'.$this->BO-> $prop->get_size().'" name="'.$prop.'" value="'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'"/><br>';
							echo '</td></tr>';
						}
						break;
					case "TEXT" :					
						// use a text widget if defined
						if (class_exists("text_box")) {
							$temp = new text_box($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "");
						} else {
							echo '<tr><td colspan="2">';
							echo $this->BO->data_labels[$prop];
							echo '</td></tr>';
	
							echo '<tr><td colspan="2">';
							echo '<textarea cols="90" rows="5" name="'.$prop.'">'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'</textarea><br>';
							echo '</td></tr>';
						}
						break;
					case "BOOLEAN" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<select size="1" name="'.$prop.'"/>';
						echo '<option value="0" selected>N</option>';
						echo '<option value="1">Y</option>';
						echo '</select><br>';
						echo '</td></tr>';
						break;
					case "ENUM" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';
						echo '<td>';
						echo '<select name="'.$prop.'"/>';
						$enum = $this->BO->get_prop_object($prop);
						$enum_vals = $this->BO-> $prop->get_options();
						asort($enum_vals);
						foreach ($enum_vals as $enum_val) {
							if ($enum->get_value() == $enum_val)
								echo '<option value="'.$enum_val.'" selected>'.$enum_val.'</option>';
							else
								echo '<option value="'.$enum_val.'">'.$enum_val.'</option>';
						}
						echo '</select><br>';
						echo '</td></tr>';
						break;
					default :
						echo '<tr><td colspan="2">';
						echo $this->BO->data_labels[$prop];
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="100" rows="3" name="'.$prop.'">'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'</textarea><br>';
						echo '</td></tr>';
						break;
				}
			}
		}

		echo '<tr><td colspan="2">';
		if (class_exists("button")) {
			$temp = new button("submit", "Create", "createBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$sysURL."/alpha/controller/ListBusinessObjects.php')", "Cancel", "cancelBut");
		} else {
			echo '<input type="submit" name="createBut" value="Create"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="submit" name="cancelBut" value="Cancel"/>';
		}
		echo '</td></tr>';
		
		$this->render_security_fields();

		echo '</form></table>';
	}

	/**
	 * draws a form to enable object editing
	 */
	function edit_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {

				$obj_type = get_class($properties[$prop]);

				switch (strtoupper($obj_type)) {
					case "INTERGER" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="11" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "DOUBLE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="13" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "DATE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="16" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("string_box")) {
							$temp = new string_box($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "");
						} else {
							echo '<tr><td style="width:25%;">';
							echo $this->BO->data_labels[$prop];
							echo '</td>';

							echo '<td>';
							echo '<input type="text" size="'.$this->BO-> $prop->get_size().'" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
							echo '</td></tr>';
						}
					break;
					case "TEXT" :
						// use a string widget if defined
						if (class_exists("text_box")) {
							$temp = new text_box($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "");
						} else {
							echo '<tr><td colspan="2">';
							echo $this->BO->data_labels[$prop];
							echo '</td></tr>';
	
							echo '<tr><td colspan="2">';
							echo '<textarea cols="90" rows="5" name="'.$prop.'">'.htmlspecialchars($this->BO->get($prop)).'</textarea><br>';
							echo '</td></tr>';
						}
					break;					
					case "BOOLEAN":
						echo '<tr><td>';
						echo $this->BO->data_labels[$prop];
						echo '</td>';

						echo '<td>';
						echo '<select size="1" name="'.$prop.'"/>';
						echo '<option value="0" '.($this->BO->$prop->get_value() == '0'? 'selected':'').'>N</option>';
						echo '<option value="1" '.($this->BO->$prop->get_value() == '1'? 'selected':'').'>Y</option>';
						echo '</select><br>';
						echo '</td></tr>';
					break;
					case "ENUM" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';
						echo '<td>';
						echo '<select name="'.$prop.'"/>';
						$enum = $this->BO->get_prop_object($prop);
						$enum_vals = $enum->get_options();
						asort($enum_vals);
						foreach ($enum_vals as $enum_val) {
							if ($enum->get_value() == $enum_val)
								echo '<option value="'.$enum_val.'" selected>'.$enum_val.'</option>';
							else
								echo '<option value="'.$enum_val.'">'.$enum_val.'</option>';
						}
						echo '</select><br>';
						echo '</td></tr>';
					break;
					default :
						echo '<tr><td colspan="2">';
						echo $this->BO->data_labels[$prop];
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="100" rows="3" name="'.$prop.'">'.$this->BO->get($prop).'</textarea><br>';
						echo '</td></tr>';
					break;
				}
			}
			if ($prop == "version_num")
				echo '<input type="hidden" name="version_num" value="'.$this->BO->get_version().'"/>';
		}

		echo '<tr><td colspan="2">';
		if (class_exists("button")) {
			$temp = new button("submit", "Save", "saveBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$sysURL."/alpha/controller/ListAll.php?bo=".get_class($this->BO)."'", "Back to List", "cancelBut");
		} else {
			echo '<input type="submit" name="saveBut" value="Save"/>';
			echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
			echo '<input type="button" name="cancelBut" value="Back to List" onclick="document.location = "'.$sysURL.'/controller/ListAll.php?bo='.get_class($this->BO).'"/>';
		}
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form></table>';
	}

	/**
	 * setter for the render mode
	 * @param string mode
	 */
	function set_render_mode($mode) {
		$this->render_mode = $mode;
	}

	/**
	 * echo out the object properties
	 */
	function print_contents() {
		print_r($this);
	}

	/**
	 * maps to the save_object method
	 * @return boolean true if save suceeded, false otherwise
	 */
	function save($OID = "") {
		return $this->save_object($OID);
	}

	/**
	 * maps to the load_object method
	 * @return boolean true if load suceeded, false otherwise
	 */
	function load($OID) {
		return $this->load_object($OID);
	}

	/**
	 * Renders the list view.
	 */
	function list_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		// first render all of the table headers
		echo '<tr>';
		foreach (array_keys($properties) as $prop) {
			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {
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
			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {
				if (get_class($properties[$prop]) != "Text") {
					echo '	<td>&nbsp;'.$this->BO-> $prop->get_value().'</td>';
				}
			}
			if ($prop == "OID")
				echo '	<td>&nbsp;'.$this->BO->get_ID().'</td>';
		}
		echo '</tr>';

		echo '<tr><td colspan="'.$col_count.'" align="center">';
		if (class_exists("button")) {
			$temp = new button("document.location = '".$sysURL."/alpha/controller/Detail.php?bo=".get_class($this->BO)."&oid=".$this->BO->get_ID()."'", "View", "viewBut");
			// render edit and delete buttons for admins only
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '&nbsp;&nbsp;';
				$temp = new button("document.location = '".$sysURL."/alpha/controller/Edit.php?bo=".get_class($this->BO)."&oid=".$this->BO->get_ID()."'", "Edit", "editBut");
				echo '&nbsp;&nbsp;';
				$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			}
		} else {
			echo '<input type="button" name="viewBut" value="View" onclick="document.location.replace(\''.$sysURL.'/controller/Detail.php?bo='.get_class($this->BO).'&oid='.$this->BO->get_ID().'\')"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="submit" name="editBut" value="Edit"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
		}
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';
	}

	/**
	 * displays a detailed view of the object
	 */
	function detailed_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table class="detailed_view" cols="2">';
		echo '<tr><th>';
		echo $this->BO->data_labels["OID"];
		echo '</th><td>&nbsp;';
		echo $this->BO->get_ID();
		echo '</td></tr>';

		foreach (array_keys($properties) as $prop) {

			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {

				$obj_type = get_class($properties[$prop]);

				switch (strtoupper($obj_type)) {
					case "DATE" :
						echo '<tr><th>';
						echo $this->BO->data_labels[$prop];
						echo '</th>';

						echo '<td>&nbsp;';
						echo $this->BO->get($prop);
						echo '</td></tr>';
						break;
					case "TEXT" :
						echo '<tr><th>';
						echo $this->BO->data_labels[$prop];
						echo '</th>';

						echo '<td><div class="text_box">';
						echo $this->BO->get($prop);
						echo '</div></td></tr>';
						break;
					default :
						echo '<tr><th>';
						echo $this->BO->data_labels[$prop];
						echo '</th>';

						echo '<td>&nbsp;';
						echo $this->BO->get($prop);
						echo '</td></tr>';
						break;
				}
			}
		}

		echo '<tr><td colspan="2" style="text-align:center;">';
		if (class_exists("button")) {
			$temp = new button("history.back()", "Back", "backBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$sysURL."/alpha/controller/Edit.php?bo=".get_class($this->BO)."&oid=".$this->BO->get_ID()."'", "Edit", "editBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
		} else {
			echo '<input type="button" name="backBut" value="Back" onclick="history.back()"/>';
			echo '<input type="submit" name="editBut" value="Edit"/>';
			echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
		}
		echo '</td></tr>';

		echo '</table>';
	}

	/**
	 * Renders the admin view for the business object screen.
	 */
	function admin_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table class="admin_view" cols="5">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'" id="admin_'.get_class($this->BO).'" method="POST">';
		// first render all of the table headers
		echo '<tr>';
		echo '	<th>Class name</th>';
		echo '	<th>Table name</th>';
		echo '	<th>Table row count</th>';
		echo '	<th>Table exists?</th>';
		echo '	<th>Table needs updating?</th>';
		echo '</tr><tr>';

		// and now the values
		echo '	<td>&nbsp;'.get_class($this->BO).'</td>';
		echo '	<td>&nbsp;'.$this->BO->get_table_name().'</td>';
		echo '	<td>&nbsp;'. ($this->BO->check_table_exists() ? $this->BO->get_count() : '<span class="warning">unavailable</span>').'</td>';
		echo '	<td>&nbsp;'. ($this->BO->check_table_exists() ? '<span class="success">Yes</span>' : '<span class="warning">No</span>').'</td>';
		echo '	<td>&nbsp;'. ($this->BO->check_table_needs_update() ? '<span class="warning">Yes</span>' : '<span class="success">No</span>').'</td>';
		echo '</tr>';

		echo '<tr><td colspan="5" align="center">';
		if (class_exists("button")) {
			$temp = new button("document.location = '".$sysURL."/alpha/controller/Create.php?bo=".get_class($this->BO)."'", "Create New", "createBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$sysURL."/alpha/controller/ListAll.php?bo=".get_class($this->BO)."&no-forceframe=true'", "List All", "listBut");
			if (!$this->BO->check_table_exists()) {
				echo '&nbsp;&nbsp;';
				$temp = new button("submit", "Create Table", "createTableBut");
				// hidden field so that we know which class to create the table for
				echo '<input type="hidden" name="createTableClass" value="'.get_class($this->BO).'"/>';
			}
			if ($this->BO->check_table_needs_update() && $this->BO->check_table_exists()) {
				echo '&nbsp;&nbsp;';
				$temp = new button("if(confirm('Are you sure you wish to recreate this class table (all data will be lost)?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'recreateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Recreate Table", "recreateTableBut");
				// hidden field so that we know which class to recreate the table for
				echo '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
				echo '&nbsp;&nbsp;';
				$temp = new button("if(confirm('Are you sure you wish to attempt to modify this class table by adding new attributes?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'updateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Update Table", "updateTableBut");
				// hidden field so that we know which class to update the table for
				echo '<input type="hidden" name="updateTableClass" value="'.get_class($this->BO).'"/>';
				
				echo '<input type="hidden" id="admin_'.get_class($this->BO).'_button_pressed" name="admin_'.get_class($this->BO).'_button_pressed" value=""/>';
			}
		} else {
			// !! this section needs updating !!
			echo '<input type="button" name="viewBut" value="View" onclick="document.location.replace(\''.$sysURL.'/alpha/controller/Detail.php?bo='.get_class($this->BO).'&oid='.$this->BO->get_ID().'\')"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="submit" name="editBut" value="Edit"/>';
			echo '&nbsp;&nbsp;';
			echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
		}
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';
	}
	
	/**
	 * method to render two hidden, encrypted form fields to be used as
	 * a check to ensure that a post to the controller is being sent from 
	 * the same server as hosting it. 
	 */
	function render_security_fields() {
		$fields = View::generate_security_fields();
		
		echo '<input type="hidden" name="var1" value="'.$fields[0].'"/>';
		echo '<input type="hidden" name="var2" value="'.$fields[1].'"/>';
	}
	
	/**
	 * generates the two security fields to prevent remote form processing
	 * @return array an array containing the two fields
	 */
	function generate_security_fields() {
		// the server hostname + today's date
		$var1 = md5($_SERVER["HTTP_HOST"].date("Ymd"));
		// the server's IP plus $var1
		$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
		
		return array($var1, $var2);
	}
}
?>