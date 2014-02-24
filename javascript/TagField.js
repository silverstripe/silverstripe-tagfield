(function($) {
	$.entwine('ss.tagfield', function($){

		$('input.tagField').entwine({
			'onadd': function(){
				var tags = this.attr('tags');

				if(tags) {
					this.tagSuggest({
						tags: JSON.parse(tags),
						separator: $(this).attr('rel')
					});
				} else {
					this.tagSuggest({
						url:  $(this).attr('href'),
						delay: 300,
						separator: $(this).attr('rel')
					});
				}
			}
		});

	})
}(jQuery));
