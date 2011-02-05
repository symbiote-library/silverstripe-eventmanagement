;jQuery(function($) {
	$("#Type input").change(function() {
		$("#Price").toggle($(this).val() == "Price");
	});
	
	$("#StartType input").change(function() {
		$("#StartDate").toggle($(this).val() == "Date");
		$("#StartDaysStartHoursStartMins").toggle($(this).val() == "TimeBefore");
	});
	
	$("#EndType input").change(function() {
		$("#EndDate").toggle($(this).val() == "Date");
		$("#EndDaysEndHoursEndMins").toggle($(this).val() == "TimeBefore");
	});
	
	if ($("#Type input:checked").length) {
		$("#Type input:checked").trigger("change");
	} else {
		$("#Price").hide();
	}
	
	if ($("#StartType input:checked").length) {
		$("#StartType input:checked").trigger("change");
	} else {
		$("#StartDate").hide();
		$("#StartDaysStartHoursStartMins").hide();
	}
	
	if ($("#EndType input:checked").length) {
		$("#EndType input:checked").trigger("change");
	} else {
		$("#EndDate").hide();
		$("#EndDaysEndHoursEndMins").hide();
	}
});