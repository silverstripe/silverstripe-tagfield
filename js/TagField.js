/**
 * Register TagField functions with fields.
 */
(function ($) {

	/*
	 * The multiple-select plugin (Chosen) applies itself to everything!
	 * We have to remove it before selectively applying select2, or
	 * we'll see an extra field where there should be only one.
	 */
	$.fn.chosenDestroy = function () {
		$(this)
			.show()
			.removeClass('chzn-done')
			.removeClass('has-chzn')
			.next()
				.remove();

		return $(this);
	};

	$.entwine('ss', function($) {

		$('.silverstripe-tag-field').entwine({
			'onadd': function() {
				var $this = $(this);

				/*
				 * Delay a cycle so we don't see 2 inputs...
				 */
				setTimeout(function () {
					$this.chosenDestroy()
						.select2({
							'tags': true,
							'tokenSeparators': [',', ' ']
						});

					/*
					 * Delay a cycle so select2 is initialised before
					 * selecting values (if data-selected-values is present).
					 */
					setTimeout(function () {
						if ($this.attr('data-selected-values')) {
							var values = $this.attr('data-selected-values');

							$this.select2('val', values.split(','));
						}
					}, 0);
				}, 0);
			}
		});
	});
})(jQuery);
