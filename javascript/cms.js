jQuery(function($) {
	$.entwine("ss", function($) {
		var conditionals = {
			"#Form_EditForm_RegEmailConfirm": "#EmailConfirmMessage, #ConfirmTimeLimit",
			"#Form_EditForm_EmailNotifyChanges" : "#NotifyChangeFields",
			"#Form_ItemEditForm_EmailReminder": "#RemindDays"
		};

		$.each(conditionals, function (check, target) {
			$(check).entwine({
				onmatch: function() {
					if (!this.prop("checked")) {
						$(target).hide();
					}
				},
				onchange: function() {
					if (this.prop("checked")) {
						$(target).slideDown();
					} else {
						$(target).slideUp();
					}
				}
			});
		});
	});
});
