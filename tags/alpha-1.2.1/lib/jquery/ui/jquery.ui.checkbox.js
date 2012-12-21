/*
 * Modified jquery.ui.checkbox widget based on code from http://wiki.jqueryui.com/Checkbox
 *
 * Depends:
 *   jquery.ui.core.js
 *   jquery.ui.widget.js
 */
(function( $ ) {

var checkboxId = 0;

$.widget( "ui.checkbox", {
	options: {
		disabled : null
	},
	_create: function() {

		var that = this;
		
		this.checkboxElement = this.element.wrap( "<div class='ui-checkbox-inputwrapper'></div>" ).parent()
				.addClass("ui-checkbox");
		
		

		this.boxElement = $("<div class='ui-checkbox-box ui-widget ui-state-active ui-corner-all' id='"+this.element.attr("id")+"-checkbox'><span class='ui-checkbox-icon'></span></div>");
		this.iconElement = this.boxElement.children( ".ui-checkbox-icon" );
		this.checkboxElement.append(this.boxElement);

		this.boxElement.bind("click", function() {
			that._toggle();
		});

		this.checkboxElement.bind("mouseover.checkbox", function() {
			if ( that.options.disabled ) {
				return;
			}
			that.boxElement
				.removeClass( "ui-state-active" )
				.addClass( "ui-state-hover" );
		});

		this.checkboxElement.bind("mouseout.checkbox", function() {
			if ( that.options.disabled ) {
				return;
			}
			that.boxElement
				.removeClass( "ui-state-hover" )
				.not(".ui-state-focus")
				.addClass( "ui-state-active" );
		});

		if ( this.element.is(":disabled") ) {
			this._setOption( "disabled", true );
		}
		this._refresh();
	},
	
	_refresh: function() {
		
		var ID = '#'+this.element.attr("id");
		
		if($(ID).attr('checked')) {
			
			$(ID).attr('checked','checked');
			this.iconElement.addClass("ui-icon ui-icon-check");
		}else{
			
			$(ID).removeAttr('checked');
			this.iconElement.removeClass("ui-icon ui-icon-check");
	
		}
	},

	_toggle: function() {
		var ID = '#'+this.element.attr("id");
		
		if($(ID).attr('checked')) {
			$(ID).removeAttr('checked');
			this.iconElement.removeClass("ui-icon ui-icon-check");
			
			$(ID).trigger("blur");

		}else{	
			$(ID).attr('checked','checked');
			this.iconElement.addClass("ui-icon ui-icon-check");
			
			$(ID).trigger("blur");
		}
	},

	widget: function() {alert('widget');
		return this.checkboxElement;
	},

});

}( jQuery ));
