;(function ($) {
$("a#event-do-invite").live("click", function() {
	GB_show(
		'',
		$(this).get(0).href,
		400,
		600,
		function() {
			var $field = $("#Form_EditForm_Invitations");
			$.ajax({
				url: $field.attr("href"),
				success: function(data) {
					$field.replaceWith($(data));
					Behaviour.apply("Form_EditForm_Invitations", true);
				}
			});
		});

	return false;
});
})(jQuery);