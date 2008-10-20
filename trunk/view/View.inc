<?php

require_once $config->get('sysRoot').'alpha/util/catch_error.inc';
require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';
require_once $config->get('sysRoot').'alpha/model/types/Double.inc';
require_once $config->get('sysRoot').'alpha/model/types/Integer.inc';
require_once $config->get('sysRoot').'alpha/model/types/String.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';
require_once $config->get('sysRoot').'alpha/model/types/Enum.inc';
require_once $config->get('sysRoot').'alpha/model/types/Boolean.inc';
require_once $config->get('sysRoot').'alpha/view/widgets/calendar.php';
require_once $config->get('sysRoot').'alpha/view/widgets/record_selector.php';

/**
 * The master rendering view class for the framework
 * 
 * @package Alpha Core
 * @author John Collins <john@design-ireland.net>
 * @copyright 2008 John Collins
 * @version $Id$
 * 
 */
class View {
	/**
	 * The business object that will be rendered
	 * 
	 * @var object
	 */
	protected $BO;

	/**
	 * Constructor for the View
	 * 
	 * @param object $BO
	 */
	public function __construct($BO) {		
		$this->BO = $BO;
	}
	
	/**
	 * Static method which returns a View object or a custom child view for the BO specified
	 * if one exists
	 * 
	 * @param object $BO the main business object that this view is going to render
	 * @param bool $return_parent flag to enforce the return of this object instead of a child (defaults to false)
	 * @return object returns a View object, or a child view object from the /view directory if one exists for this BO 
	 */
	public static function getInstance($BO, $returnParent=false) {
		global $config;
		
		$filename = get_class($BO);
		$underscore_pos = strrpos($filename, '_');
		$filename = substr($filename, 0, $underscore_pos);		

		// Check to see if a custom view exists for this BO, and if it does return that view instead		
		if (!$returnParent) {
			if (file_exists($config->get('sysRoot').'view/'.$filename.'.inc')) {
				require_once $config->get('sysRoot').'view/'.$filename.'.inc';

				return new $filename($BO);
			}elseif (file_exists($config->get('sysRoot').'alpha/view/'.$filename.'.inc')) {
				require_once $config->get('sysRoot').'alpha/view/'.$filename.'.inc';

				return new $filename($BO);
			}else{
				return new View($BO, true);
			}
		}else{
			return new View($BO, true);
		}
	}

	/**
	 * Simple setter for the view business object
	 * 
	 * @param object $BO
	 */
	public function setBO($BO) {
		$this->BO = $BO;
	}

	/**
	 * Renders the default create view
	 */
	public function createView() {
		if(method_exists($this, 'before_createView_callback'))
			$this->before_createView_callback();
		
		global $config;
		
		// hash array of HTML fields to pass to the template
		$fields = array();		
		
		// the form action
		$fields['formAction'] = $_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"];
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('create');
		
		// buffer HTML output for Create and Cancel buttons		
		$button = new button('submit', 'Create', 'createBut');
		$fields['createButton'] = $button->render();
		
		$button = new button("document.location.replace('".$config->get('sysURL')."/alpha/controller/ListBusinessObjects.php')", 'Cancel', 'cancelBut');
		$fields['cancelButton'] = $button->render();		
		
		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();		

		$this->loadTemplate($this->BO, 'create', $fields);
		
		if(method_exists($this, 'after_createView_callback'))
			$this->after_createView_callback();
	}

	/**
	 * draws a form to enable object editing
	 */
	function edit_view() {
		if(method_exists($this, 'before_edit_view_callback'))
			$this->before_edit_view_callback();
		
		global $config;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->default_attributes) && !in_array($prop, $this->BO->transient_attributes)) {

				$obj_type = get_class($properties[$prop]);

				switch (strtoupper($obj_type)) {
					case "INTEGER" :
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
						$temp = new calendar($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
					break;					
					case "TIMESTAMP" :
						$temp = new calendar($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
					break;
					case "RELATION" :
						$temp = new record_selector($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
					break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("StringBox")) {
							$temp = new StringBox($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "");
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
						echo '<option value="0" '.($this->BO->$prop->getValue() == '0'? 'selected':'').'>N</option>';
						echo '<option value="1" '.($this->BO->$prop->getValue() == '1'? 'selected':'').'>Y</option>';
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
						foreach ($enum_vals as $enum_val) {
							if ($enum->getValue() == $enum_val)
								echo '<option value="'.$enum_val.'" selected>'.$enum_val.'</option>';
							else
								echo '<option value="'.$enum_val.'">'.$enum_val.'</option>';
						}
						echo '</select><br>';
						echo '</td></tr>';
					break;
					case "DENUM" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->data_labels[$prop];
						echo '</td>';
						echo '<td>';
						echo '<select name="'.$prop.'"/>';
						$denum = $this->BO->get_prop_object($prop);
						$denum_vals = $denum->get_options();						
						foreach (array_keys($denum_vals) as $denum_index) {
							if ($denum->getValue() == $denum_index)
								echo '<option value="'.$denum_index.'" selected>'.$denum_vals[$denum_index].'</option>';
							else
								echo '<option value="'.$denum_index.'">'.$denum_vals[$denum_index].'</option>';
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
				
			// if the property is transient but a one-to-many relationship, will will render it read-only
			if(in_array($prop, $this->BO->transient_attributes) && strtoupper(get_class($properties[$prop])) == 'RELATION' && $properties[$prop]->getRelationType() == 'ONE-TO-MANY') {
				$temp = new record_selector($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
			}
		}

		echo '<tr><td colspan="2">';
		if (class_exists("button")) {
			$temp = new button("submit", "Save", "saveBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$config->get('sysURL')."/alpha/controller/ListAll.php?bo=".get_class($this->BO)."'", "Back to List", "cancelBut");
		} else {
			echo '<input type="submit" name="saveBut" value="Save"/>';
			echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
			echo '<input type="button" name="cancelBut" value="Back to List" onclick="document.location = "'.$config->get('sysURL').'/controller/ListAll.php?bo='.get_class($this->BO).'"/>';
		}
		echo '</td></tr>';

		$this->renderSecurityFields();
		
		echo '</form></table>';
		
		if(method_exists($this, 'after_edit_view_callback'))
			$this->after_edit_view_callback();
	}

	/**
	 * Renders the list view
	 */
	public function listView() {
		if(method_exists($this, 'before_listView_callback'))
			$this->before_listView_callback();
		
		global $config;
		
		// hash array of HTML fields to pass to the template
		$fields = array();
		
		// the form action
		$fields['formAction'] = $_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"];
		
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
			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';				
			}
			if ($propName == 'OID')
				$html .= '	<th>'.$this->BO->getDataLabel($propName).'</th>';
		}
		$html .= '</tr><tr>';
		
		$fields['formHeadings'] = $html;
		
		$html = '';

		// and now the values
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$propClass = get_class($this->BO->getPropObject($propName));
				
				if ($propClass != 'Text') {
					$html .= '	<td>&nbsp;'.$this->BO->get($propName).'</td>';
				}else{
					$text = htmlentities($this->BO->get($propName));
					if(strlen($text) > 70)
						$html .= '	<td>&nbsp;'.substr($text, 0, 70).'...</td>';
					else
						$html .= '	<td>&nbsp;'.$text.'</td>';
				}
			}
			if ($propName == 'OID')
				$html .= '	<td>&nbsp;'.$this->BO->getID().'</td>';
		}
		$html .= '</tr>';
		
		$fields['formFields'] = $html;
		
		// View button
		$button = new button("document.location = '".Front_Controller::generate_secure_URL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'", 'View', 'viewBut');
		$fields['viewButton'] = $button->render();

		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['current_user']) && $_SESSION['current_user']->getAccessLevel() == 'Administrator') {
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".Front_Controller::generate_secure_URL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getID())."'", "Edit", "editBut");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;

		// buffer security fields to $formSecurityFields variable		
		$fields['formSecurityFields'] = $this->renderSecurityFields();		

		$this->loadTemplate($this->BO, 'list', $fields);
		
		if(method_exists($this, 'after_listView_callback'))
			$this->after_listView_callback();
	}

	/**
	 * Displays a detailed view of the object (read-only)
	 */
	public function detailedView() {
		if(method_exists($this, 'before_detailedView_callback'))
			$this->before_detailedView_callback();
		
		global $config;
		
		// hash array of HTML fields to pass to the template
		$fields = array();

		// we may want to display the OID regardless of class
		$fields['OIDLabel'] = $this->BO->getDataLabel('OID');		
		$fields['OID'] = $this->BO->getID();		
		
		// buffer form fields to $formFields
		$fields['formFields'] = $this->renderAllFields('view');
		
		// Back button
		$button = new button('history.back()', 'Back', 'backBut');
		$fields['backButton'] = $button->render();
		
		$html = '';
		// render edit and delete buttons for admins only
		if (isset($_SESSION['current_user']) && $_SESSION['current_user']->getAccessLevel() == 'Administrator') {
			$html .= '&nbsp;&nbsp;';
			$button = new button("document.location = '".Front_Controller::generate_secure_URL('act=Edit&bo='.get_class($this->BO)."&oid=".$this->BO->getID())."'", "Edit", "editBut");
			$html .= $button->render();
			$html .= '&nbsp;&nbsp;';
			$button = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			$html .= $button->render();
		}
		$fields['adminButtons'] = $html;
		
		$this->loadTemplate($this->BO, 'detail', $fields);
		
		if(method_exists($this, 'after_detailedView_callback'))
			$this->after_detailedView_callback();
	}

	/**
	 * Renders the admin view for the business object screen.
	 */
	function admin_view() {
		if(method_exists($this, 'before_admin_view_callback'))
			$this->before_admin_view_callback();
		
		global $config;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table class="admin_view" cols="5">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" id="admin_'.get_class($this->BO).'" method="POST">';
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
		echo '	<td>&nbsp;'.$this->BO->getTableName().'</td>';
		echo '	<td>&nbsp;'. ($this->BO->checkTableExists() ? $this->BO->getCount() : '<span class="warning">unavailable</span>').'</td>';
		echo '	<td>&nbsp;'. ($this->BO->checkTableExists() ? '<span class="success">Yes</span>' : '<span class="warning">No</span>').'</td>';
		echo '	<td>&nbsp;'. ($this->BO->checkTableNeedsUpdate() ? '<span class="warning">Yes</span>' : '<span class="success">No</span>').'</td>';
		echo '</tr>';

		echo '<tr><td colspan="5" align="center">';
		$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=Create&bo='.get_class($this->BO))."'", "Create New", "createBut");
		echo $temp->render();
		echo '&nbsp;&nbsp;';
		$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=ListAll&bo='.get_class($this->BO).'&no-forceframe=true')."'", "List All", "listBut");
		echo $temp->render();
		if (!$this->BO->checkTableExists()) {
			echo '&nbsp;&nbsp;';
			$temp = new button("submit", "Create Table", "createTableBut");
			echo $temp->render();
			// hidden field so that we know which class to create the table for
			echo '<input type="hidden" name="createTableClass" value="'.get_class($this->BO).'"/>';
		}
		if ($this->BO->checkTableNeedsUpdate() && $this->BO->checkTableExists()) {
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to recreate this class table (all data will be lost)?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'recreateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Recreate Table", "recreateTableBut");
			echo $temp->render();
			// hidden field so that we know which class to recreate the table for
			echo '<input type="hidden" name="recreateTableClass" value="'.get_class($this->BO).'"/>';
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to attempt to modify this class table by adding new attributes?')) {document.getElementById('admin_".get_class($this->BO)."_button_pressed').value = 'updateTableBut'; document.getElementById('admin_".get_class($this->BO)."').submit();}" , "Update Table", "updateTableBut");
			echo $temp->render();
			// hidden field so that we know which class to update the table for
			echo '<input type="hidden" name="updateTableClass" value="'.get_class($this->BO).'"/>';
				
			echo '<input type="hidden" id="admin_'.get_class($this->BO).'_button_pressed" name="admin_'.get_class($this->BO).'_button_pressed" value=""/>';
		}		
		echo '</td></tr>';

		$this->renderSecurityFields();
		
		echo '</form>';
		echo '</table>';
		
		if(method_exists($this, 'after_admin_view_callback'))
			$this->after_admin_view_callback();
	}
	
	/**
	 * method to render two hidden, encrypted form fields to be used as
	 * a check to ensure that a post to the controller is being sent from 
	 * the same server as hosting it.
	 * 
	 * @return string 
	 */
	public static function renderSecurityFields() {
		$html = '';
		
		$fields = View::generateSecurityFields();
		
		$html .= '<input type="hidden" name="var1" value="'.$fields[0].'"/>';
		$html .= '<input type="hidden" name="var2" value="'.$fields[1].'"/>';
		
		return $html;
	}
	
	/**
	 * generates the two security fields to prevent remote form processing
	 * 
	 * @return array an array containing the two fields
	 */
	public static function generateSecurityFields() {
		// the server hostname + today's date
		$var1 = md5($_SERVER["HTTP_HOST"].date("Ymd"));
		// the server's IP plus $var1
		$var2 = md5($_SERVER['REMOTE_ADDR'].$var1);
		
		return array($var1, $var2);
	}
	
	/**
	 * Method to render the default Integer HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public function renderIntegerField($name, $label, $mode, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td style="width:25%;">';
			$html .= $label;
			$html .= '</td>';
	
			$html .= '<td>';
			$html .= '<input type="text" size="11" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}
				
		return $html;
	}
	
	/**
	 * Method to render the default Double HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public static function renderDoubleField($name, $label, $mode, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td style="width:25%;">';
			$html .= $label;
			$html .= '</td>';
	
			$html .= '<td>';
			$html .= '<input type="text" size="13" name="'.$name.'" value="'. (isset ($_POST[$name]) ? $_POST[$name] : '').'"/><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}
				
		return $html;
	}
	
	/**
	 * Method to render the default Boolean HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public function renderBooleanField($name, $label, $mode, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td style="width:25%;">';
			$html .= $label;
			$html .= '</td>';
	
			$html .= '<td>';
			$html .= '<select size="1" name="'.$name.'"/>';
			$html .= '<option value="0" selected>N</option>';
			$html .= '<option value="1">Y</option>';
			$html .= '</select><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}
				
		return $html;
	}
	
	/**
	 * Method to render the default Enum HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param array $options The Enum options
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public function renderEnumField($name, $label, $mode, $options, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td style="width:25%;">';
			$html .= $label;
			$html .= '</td>';
			$html .= '<td>';
			$html .= '<select name="'.$name.'"/>';			
			foreach ($options as $val) {
				$html .= '<option value="'.$val.'">'.$val.'</option>';
			}
			$html .= '</select><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}
				
		return $html;
	}
	
	/**
	 * Method to render the default DEnum HTML
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)
	 * @param array $options The DEnum options
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public function renderDEnumField($name, $label, $mode, $options, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td style="width:25%;">';
			$html .= $label;
			$html .= '</td>';
			$html .= '<td>';
			$html .= '<select name="'.$name.'"/>';
			foreach (array_keys($options) as $index) {
				$html .= '<option value="'.$index.'">'.$options[$index].'</option>';
			}
			$html .= '</select><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}		
		
		return $html;
	}
	
	/**
	 * Method to render the default field HTML when type is not known
	 *
	 * @param string $name The field name
	 * @param string $label The label to apply to the field
	 * @param string $mode The field mode (create/edit/view)	 
	 * @param string $value The field value (optional)
	 * @return string
	 */
	public function renderDefaultField($name, $label, $mode, $value='') {
		$html = '';
		
		if ($mode == 'create') {
			$html .= '<tr><td colspan="2">';
			$html .= $label;
			$html .= '</td></tr>';
	
			$html .= '<tr><td colspan="2">';
			$html .= '<textarea cols="100" rows="3" name="'.$name.'">'. (isset ($_POST[$name]) ? $_POST[$name] : '').'</textarea><br>';
			$html .= '</td></tr>';
		}
		
		if ($mode == 'edit') {
			
		}
		
		if ($mode == 'view') {
			$html .= '<tr><th>';
			$html .= $label;
			$html .= '</th>';

			$html .= '<td>&nbsp;';
			$html .= $value;
			$html .= '</td></tr>';
		}
		
		return $html;
	}
	
	/**
	 * Renders all fields for the current BO in edit/create/view mode
	 *
	 * @param string $mode
	 * @return string
	 */
	public function renderAllFields($mode) {
		$html = '';
				
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$propClass = get_class($this->BO->getPropObject($propName));
				
				switch (strtoupper($propClass)) {
					case 'INTEGER' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderIntegerField($propName, $this->BO->getDataLabel($propName), 'create');
						}				
					break;
					case 'DOUBLE' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderDoubleField($propName, $this->BO->getDataLabel($propName), 'create');
						}
					break;
					case 'DATE' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$date = new calendar($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $date->render();
						}
					break;
					case 'TIMESTAMP' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$timestamp = new calendar($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $timestamp->render();
						}
					break;
					case 'STRING' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$string = new StringBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName, '');
							$html .= $string->render();
						}
					break;
					case 'TEXT' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{					
							$text = new TextBox($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName, '');
							$html .= $text->render();
						}
					break;
					case 'BOOLEAN' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$html .= $this->renderBooleanField($propName, $this->BO->getDataLabel($propName), 'create');
						}
					break;
					case 'ENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->get($propName));
						}else{
							$enum = $this->BO->getPropObject($propName);
							$html .= $this->renderEnumField($propName, $this->BO->getDataLabel($propName), 'create', $enum->getOptions());
						}
					break;
					case 'DENUM' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->getPropObject($propName)->getDisplayValue());
						}else{
							$denum = $this->BO->getPropObject($propName);
							$html .= $this->renderDEnumField($propName, $this->BO->getDataLabel($propName), 'create', $denum->getOptions());
						}
					break;
					case 'RELATION' :
						if($mode == 'view') {
							$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), 'view', $this->BO->getPropObject($propName)->getRelatedClassDisplayFieldValue());
						}else{
							$rel = new record_selector($this->BO->getPropObject($propName), $this->BO->getDataLabel($propName), $propName);
							$html .= $rel->render();
						}
					break;
					default :
						$html .= $this->renderDefaultField($propName, $this->BO->getDataLabel($propName), $mode);
					break;
				}
			}
		}
		
		return $html;
	}
	
	/**
	 * Loads a .phtml template for the BO specified if one exists.  Lower level custom templates
	 * take precedence.
	 * 
	 * @param object $BO
	 * @param string $mode
	 * @param array $fields 
	 */
	public function loadTemplate($BO, $mode, $fields) {
		global $config;
		
		$filename = $mode.'.phtml';
		$classTemplateDir = get_class($BO);

		$customPath = $config->get('sysRoot').'view/templates/'.$classTemplateDir.'/'.$filename;
		$defaultPath = $config->get('sysRoot').'alpha/view/templates/'.$filename;

		// Check to see if a custom template exists for this BO, and if it does load that		
		if (file_exists($customPath)) {
			require $customPath;				
		}elseif (file_exists($defaultPath)) {
			require $defaultPath;		
		}
	}
}
?>