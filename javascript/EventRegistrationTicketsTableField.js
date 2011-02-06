;(function($) {
	$("a.event-tickets-toggle-description").live("click", function() {
		var $desc = $(this).parents("tr").next();

		if ($desc.is(":visible")) {
			$(this).removeClass("open");
			$desc.hide();
		} else {
			$(this).addClass("open")
			$desc.show();
		}
	});
})(jQuery);