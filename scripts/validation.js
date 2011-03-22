(function($) {

	function validateForm(form) {
		var fields = getFields(form);
    
		for(var i = 0; i < fields.length; i++) {
			if(fields[i].attr('type') != 'hidden' && !fields[i].attr('readonly'))
				addBlurEventToField(fields[i]);
			if(fields[i].attr('type') == 'hidden' && $('#'+fields[i].attr('name')+'_display').length > 0)
				addBlurEventToField($('#'+fields[i].attr('name')+'_display'));
		}
    
		form.submit(function() {
			var isValid = true;
			
			for(var i = 0; i < fields.length; i++) {
				if(validateField(fields[i]) != '') {
					fields[i].blur();
					isValid = false;
				}
			}

			return isValid;
		});
	}
  
	function getFields(form) {
		var tags = 'input textarea select'.split(' ');
		var fields = [];
    
		for(var i = 0; i < tags.length; i++) {
			form.find(tags[i]).each(function() {
				if($(this).attr('type') != 'submit') {
					fields.push($(this));
				}
			});
		}
    
		return fields;
	}
  
  
	function addBlurEventToField(field) {
		var errorContainer = field.after(holder.errorContainer.clone()).next();
		var messageSpan = errorContainer.find('span[class="message"]');
		var isVisible = false;
    
		field.blur(function() {
			var error = validateField(field);
      
			if(error != '') {
				if(!isVisible) {
					messageSpan.html(error);
					showContainer(errorContainer);
					isVisible = true;
					positionContainer(errorContainer, field);
				}else{
					messageSpan.html(error);
				}
			}else{
				hideContainer(errorContainer);
				isVisible = false;
			}
		});
	}

	function validateField(field) {
		var html = '';
		
		if(field.attr('name').match(/_display/g)) {
			var fieldName = field.attr('name').replace(/_display/g,'');
			var fieldVal = $('#'+fieldName).val();
		}else{
			var fieldName = field.attr('name');
			var fieldVal = field.val();
		}
		
			
		if($('#'+fieldName+'_rule').length > 0) {
			var rule = $('#'+fieldName+'_rule').val();
			var flag = '';
			if(rule.match(/\/i/g)) {
				flag = 'i';
				rule = rule.replace(/\/i/g,'');
			}
			if(rule.match(/\/g/g)) {
				flag = 'g';
				rule = rule.replace(/\/g/g,'');
			}
			if(rule.match(/\/m/g)) {
				flag = 'm';
				rule = rule.replace(/\/m/g,'');
			}
			rule = rule.replace(/\//g,'');
			
			if(flag == '')
				var regEx = new RegExp(rule);
			else
				var regEx = new RegExp(rule, flag);
			
			if (!fieldVal.match(regEx)) {
				html += '<span>'+$('#'+fieldName+'_msg').val()+'</span>';
			}
		}
    
		return html;
	}

	var errorContainer = $('<div class="ui-state-error ui-corner-all" style="display:none; position:absolute; width:auto; padding:0pt 0.7em; z-index:100;"><div><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span><span class="message"></span></p></div></div>');
	
	errorContainer.css({
		display: 'none',
		position: 'absolute',
		width: 'auto',
		padding: '0pt 0.7em'
    });
  
	var positionContainer = function(errorContainer, field) {
		if(field[0].nodeName == 'INPUT' && field.attr('type') != 'hidden') {
			errorContainer.animate({
				top: field.offset().top - errorContainer.height()
			});
		}
	};
  
  
	var showContainer = function(errorContainer) {
		errorContainer.slideDown('slow');
	};
  
	var hideContainer = function(errorContainer) {
		errorContainer.slideUp('fast');
	};
  
  
	$.fn.validateForm = function() {
		holder = $.extend({}, $.fn.validateForm.atts);
    
		return this.each(function() {
			validateForm($(this));
		});
	};

	$.fn.validateForm.atts = {
			errorContainer: errorContainer,
			positionContainer: positionContainer,
			showContainer: showContainer,
			hideContainer: hideContainer
	};
})(jQuery);

$(document).ready(function() {
	$('form').validateForm();
});
