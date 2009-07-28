<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';

/**
 * Calendar HTML custom widget
 * 
 * @package alpha::view::widgets
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 *  
 */
class calendar{
	
	/**
	 * the date or timestamp object for the widget
	 * @var Date/Timestamp
	 */
	var $date_object = null;	
	
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
	 * the constructor
	 * @param object $object the date or timestamp object that will be edited by this calender
	 * @param string $label the data label for the date object
	 * @param string $name the name of the HTML input box	
	 */
	function calendar($object, $label="", $name="") {
		
		// check the type of the object passed
		if(strtoupper(get_class($object)) == "DATE" || strtoupper(get_class($object)) == "TIMESTAMP"){
			$this->date_object = $object;
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Calendar widget can only accept a Date or Timestamp object!','calendar()','framework');
			exit;
		}
		$this->label = $label;
		$this->name = $name;
	}
	
	/**
	 * Renders the text box and icon to open the calendar pop-up
	 *
	 * @param bool $table_tags
	 * @return string
	 */
	function render($table_tags=true) {
		global $config;
		$html = '';		
		
		/*
		 * decide on the size of the text box and the height of the widget pop-up, 
		 * depending on the date_object type
		 */
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP") {
			$size = 18;
			$cal_height = 230;
		}else{
			$size = 10;
			$cal_height = 230;
		}
		
		$value = $this->date_object->getValue();
		if($value == '0000-00-00')
			$value = '';
		
		if($table_tags) {
			$html .= '<tr><td style="width:25%;">';
			$html .= $this->label;
			$html .= '</td>';

			$html .= '<td>';
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$html .= '<script language="javascript">';
			if($this->date_object instanceof Timestamp)
				$html .= "$('#".$this->name."').datepicker({dateFormat:'yy-mm-dd HH:II:SS',showOn:'button',buttonImage:'".$config->get('sysURL')."alpha/images/icons/calendar.png'})";
			else
				$html .= '$(document).ready(function(){$(\'#'.$this->name.'\').datepicker({dateFormat:\'yy-mm-dd\',defaultDate:\''.$value.'\',showOn:\'button\',buttonImageOnly:\'true\',buttonImage:\''.$config->get('sysURL').'alpha/images/icons/calendar.png\'})});';
			$html .= '</script>';
			$html .= '</td></tr>';
		}else{
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=205,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $config->get('sysURL')."/alpha/images/icons/calendar.png");
			$html .= $tmp->render();
		}
		
		return $html;
	}
}

?>