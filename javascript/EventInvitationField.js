;(function ($) {
$("a#event-do-invite").live("click", function() {
	GB_show(
		'',
		$(this).get(0).href,
		400,
		600);

	return false;
});
})(jQuery);