<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<% base_tag %>
		
		<style type="text/css">
			h1 { font-size: 24px !important; margin: 0 0 10px 0; }
			.registration { border: 2px solid #CCC; margin-bottom: 15px; }
			.registration h3 { background: #F4F4F4; border-bottom: 2px solid #CCC; padding: 5px; font-size: 20px; margin: 0; }
			.registration h3 span { font-size: 20px; color: #999; float: right; }
			.registration dl { margin: 5px; padding: 0; }
			.registration dt { width: 80px; float: left; font-weight: bold; }
			.registration dt, .registration dd { font-size: 12px; line-height: 18px; }
			.registration .tickets { margin: 5px; }
			.registration table { border-collapse: collapse; width: 100%;
				border-left: 1px solid #CCC; border-top: 1px solid #CCC; }
			.registration thead { background: #F4F4F4; }
			.registration th, .registration td { border-right: 1px solid #CCC; border-bottom: 1px solid #CCC; padding: 5px; }
		</style>
	</head>
	<body onload="window.print();">
		<% if SourceItems %>
			<% control SourceItems.First %>
				<h1>Event Registrations For $Time.EventTitle</h1>
			<% end_control %>
			<% control SourceItems %>
				<div class="registration">
					<h3>$Name <span>#$ID</span></h3>
					<dl>
						<dt>Name:</dt>
						<dd>$Name ($Email)</dd>
						<dt>Event:</dt>
						<dd>$Time.EventTitle at $Time.Summary</dd>
						<dt>Order Info:</dt>
						<dd>Registration #$ID, Ordered at $Created.Nice</dd>
						<dt>Status:</dt>
						<dd>$Status</dd>
					</dl>
					<div class="tickets">
						<table>
							<thead>
								<tr>
									<th>Ticket Title</th>
									<th>Quantity</th>
									<th>Price</th>
								</tr>
							</thead>
							<tbody>
								<% control Tickets %>
									<tr>
										<td>$Title</td>
										<td>$Quantity</td>
										<td>$Price.Nice</td>
									</tr>
								<% end_control %>
							</tbody>
						</table>
					</div>
				</div>
			<% end_control %>
		<% else %>
			<p>There are no registrations to print.</p>
		<% end_if %>
	</body>
</html>