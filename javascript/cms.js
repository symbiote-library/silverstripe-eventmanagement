jQuery(function($) {
	$.entwine("ss", function($) {
		$("#Form_EditForm_RegEmailConfirm").entwine({
			onmatch: function() {
				if (!this.prop("checked")) {
					$("#EmailConfirmMessage, #ConfirmTimeLimit").hide();
				}
			},
			onchange: function() {
				if (this.prop("checked")) {
					$("#EmailConfirmMessage, #ConfirmTimeLimit").slideDown();
				} else {
					$("#EmailConfirmMessage, #ConfirmTimeLimit").slideUp();
				}
			}
		});

		$("#Form_EditForm_EmailNotifyChanges").entwine({
			onmatch: function() {
				if (!this.prop("checked")) {
					$("#NotifyChangeFields").hide();
				}
			},
			onchange: function() {
				if (this.prop("checked")) {
					$("#NotifyChangeFields").slideDown();
				} else {
					$("#NotifyChangeFields").slideUp();
				}
			}
		})
	});
});
