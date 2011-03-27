$(function() {
	var template = $('#debug-tmpl').html(),
		$window = $(window),
		$wrapper, $content, $icon
	;
	
	//load the debugger stylesheet
	$('<link>').appendTo('head').attr({
		rel: 'stylesheet',
		type: 'text/css',
		href: '/css/themes/default/common/debug.css'
	});
	
	//add the debugging elements to the page
	$('body')
		.prepend($('<div></div>').attr('id', 'debug')
			.append($wrapper = $('<div></div>').attr('id', 'debug-wrapper')
				.append($content = $('<div></div>').attr('id', 'debug-content'))
			)
			.append($icon = $('<div></div>').attr('id', 'debug-icon')
				.append($('<span></span>').text('debug'))
			)
		)
	;
	
	//set up the icon to trigger the showing and hiding of the content
	$icon
		.bind('click', function(e, force) {
			if (($content.is(':visible') || force === false) && force !== true) {
				PHORK.utils.cookies.set('debug', 0);
				$(this).removeClass('active');
				$wrapper.hide();
			} else {
				PHORK.utils.cookies.set('debug', 1);
				$(this).addClass('active');
				$wrapper.show();
				$content.trigger('resized');
			}
		})
	;	
	
	//set up the content element to be appended to and resizable
	$content
		.bind('append', function(e, time, text) {
			$(this)
				.append(template
					.replace(/\[{2}([A-Z-]+)\]{2}/g, function(match, variable) {
						switch (variable.toLowerCase()) {
							case 'time':
								return time;
								break;
								
							case 'text':
								return text;
								break;
						}
					})
				)
				.trigger('resized')
			;
		})
		.bind('resized', function() {
			var $this = $(this),
				max = Math.floor($(window).height() * .8)
			;
			
			if ($this.height() > max) {
				$this
					.addClass('scrollable')
					.height(max)
				;
			} else {
				$this.removeClass('scrollable');
			}
		})
	;	
	
	//bind the escape key to close the debugger
	$(document)
		.bind('keydown', function(e) {
			if (e.keyCode == 27) {
				$icon.trigger('click', false);
			}
		})
	;
		
	//load the debugging data from the API
	$.ajax({
		url: PHORK.utils.api.authorize(debugApiUrl),
		success: function(data, status, xhr) {
			if (data.items) {
				for (var i in data.items) {
					$content.trigger('append', data.items[i]);
				}
			}
			
			if (PHORK.utils.cookies.get('debug') == 1) {
				$icon.trigger('click', true);
			}
		}
	});
		
	//override the console log to also display the content in the debug window
	PHORK.registry.log = console.log;
	console.log = function() {
		$content.trigger('append', ['[from console.log]', $.makeArray(arguments).join(': ')]); 
		PHORK.registry.log.apply(this, arguments);
	};
});