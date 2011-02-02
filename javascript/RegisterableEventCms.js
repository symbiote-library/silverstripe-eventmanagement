;(function ($) {
	$("#Form_EditForm_RegEmailConfirm").live("click", function() {
		$("#AfterConfirmTitle, #AfterConfirmContent").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_RegEmailConfirm").livequery(function() {
		$("#AfterConfirmTitle, #AfterConfirmContent").toggle($(this).is(":checked"));
	});

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

	$("#Form_EditForm_RequireLoggedIn").live("click", function() {
		$("#OneRegPerMember").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_RequireLoggedIn").livequery(function() {
		$("#OneRegPerMember").toggle($(this).is(":checked"));
	});
})(jQuery);