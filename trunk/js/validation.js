(function($) {

	function validateForm(form) {
		var fields = getFields(form);

		for(var i = 0; i < fields.length; i++) {
			if(fields[i].attr('type') != 'hidden' && !fields[i].attr('readonly'))
				addBlurEventToField(fields[i]);
			if(fields[i].attr('type') == 'hidden' && $('[id="'+fields[i].attr('name')+'_display"]').length > 0)
				addBlurEventToField($('[id="'+fields[i].attr('name')+'_display"]'));
		}

		form.submit(function() {
			var isValid = true;

			for(var i = 0; i < fields.length; i++) {
				if(fields[i].attr('type') != 'hidden' && fields[i].attr('type') != 'submit') {
					if(validateField(fields[i]) != '') {
						fields[i].blur();
						isValid = false;
					}
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
					$('[id="'+field.attr('id')+'"]').parent().toggleClass('has-error');
				}else{
					messageSpan.html(error);
					$('[id="'+field.attr('id')+'"]').parent().toggleClass('has-error');
				}
			}else{
				hideContainer(errorContainer);
				isVisible = false;
			}
		});
	}

	function validateField(field) {
		var html = '';

		if(!field.attr('id'))
			return html;

		if(field.attr('id').match(/_display/g)) {
			var fieldName = field.attr('id').replace(/_display/g,'');
			var fieldVal = $('[id="'+fieldName+'"]').val();
		}else{
			var fieldName = field.attr('id');
			var fieldVal = field.val();
		}


		if($('[id="'+fieldName+'_rule"]').length > 0) {
			var rule = $('[id="'+fieldName+'_rule"]').val();
			var flag = '';

			if(rule.match(/equal\ to\ /g)) {
				equalToField = rule.replace(/equal\ to\ /g,'');
				if(fieldVal == $('[id="'+equalToField+'"]').val()) {
					return html;
				}else{
					return '<p>'+$('[id="'+fieldName+'_msg"]').val()+'</p>';
				}
			}

			if(rule.match(/is\ checked/g)) {
				if($('[id="'+fieldName+'"]').is(':checked')) {
					return html;
				}else{
					return '<p>'+$('[id="'+fieldName+'_msg"]').val()+'</p>';
				}
			}

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
				html += '<p>'+$('[id="'+fieldName+'_msg"]').val()+'</p>';
			}
		}

		return html;
	}

	var errorContainer = $('<div class="text-danger" style="display:none; position:absolute; width:auto; padding:0pt 0.7em; z-index:100;""><div><p><span style="float: left; margin-right: 0.3em;"></span><span class="message"></span></p></div></div>');

	errorContainer.css({
		display: 'none',
		position: 'absolute',
		width: 'auto',
		padding: '0pt 0.7em'
    });

	var positionContainer = function(errorContainer, field) {
	};

	var showContainer = function(errorContainer) {
		errorContainer.fadeIn('slow');
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