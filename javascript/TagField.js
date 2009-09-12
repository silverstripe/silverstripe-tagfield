(function($) {
	SSTagFieldLoader = function() {
		var tags = $(this).attr('tags');
		if(tags) {
			$(this).tagSuggest({
				tags:  tags,
				separator: $(this).attr('rel')
			});
		} else {
			$(this).tagSuggest({
				url:  $(this).attr('href'),
				separator: $(this).attr('rel')
			});
		}
	}
	
	if (typeof $(document).livequery != 'undefined') {
		$('input.tagField').livequery(SSTagFieldLoader);
	}	else {
		$(document).ready(function(){
			$('input.tagField').each(SSTagFieldLoader);
		});
	}
})(jQuery);
