<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';

/**
 *
 * The rendering class for the person_object class
 * 
 * @package Photo-Site
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class person extends View
{
	/**
	 * constructor for the person	
	 * @param person_object $person_object the person business object for rendering
	 */
	function person($person_object)
	{
		$this->BO = $person_object;
	}	
	
	/**
	 * method to render the login HTML form
	 */
	function display_login_form() {
		global $config;
		
		echo '<div class="bordered" style="text-align:center; padding:10px; margin:10px;">';
		echo '<table cols="2" align="center">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';
		echo '<tr>';
		echo '	<td>Password</td> <td><input type="password" name="password" size="50"/></td>';
		echo '</tr>';
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Login","loginBut");
			echo '<div align="center">'.$temp->render(80).'</div>';			
		}else{
			echo '	<input type="submit" name="loginBut" value="Login"/>';
			$temp = new button("document.location.replace('".$config->get('sysURL')."')","Cancel","cancelBut");
			echo $temp->render();
		}
		echo '</td></tr>';
		
		echo $this->renderSecurityFields();
		
		echo '</form>';
		echo '</table>';
		echo '<p><a href="'.$_SERVER["PHP_SELF"].'?reset=true">Forgotten your password?</a></p>';
		echo '</div>';
	}
	
	/**
	 * method to render the reset password HTML form
	 */
	function display_reset_form() {
		global $config;
		
		echo '<p>If you have forgotten your password, you can use this form to have a new password automatically generated and sent to your e-mail address.</p>';
		echo '<table cols="2">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?reset=true" method="POST">';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';		
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Reset Password","resetBut");
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$config->get('sysURL')."')","Cancel","cancelBut");
			echo $temp->render();
		}else{
			echo '	<input type="submit" name="resetBut" value="Reset Password"/>';
			echo '	<input type="button" name="cancel" value="Cancel" onclick="document.location.replace(\''.$config->get('sysURL').'\')"/>';
		}
		echo '</td></tr>';
		
		echo $this->renderSecurityFields();
		
		echo '</form>';
		echo '</table>';	
	}
	
	/**
	 * method to render the user registration form
	 */
	function register_view() {
		global $config;
		
		echo '<p>In order to post comments on the articles on this web site, and to post comments on the forum, you will need to create a user account.  In order to do so, please provide a valid email address below and a password will be sent to your inbox shortly (you can change your password once you log in).</p>';
		echo '<p class="warning">Please note: the forum is currently not available, but will be made available in the coming weeks!</p>';
		echo '<table cols="2">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?reset=true" method="POST">';
		echo '<tr>';
		echo '	<td>Forum name</td> <td><input type="text" name="displayname" size="50" value="'.(isset($_POST["displayname"])? $_POST["displayname"] : '').'"/></td>';
		echo '</tr>';
		echo '<tr>';
		echo '	<td>E-mail Address</td> <td><input type="text" name="email" size="50" value="'.(isset($_POST["email"])? $_POST["email"] : '').'"/></td>';
		echo '</tr>';		
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {			
			$temp = new button("submit","Register","registerBut");
			echo $temp->render();
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location.replace('".$config->get('sysURL')."')","Cancel","cancelBut");
			echo $temp->render();
		}else{
			echo '	<input type="submit" name="registerBut" value="Register"/>';
			echo '	<input type="button" name="cancel" value="Cancel" onclick="document.location.replace(\''.$config->get('sysURL').'\')"/>';
		}
		echo '</td></tr>';
		
		echo $this->renderSecurityFields();
		
		echo '</form>';
		echo '</table>';	
	}
	
	/**
	 * custom edit view to enable the reseting of passwords
	 */
	function edit_view() {
		global $config;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && $prop != "password" && $prop != "access_level" && $prop != "state") {

				$obj_type = get_class($properties[$prop]);

				switch (strtoupper($obj_type)) {
					case "INTEGER" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->getDataLabel($prop);
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="11" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "DOUBLE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->getDataLabel($prop);
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="13" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "DATE" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->getDataLabel($prop);
						echo '</td>';

						echo '<td>';
						echo '<input type="text" size="16" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
						echo '</td></tr>';
					break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("string_box")) {
							$temp = new string_box($this->BO->get_prop_object($prop), $this->BO->getDataLabel($prop), $prop, "");
						} else {
							echo '<tr><td style="width:25%;">';
							echo $this->BO->getDataLabel($prop);
							echo '</td>';

							echo '<td>';
							echo '<input type="text" size="'.$this->BO-> $prop->get_size().'" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
							echo '</td></tr>';
						}
					break;
					case "TEXT" :
						// use a text widget if defined
						if (class_exists("text_box")) {
							$temp = new text_box($this->BO->get_prop_object($prop), $this->BO->getDataLabel($prop), $prop, "");
						} else {
							echo '<tr><td colspan="2">';
							echo $this->BO->getDataLabel($prop);
							echo '</td></tr>';
	
							echo '<tr><td colspan="2">';
							echo '<textarea cols="90" rows="5" name="'.$prop.'">'.htmlspecialchars($this->BO->get($prop)).'</textarea><br>';
							echo '</td></tr>';
						}
					break;					
					case "BOOLEAN":
						echo '<tr><td>';
						echo $this->BO->getDataLabel($prop);
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
						echo $this->BO->getDataLabel($prop);
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
					default :
						echo '<tr><td colspan="2">';
						echo $this->BO->getDataLabel($prop);
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="100" rows="3" name="'.$prop.'">'.$this->BO->get($prop).'</textarea><br>';
						echo '</td></tr>';
					break;
				}
			}
			if ($prop == "version_num")
				echo '<input type="hidden" name="version_num" value="'.$this->BO->get_version().'"/>';
			if ($prop == "password") {
				$temp = new String();
				$temp->is_password();
				$temp->is_required(false);
				$temp = new string_box($temp, "New password (leave blank for no change)", "new_password", "");
				echo '<input type="hidden" name="password" value="'.$this->BO->get_password().'"/>';
			}
			// need to ensure that only admins get the privileages enum drop-down
			if ($prop == "access_level" && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '<tr><td style="width:25%;">';
				echo $this->BO->getDataLabel($prop);
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
			}elseif ($prop == "access_level" && $_SESSION["current_user"]->get_access_level() != "Administrator"){
				echo '<input type="hidden" name="access_level" value="'.$this->BO->get("access_level").'"/>';
			}
			// need to ensure that only admins get the state enum drop-down
			if ($prop == "state" && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '<tr><td style="width:25%;">';
				echo $this->BO->getDataLabel($prop);
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
			}elseif ($prop == "state" && $_SESSION["current_user"]->get_access_level() != "Administrator"){
				echo '<input type="hidden" name="state" value="'.$this->BO->get("state").'"/>';
			}
		}

		echo '<tr><td colspan="2">';
		
		$temp = new button("submit", "Save", "saveBut");
		if ($_SESSION["current_user"]->get_access_level() == "Administrator") {
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".FrontController::generateSecureURL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		}		
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form></table>';
	}
	
	/**
	 * Custom list view with no password field present.
	 */
	function list_view() {
		global $config;

		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();

		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		// first render all of the table headers
		echo '<tr>';
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$propClass = get_class($this->BO->get($propName));				
		
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && $propName != 'password') {
				if ($propClass != 'Text') {
					$col_count++;
					echo '	<th>'.$this->BO->getDataLabel($propName).'</th>';
				}
			}
			if ($propName == 'OID')
				echo '	<th>'.$this->BO->getDataLabel($propName).'</th>';
		}
		echo '</tr><tr>';

		// and now the values
		foreach($properties as $propObj) {
			$propName = $propObj->name;
			$propClass = get_class($this->BO->get($propName));
		
			if (!in_array($propName, $this->BO->getDefaultAttributes()) && $propName != 'password') {
				if ($propClass != 'Text') {
					echo '	<td>&nbsp;'.$this->BO->get($propName).'</td>';
				}
			}
			if ($propName == 'OID')
				echo '	<td>&nbsp;'.$this->BO->getID().'</td>';
		}
		echo '</tr>';

		echo '<tr><td colspan="'.$col_count.'" align="center">';
		
		$temp = new button("document.location = '".FrontController::generateSecureURL('act=Detail&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'", "View", "viewBut");
		// render edit and delete buttons for admins only
		if (isset($_SESSION['currentUser']) && $_SESSION['currentUser']->inGroup('Admin')) {
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".FrontController::generateSecureURL('act=edit_person_object&oid='.$this->BO->getID())."'", "Edit", "editBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
		}		
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';
	}
}

?>
