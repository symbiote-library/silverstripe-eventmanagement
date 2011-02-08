<% require themedCSS(EventRegistrationDetails) %>

<div id="content" class="typography">
	<h2>$Title</h2>
	
	<% if Message %>
		<p id="registration-message" class="message">
			$Message
		</p>
	<% end_if %>
	
	<% control Registration %>
		<% if Status = Unconfirmed %>
			<p id="registration-unconfirmed" class="message bad">
				This registration has not yet been confirmed. In order to
				confirm your registration, please check your emails for a
				confirmation email and click on confirmation link contained in
				it.
			</p>
			
			<% if ConfirmTimeLimit %>
				<p id="registration-unconfirmed-limit" class="message bad">
					If you do not confirm your registration within
					$ConfirmTimeLimit.TimeDiff, it will be canceled.
				</p>
			<% end_if %>
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
	
	<% if Registration.Payment %>
		<% control Registration.Payment %>
			<h3>Payment Details</h3>
			<dl id="payment-details">
				<dt>Method:</dt>
				<dd>$PaymentMethod</dd>
				<dt>Amount:</dt>
				<dd>$Amount.Nice</dd>
				<dt>Status:</dt>
				<dd>$Status</dd>
			</dl>
		<% end_control %>
	<% end_if %>
	
	<% if HasTicketFile %>
		<% control Registration %>
			<% if Status = Valid %>
				<h3>Ticket File</h3>
				<p><a href="$Top.Link(ticketfile)">Download ticket file.</a></p>
			<% end_if %>
		<% end_control %>
	<% end_if %>
</div>