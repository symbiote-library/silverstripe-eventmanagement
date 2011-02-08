<h1>Registration Details For $Registration.Time.EventTitle ($SiteConfig.Title)</h1>

<p>To $Registration.Name,</p>

<p>
	Thank you for registering for $Registration.Time.EventTitle! Below are the
	details of the event and your registration:
</p>

<% control Registration %>
	<h2>Registration Details</h2>
	<dl>
		<dt>Name:</dt>
		<dd>$Name</dd>
		<dt>Email:</dt>
		<dd>$Email</dd>
		<dt>Event:</dt>
		<dd>$Time.EventTitle ($Time.Summary)</dd>
		<dt>Created:</dt>
		<dd>$Created.Nice</dd>
	</dl>
	
	<h2>Tickets</h2>
	<table>
		<thead>
			<tr>
				<th>Ticket Title</th>
				<th>Price</th>
				<th>Quantity</th>
			</tr>
		</thead>
		<tbody>
			<% control Tickets %>
				<tr>
					<td>$Title</td>
					<td>$PriceSummary</td>
					<td>$Quantity</td>
				</tr>
			<% end_control %>
		</tbody>
	</table>
	
	<p>
		<strong>Total Cost:</strong> <% if Total %>$Total.Nice<% else %>Free!<% end_if %>
	</p>
	
	<% if Payment %>
		<% control Payment %>
			<h2>Payment Details</h2>
			<dl>
				<dt>Method:</dt>
				<dd>$PaymentMethod</dd>
				<dt>Amount:</dt>
				<dd>$Amount.Nice</dd>
				<dt>Status:</dt>
				<dd>$Status</dd>
			</dl>
		<% end_control %>
	<% end_if %>
	
	<ul>
		<li><a href="$Link">Registration details</a></li>
		<li><a href="$Time.Link">Event details</a></li>
	</ul>
<% end_control %>