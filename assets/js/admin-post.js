(function ($) {
	"use strict";

	$(function () {
		$(".low-cnt-color-field").each(function () {
			var $field = $(this);
			$field.wpColorPicker({
				defaultColor: $field.data("default-color") || false,
			});
		});
	});
})(window.jQuery);
