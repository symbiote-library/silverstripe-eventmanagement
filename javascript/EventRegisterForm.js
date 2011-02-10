;(function($) {
	var $countdown = $("#registration-countdown");

	if ($countdown.length) {
		var times = $countdown.text().match(/([0-9]{2}):([0-9]{2}):([0-9]{2})/);
		var total = parseInt(times[1], 10) * 3600 + parseInt(times[2], 10) * 60 + parseInt(times[3], 10);

		// Handles updating the countdown timer showing the user how long they
		// have left to complete the countdown, and reloads the page when the
		// session expires.
		function updateCountdown() {
			--total;

			// If the session has expired, then notify the user and reload the
			// page so they are redirected by the server back to the start.
			if (total <= 0) {
				alert(ss.i18n._t(
					'EventManagement.REGISTRATIONEXPIRED', 'Sorry, but your'
					+ ' registration session has expired and your tickets'
					+ ' have been released. Please try registering again.'));

				clearInterval(interval);
				location.reload();

				return;
			}

			// Otherwise split the sseconds up into hours, minutes and seconds
			// and set them as the text.
			var hours = Math.floor(total / 3600);
			var mins  = Math.floor((total - hours * 3600) / 60);
			var secs  = total - hours * 3600 - mins * 60;

			hours = ("00" + hours).slice(-2);
			mins  = ("00" + mins).slice(-2);
			secs  = ("00" + secs).slice(-2);

			$countdown.text([hours, mins, secs].join(":"));
		}

		var interval = setInterval(updateCountdown, 1000);
	}
})(jQuery);