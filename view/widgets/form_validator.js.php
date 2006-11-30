<?php

// $Id$

if (!isset($sysRoot))
	$sysRoot = '../../';

if (!isset($sysURL))
	$sysURL = '../../';

require_once $sysRoot.'util/handle_error.inc';

require_once $sysRoot.'model/types/String.inc';
require_once $sysRoot.'model/types/Text.inc';

/**
 *
 * Renders the client-side form validation Javascript
 * 
 * @package Alpha Widgets
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * @todo add in an append_validator method to add support for additional BOs to the validation_rules array
 * @todo add support for field types other than text
 * 
 */
class form_validator
{
	/**
	 * the business object that will be validated
	 * @var object
	 */
	var $BO = null;
	
	/**
	 * constructor that builds a new
	 * @param object $BO the business object that we want to validate
	 */
	function form_validator($BO) {		
		$this->BO = $BO;
		
		echo "// set up the validation rules used on the fields in this page\n";
			
		$properties = get_object_vars($this->BO);		
		
		foreach(array_keys($properties) as $prop) {
			if($prop != "TABLE_NAME" && $prop != "last_query" && $prop != "OID" && $prop != "render_mode" && $prop != "data_labels" && $prop != "version_num" && $prop != "access_level") {
				if (strtoupper(get_class($properties[$prop])) != "ENUM") {
					echo " validation_rules[\"".$prop."\"] = ".$this->BO->$prop->get_rule().";\n";
					echo " validation_rules[\"".$prop."_msg\"] = \"".$this->BO->$prop->get_helper()."\";\n";
				}
			}
		}	
	}
	
	/**
	 * renders the Javascript to control the behaviour of the form validation
	 */
	function render_javascript() {
		
		header("Content-type: application/x-javascript");
		
		// begining of javascript
		// ----------------------
		echo <<<EOS
		
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
				case "text":
					return check_text_field(field);
				break;
				case "password":
					return check_text_field(field);
				break;
				case "textarea":					
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
					case "text":						
						if(!check_text_field(field))
							val_count--;
					break;
					case "password":						
						if(!check_text_field(field))
							val_count--;
					break;
					case "textarea":						
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
			var rule = validation_rules[field.getAttribute("name")];
						
			// now use the rule regular expression to validate the field
			if (!field.value.match(rule)) {				
				raise_error(validation_rules[field.name+"_msg"]);
				field.style.backgroundColor = 'yellow';
				return false;
			}else{
				field.style.backgroundColor = 'white';
				return true;
			}
		}
		
		function check_text_area(field) {
			// get the validation rule for this field
			var rule = validation_rules[field.getAttribute("name")];
			
			// now use the rule regular expression to validate the field
			if (!field.value.match(rule)) {				
				raise_error(validation_rules[field.name+"_msg"]);
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
				addFormEvent(document.forms[j], "submit", validate_form);
				// and now a blur listener per form field
				for(i=0; i<document.forms[j].elements.length; i++){
					var form_type = document.forms[j].elements[i].type;
					if (form_type == "text" || form_type == "password" || form_type == "textarea") {			   			
			   			addFormEvent(document.forms[j].elements[i], "blur", check_field);
					}
			   	}
			}
		}
		
		addOnloadEvent(addFormListeners);
EOS;
// end of javascript
// -----------------
	}
}

// if called from a Javascript link, render the Javascript code, else render the HTML
// link to the Javascript code contained here.
if (isset($_GET["render_javascript"]))
	form_validator::render_javascript();
else
	echo '<script type="text/javascript" src="'.$sysURL.'/view/widgets/form_validator.js.php?render_javascript"></script>';


?>
