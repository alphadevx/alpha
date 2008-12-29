<?php

// $Id$

require_once $config->get('sysRoot').'alpha/model/person_object.inc';
require_once $config->get('sysRoot').'alpha/view/View.inc';
require_once $config->get('sysRoot').'alpha/util/markdown_facade.inc';


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
		global $config;
		
		$markdown = new markdown_facade($this->BO);
		 
		echo $markdown->get_content();
		
		if (class_exists("button")) {
			// render edit buttons for admins only
			if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->getAccessLevel() == "Administrator" && $this->BO->getID() != 0) {
				echo '&nbsp;&nbsp;';
				$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=Edit&bo='.get_class($this->BO).'&oid='.$this->BO->getID())."'","Edit","editBut");
			}
		}
	}
	
	/**
	 * a method for generating the special view of an article on the "new additions" page
	 * @param int $count whether this new additions view is the first or second one on the page (only two are expected)
	 */
	function new_additions_view($count=1) {
		global $config;
		
		echo '<div id="article'.$count.'Div" style="border: 2px solid white;">';
		echo '<h2>'.$this->BO->get("title").'</h2>';
		echo '<p>'.$this->BO->get("description").'</p>';
		echo '<table style="border: 1px solid white; width:98%; table-layout:fixed; margin:1%; position:absolute; bottom:0px;" cols="3">';
		echo '<tr>';
		$prop_obj = $this->BO->get_prop_object("date_added");
		echo '<td>Date Added: <em>'.$prop_obj->getValue().'</em></td>';
		$prop_obj = $this->BO->get_prop_object("section");
		echo '<td align="center">Site Section: <em>'.$prop_obj->getDisplayValue().'</em></td>';
		echo '<td align="right">';
		if (class_exists("button")) {			
			$temp = new button("document.location.replace('".$config->get('sysURL')."/alpha/controller/view_article.php?oid=".$this->BO->get_ID()."')","Open","openBut");
		}else{			
			echo '<input type="button" name="openBut" value="Open" onclick="document.location.replace(\''.$config->get('sysURL').'\'/controller/view_article.php?oid='.$this->BO->get_ID().'\')"/>';
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
		global $config;		
		
		$properties = get_object_vars($this->BO);
		$obj_type = '';
		
		$col_count = 1;

		echo '<table class="list_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		echo '<tr>';
		echo '<th>'.$this->BO->get("title").'</th>';
		echo '</tr><tr>';
		$prop_obj = $this->BO->get_prop_object("date_added");
		echo '<td>'.$this->BO->get("description").'<br><br>'.$this->BO->data_labels["section"].': <em>'.$this->BO->section->get_display_value().'</em>&nbsp;&nbsp;&nbsp;'.$this->BO->data_labels["date_added"].': <em>'.$prop_obj->getValue().'</em></td>';	
		echo '</tr>';
		
		echo '<tr><td colspan="'.$col_count.'" align="center">';
		
		$temp = new button("document.location.replace('".$config->get('sysURL')."/alpha/controller/view_article.php?oid=".$this->BO->get_ID().(isset($_GET["no-forceframe"]) ? "&no-forceframe=true" : "")."')","Open","openBut");
		// render edit and delete buttons for admins only
		if (isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_access_level() == "Administrator") {
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=edit_article_object&bo='.get_class($this->BO).'&oid='.$this->BO->get_ID())."'","Edit","editBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}","Delete","deleteBut");
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
		global $config;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="create_view">';
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
						$temp = new calendar($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
					break;
					case "TIMESTAMP" :
						$temp = new calendar($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop);
					break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("StringBox")) {
							$temp = new StringBox($this->BO-> $prop, $this->BO->data_labels[$prop], $prop, "");
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
						if (class_exists("TextBox")) {
							$rows = 18;
							if ($prop == "header_content")
								$rows = 5;
							$markdown_content = false;
							if ($prop == "content")
								$markdown_content = true;
							$temp = new TextBox($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "", $rows, true, 0, $markdown_content);
						} else {
							echo '<tr><td colspan="2">';
							echo $this->BO->data_labels[$prop];
							echo '</td></tr>';
	
							echo '<tr><td colspan="2">';
							echo '<textarea cols="115" rows="18" name="'.$prop.'">'. (isset ($_POST[$prop]) ? $_POST[$prop] : '').'</textarea><br>';
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
						$enum_vals = $this->BO-> $prop->get_options();						
						foreach ($enum_vals as $enum_val) {
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
			$temp = new button("document.location.replace('".$config->get('sysURL')."/alpha/controller/ListBusinessObjects.php')", "Cancel", "cancelBut");
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
		global $config;		

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view">';
		echo '<form id="editArticle" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->getDefaultAttributes()) && !in_array($prop, $this->BO->getTransientAttributes())) {

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
						$temp = new calendar($this->BO->getPropObject($prop), $this->BO->getDataLabel($prop), $prop);						
						echo $temp->render();
					break;
					case "TIMESTAMP" :
						$temp = new calendar($this->BO->getPropObject($prop), $this->BO->getDataLabel($prop), $prop);
						echo $temp->render();
					break;
					case "STRING" :
						// use a string widget if defined
						if (class_exists("StringBox")) {
							$temp = new StringBox($this->BO->getPropObject($prop), $this->BO->getDataLabel($prop), $prop, "editArticle");
							echo $temp->render();
						} else {
							echo '<tr><td style="width:25%;">';
							echo $this->BO->getDataLabel($prop);
							echo '</td>';

							echo '<td>';
							echo '<input type="text" size="'.$this->BO->getPropObject($prop)->getSize().'" name="'.$prop.'" value="'.$this->BO->get($prop).'"/><br>';
							echo '</td></tr>';
						}
					break;
					case "TEXT" :
						// use a text widget if defined
						if (class_exists("TextBox")) {
							$rows = 18;
							if ($prop == "header_content")
								$rows = 5;
							$markdown_content = false;
							$temp = new TextBox($this->BO->getPropObject($prop), $this->BO->getDataLabel($prop), $prop, "editArticle", $rows, 0);
							if ($prop == "content")
								echo $temp->render(true,true);
							else
								echo $temp->render();
						} else {
							echo '<tr><td colspan="2">';
							echo $this->BO->getDataLabel($prop);
							echo '</td></tr>';
	
							echo '<tr><td colspan="2">';
							echo '<textarea cols="115" rows="18" name="'.$prop.'">'.$this->BO->get($prop).'</textarea><br>';
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
						$prop_obj = $this->BO->getPropObject($prop);
						$enum_vals = $prop_obj->getPropObject();						
						foreach ($enum_vals as $enum_val) {
							if ($prop_obj->getValue() == $enum_val)
								echo '<option value="'.$enum_val.'" selected>'.$enum_val.'</option>';
							else
								echo '<option value="'.$enum_val.'">'.$enum_val.'</option>';
						}
						echo '</select><br>';
						echo '</td></tr>';
					break;
					case "DENUM" :
						echo '<tr><td style="width:25%;">';
						echo $this->BO->getDataLabel($prop);
						echo '</td>';
						echo '<td>';
						echo '<select name="'.$prop.'"/>';
						$denum = $this->BO->getPropObject($prop);
						$denum_vals = $denum->getOptions();						
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
						echo $this->BO->getDataLabel($prop);
						echo '</td></tr>';

						echo '<tr><td colspan="2">';
						echo '<textarea cols="100" rows="3" name="'.$prop.'">'.$this->BO->get($prop).'</textarea><br>';
						echo '</td></tr>';
					break;
				}
			}
			if ($prop == "version_num")
				echo '<input type="hidden" name="version_num" value="'.$this->BO->getVersion().'"/>';
		}

		echo '<tr><td colspan="2">';		
		$temp = new button("submit", "Save", "saveBut");
		echo $temp->render();
		echo '&nbsp;&nbsp;';
		$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->getID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
		echo $temp->render();
		echo '&nbsp;&nbsp;';
		$temp = new button("document.location = '".Front_Controller::generate_secure_URL('act=ListAll&bo='.get_class($this->BO))."'", "Back to List", "cancelBut");
		echo $temp->render();
		
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
						echo $temp->render();
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
			echo $temp->render();
		} else {
			echo '<input type="submit" name="uploadBut" value="Upload"/>';
		}
		echo '</td></tr>';
		
		echo '<input type="hidden" name="file_to_delete" value=""/>';
		echo View::renderSecurityFields();
		
		echo '</form></table>';
	}
}

?>
