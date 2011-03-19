$(function() {
	var utils = PHORK.utils,
		overlay = PHORK.overlay
	;

	//add back to top elements to each headline
	$('h3').each(function() {
		$(this).append(
			$('<span class="top">Back to top</span>').bind('click', function() {
				$(window).trigger('goto', [0]);
			})
		)
	});

	//attach a click event to each nav item to scroll to the right position
	$('#toc li a').bind('click', function(e) {
		e.preventDefault();
		
		var $content = $($(this).attr('href'));
		if ($content.size()) {
			$(window).trigger('goto', [$content.offset().top] - $('div.columns .column.left').position().top);
		}
	});
	
	//set up and event to turn the right column into fixed position
	var $nav = $('div.columns .column.right').each(function() {
		var $this = $(this)
			$window = $(window),
			position = $this.position()
		;
		
		if ($this.height() + $this.offset().top < $window.height() - $('#footer-shift').height()) {
			$this
				.data('top', position.top)
				.data('left', position.left)
				.bind('affix', function() {
					var $this = $(this);
					$this
						.css({
							top: $this.offset().top,
							left: $this.offset().left
						})
						.addClass('fixed')
					;
				})
				.bind('unfix', function() {
					var $this = $(this);
					$this
						.removeClass('fixed')
						.css({
							top: $this.data('top'),
							left: $this.data('left')
						})
					;
				})
				.trigger('affix')
			;
			
			$window
				.bind('resizing', function() {
					$this.trigger('unfix');
				})
				.bind('resized', function() {
					$this.trigger('affix');
				})
			;
		}
	});
	
	//show a delayed alert when the page loads the first time
	if (!utils.cookies.get('demo')) {
		setTimeout(function() {
			new utils.notify.insert('Everything that you see on this site is included in the Phork/it package. Please look around, sign up, play with the UI, and see what it can do.');
			utils.cookies.set('demo', 1, 1)
		}, 1000);
	}
	
	//attach a click event to alerter elements to show a new alert
	$('.alerter').bind('click', function() {
		new utils.notify.insert('This is an example of an alert using Javascript from <em>/js/themes/default/demo.js</em> and the alerter utility in <em>/js/themes/default/common/utils.js</em>');
	});
	
	//set up the various example triggers
	$('.trigger')
		.bind('click', function() {
			var $this = $(this);
			switch ($this.data('trigger')) {
				case 'subnav':
					$('#nav-about').data('trigger').trigger('click');
					setTimeout(function() {
						$('#nav-about').data('trigger').trigger('click')
					}, 1000);
					break;
					
				case 'confirm':
					new utils.notify.overlay('This is a pretty awesome overlay, right?', {
						cancelled: {
							type: 'cancel',
							value: 'Not really',
							close: true
						},
						confirmed: {
							value: 'Yes it is!',
							close: true,
							action: function() {
								$(window).trigger('goto', [0]);
								setTimeout(function() {
									new utils.notify.insert('Thanks! You get another alert demo for that. Ta-da!', false);
								}, 400);
							}
						}
					});
					break;
			}
		})
		.addClass('pointer')
	;
	
	//initialize each API link to open in an overlay
	$('.code a.output').each(function() {
		var $this = $(this);
		new PHORK.overlay.core()
			.init($this, {
				source: 'url',
				type: 'code',
				position: 'center',
				modal: true,
				toolbar: true,
				width: 800,
				initialized: function(self, success, xhr) {
					if (xhr.responseText) {
						self.$toolbar.prepend($('<span></span>').text(self.$element.attr('href')));
						self.$content
							.empty()
							.append($('<div></div>')
								.addClass('scrollable')
								.append($('<pre></pre>')
									.text(xhr.responseText)
								)
							)
						;
					}
				}
			})
			.bind('click')
		;
	});
});