$(function() {
	var overlay = PHORK.overlay;
	
	//set up the user tooltip hover states
	$('a.tooltip.userbox').livequery(function() {
		var $this = $(this);
		new overlay.tooltip().init($this, {
			source: 'template',
			type: 'userbox',
			position: 'attached',
			solo: true,
			transition: {
				hide: 'fadeOut'
			}
		});
		$this.attr('title', '');
	});
});