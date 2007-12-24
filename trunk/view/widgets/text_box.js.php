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
class text_box
{
	/**
	 * the text object that will be edited by this text box
	 * @var Text
	 */
	var $text_object;
	/**
	 * the data label for the text object
	 * @var string
	 */
	var $label;
	/**
	 * the name of the HTML input box
	 * @var string
	 */
	var $name;
	/**
	 * the amount of rows to display by default
	 * @var int
	 */
	var $rows;
	/**
	 * an optional additional idenitfier to append to the id of the text box where many are on one page
	 * @var int
	 */
	var $identifier;
	
	/**
	 * the constructor
	 * @param Text $text the text object that will be edited by this text box
	 * @param string $label the data label for the text object
	 * @param string $name the name of the HTML input box
	 * @param string $form_id the id of the form that contains this text box
	 * @param int $rows the display size (rows)
	 * @param bool $table_tags determines if table tags are also rendered for the text_box
	 * @param int $identifier an additional idenitfier to append to the id of the text box
	 */
	function text_box($text, $label, $name, $form_id, $rows=5, $table_tags=true, $identifier=0) {
		$this->set_text_object($text);
		$this->set_label($label);
		$this->set_name($name);
		$this->identifier = $identifier;
		if (!empty($form_id))
			$this->set_form_id($form_id);
		else
			$this->render_javascript();
		$this->set_rows($rows);
		
		$this->render($table_tags);
	}
	
	/**
	 * renders the HTML and javascript for the text box	 
	 */
	function render() {
		global $sysURL;
		
		$text_obj = $this->get_text_object(); 
			
		echo '<tr><td colspan="2">';
		echo $this->get_label();
		echo '</td></tr>';
	
		//echo '<a name="text_field_'.$this->get_name().'_'.$this->identifier.'" />';
		echo '<tr><td colspan="2">';
		echo '<textarea id="text_field_'.$this->get_name().'_'.$this->identifier.'" style="width:100%;" rows="'.$this->get_rows().'" name="'.$this->get_name().'">'.htmlspecialchars(((isset($_POST[$this->get_name()]) && $text_obj->get_value() == "")? $_POST[$this->get_name()] : $text_obj->get_value())).'</textarea><br>';
		echo '</td></tr>';
		echo '<tr><td colspan="2">';
		$tmp = new button("document.getElementById('text_field_".$this->get_name()."_".$this->identifier."').rows = (parseInt(document.getElementById('text_field_".$this->get_name()."_".$this->identifier."').rows) + 10);", "Increase text area", $this->get_name()."IncBut", $sysURL."/alpha/images/icons/arrow_down.png");
		$tmp = new button("if(document.getElementById('text_field_".$this->get_name()."_".$this->identifier."').rows > 10) {document.getElementById('text_field_".$this->get_name()."_".$this->identifier."').rows = (parseInt(document.getElementById('text_field_".$this->get_name()."_".$this->identifier."').rows) - 10)};", "Decrease text area", $this->get_name()."DecBut", $sysURL."/alpha/images/icons/arrow_up.png");
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
	 * setter for rows
	 * @param string $rows
	 */
	function set_rows($rows)
	{
		$this->rows = $rows;
	}

	/**
	 * getter for rows
	 * @return string rows
	 */
	function get_rows() {
		return $this->rows;
	}
}

?>
