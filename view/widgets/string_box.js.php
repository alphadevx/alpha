<?php

// $Id$

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

require_once $config->get('sysRoot').'alpha/model/types/String.inc';

/**
* String HTML input box custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2008 John Collins
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
	 * @param String $string the string object that will be edited by this text box
	 * @param string $label the data label for the string object
	 * @param string $name the name of the HTML input box
	 * @param string $form_id the id of the form that contains this string box
	 * @param int $size the display size (characters)
	 * @param bool $table_tags determines if table tags are also rendered for the string_box
	 * @param bool $read_only set to true to make the text box readonly (defaults to false)
	 */
	function string_box($string, $label, $name, $form_id, $size=0, $table_tags=true, $read_only=false) {
		$this->set_string_object($string);
		$this->set_label($label);
		$this->set_name($name);
		if (!empty($form_id))
			$this->set_form_id($form_id);
		else
			$this->render_javascript();
		$this->set_size($size);
		
		$this->render($table_tags, $read_only);
	}
	
	/**
	 * renders the HTML and javascript for the string box
	 * @param bool $table_tags determines if table tags are also rendered for the string_box
	 * @param bool $read_only set to true to make the text box readonly (defaults to false)
	 */
	function render($table_tags=true, $read_only=false) {
		$string_obj = $this->get_string_object(); 
		if ($table_tags) {
			echo '<tr><td style="width:25%;">';
			echo $this->get_label();
			echo '</td>';
	
			echo '<td>';
			echo '<input '.($string_obj->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->get_name().'" id="'.$this->get_name().'" value="'.((isset($_POST[$this->get_name()]) && $string_obj->getValue() == "")? $_POST[$this->get_name()] : $string_obj->getValue()).'"'.($read_only ? 'readonly class="readonly"' : '').'/>';
			echo '</td></tr>';
		}else{
			echo '<input '.($string_obj->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->get_name().'" id="'.$this->get_name().'" value="'.((isset($_POST[$this->get_name()]) && $string_obj->getValue() == "")? $_POST[$this->get_name()] : $string_obj->getValue()).'"'.($read_only ? 'readonly class="readonly"' : '').'/>';
		}
	}
	
	/**
	 * renders the Javascript to control the behaviour of the text box
	 */
	function render_javascript() {		
		// begining of javascript
		// ----------------------
		echo '<script language="javascript">';
		
		echo " validation_rules[\"".$this->get_name()."\"] = ".$this->string_object->getRule().";\n";
		echo " validation_rules[\"".$this->get_name()."_msg\"] = \"".$this->string_object->getHelper()."\";\n";
		echo '</script>';
	}
	
	/**
	 * setter for string object
	 * @param string $string
	 */
	function set_string_object($string)
	{
		$this->string_object = $string;
	}

	/**
	 * getter for string object
	 * @return string string
	 */
	function get_string_object() {
		return $this->string_object;
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
	 * @return string form_id
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
