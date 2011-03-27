$(function() {
	var registry = PHORK.registry,
		overlay = PHORK.overlay,
		utils = PHORK.utils = {};
	;
	
	
	//displays alerts as an overlay, a standard list item, or a JS dialog
	var notify = {
		'degrade': false,
		'overlay': function(message, buttons) {
			if (typeof message == 'object') {
				message = message.join('. ');
			}
			
			if (!overlay || this.degrade) {
				if (buttons) {
					if (confirm(message)) {
						if (buttons.confirmed && typeof buttons.confirmed.action == 'function') {
							buttons.confirmed.action();
						}
					} else {
						if (buttons.cancelled && typeof buttons.cancelled.action == 'function') {
							buttons.cancelled.action();
						}
					}
				} else {
					alert(message);
				}
			} else {
				new overlay.core().init(null, {
					source: 'message',
					message: message,
					modal: true,
					type: 'notify',
					disposable: true,
					toolbar: true,
					buttons: buttons
				}).show();
			}
		},
		
		'insert': function(message, top) {
			var $window = $(window),
				$alerts = $('#alert')
			;
				
			$window.trigger('resizing');
			
			if (!$alerts.size()) {
				$('#content').before($alerts = $('<ul id="alert"></ul>').hide());
			}
			
			$alerts
				.show()
				.append($('<li><span class="text"></span><span class="close"></span></li>').hide())
				.find('.text').last()
					.append(message)
					.parent()
						.show('blind', 300, function() {
							$window.trigger('resized');
						})
			;
			
			if (top) {
				$window.trigger('goto', [0]);
			}
		}
	};
	PHORK.utils.notify = notify;	
		
	
	//builds authorized API URLs and parses result errors
	var api = {
		'authorize': function(url) {
			return url + '?sid=' + cookies.get('PHPSESSID');
		},
		
		'error': function(xhr, status, error) {
			try {
				if (xhr.responseText) {
					if (data = $.parseJSON(xhr.responseText)) {
						errors = data.errors;
					}
				}
			} catch (e) {
				errors = [error];
			}
			return typeof errors ? errors : ['There was an error'];
		}
	}
	utils.api = api;
	
	
	//creates, retrieves and deletes cookies
	var cookies = {
		'get': function(name) {
			var results = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
			return results ? unescape(results[2]) : null;
		},
		
		'set': function(name, value, days) {
			var expires = '';
			if (days) {
				var date = new Date();
				date.setTime(date.getTime() + (days * 86400 * 1000));
				expires = '; expires=' + date.toGMTString();
			}
			document.cookie = name + '=' + value + expires + '; path=' + registry.constants.cookiePath;
		},
		
		'delete': function(name) {
			this.set(name, '', -1);
		}
	};
	utils.cookies = cookies;
	
	
	//searches an element for a class in the format prefix-arg1-arg2 and returns the args
	var classargs = function(element, prefix, length) {
		var segments, classes = $(element).attr('class').split(' ');
		
		for (var i in classes) {
			segments = classes[i].split('-');
			if (segments[0] == prefix && segments.length == length) {
				return segments;
			}
		}
	}
	utils.classargs = classargs;
	
	
	//displays a count for the number of characters in an form field
	var counted = function($element, $result) {
		var segments = classargs($element, 'range', 3);
		if (segments) {
			min = segments[1];
			max = segments[2];
		}
		
		$element.bind('keyup', function() {
			var length = $(this).val().length;
			$result.html(length ? length : '');
			
			if (length < min || length > max) {
				$result.addClass('error');
			} else {
				$result.removeClass('error');
			}
		}).trigger('keyup');
	}
	utils.counted = counted;
});