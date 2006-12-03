<?php

// $Id$

require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/View.inc';
require_once $sysRoot.'alpha/util/markdown_facade.inc';

/**
 *
 * The rendering class for the article_object class
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class article extends View
{
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * constructor for the person	
	 * @param article_object $article_object the article business object for rendering
	 */
	function article($article_object)
	{
		$this->BO = $article_object;
	}
	
	/**
	 * method to generate the markdown HTML render of the article content
	 */
	function markdown_view() {
		global $sysURL;
		
		$markdown = new markdown_facade($this->BO);
		 
		echo $markdown->get_content();
		
		if (class_exists("button")) {
			// render edit buttons for admins only
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '&nbsp;&nbsp;';
				$temp = new button("document.location = '".$sysURL."/controller/Edit.php?bo=".get_class($this->BO)."&oid=".$this->BO->get_ID()."'","Edit","editBut");
			}
		}else{
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '&nbsp;&nbsp;';
				echo '<input type="submit" name="editBut" value="Edit"/>';
			}
		}
	}
	
	/**
	 * a method for generating the special view of an article on the "new additions" page
	 * @param int $count whether this new additions view is the first or second one on the page (only two are expected)
	 */
	function new_additions_view($count=1) {
		global $sysURL;
		
		echo '<div id="article'.$count.'Div" style="border: 2px solid white;">';
		echo '<h2>'.$this->BO->get("title").'</h2>';
		echo '<p>'.$this->BO->get("description").'</p>';
		echo '<table style="border: 1px solid white; width:98%; table-layout:fixed; margin:1%; position:absolute; bottom:0px;" cols="3">';
		echo '<tr>';
		$prop_obj = $this->BO->get_prop_object("date_added");
		echo '<td>Date Added: <em>'.$prop_obj->get_date().'</em></td>';
		$prop_obj = $this->BO->get_prop_object("section");
		echo '<td align="center">Site Section: <em>'.$prop_obj->get_value().'</em></td>';
		echo '<td align="right">';
		if (class_exists("button")) {			
			$temp = new button("document.location.replace('".$sysURL."/controller/view_article.php?oid=".$this->BO->get_ID()."')","Open","openBut");
		}else{			
			echo '<input type="button" name="openBut" value="Open" onclick="document.location.replace(\''.$sysURL.'\'/controller/view_article.php?oid='.$this->BO->get_ID().'\')"/>';
		}
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';		
	}
	
	/**
	 * Renders the custom list view.
	 */
	function list_view() {
		global $sysURL;		
		
		$properties = get_object_vars($this->BO);
		$obj_type = '';
		
		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		echo '<tr>';
		echo '<th>'.$this->BO->get("title").'</th>';
		echo '</tr><tr>';
		$prop_obj = $this->BO->get_prop_object("date_added");
		echo '<td>'.$this->BO->get("description").'<br><br>'.$this->BO->data_labels["section"].': <em>'.$this->BO->get("section").'</em>&nbsp;&nbsp;&nbsp;'.$this->BO->data_labels["date_added"].': <em>'.$prop_obj->get_date().'</em></td>';	
		echo '</tr>';
		
		echo '<tr><td colspan="'.$col_count.'" align="center">';
		if (class_exists("button")) {
			$temp = new button("document.location.replace('".$sysURL."/alpha/controller/view_article.php?oid=".$this->BO->get_ID().(isset($_GET["no-forceframe"]) ? "&no-forceframe=true" : "")."')","Open","openBut");
			// render edit and delete buttons for admins only
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '&nbsp;&nbsp;';
				$temp = new button("document.location = '".$sysURL."/controller/Edit.php?bo=".get_class($this->BO)."&oid=".$this->BO->get_ID()."'","Edit","editBut");
				echo '&nbsp;&nbsp;';
				$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}","Delete","deleteBut");
			}
		}else{
			echo '<input type="button" name="openBut" value="Open" onclick="document.location.replace(\''.$sysURL.'\'/controller/view_article.php?oid='.$this->BO->get_ID().(isset($_GET["no-forceframe"]) ? "&no-forceframe=true" : "").'\')"/>';
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
				echo '&nbsp;&nbsp;';
				echo '<input type="submit" name="editBut" value="Edit"/>';
				echo '&nbsp;&nbsp;';
				echo '<input type="button" name="deleteBut" value="Delete" onclick="if(confirm(\'Are you sure you wish to delete this item?\')) {document.getElementById(\'delete_oid\').value = \''.$this->BO->get_ID().'\'; document.getElementById(\'delete_form\').submit();}"/>';
			}
		}
		echo '</td></tr>';
		
		$this->render_security_fields();
		
		echo '</form>';
		echo '</table>';
	}
	
	/**
	 * renders the custom create view
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
						echo '<tr><td colspan="2">';
						echo $this->BO->data_labels[$prop];
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="115" rows="18" name="'.$prop.'">'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'</textarea><br>';
						echo '</td></tr>';
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
						$enum_vals = $this->BO-> $prop->get_options();
						asort($enum_vals);
						foreach ($enum_vals as $enum_val) {
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
		echo '<p>Please note that you will only be able to attach files to the article once it has been created.</p>';
	}
	
	/**
	 * renders to custom edit view with attachments options
	 */
	function edit_view() {
		global $sysURL;		

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if ($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num") {

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
						echo '<tr><td colspan="2">';
						echo $this->BO->data_labels[$prop];
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="115" rows="18" name="'.$prop.'">'.$this->BO->get($prop).'</textarea><br>';
						echo '</td></tr>';
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
						$prop_obj = $this->BO->get_prop_object($prop);
						$enum_vals = $prop_obj->get_options();
						asort($enum_vals);
						foreach ($enum_vals as $enum_val) {
							if ($prop_obj->get_value() == $enum_val)
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
		
		// file upload section
		
		echo '<tr><td colspan="2">&nbsp;</td></tr><tr><th colspan="2" style="text-align:left;">File Attachments:</th></tr>';
		
		if (is_dir($this->BO->get_attachments_location())) {
			$handle = opendir($this->BO->get_attachments_location());
	   		
	        // loop over the business object directory
		    while (false !== ($file = readdir($handle))) {
		    	if($file != '.' && $file != '..') {
			    	echo '<tr><td>';		    	
					echo '&nbsp;'.$file.'&nbsp;<em>('.number_format(filesize($this->BO->get_attachments_location().'/'.$file)/1024).' KB)</em>';
					echo '</td>';
					echo '<td>';
					if (class_exists("button")) {
						$temp = new button("if(confirm('Are you sure you want to delete the file ".$file."')) { document.forms[0]['file_to_delete'].value = '".$file."'; document.forms[0].submit();}", "Delete", "deleteBut");
					}else{
						echo '<input name="deleteBut" type="submit" value="Delete" onclick=""/>';
					}
					echo '</td></tr>';
		    	}
		    }
			
			echo '<tr><td>';
			echo 'Attachment file location';
			echo '</td>';
		}else{
			// we will take this opportunity to create the attachments folder is it does
			// not already exist.
			$this->BO->create_attachments_folder();
		}

		echo '<td>';
		echo '<input name="userfile" type="file" value="Browse..." size="70"/>';
		echo '</td></tr>';
		
		echo '<tr><td colspan="2">';
		if (class_exists("button")) {
			$temp = new button("submit", "Upload", "uploadBut");
		} else {
			echo '<input type="submit" name="uploadBut" value="Upload"/>';
		}
		echo '</td></tr>';
		
		echo '<input type="hidden" name="file_to_delete" value=""/>';

		$this->render_security_fields();
		
		echo '</form></table>';
	}
}

?>
