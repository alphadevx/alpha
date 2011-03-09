<?php

require_once $config->get('sysRoot').'alpha/util/InputFilter.inc';
require_once $config->get('sysRoot').'alpha/model/types/Text.inc';

/**
 * Text HTML input box custom widget
 * 
 * @package alpha::view::widgets
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
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
class TextBox {
	/**
	 * The text object that will be edited by this text box
	 * 
	 * @var Text
	 * @since 1.0
	 */
	var $textObject;
	
	/**
	 * The data label for the text object
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
	 * The amount of rows to display by default
	 * 
	 * @var integer
	 * @since 1.0
	 */
	var $rows;
	
	/**
	 * An optional additional idenitfier to append to the id of the text box where many are on one page
	 * 
	 * @var integer
	 * @since 1.0
	 */
	var $identifier;
	
	/**
	 * The constructor
	 * 
	 * @param Text $text The text object that will be edited by this text box.
	 * @param string $label The data label for the text object.
	 * @param string $name The name of the HTML input box.
	 * @param integer $rows The display size (rows).
	 * @param integer $identifier An additional idenitfier to append to the id of the text box.
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function __construct($text, $label, $name, $rows=5, $identifier=0) {
		if($text instanceof Text)
			$this->textObject = $text;
		else
			throw new IllegalArguementException('Text object passed ['.var_export($text, true).'] is not a valid Text object!');
			
		$this->label = $label;
		$this->name = $name;
		$this->rows = $rows;		
		$this->identifier = $identifier;
	}
	
	/**
	 * Renders the HTML and javascript for the text box
	 * 
	 * @param bool $tableTags determines if table tags are also rendered for the TextBox
	 * @param bool $markdown set to true if you want to render additional buttons for Markdown content
	 * @return string
	 * @since 1.0
	 */
	public function render($tableTags=true, $markdown=false) {
		global $config;
		
		$html = '';
		
		if($tableTags) {
			$html .= '<tr><th colspan="2">';
			$html .= $this->label;
			$html .= '</th></tr>';	
			
			$html .= '<tr><td colspan="2">';
		}
		
		$html .= '<textarea id="text_field_'.$this->name.'_'.$this->identifier.'" style="width:100%;" rows="'.$this->rows.'" name="'.$this->name.'">';
		
		if($this->textObject->getAllowHTML())
			$html .= InputFilter::decode($this->textObject->getValue(), true);
		else
			$html .= InputFilter::decode($this->textObject->getValue());
			
		$html .= '</textarea><br>';
		
		if($tableTags) {
			$html .= '</td></tr>';
			$html .= '<tr><td colspan="2">';
		}
		$increaseTextArea = new button("document.getElementById('text_field_".$this->name."_".$this->identifier."').rows = (parseInt(document.getElementById('text_field_".$this->name."_".$this->identifier."').rows) + 10);", "Increase text area", $this->name."IncBut", $config->get('sysURL')."/alpha/images/icons/arrow_down.png");
		$html .= $increaseTextArea->render();
		$decreaseTextArea = new button("if(document.getElementById('text_field_".$this->name."_".$this->identifier."').rows > 10) {document.getElementById('text_field_".$this->name."_".$this->identifier."').rows = (parseInt(document.getElementById('text_field_".$this->name."_".$this->identifier."').rows) - 10)};", "Decrease text area", $this->name."DecBut", $config->get('sysURL')."/alpha/images/icons/arrow_up.png");
		$html .= $decreaseTextArea->render();
		
		// additional buttons for Markdown content fields
		if($markdown) {
			$syntaxHelp = new button("window.open('".$config->get('sysURL')."/alpha/controller/view_article_title.php?title=Markdown: Syntax&no-forceframe=true','helpWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=700,height=400,left=20,top=20');", "Syntax help", "helpBut", $config->get('sysURL')."/alpha/images/icons/help.png");
			$html .= $syntaxHelp->render();
		}
		if($tableTags)
			$html .= '</td></tr>';

		if($this->textObject->getRule() != '') {
			$html .= '<input type="hidden" id="'.$this->name.'_msg" value="'.$this->textObject->getHelper().'"/>';
			$html .= '<input type="hidden" id="'.$this->name.'_rule" value="'.$this->textObject->getRule().'"/>';
		}
		
		return $html;
	}
	
	/**
	 * Setter for text object
	 * 
	 * @param string $text
	 * @since 1.0
	 * @throws IllegalArguementException
	 */
	public function setTextObject($text) {
		if($text instanceof Text)
			$this->text = $text;
		else
			throw new IllegalArguementException('Text object passed ['.var_export($text, true).'] is not a valid Text object!');
	}

	/**
	 * Getter for text object
	 * 
	 * @return Text
	 * @since 1.0
	 */
	function getTextObject() {
		return $this->textObject;
	}
}

?>
