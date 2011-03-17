<?php

require_once $config->get('sysRoot').'alpha/model/types/String.inc';

/**
 * String HTML input box custom widget
 * 
 * @package alpha::view::widgets
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2011, John Collins (founder of Alpha Framework).  
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
class StringBox {
	/**
	 * The string object that will be edited by this string box
	 * 
	 * @var String
	 * @since 1.0
	 */
	public $stringObject;
	
	/**
	 * The data label for the string object
	 * 
	 * @var string
	 * @since 1.0
	 */
	public $label;
	
	/**
	 * The name of the HTML input box
	 * 
	 * @var string
	 * @since 1.0
	 */
	public $name;
	
	/**
	 * The display size of the input box
	 * 
	 * @var integer
	 * @since 1.0
	 * 
	 */
	public $size;
	
	/**
	 * The constructor
	 * 
	 * @param String $string The string object that will be edited by this text box.
	 * @param string $label The data label for the string object.
	 * @param string $name The name of the HTML input box.
	 * @param integer $size The display size (characters).
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function __construct($string, $label, $name, $size=0) {
		if($string instanceof String)
			$this->stringObject = $string;
		else
			throw new IllegalArguementException('String object passed ['.var_export($string, true).'] is not a valid String object!');
		
		$this->label = $label;
		$this->name = $name;
		$this->size = $size;
	}
	
	/**
	 * Renders the HTML and javascript for the string box
	 * 
	 * @param bool $tableTags determines if table tags are also rendered for the StringBox
	 * @param bool $readOnly set to true to make the text box readonly (defaults to false)
	 * @return string
	 * @since 1.0
	 */
	public function render($tableTags=true, $readOnly=false) {
		$html = '';
		 
		if ($tableTags) {
			$html .= '<tr><th style="width:25%;">';
			$html .= $this->label;
			$html .= '</th>';
	
			$html .= '<td>';
			$html .= '<input '.($this->stringObject->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->name.'" id="'.$this->name.'" value="'.((isset($_POST[$this->name]) && $this->stringObject->getValue() == "")? $_POST[$this->name] : $this->stringObject->getValue()).'"'.($readOnly ? 'readonly class="readonly"' : '').'/>';
			$html .= '</td></tr>';
		}else{
			$html .= '<input '.($this->stringObject->checkIsPassword()? 'type="password"':'type="text"').($this->size == 0 ? ' style="width:100%;"' : ' size="'.$this->size.'"').' maxlength="'.String::MAX_SIZE.'" name="'.$this->name.'" id="'.$this->name.'" value="'.((isset($_POST[$this->name]) && $this->stringObject->getValue() == "")? $_POST[$this->name] : $this->stringObject->getValue()).'"'.($readOnly ? 'readonly class="readonly"' : '').'/>';
		}
		
		if($this->stringObject->getRule() != '') {
			$html .= '<input type="hidden" id="'.$this->name.'_msg" value="'.$this->stringObject->getHelper().'"/>';
			$html .= '<input type="hidden" id="'.$this->name.'_rule" value="'.$this->stringObject->getRule().'"/>';
		}
		
		return $html;
	}
	
	/**
	 * Setter for string object
	 * 
	 * @param String $string
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setStringObject($string) {
		if($string instanceof String)
			$this->stringObject = $string;
		else
			throw new IllegalArguementException('String object passed ['.var_export($string, true).'] is not a valid String object!');
	}

	/**
	 * Qetter for string object
	 * 
	 * @return String
	 * @since 1.0
	 */
	public function getStringObject() {
		return $this->stringObject;
	}
}

?>
