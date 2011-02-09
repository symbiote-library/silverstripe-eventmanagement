<% require themedCSS(EventTimeDetails) %>

<div id="content" class="typography">
	<div id="event-sidebar" class="right">
		<div class="event-sidebar-item">
			<h3>Add To Calendar</h3>
			<div class="event-sidebar-item-content">
				<a href="$DateTime.ICSLink">$DateTime.Summary</a>
			</div>
		</div>

		$ExtraSidebarContent

		<div class="event-sidebar-item">
			<h3>Un-register From Event</h3>
			<div class="event-sidebar-item-content">
				<p>
					To cancel a registration to this event, please enter your
					email address:
				</p>
				$UnregisterForm
			</div>
		</div>
	</div>
	<div id="event-details">
		<% control DateTime %>
			<h2>$EventTitle</h2>
			<p class="event-details-date">$Summary</p>
		<% end_control %>

		<% if EventInFuture %>
			<h3>Register For Event</h3>
			<% if EventIsFull %>
				<p>Sorry, this event is already full.</p>
			<% else %>
				<p id="register">
					<a href="$Link(register)">Register for event &raquo;</a>
				</p>
			<% end_if %>
		<% end_if %>

		<h3>Event Information</h3>
		$DateTime.Event.Content
	</div>
</div>