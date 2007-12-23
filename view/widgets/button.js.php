<?php

// $Id$

if (!isset($sysRoot))
	$sysRoot = '../../../';

if (!isset($sysURL))
	$sysURL = '../../../';

require_once $sysRoot.'alpha/util/handle_error.inc';

require_once $sysRoot.'alpha/model/types/String.inc';
require_once $sysRoot.'alpha/model/types/Text.inc';

/**
* Button HTML custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*  
*/
class button
{
	/**
	 * the javascript action to carry out when the button is pressed
	 * @var text
	 */
	var $action;
	/**
	 * the title to display on the button
	 * @var string
	 */
	var $title;
	/**
	 * the javascript id for the button layer
	 * @var string
	 */
	var $id;
	/**
	 * when provided, the button will be a clickable image using this image
	 * @var string
	 */
	var $imgURL;
	
	/**
	 * the constructor
	 * @param text $action the javascript action to be carried out (set to "submit" to make a submit button, "file" for file uploads)
	 * @param string $title the title to appear on the button
	 * @param string $id the javascript id for the button layer
	 * @param string $imgURL when provided, the button will be a clickable image using this image
	 */
	function button($action, $title, $id, $imgURL='') {
		$this->action = new Text();
		$this->action->set_rule("/.*/i");
		$this->title = new String();
		$this->title->set_rule("/.*/i");
		$this->id = new String();
		$this->id->set_rule("/.*/i");
		if(!empty($imgURL))
			$this->imgURL = $imgURL;
		
		if (isset($action) && $action != "")
			$this->action->set_value($action);
		else
			$this->action->set_value("alert('Please set an action for this button!')");
			
		$this->set_title($title);
		$this->set_id($id);
		
		$this->render();		
	}
	
	/**
	 * renders the HTML and javascript for the button	 *
	 */
	function render() {
		if(empty($this->imgURL)) {
			switch ($this->action->get_value()) {
				case "submit":
					echo '<input type="submit" id="'.$this->get_id().'" name="'.$this->get_id().'" class="norButton" value="'.$this->get_title().'"/>';
				break;
				case "file":
					echo '<input type="file" id="'.$this->get_id().'" name="'.$this->get_id().'" class="norButton" value="'.$this->get_title().'"/>';
				break;
				default:
					echo '<input type="button" id="'.$this->get_id().'" name="'.$this->get_id().'" class="norButton" onClick="'.$this->get_action().';" value="'.$this->get_title().'"/>';
				break;
			}
		}else{
			// in the special case where a clickable image is being used
			echo '<img src="'.$this->imgURL.'" alt="'.$this->title.'" onClick="'.$this->get_action().'" style="cursor:pointer;"/>';
		}
	}
	
	/**
	 * renders the Javascript to control the behaviour of the button
	 */
	function render_javascript() {
		header("Content-type: application/x-javascript");
		
		// begining of javascript
		// ----------------------
		echo <<<EOS
				
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
			if (button.tagName != "INPUT") {
				button = button.parentNode;
			}
				
			if (selectedButton != button){
				button.className = "oveButton";
				button.style.cursor = "hand";
				button.style.cursor = "pointer";
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
			if (button.tagName != "INPUT") {
				button = button.parentNode;
			}
			
			if (selectedButton != button) {
				button.className = "norButton";
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
			if (button.tagName != "INPUT") {
				button = button.parentNode;
			}
			
			button.className = "selButton";
			button.style.cursor = "hand";
			button.style.cursor = "pointer";
			if (selectedButton != null && selectedButton != button) {
				selectedButton.className = "norButton";
			}
			selectedButton = button;
		}


		function addButtonListeners() {
			var no_page_inputs = document.getElementsByTagName("input").length;
	
			for(j=0; j<no_page_inputs; j++) {
				// add the listeners to button for highlights
				currentInput = document.getElementsByTagName("input")[j];
				if (currentInput.className == "norButton") {
					addButtonEvent(currentInput, "mouseover", buttonOver);
					addButtonEvent(currentInput, "mouseout", buttonOut);
					addButtonEvent(currentInput, "mousedown", buttonSelect);
				}
			}
			
			//if(buildCalled != "undefined")
				//build();
		}
		
		addOnloadEvent(addButtonListeners);
EOS;
// end of javascript
// -----------------
	} 
	
	/**
	 * setter for action
	 * @param string $action
	 */
	function set_action($action)
	{
		$this->action->set_value($action);
	}

	/**
	 * getter for action
	 * @return string action
	 */
	function get_action() {
		return $this->action->get_value();
	}
	
	/**
	 * setter for title
	 * @param string $title
	 */
	function set_title($title)
	{
		$this->title->set_value($title);
	}

	/**
	 * getter for title
	 * @return string title
	 */
	function get_title() {
		return $this->title->get_value();
	}
	
	/**
	 * setter for id
	 * @param string $id
	 */
	function set_id($id)
	{
		$this->id->set_value($id);
	}

	/**
	 * getter for id
	 * @return string id
	 */
	function get_id() {
		return $this->id->get_value();
	}
}

// if called from a Javascript link, render the Javascript code, else render the HTML
// link to the Javascript code contained here.
if (isset($_GET["render_javascript"]))
	button::render_javascript();
else
	echo '<script language="JavaScript" src="'.$sysURL.'/alpha/view/widgets/button.js.php?render_javascript"></script>';

?>
