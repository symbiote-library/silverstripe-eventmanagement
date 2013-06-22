<% with $DateTime.Event %>
	<h1>$Title</h1>
	$Content
<% end_with %>

<% if $EventInFuture %>
	<h3>Register For Event</h3>

	<% if $EventIsFull %>
		<p>Sorry, this event is already full.</p>
	<% else %>
		<p id="register">
			<a href="$Link('register')">Register for event &raquo;</a>
		</p>
	<% end_if %>
<% end_if %>

<h3>Un-Register From Event</h3>

<p>
	To cancel a registration to this event, please enter your email address:
</p>

$UnRegisterForm
