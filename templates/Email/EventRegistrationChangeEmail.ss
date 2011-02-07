<h1>Event Details Changed For $Time.EventTitle ($SiteConfig.Title)</h1>

<p>To $Name,</p>

<p>
	You recently registered for $Time.EventTitle on $Time.Summary. Some of the
	details for the event have changed:
</p>

<dl>
	<% control Changed %>
		<dt>$Label</dt>
		<dd>$After <% if Before %>(was $Before)<% end_if %></dd>
	<% end_control %>
</dl>

<p>
	To view further details for this event, please <a href="$Link">click here</a>
</p>