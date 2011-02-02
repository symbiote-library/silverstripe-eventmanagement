;(function ($) {
	$("input[name='LimitedPlaces']").live("click", function() {
		$("#NumPlaces").toggle($(this).is(":checked"));
	});

	$("input[name='LimitedPlaces']").livequery(function() {
		$("#NumPlaces").toggle($(this).is(":checked"));
	});
})(jQuery);