(function($) {
	SSTagFieldLoader = function() {
		var tags = $(this).attr('tags');
		if(tags) {
			$(this).tagSuggest({
				tags: JSON.parse(tags),
				separator: $(this).attr('rel')
			});
		} else {
			$(this).tagSuggest({
				url:  $(this).attr('href'),
				delay: 300,
				separator: $(this).attr('rel')
			});
		}
	}
	
	
	$(document).ready(function(){
		if (typeof $(document).livequery != 'undefined') 
			$('input.tagField').livequery(SSTagFieldLoader);
		else	$('input.tagField').each(SSTagFieldLoader);
	});
})(jQuery);
