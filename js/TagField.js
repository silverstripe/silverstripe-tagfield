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
			.show() // The field needs to be visible so Select2 evaluates the width correctly.
			.removeClass('chzn-done')
			.removeClass('has-chzn')
			.next()
				.remove();

		return $(this);
	};

	$.entwine('ss', function($) {

		$('.silverstripe-tag-field + .chzn-container').entwine({
			applySelect2: function () {
				var self = this,
					$select = $(this).prev();

				// There is a race condition where Select2 might not
				// be bound to jQuery yet. So here we make sure Select2
				// is defined before trying to invoke it.
				if ($.fn.select2 === void 0) {
					return setTimeout(function () {
						self.applySelect2();
					}, 0);
				}

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
			},
			onmatch: function() {
				this.applySelect2();
			}
		});
	});
})(jQuery);
