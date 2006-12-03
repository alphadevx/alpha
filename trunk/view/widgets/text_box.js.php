<?php

// $Id$

require_once $sysRoot.'alpha/util/handle_error.inc';

require_once $sysRoot.'alpha/model/types/String.inc';

/**
* Text HTML input box custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*  
*/
class string_box
{
	/**
	 * the string object that will be edited by this text box
	 * @var string
	 */
	var $string_object;
	/**
	 * the data label for the string object
	 * @var string
	 */
	var $label;
	/**
	 * the name of the HTML input box
	 * @var string
	 */
	var $name;
	/**
	 * the display size of the input box
	 * @var int
	 */
	var $size;
	
	/**
	 * the constructor
	 * @param Text $text the text object that will be edited by this text box
	 * @param string $label the data label for the text object
	 * @param string $name the name of the HTML input box
	 * @param string $form_id the id of the form that contains this text box
	 * @param int $size the display size (characters)
	 * @param bool $table_tags determines if table tags are also rendered for the text_box
	 */
	function text_box($text, $label, $name, $form_id, $size=90, $table_tags=true) {
		$this->set_text_object($text);
		$this->set_label($label);
		$this->set_name($name);
		if (!empty($form_id))
			$this->set_form_id($form_id);
		else
			$this->render_javascript();
		$this->set_size($size);
		
		$this->render($table_tags);
	}
	
	/**
	 * renders the HTML and javascript for the text box	 
	 */
	function render() {
		$text_obj = $this->get_text_object(); 
		
		echo '<tr><td colspan="2">';
		echo "groovey".$this->get_label();
		echo '</td></tr>';
	
		echo '<tr><td colspan="2">';
		echo '<textarea cols="90" rows="5" name="'.$this->get_name().'">'.htmlspecialchars(((isset($_POST[$this->get_name()]) && $text_obj->get_value() == "")? $_POST[$this->get_name()] : $text_obj->get_value())).'</textarea><br>';
		echo '</td></tr>';		
	}
	
	/**
	 * renders the Javascript to control the behaviour of the text box
	 */
	function render_javascript() {		
		// begining of javascript
		// ----------------------
		echo '<script language="javascript">';
		
		echo " validation_rules[\"".$this->get_name()."\"] = ".$this->text_object->get_rule().";\n";
		echo " validation_rules[\"".$this->get_name()."_msg\"] = \"".$this->text_object->get_helper()."\";\n";
		echo '</script>';
	}
	
	/**
	 * setter for text object
	 * @param string $text
	 */
	function set_text_object($text)
	{
		$this->text_object = $text;
	}

	/**
	 * getter for text object
	 * @return text text
	 */
	function get_text_object() {
		return $this->text_object;
	}
	
	/**
	 * setter for form_id
	 * @param string $form_id
	 */
	function set_form_id($form_id)
	{
		$this->form_id = $form_id;
	}

	/**
	 * getter for form_id
	 * @return text form_id
	 */
	function get_form_id() {
		return $this->form_id;
	}
	
	/**
	 * setter for label
	 * @param string $label
	 */
	function set_label($label)
	{
		$this->label = $label;
	}

	/**
	 * getter for label
	 * @return string label
	 */
	function get_label() {
		return $this->label;
	}
	
	/**
	 * setter for name
	 * @param string $name
	 */
	function set_name($name)
	{
		$this->name = $name;
	}

	/**
	 * getter for name
	 * @return string name
	 */
	function get_name() {
		return $this->name;
	}
	
	/**
	 * setter for size
	 * @param string $size
	 */
	function set_size($size)
	{
		$this->size = $size;
	}

	/**
	 * getter for size
	 * @return string size
	 */
	function get_size() {
		return $this->size;
	}
}

?>
