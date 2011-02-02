;(function ($) {
	$("#Form_EditForm_MultiplePlaces").live("click", function() {
		$("#MaxPlaces").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_MultiplePlaces").livequery(function() {
		$("#MaxPlaces").toggle($(this).is(":checked"));
	});
})(jQuery);