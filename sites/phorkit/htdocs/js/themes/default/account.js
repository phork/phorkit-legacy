$(function() {
	$('div.arrowed').each(function() {
		var $this = $(this),
			$arrow = $this.find('.arrow'),
			$boxed = $this.find('.boxed')
		;
		    
		if ($arrow.size() == 1 && $boxed.size()) {
			$boxed
				.mouseover(function() {
					$arrow.addClass('active');
				})
				.mouseout(function() {
					$arrow.removeClass('active');
				})
			;
		}
	});
	
	$('input.focused').first().focus();
});