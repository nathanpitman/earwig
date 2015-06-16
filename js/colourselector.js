if (typeof(NINEFOURLABS) == 'undefined') {
	NINEFOURLABS	= {};
	NINEFOURLABS.UI	= {};
}

NINEFOURLABS.UI.ColourSelector = function()
{
	var init	= function() {
		colourSelector();
	}
		
	var colourSelector = function() {
		var hex = $('.colourSelector').val();
		$('.colourSelector').css('color', hex);
		
		$('.colourSelector').ColorPicker({
			onSubmit: function(hsb, hex, rgb, el) {
				$(el).val('#'+hex);
				$(el).ColorPickerHide();
				$('.colourSelector').css('color', '#'+hex);
			},
			onBeforeShow: function () {
				$(this).ColorPickerSetColor(this.value);
			}
		})
		.bind('keyup', function(){
			$(this).ColorPickerSetColor(this.value);
		});

	}

	return {
		init: init
	}
}();

jQuery(function($) { NINEFOURLABS.UI.ColourSelector.init(); });  