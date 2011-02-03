<html>
	<head>
		<% base_tag %>
		
		<style type="text/css">
			body { font: 11px/16px sans-serif; }
			fieldset { margin: 0; padding: 0; border: none; }
			th { padding: 4px; }
			a { color: #0074C6; }
			a.addrow { font-size: 12px; }
			.Actions { margin-top: 10px; }
			input.action { font-weight: bold; padding: 2px; cursor: pointer; }
		</style>
	</head>
	<body>
		<% if Result %>
			<h2><% _t('RESULT', 'Result') %></h2>
			<ul>
				<% control Result %>
					<li>
						$Name &lt;$Email&gt;
						<% if Sent %>
							<% _t('SENT', 'Sent') %>
						<% else %>
							<strong><% _t('NOTSENT', 'Not sent:') %> $Reason</strong>
						<% end_if %>
					</li>
				<% end_control %>
			</ul>
		<% else %>
			$Form
		<% end_if %>
	</body>
</html>