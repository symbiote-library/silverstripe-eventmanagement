<% require themedCSS(EventRegistrationDetails) %>

<div id="content" class="typography">
	<h2>$Title</h2>
	
	<% control Registration %>
		<dl id="registration-details">
			<dt>Name:</dt>
			<dd>$Name</dd>
			<dt>Email:</dt>
			<dd>$Email</dd>
			<dt>Event:</dt>
			<dd>$Time.EventTitle ($Time.Summary)</dd>
			<dt>Created:</dt>
			<dd>$Created.Nice</dd>
			<dt>Status:</dt>
			<dd>$Status</dd>
		</dl>
	<% end_control %>
	
	<h3>Tickets</h3>
	$TicketsTable.FieldHolder
</div>