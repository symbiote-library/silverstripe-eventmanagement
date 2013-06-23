jQuery.entwine("ss", function($) {
	var toggles = {
		"#Form_ItemEditForm_Type": {
			"#Price": "Price"
		},
		"#Form_ItemEditForm_StartType": {
			"#StartDate": "Date",
			"#StartOffset": "TimeBefore"
		},
		"#Form_ItemEditForm_EndType": {
			"#EndDate": "Date",
			"#EndOffset": "TimeBefore"
		}
	};

	$.each(toggles, function(k, v) {
		$(k).entwine({
			onadd: function() {
				this.update();
			},
			"from .radio": {
				onchange: function() {
					this.update();
				}
			},
			update: function() {
				var current = this.find("input:checked").val();

				$.each(v, function(field, value) {
					$(field)[current == value ? "show" : "hide"]();
				});
			}
		});
	});
});
