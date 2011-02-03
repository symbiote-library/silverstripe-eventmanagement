;(function ($) {
	function resetSelector() {
		$("li.selected").removeClass("selected").find(":checked").attr("checked", false);
	}
	
	function startLoading() {
		$("<div></div>", { "class": "loading" }).insertAfter($(".SelectionGroup").hide());
	}
	
	function stopLoading() {
		$("div.loading").remove() && $(".SelectionGroup").show();
	}
	
	function loadEmails() {
		var param = $(this).serialize();
		var url   = $(this).metadata().link + '?' + param;
		
		$(this).val("");
		resetSelector();
		startLoading();
		
		$.ajax({
			url: url,
			success: function (data) {
				var $table = $('#Form_InviteForm_Emails');
				
				var $last = $("tbody tr:last", $table);
				if ($(".Name input", $last).val() || $(".Email input", $last).val()) {
					$table.get(0).addRow({ target: $("a.addrow").get(0) });
				}
				
				$.each(data, function(k, val) {
					if (!$("td.Email input[value=" + val.email + "]", $table).length) {
						$("tbody tr:last", $table)
							.find(".Name input").val(val.name).end()
							.find(".Email input").val(val.email).end();
						$table.get(0).addRow({ target: $("a.addrow").get(0) });
					}
				});
				stopLoading();
			}
		});
	}
	
	$("#Form_InviteForm_GroupID").change(loadEmails);

	$("#Form_InviteForm_PastTimeID").change(loadEmails);
	
	$(function() {
		resetSelector();
	});
})(jQuery);