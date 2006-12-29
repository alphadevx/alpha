<?php

// $Id$

require_once $sysRoot.'alpha/model/article_comment_object.inc';
require_once $sysRoot.'alpha/model/person_object.inc';
require_once $sysRoot.'alpha/view/View.inc';
require_once $sysRoot.'alpha/util/markdown_facade.inc';

/**
 *
 * The rendering class for the news_object class
 * 
 * @package Design-Ireland
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
class article_comment extends View
{
	/**
	 * the render mode for the object view, default is HTML
	 * @var string
	 */
	var $render_mode = 'HTML';

	/**
	 * constructor for the article_comment	
	 * @param article_comment_object $article_comment_object the article_comment business object for rendering
	 */
	function article_comment($article_comment_object)
	{
		$this->BO = $article_comment_object;
	}
	
	/**
	 * method to generate the markdown HTML render of the article_comment content
	 */
	function markdown_view() {
		$markdown = new markdown_facade($this->BO);
		$author = new person_object();
		$author->load_object($this->BO->get_creator_ID()->get_value());
		
		echo '<center><blockquote class="usercomment">';
		
		echo '<p>Posted by '.($author->get("URL") == ""? $author->get("displayname") : '<a href="'.$author->get("URL").'" target="new window">'.$author->get("displayname").'</a>').' at '.$this->BO->get_create_TS()->get_value().'</p>';
		if(isset($_SESSION["current_user"]) && $_SESSION["current_user"]->get_ID() == $author->get_ID())
			$this->edit_view();
		else
			echo $markdown->get_content();
		
		echo '</blockquote></center>';
	}
	
	/**
	 * renders the custom create view
	 */
	function create_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';
		
		echo "<h2>Post a new comment:</h2>";

		echo '<table cols="2" class="create_view">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->default_attributes) && $prop != "article_oid") {

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
							$rows = 10;							
							$temp = new text_box($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop, "", $rows);
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
			if($prop == "article_oid")
				echo '<input type="hidden" name="article_oid" value="'.$this->BO->get("article_oid").'"/>';
		}
		
		echo '<tr><td colspan="2">';
		
		$temp = new button("submit", "Post Comment", "createBut");
			
		echo '</td></tr>';

		$this->render_security_fields();
		
		echo '</form></table>';
		echo '<p>Please note that any comment you post may be moderated for spam or offensive material.</p>';
	}
	
	/**
	 * custom edit view
	 */
	function edit_view() {
		global $sysURL;

		$properties = get_object_vars($this->BO);
		$obj_type = '';

		echo '<table cols="2" class="edit_view" style="width:100%; margin:0px">';
		echo '<form action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" method="POST">';
		foreach (array_keys($properties) as $prop) {
			if (!in_array($prop, $this->BO->default_attributes) && $prop != "article_oid") {

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
						// use a string widget if defined
						if (class_exists("text_box")) {
							// need to decode the text for the comment
							$text = $this->BO->get_prop_object($prop);
							$filter = new input_filter($text);
							$text->set_value($filter->decode());
							$temp = new text_box($this->BO->get_prop_object($prop), $this->BO->data_labels[$prop], $prop."_".$this->BO->get_ID(), "");
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
			if($prop == "article_oid")
				echo '<input type="hidden" name="article_oid" value="'.$this->BO->get("article_oid").'"/>';
		}

		echo '<tr><td colspan="2">';
		
		$temp = new button("submit", "Update Your Comment", "saveBut");
		echo '&nbsp;&nbsp;';
		
		// render special buttons for admins only
		if ($_SESSION["current_user"]->get_access_level() == "Administrator") {
			$temp = new button("if(confirm('Are you sure you wish to delete this item?')) {document.getElementById('delete_oid').value = '".$this->BO->get_ID()."'; document.forms[0].submit();}", "Delete", "deleteBut");
			echo '&nbsp;&nbsp;';
			$temp = new button("document.location = '".$sysURL."/alpha/controller/ListAll.php?bo=".get_class($this->BO)."'", "Back to List", "cancelBut");
			echo '</td></tr>';
		}

		$this->render_security_fields();
		
		echo '</form></table>';
	}
}

?>
