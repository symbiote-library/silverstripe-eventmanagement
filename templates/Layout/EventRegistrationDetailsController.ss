<h1>$Title</h1>

<% if $Message %>
	<p class="message">$Message</p>
<% end_if %>

<% with $Registration %>
	<% if $Status = "Unconfirmed" %>
		<p id="registration-unconfirmed" class="message">
			This registration has not yet been confirmed. In order to
			confirm your registration, please check your emails for a
			confirmation email and click on confirmation link contained in
			it.
		</p>

		<% if $ConfirmTimeLimit %>
			<p id="registration-unconfirmed-limit" class="message">
				If you do not confirm your registration within
				$ConfirmTimeLimit.TimeDiff, it will be canceled.
			</p>
		<% end_if %>
	<% end_if %>

	<% if $Status = "Canceled" %>
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
		<dd>$Time.Event.Title</dd>
		<dt>Created:</dt>
		<dd>$Created.Nice</dd>
		<dt>Status:</dt>
		<dd>$Status</dd>
	</dl>

	<h3>Tickets</h3>
	$TicketsTable.FieldHolder

	<% if $Registration.Payment %>
		<% with $Registration.Payment %>
			<h3>Payment Details</h3>
			<dl id="payment-details">
				<dt>Method:</dt>
				<dd>$PaymentMethod</dd>
				<dt>Amount:</dt>
				<dd>$Amount.Nice</dd>
				<dt>Status:</dt>
				<dd>$Status</dd>
			</dl>
		<% end_with %>
	<% end_if %>

	<% if $HasTicketFile %>
		<% with $Registration %>
			<% if $Status = "Valid" %>
				<h3>Ticket File</h3>
				<p><a href="$Top.Link("ticketfile")">Download ticket file.</a></p>
			<% end_if %>
		<% end_with %>
	<% end_if %>
<% end_with %>
