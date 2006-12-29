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
		
		echo '<blockquote class="usercomment">';
		
		echo '<p>Posted by '.$author->get("displayname").' at '.$this->BO->get_create_TS()->get_value().'</p>';
		echo $markdown->get_content();
		
		echo '</blockquote>';
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
}

?>
