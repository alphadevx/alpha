<?php

/**
 * Button HTML custom widget
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
class Button {
	/**
	 * The Javascript action to carry out when the button is pressed.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $action;
	
	/**
	 * The title to display on the button.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $title;
	
	/**
	 * The HTML id attribute for the button.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $id;
	
	/**
	 * If provided, the button will be a clickable image using this image.
	 * 
	 * @var string
	 * @since 1.0
	 */
	private $imgURL;
	
	/**
	 * The constructor
	 * 
	 * @param string $action The javascript action to be carried out (or set to "submit" to make a submit button, "file" for file uploads).
	 * @param string $title The title to appear on the button.
	 * @param string $id The HTML id attribute for the button.
	 * @param string $imgURL If provided, the button will be a clickable image using this image.
	 * @since 1.0
	 */
	public function __construct($action, $title, $id, $imgURL='') {
		$this->action = $action;
		$this->title = $title;
		$this->id = $id;
		$this->imgURL = $imgURL;			
		$this->title = $title;
	}
	
	/**
	 * Renders the HTML and javascript for the button.
	 * 
	 * @param integer $width The width in pixels of the button (will also accept percentage values), defaults to 0 meaning auto-width to fit text.
	 * @since 1.0
	 * @return string
	 */
	public function render($width=0) {
		$html = '';
		
		if(empty($this->imgURL)) {
			switch ($this->action) {
				case 'submit':
					$html .= '<script>
						$(function() {
							$("#'.$this->id.'").button();
						});
						</script>
					';
					$html .= '<input type="submit" id="'.$this->id.'" name="'.$this->id.'" value="'.$this->title.'"'.($width == 0? '':' style="width:'.$width.';"').'/>';
				break;
				case 'file':
					$html .= '<script>
						$(function() {
							$("#'.$this->id.'").button();
						});
						</script>
					';
					$html .= '<input type="file" id="'.$this->id.'" name="'.$this->id.'" value="'.$this->title.'"'.($width == 0? '':' style="width:'.$width.';"').'/>';
				break;
				default:
					$html .= '<script>
						$(function() {
							$("#'.$this->id.'").button();
							$("#'.$this->id.'").click(function() { '.$this->action.'; });
						});
						</script>
					';
					$html .= '<input type="button" id="'.$this->id.'" name="'.$this->id.'" value="'.$this->title.'"'.($width == 0? '':' style="width:'.$width.';"').'/>';
				break;
			}
		}else{
			// in the special case where a clickable image is being used			
			$html .= '<img src="'.$this->imgURL.'" alt="'.$this->title.'" onClick="'.$this->action.'" style="cursor:pointer; vertical-align:bottom;"/>';
		}
		
		return $html;
	}
	
	/**
	 * Returns the Javascript for injecting into <head> to control the behaviour of the buttons.
	 * 
	 * @since 1.0
	 * @return string
	 * @deprecated Now using JQuery UI Button widget, so this custom JavaScript is no longer required
	 */
	public static function renderJavascript() {
		$javascript = "
						
		function addButtonEvent(obj, type, fn) { 
			  	if (obj.attachEvent) { 
    			obj['e'+type+fn] = fn; 
    			obj[type+fn] = function(){obj['e'+type+fn]( window.event );} 
    			obj.attachEvent('on'+type, obj[type+fn]); 
  			}else{
    			obj.addEventListener( type, fn, false );
			}
		}
		 
		function removeButtonEvent(obj, type, fn) { 
  			if (obj.detachEvent) { 
    			obj.detachEvent('on'+type, obj[type+fn]); 
    			obj[type+fn] = null;
  			}else{
    			obj.removeEventListener( type, fn, false );
    		} 
		}
		
		// onmouseover/onclick events for the side bar links
		var selectedButton = null;
		
		function buttonOver(evt){
			// first get the event
			if (!evt) var evt = window.event;
			// now get the target button
			var button;
			if (evt.target) button = evt.target;
			else if (evt.srcElement) button = evt.srcElement;
			if (button.nodeType == 3) // defeat Safari bug
				button = button.parentNode;
			
			// handles nested elements in button div tags
			if (button.tagName != 'INPUT') {
				button = button.parentNode;
			}
				
			if (selectedButton != button){
				button.className = 'oveButton';
				button.style.cursor = 'hand';
				button.style.cursor = 'pointer';
			}
		}
		
		function buttonOut(evt){
			// first get the event
			if (!evt) var evt = window.event;
			// now get the target button
			var button;
			if (evt.target) button = evt.target;
			else if (evt.srcElement) button = evt.srcElement;
			if (button.nodeType == 3) // defeat Safari bug
				button = button.parentNode;
			
			// handles nested elements in button div tags
			if (button.tagName != 'INPUT') {
				button = button.parentNode;
			}
			
			if (selectedButton != button) {
				button.className = 'norButton';
			}
		}
		
		function buttonSelect(evt){
			// first get the event
			if (!evt) var evt = window.event;
			// now get the target button
			var button;
			if (evt.target) button = evt.target;
			else if (evt.srcElement) button = evt.srcElement;
			if (button.nodeType == 3) // defeat Safari bug
				button = button.parentNode;
			
			// handles nested elements in button div tags
			if (button.tagName != 'INPUT') {
				button = button.parentNode;
			}
			
			button.className = 'selButton';
			button.style.cursor = 'hand';
			button.style.cursor = 'pointer';
			if (selectedButton != null && selectedButton != button) {
				selectedButton.className = 'norButton';
			}
			selectedButton = button;
		}


		function addButtonListeners() {
			var no_page_inputs = document.getElementsByTagName('input').length;
	
			for(j=0; j<no_page_inputs; j++) {
				// add the listeners to button for highlights
				currentInput = document.getElementsByTagName('input')[j];
				if (currentInput.className == 'norButton') {
					addButtonEvent(currentInput, 'mouseover', buttonOver);
					addButtonEvent(currentInput, 'mouseout', buttonOut);
					addButtonEvent(currentInput, 'mousedown', buttonSelect);
				}
			}
		}
		
		addOnloadEvent(addButtonListeners);
		";
		
		return $javascript;
	} 
	
}

?>