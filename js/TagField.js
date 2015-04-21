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
			.removeClass('chzn-done')
			.removeClass('has-chzn')
			.next()
				.remove();

		return $(this);
	};

	$.entwine('ss', function($) {

		$('.silverstripe-tag-field + .chzn-container').entwine({
			onmatch: function() {
				var $select = $(this).prev();

				$select
					.chosenDestroy()
					.select2({
						'tags': true,
						'tokenSeparators': [',', ' ']
					});

				/*
				 * Delay a cycle so select2 is initialised before
				 * selecting values (if data-selected-values is present).
				 */
				setTimeout(function () {
					if ($select.attr('data-selected-values')) {
						var values = $select.attr('data-selected-values');

						$select.select2('val', values.split(','));
					}
				}, 0);
			}
		});
	});
})(jQuery);
