$(function() {
	PHORK.nav = function($parent) {
		this.$parent = $parent;
		this.hovered = {};
		this.offset = {};
		
		//initialize the subnav triggers
		this.init = function() {
			var $link = this.$parent.find('a').first();
			$link.after($trigger = $('<a>').attr('class', 'subnav').show());
			$link.width($link.width() + 20);
			this.$parent.data('trigger', $trigger);
			
			$('body').append($items = $('<ul></ul>')
				.data('parent', this.$parent)
				.attr('class', 'subnav')
				.hide()
			);
			this.$parent.data('items', $items);
			
			var self = this;
			this.$parent.data('items').hover(function(e) { self.hover(e, 'items', true); }, function(e) { self.hover(e, 'items', false); });
			this.$parent.hover(function(e) { self.hover(e, 'parent', true); }, function(e) { self.hover(e, 'parent', false); });
					
			this.$parent.data('trigger').click(function(e) {
				if (self.$parent.data('items').is(':visible')) {
					self.hide(e, true);
				} else {
					self.show(e);
				}
			});
			this.$parent.data('items').hover(function(e) {
				self.show(e);
			});
		};
		
		//adds the subnav list items
		this.append = function(links) {
			if (!this.$parent.data('trigger')) {
				this.init();
			}
			
			var $items = this.$parent.data('items');
			$(links).each(function(key, item) {
				if (typeof item == 'object') {
					$items.append($appended = $('<li></li>')
						.append($('<a>')
							.append(item.title)
								.attr({
									href: item.href
								})
							)
						)
					;
					
					if (item.spacer) {
						$appended.addClass('spacer');
					}
					
					if (item.id) {
						$appended.attr('id', item.id);
					}
				}
			});
			
			return this;
		};
		
		//sets the hover flag
		this.hover = function(e, element, on) {
			if (!(this.hovered[element] = on)) {
				var self = this;
				setTimeout(function() { 
					self.hide(); 
				}, 100);
			}
		};
		
		//shows and positions the subnav
		this.show = function(e) {
			this.$parent.addClass('hover');
			this.$parent.data('items')
				.css({
					position: 'absolute',
					left: this.$parent.offset().left + this.offset.left,
					top: this.$parent.offset().top + this.$parent.height() + this.offset.top
				})
				.stop(true, true)
				.slideDown('fast')
				.show();
		};
		
		//hides the subnav if there's no hover flag
		this.hide = function(e, force) {
			if (!force) {
				for (var i in this.hovered) {
					if (this.hovered[i]) {
						return;
					}
				}
			}
			this.$parent.data('items').slideUp('slow', function() {
				$(this).data('parent').removeClass('hover');
			});
		};
	};
});