$(function() { 
	
	//trigger the focus / blur label changing in the input box
	$('form div.input-block input')
		.bind('focus', function(e) {
			var $this = $(this);
			$this.parent().parent().find('label').hide();
		})
		.bind('blur', function(e) {
			var $this = $(this);
			if (!$this.val()) {
				$this.parent().parent().find('label').show();
			}
		})
		.each(function() {
			var $this = $(this);
			if ($this.val()) {
				$this.trigger('focus').trigger('blur');
			}
		});
	;
});