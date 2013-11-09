<?php

/**
 * A HTML widget for rendering a text box with calendar icon for Date/Timestamp types
 * 
 * @package alpha::view::widgets
 * @since 1.0
 * @author John Collins <dev@alphaframework.org>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2012, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class DateBox {	
	/**
	 * The date or timestamp object for the widget.
	 * 
	 * @var Date/Timestamp
	 * @since 1.0
	 */
	var $dateObject = null;	
	
	/**
	 * The data label for the object
	 * 
	 * @var string
	 * @since 1.0
	 */
	var $label;
	
	/**
	 * The name of the HTML input box
	 * 
	 * @var string
	 * @since 1.0
	 */
	var $name;
	
	/**
	 * The constructor
	 * 
	 * @param Date/Timestamp $object The date or timestamp object that will be edited by this widget.
	 * @param string $label The data label for the object.
	 * @param string $name The name of the HTML input box.	
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function __construct($object, $label="", $name="") {
		
		// check the type of the object passed
		if($object instanceof Date || $object instanceof Timestamp) {
			$this->dateObject = $object;
		}else{
			throw new IllegalArguementException('DateBox widget can only accept a Date or Timestamp object!');
		}
		
		$this->label = $label;
		$this->name = $name;
	}
	
	/**
	 * Renders the text box and icon to open the calendar pop-up
	 *
	 * @param boolean $tableTags
	 * @return string
	 * @since 1.0
	 */
	public function render($tableTags=true) {
		global $config;
		
		$html = '';		
		
		/*
		 * decide on the size of the text box and the height of the widget pop-up, 
		 * depending on the dateObject type
		 */
		if(strtoupper(get_class($this->dateObject)) == "TIMESTAMP") {
			$size = 18;
			$cal_height = 230;
		}else{
			$size = 10;
			$cal_height = 230;
		}
		
		$value = $this->dateObject->getValue();
		if($value == '0000-00-00')
			$value = '';
		
		if($tableTags) {
			$html .= '<tr><th style="width:25%;">';
			$html .= $this->label;
			$html .= '</th>';

			$html .= '<td>';
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$html .= '<script language="javascript">';
			if($this->dateObject instanceof Timestamp)
				$html .= "$('#".$this->name."').datepicker({dateFormat:'yy-mm-dd HH:II:SS',showOn:'button',buttonImage:'".$config->get('app.url')."alpha/images/icons/calendar.png'})";
			else
				$html .= '$(document).ready(function(){$(\'#'.$this->name.'\').datepicker({dateFormat:\'yy-mm-dd\',defaultDate:\''.$value.'\',showOn:\'button\',buttonImageOnly:\'true\',buttonImage:\''.$config->get('app.url').'alpha/images/icons/calendar.png\'})});';
			$html .= '</script>';
			$html .= '</td></tr>';
		}else{
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$html .= '<script language="javascript">';
			if($this->dateObject instanceof Timestamp)
				$html .= "$('#".$this->name."').datepicker({dateFormat:'yy-mm-dd HH:II:SS',showOn:'button',buttonImage:'".$config->get('app.url')."alpha/images/icons/calendar.png'})";
			else
				$html .= '$(document).ready(function(){$(\'#'.$this->name.'\').datepicker({dateFormat:\'yy-mm-dd\',defaultDate:\''.$value.'\',showOn:\'button\',buttonImageOnly:\'true\',buttonImage:\''.$config->get('app.url').'alpha/images/icons/calendar.png\'})});';
			$html .= '</script>';
		}
		
		return $html;
	}
}

?>