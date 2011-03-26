$(function() {
	var registry = PHORK.registry,
		utils = PHORK.utils,
		overlay = PHORK.overlay,
		$window = $(window)
	;
	
	//define any constants and save them in the registry
	registry.constants = {
		baseUrl:	'',
		cookiePath:	'/'
	};
	
	//remove outline from clicked links and set up external links open a new window
	$('a[rel=external]').attr('target','_blank');
	$('a').click(function() { this.blur(); });
	
	//add the javascript stylesheet
	$('<link>').appendTo('head').attr({
		rel: 'stylesheet',
		type: 'text/css',
		href: '/css/themes/default/common/javascript.css'
	});
	
	//replace all the email placeholders with the real address
	$('span.email-replace')
		.html(function(index, email) {
			return email.replace(' [at] this domain', '@' + window.location.hostname.replace('www.', ''));
		})
		.wrap(function() {
			return $('<a href="mailto:' + $(this).html() + '"></a>');
		})
		.removeClass('email-replace')
	;
		
	//add a close button to all existing alerts
	$('ul#alert li').each(function() {
		$(this).append($('<span></span>').addClass('close'));
	});
	
	//add the close functionality to all new alerts
	$('li span.close').live('click', function(e) {
		var $window = $(window);
		$window.trigger('resizing');
		
		$(this).closest('li').hide('blind', 300, function() {
			var $parent = $(this).parent('ul');
			if ($parent.find('li:visible').size() == 0) {
				$parent.hide();
			}
			$window.trigger('resized');
		});
	});
	
	//add the clear functionality to all clearable forms
	$('div.labeled').livequery(function() {
		$(this).find('input,textarea').each(function() {
			var $this = $(this),
				$label = $('label[for=' + $this.attr('id') + ']')
			;
			
			$this
				.bind('focus', function() {
					$label.hide();
				})
				.bind('blur', function() {
					if (!$this.val()) {
						$label.show();
					}
				})
			;
			
			if ($this.val()) {
				$label.hide();
			}
			
			$label
				.removeClass('js-hide')
				.bind('click', function() {
					$this.trigger('focus');
				})
			;
		});
	});
	
	//set up the link confirmations
	$('a.confirm').live('click', function(e, confirmed) {
		var $this = $(this);
		
		if (confirmed !== true) {
			e.preventDefault();
			new utils.notify.overlay('Are you sure you want to ' + $this.data('confirm') + '?' + ($this.hasClass('permanent') ? ' There is no undo.' : ''), {
				cancelled: {
					type: 'cancel',
					value: 'Cancel',
					close: true
				},
				confirmed: {
					value: $this.data('confirm'),
					close: true,
					action: function() {
						$this.trigger('click', [true]);
					}
				}
			});
		} else {
			self.location.href = $this.attr('href');
		}
	});
	
	//set up current and future AJAX forms
	$('form.ajax').live('submit', function(e) {
		e.preventDefault();
		
		var $this = $(this);
		$this.addClass('posting');
		
		$.ajax({
			url: $this.attr('action'), 
			type: 'POST',
			data: $this.serialize(), 
			dataType: 'json',
			cache: false,
			success: function(data, status, xhr) {
				$this.trigger('success', data);
			}, 
			error: function(xhr, status, error) {
				$this.trigger('error', [error, {
					errors: utils.api.error(xhr, status, error)
				}]);
			},
			complete: function(xhr, status) {
				$this.removeClass('posting');
			}
		});
	});
		
	//initialize the login overlay which will reload the page upon successful login
	$('.overlay-login-trigger').each(function() {
		var $this = $(this);
		new overlay.core()
			.init($this, {
				source: 'url',
				type: 'login',
				position: 'center',
				modal: true,
				toolbar: true,
				width: 390,
				initialized: function(self, success) {
					if (success) {
						self.$content.find('form.ajax')
							.bind('submit', function() {
								$(this).find('div.error').remove();
								self.resize({
									width: false,
									height: true
								})
							})
							.bind('success', function(e, data) {
								window.location.reload();
							})
							.bind('error', function(e, error, data) {
								$(this).prepend($('<div></div>')
									.addClass('error')
									.html(data.errors.join('<br />'))
								);
								
								self.resize({
									width: false,
									height: true
								});
							})
						;
					}
				}
			})
			.bind('click')
		;
	});
	
	//set up any tooltips
	$('.tipped').each(function() {
		var $this = $(this);
		new overlay.tooltip().init($this, {
			source: 'message',
			message: $this.attr('title'),
			position: 'cursor',
			duration: 1500,
			stacked: true,
			transition: {
				show: 'fadeIn',
				hide: 'fadeOut'
			}
		});
		$this.attr('title', '');
	});
	
	//add the form field counters
	$('textarea.counted').each(function() {
		utils.counted($(this), $(this).next());
	});
	
	//set up custom resizing and a debounced resized event
	$window
		.data('timeout', null)
		.bind('resize', function(e) {
			var $this = $(this);
			$this.trigger('resizing');
			
			clearTimeout($this.data('timeout'));
			$this.data('timeout', setTimeout(function() {
				$this.trigger('resized');
			}, 500));
		})
	;
	
	//set up the scroll top action
	$window.bind('goto', function(e, position) {
		$('html, body').animate({scrollTop: position}, 'fast');
	});
	
	//store the cursor position
	$window.bind('mousemove', function(e) {
		registry.mouse = {
			x: e.pageX,
			y: e.pageY
		};
	}); 
});