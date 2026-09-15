(function ($) {
	"use strict";

	$(function () {
		var $tbody = $(".wp-list-table tbody");
		if (!$tbody.length || typeof lowCntList === "undefined") {
			return;
		}

		$tbody.sortable({
			handle: ".low-cnt-order-handle",
			placeholder: "low-cnt-sort-placeholder",
			helper: function (event, ui) {
				ui.children().each(function () {
					$(this).width($(this).width());
				});
				return ui;
			},
			update: function () {
				var ids = [];
				$tbody.children("tr").each(function () {
					var id = $(this).attr("id");
					if (id && id.indexOf("post-") === 0) {
						ids.push(id.replace("post-", ""));
					}
				});

				$.post(lowCntList.ajaxUrl, {
					action: "low_cnt_reorder",
					nonce: lowCntList.nonce,
					ids: ids,
				}).done(function () {
					$tbody.children("tr").each(function (index) {
						$(this).find(".low-cnt-order-value").text(index);
					});
				});
			},
		});
	});
})(window.jQuery);
