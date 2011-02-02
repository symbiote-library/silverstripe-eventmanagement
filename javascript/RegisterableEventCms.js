;(function ($) {
	$("#Form_EditForm_LimitedPlaces").live("click", function() {
		$("#NumPlaces").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_LimitedPlaces").livequery(function() {
		$("#NumPlaces").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_MultiplePlaces").live("click", function() {
		$("#MaxPlaces").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_MultiplePlaces").livequery(function() {
		$("#MaxPlaces").toggle($(this).is(":checked"));
	});
})(jQuery);