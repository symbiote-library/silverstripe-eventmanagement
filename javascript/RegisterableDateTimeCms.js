;(function ($) {
	var send = $("input[name='EmailReminder']");
	var time = $("#Sendthereminderemail");
	
	send.live("click", function() {
		time.toggle(send.is(":checked"));
	});
	
	time.toggle(send.is(":checked"));
})(jQuery);