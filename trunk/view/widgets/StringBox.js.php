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
class StringBox
{
	/**
	 * the string object that will be edited by this text box
	 * @var String
	 */
	var $stringObject;
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
	 */
	function StringBox($string, $label, $name, $form_id, $size=0) {
		$this->setStringObject($string);
		$this->set_label($label);
		$this->set_name($name);
		$this->set_form_id($form_id);
		$this->set_size($size);
	}
	
	/**
	 * Renders the HTML and javascript for the string box
	 * 
	 * @param bool $tableTags determines if table tags are also rendered for the StringBox
	 * @param bool $readOnly set to true to make the text box readonly (defaults to false)
	 * @return string
	 */
	public function render($tableTags=true, $readOnly=false) {
		$html = '';
		
		$string_obj = $this->getStringObject(); 
		if ($tableTags) {
			$html .= '<tr><th style="width:25%;">';
			$html .= $this->get_label();
			$html .= '</th>';
	
			$html .= '<td>';
			$html .= '<input '.($string_obj->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->get_name().'" id="'.$this->get_name().'" value="'.((isset($_POST[$this->get_name()]) && $string_obj->getValue() == "")? $_POST[$this->get_name()] : $string_obj->getValue()).'"'.($readOnly ? 'readonly class="readonly"' : '').'/>';
			$html .= '</td></tr>';
		}else{
			$html .= '<input '.($string_obj->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->get_name().'" id="'.$this->get_name().'" value="'.((isset($_POST[$this->get_name()]) && $string_obj->getValue() == "")? $_POST[$this->get_name()] : $string_obj->getValue()).'"'.($readOnly ? 'readonly class="readonly"' : '').'/>';
		}
		
		if($this->getStringObject()->getRule() != '') {
			$html .= '<input type="hidden" id="'.$this->get_name().'_msg" value="'.$this->getStringObject()->getHelper().'"/>';
			$html .= '<input type="hidden" id="'.$this->get_name().'_rule" value="'.$this->getStringObject()->getRule().'"/>';
		}
		
		return $html;
	}
	
	/**
	 * setter for string object
	 * @param string $string
	 */
	function setStringObject($string)
	{
		$this->stringObject = $string;
	}

	/**
	 * getter for string object
	 * @return String
	 */
	function getStringObject() {
		return $this->stringObject;
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
