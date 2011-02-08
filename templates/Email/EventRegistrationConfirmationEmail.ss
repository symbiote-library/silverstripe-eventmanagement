<h1>Confirm Registration For $Time.EventTitle ($SiteConfig.Title)</h1>

<p>To $Name,</p>

<p>
	You have registered for $Time.EventTitle at $Time.Summary. In order to
	confirm your attendance at this event, please <a href="$ConfirmLink">click
	here</a>, or visit the link below:
</p>

<p><a href="$ConfirmLink">$ConfirmLink</a></p>

<p>
	<strong>Important:</strong> You must confirm your registration before it
	is marked as valid.
	<% if Registration.ConfirmTimeLimit %>
		If you do not confirm your registration within
		$Registration.ConfirmTimeLimit.TimeDiff, it will be canceled.
	<% end_if %>
</p>

<p>
	To view the details of your registration, please <a href="$RegLink">click here</a>.
</p>

<p>
	If you did not register for this event, or believe this email was sent to
	you in error, please ignore this email and no further action will be taken.
</p>