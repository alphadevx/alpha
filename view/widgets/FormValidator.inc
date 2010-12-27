<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';

/**
 *
 * Renders the client-side form validation Javascript
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
class FormValidator {
	/**
	 * The business object that will be validated
	 * @var AlphaDAO
	 * @since 1.0
	 */
	private $BO;
	
	/**
	 * Constructor
	 * 
	 * @param AlphaDAO $BO The business object that we want to validate
	 * @throws IllegalArguementException
	 * @since 1.0
	 */
	public function __construct($BO) {
		if(AlphaDAO::checkClassDefExists(get_class($BO)))		
			$this->BO = $BO;
		else
			throw new IllegalArguementException('The definition for the BO ['.get_class($BO).'] is not defined anywhere!');
	}
	
	/**
	 * Returns the Javascript reg-ex expressions for validating each attribute of this BO
	 * 
	 * @return string
	 * @since 1.0
	 */
	public function renderJavascriptRules() {
		// get the class attributes
		$reflection = new ReflectionClass(get_class($this->BO));
		$properties = $reflection->getProperties();
		
		$javascript = '';

		foreach($properties as $propObj) {			
			$propName = $propObj->name;
			// we con't validate default house keeping attributes, or transient (non-saved) attributes
			if(!in_array($propName, $this->BO->getDefaultAttributes()) && !in_array($propName, $this->BO->getTransientAttributes())) {
				$propClass = get_class($this->BO->getPropObject($propName));
				if (strtoupper($propClass) != "ENUM" &&
				strtoupper($propClass) != "DENUM" &&
				strtoupper($propClass) != "DENUMITEM" && 
				strtoupper($propClass) != "BOOLEAN") {
					$javascript .= " validation_rules[\"".$propName."\"] = ".$this->BO->getPropObject($propName)->getRule().";\n";
					$javascript .= " validation_rules[\"".$propName."_msg\"] = \"".$this->BO->getPropObject($propName)->getHelper()."\";\n";
				}
			}
		}
		
		return $javascript;
	}
	
	/**
	 * Returns the Javascript to control the behaviour of the form validation.
	 * 
	 * @return string
	 * @todo JS will be converted to JQuery at some point, and make use of JQuery UI dialogs
	 * @since 1.0
	 */
	public function renderJavascript() {		
		$javascript = "
		
		var validation_rules = new Array();
		
		function check_field(evt) {
			// first get the event
			if (!evt) var evt = window.event;
			// now get the target element/form field
			var field;
			if (evt.target) field = evt.target;
			else if (evt.srcElement) field = evt.srcElement;
			if (field.nodeType == 3) // defeat Safari bug
				field = field.parentNode;
			
			var field_type = field.type;
			
			switch(field_type) {
				case 'text':
					return check_text_field(field);
				break;
				case 'password':
					return check_text_field(field);
				break;
				case 'textarea':					
					return check_text_area(field);
				break;
			}	
		}
		
		function validate_form(evt) {
			// first get the event
			if (!evt) var evt = window.event;
			// now get the target form
			var form;
			if (evt.target) form = evt.target;
			else if (evt.srcElement) form = evt.srcElement;
			if (form.nodeType == 3) // defeat Safari bug
				form = form.parentNode;
			
			// the main boolean value for validating the entire form
			var form_valid = true;
			var val_count = 0;
			
			// now loop through all of the forms fields and validate them
			for(i=0; i<form.elements.length; i++){
				var field = form.elements[i];
				var field_type = field.type;				
			
				switch(field_type) {
					case 'text':						
						if(!check_text_field(field))
							val_count--;
					break;
					case 'hidden':						
						if(!check_hidden_field(field))
							val_count--;
					break;
					case 'password':						
						if(!check_text_field(field))
							val_count--;
					break;
					case 'textarea':						
						if(!check_text_field(field))
							val_count--;
					break;
				}
			}
			if(val_count < 0)
				form_valid = false;
			else
				form_valid = true;			
			
			if (evt.preventDefault) {
				if (form_valid == false)
		    		evt.preventDefault(); // DOM/Mozilla method to cancel the submission  	
			}else{
				// works in IE
				evt.returnValue = form_valid;
			}
		}
		
		function check_text_field(field) {
			// get the validation rule for this field
			var rule = validation_rules[field.getAttribute('name')];
			
			// if no rule is defined for the field, return true
				if(!rule)
					return true;
						
			// now use the rule regular expression to validate the field
			if (!field.value.match(rule)) {				
				raise_error(validation_rules[field.name+'_msg']);
				field.style.backgroundColor = 'yellow';
				return false;
			}else{
				if(field.style.className != 'readonly')
					field.style.backgroundColor = 'white';
				return true;
			}
		}
		
		function check_hidden_field(field) {
			// get the validation rule for this field
			var rule = validation_rules[field.getAttribute('name')];
			
			var displayField = document.getElementById(field.getAttribute('name')+'_display') 
			
			// if no rule is defined for the field, return true
				if(!rule)
					return true;
						
			// now use the rule regular expression to validate the field
			if (!field.value.match(rule)) {				
				raise_error(validation_rules[field.name+'_msg']);
				displayField.style.backgroundColor = 'yellow';
				return false;
			}else{
				if(field.style.className != 'readonly')
					displayField.style.backgroundColor = 'white';
				return true;
			}
		}
		
		function check_text_area(field) {
			// get the validation rule for this field
			var rule = validation_rules[field.getAttribute('name')];
			
			// now use the rule regular expression to validate the field
			if (!field.value.match(rule)) {				
				raise_error(validation_rules[field.name+'_msg']);
				field.style.backgroundColor = 'yellow';
				return false;
			}else{
				field.style.backgroundColor = 'white';
				return true;
			}
		}
		
		function raise_error(msg) {
			alert(msg);	
		}
		
		function addFormEvent(obj, type, fn) {			
		  	if (obj.attachEvent) { 
		    	obj['e'+type+fn] = fn; 
		    	obj[type+fn] = function(){obj['e'+type+fn]( window.event );} 
		    	obj.attachEvent('on'+type, obj[type+fn]); 
		  	}else{
		    	obj.addEventListener( type, fn, false );
			}
		} 
		function removeFormEvent(obj, type, fn) { 
		  	if (obj.detachEvent) { 
		    	obj.detachEvent('on'+type, obj[type+fn]); 
		    	obj[type+fn] = null;
		  	}else{
		    	obj.removeEventListener( type, fn, false );
		    } 
		} 
		
		
		function addFormListeners() {
			var no_page_forms = document.forms.length;
			
			for(j=0; j<no_page_forms; j++) {
				// add the submit listener per form
				addFormEvent(document.forms[j], 'submit', validate_form);
			}
		}
		
		addOnloadEvent(addFormListeners);";
		
		return $javascript;
	}
}

?>
