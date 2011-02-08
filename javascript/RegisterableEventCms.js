;(function ($) {
	$("#Form_EditForm_RegEmailConfirm").live("click", function() {
		var sel = "#EmailConfirmMessage, #ConfirmTimeLimit, #AfterConfirmTitle, #AfterConfirmContent";
		$(sel).toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_RegEmailConfirm").livequery(function() {
		var sel = "#EmailConfirmMessage, #ConfirmTimeLimit, #AfterConfirmTitle, #AfterConfirmContent";
		$(sel).toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_UnRegEmailConfirm").live("click", function() {
		$("#AfterConfUnregTitle, #AfterConfUnregContent").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_UnRegEmailConfirm").livequery(function() {
		$("#AfterConfUnregTitle, #AfterConfUnregContent").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_EmailNotifyChanges").live("click", function() {
		$("#NotifyChangeFields").toggle($(this).is(":checked"));
	});

	$("#Form_EditForm_EmailNotifyChanges").livequery(function() {
		$("#NotifyChangeFields").toggle($(this).is(":checked"));
	});
})(jQuery);