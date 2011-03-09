$(function() {
	var registry = PHORK.registry;
	PHORK.registry.overlay = null;
	PHORK.overlay = {};
	
	//all overlays should either be a core overlay or extend it
	var core = function() {};
	PHORK.overlay.core = core;
	
	//initializes the overlay and binds the events to show it
	core.prototype.init = function($element, config) {
		var self = this;			
		
		this.$element = $element;
		this.$overlay = null;
		this.$content = null;
		this.$toolbar = null;
		this.$buttons = null;
		this.config = config || {};
		
		return this;
	};
	
	//binds the toggling of the overlay to an action (eg. click)
	core.prototype.bind = function(action) {
		var self = this;
		
		this.$element.bind(action, function(e) { 
			e.preventDefault(); 
			return self.toggle(); 
		});
		
		return this;
	};
	
	//builds a loading overlay and makes the call to load the content into it
	core.prototype.load = function() {
		var self = this;
		
		this.$overlay ? this.$overlay.empty() : $('body').append(this.$overlay = $('<div></div>').hide());
		
		if (this.config.toolbar) {
			this.$overlay.append(this.$toolbar = $('<div></div>')
				.addClass('overlay-toolbar')
				.append($('<div></div>')
					.addClass('overlay-close')
					.bind('click', function() {
						self.hide();
					})
				)
			);
		}
		
		this.$overlay
			.addClass('overlay' + (this.config.type ? ' ' + this.config.type : ''))
			.append(this.$content = $('<div></div>')
				.addClass('overlay-content')
				.append(function(index, html) {
					if (self.config.source == 'element') {
						if (!self.config.width) {
							self.config.width = self.$element.width();
						}
						if (!self.config.height) {
							self.config.height = self.$element.height();
						}
						return self.$element.detach();
					} else if (self.config.source == 'message') {
						if (self.config.message) {
							return self.config.message;
						} else if (this.$element) {
							return this.$element.data('message');
						}
					} else {
						return $('<div></div>').addClass('loading');
					}
				})
			)
		;
		
		if (this.config.width) {
			this.$content.width(this.config.width);
		}
		
		if (this.config.height) {
			this.$content.height(this.config.height);
		}
		
		switch (this.config.source) {
			case 'element':
				this
					.resize()
					.$content
						.show()
				;
				break;
				
			case 'template':
				var template = this.$element && this.$element.data('template') ? this.$element.data('template') : this.config.template;
				this.$overlay.html($('#' + template + '-tmpl').html()
					.replace(/\[{2}([A-Z-]+)\]{2}/g, function(match, variable) {
						return self.$element.data(variable.toLowerCase())
					})
				);
				
				if (typeof this.config.initialized == 'function') {
					this.config.initialized(this, [true]);
				}
				break;
				
			case 'url':
				var url = this.$element && this.$element.attr('href') ? this.$element.attr('href') + '?overlay=1' : this.config.href;
				this.$content.load(url, function(response, status, xhr) {
					if (status == 'success') {
						self.$overlay.addClass('initialized');
					} else {
						$(this).html('Uh oh. Something went wrong.');
						self.$overlay.addClass('error');
					}
					
					if (typeof self.config.initialized == 'function') {
						self.config.initialized(self, [status == 'success']);
					}
					
					self.resize();
				});
				break;
				
			default:
				if (typeof this.config.initialized == 'function') {
					this.config.initialized(this, true);
				}
				this.$overlay.addClass('initialized');
				break;
		}
		
		if (this.config.buttons) {
			this.$overlay.append(this.$buttons = $('<div></div>')
				.addClass('overlay-buttons')
			);
			
			for (var i in this.config.buttons) {
				(function() {
					var button = self.config.buttons[i];
					self.$buttons.append($('<button></button>')
						.html(button.value)
						.bind('click', function() {
							if (typeof button.action == 'function') {
								button.action(self);
							}
							if (button.close) {
								setTimeout(function() {
									self.hide();
								}, 100);
							}
						})
						.addClass(button.type)
						.data(button.data ? button.data : {})
					);
				})();
			}
		}
		
		return this;
	};
	
	//resizes the div to fit the first content child
	core.prototype.resize = function(properties) {
		var contentWidth = this.$content.width(),
			contentHeight = this.$content.height(),
			scrollWidth = this.$content.attr('scrollWidth'),
			scrollHeight = this.$content.attr('scrollHeight'),
			increaseWidth = scrollWidth - contentWidth,
			increaseHeight = scrollHeight - contentHeight
		;
		
		if ((!properties || properties.width) && increaseWidth > 0) {
			this.$overlay.width(this.$overlay.width() + increaseWidth);
			this.$content.width(contentWidth + increaseWidth);
		}
		
		if ((!properties || properties.height) && increaseHeight > 0) {
			this.$overlay.height(this.$overlay.height() + increaseHeight);
			this.$content.height(contentHeight + increaseHeight);
		}
		
		return this.position();
	};
	
	//positions the overlay relative to its element or in the center of the window
	core.prototype.position = function() {
		var windowWidth = $(window).width(),
			windowHeight = $(window).height(),
			overlayWidth = this.$overlay.width(),
			overlayHeight = this.$overlay.height(),
			maxWidth = Math.round(windowWidth * .8),
			maxHeight = Math.round(windowHeight * .8)
		;
		
		if (overlayWidth == (overlayWidth = Math.min(overlayWidth, maxWidth)) && overlayHeight == (overlayHeight = Math.min(overlayHeight, maxHeight))) {
			this.$content.addClass('noscroll');
		}
		
		switch (this.config.position) {
			case 'attached':
				this.$overlay.css({
					position: 'absolute',
					top: this.$element.offset().top,
					left: this.$element.offset().left,
					width: overlayWidth + 'px',
					height: overlayHeight + 'px'
				});
				break;
				
			case 'cursor':
				this.$overlay.css({
					position: 'absolute',
					top: registry.mouse ? registry.mouse.y : this.$element.offset().top,
					left: registry.mouse ? registry.mouse.x : this.$element.offset().left,
					width: overlayWidth + 'px',
					height: overlayHeight + 'px'
				});
				break;
				
			default:
				this.$overlay.css({
					top: Math.round((windowHeight - overlayHeight) / 2) + 'px',
					left: Math.round((windowWidth - overlayWidth) / 2) + 'px'
				});
				break;
		}
		
		return this;
	};
	
	//toggles the overlay visibility
	core.prototype.toggle = function() {
		if (this.config.toggleable && this.$overlay && this.$overlay.is(':visible')) {
			return this.hide();
		} else {
			return this.show();
		}
		
		return this;
	};
	
	//shows the overlay
	core.prototype.show = function() {
		var self = this;
		
		registry.overlay && (registry.overlay.modal || registry.overlay.config.solo) && registry.overlay.hide();
		if (this.$overlay || this.load()) {
			if (this.config.modal) {
				$('body').trigger('disable');
			}
			
			if (this.config.transition && this.config.transition.show) {
				this.$overlay[this.config.transition.show](400);
			} else {
				this.$overlay.show();
			}
			this.position();
			
			if (this.config.duration) {
				setTimeout(function() {
					self.hide();
				}, this.config.duration);
			}
			
			registry.overlay = this;
		}
		
		return this;
	};
	
	//hides the overlay
	core.prototype.hide = function() {
		if (this.$overlay) {
			if (this.config.transition && this.config.transition.hide) {
				this.$overlay[this.config.transition.hide](400);
			} else {
				this.$overlay.hide();
			}
			
			if (this.config.modal) {
				$('body').trigger('enable');
			}
			
			if (this.config.disposable) {
				this.destroy();
			}
			
			registry.overlay = null;
		}
		
		return this;
	};
	
	//removes the overlay from the body
	core.prototype.destroy = function() {
		if (this.$overlay) {
			this.$overlay.remove();
			this.$overlay = null;
		}
		
		return this;
	};
	
	
	//--------------------- TOOLTIPS ---------------------
	
	
	//define a tooltip overlay with special hover triggers
	var tooltip = function() {};
	tooltip.inheritsFrom(core);
	PHORK.overlay.tooltip = tooltip;
	
	//initializes the overlay and sets up the tooltip bindings
	tooltip.prototype.init = function($element, config) {
		this.delayed = {
			show: null, 
			hide: null
		};
		
		config = config || {};
		config.disposable = false;
		config.toolbar = false;
		
		if (!config.type) {
			config.type = 'tooltip';
		}
		if (!config.position) {
			config.position = 'cursor';
		}
		
		this.parent.init.call(this, $element, config);
		this.load().bind(null);
	};
	
	//sets up the tooltip triggers on mouseover and mouseout
	tooltip.prototype.bind = function(action) {
		var self = this;
		
		this.$element
			.bind('mouseover', function(e) {
				clearTimeout(self.delayed.hide);
				if (!self.$overlay.is(':visible')) {
					self.delayed.show = setTimeout(function() {
						self.show().resize();
					}, 400);
				}
			})
			.bind('mouseout', function(e) {
				clearTimeout(self.delayed.show);
				if (self.$overlay.is(':visible')) {
					self.delayed.hide = setTimeout(function() {
						self.hide();
					}, 400);
				}
			});
		;
		
		this.$overlay
			.bind('mouseover', function(e) {
				self.$element.trigger('mouseover');
			})
			.bind('mouseout', function(e) {
				self.$element.trigger('mouseout');
			})
		;
		
		$(window).bind('scroll', function() {
			self.$overlay.trigger('mouseleave');
		});
		
		return this;
	};
	
	
	//--------------------- MODALITY ---------------------
	
	
	//set up a single full screen body mask for modal windows
	$('body')
		.bind('disable', function() {
			var $this = $(this),
				$modal = $this.data('modal')
			;
			
			if (!$modal) {
				$this.data('modal', $modal = {
					counter: 0,
					$element: $('<div></div>')
						.addClass('disabled')
						.height($(document).height())
				});
				$this.append($modal.$element);
			}
			
			$modal.counter++;
			$modal.$element.show();
		})
		.bind('enable', function() {
			var $this = $(this),
				$modal = $this.data('modal')
			;
			
			if ($modal && --$modal.counter == 0) {
				$modal.$element.hide();
			}
		})
	;
});