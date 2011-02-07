<% require themedCSS(EventRegistrationDetails) %>

<div id="content" class="typography">
	<h2>$Title</h2>
	
	<% control Registration %>
		<% if Status = Unconfirmed %>
			<p id="registration-unconfirmed" class="message bad">
				This registration has not yet been confirmed. In order to
				confirm your registration, please check your emails for a
				confirmation email and click on confirmation link contained in
				it.
			</p>
		<% end_if %>
		
		<% if Status = Canceled %>
			<p id="registration-canceled" class="message">
				This registration has been canceled.
			</p>
		<% end_if %>
		
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