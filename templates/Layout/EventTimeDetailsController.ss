<% require themedCSS(EventTimeDetails) %>

<div id="content" class="typography">
	<div id="event-sidebar" class="right">
		<div class="event-sidebar-item">
			<h3>Add To Calendar</h3>
			<div class="event-sidebar-item-content">
				<a href="$DateTime.ICSLink">$DateTime.Summary</a>
			</div>
		</div>

		<div class="event-sidebar-item">
			<h3>Un-register From Event</h3>
			<div class="event-sidebar-item-content">
			</div>
		</div>
	</div>
	<div id="event-details">
		<% control DateTime %>
			<h2>$EventTitle</h2>
			<p class="event-details-date">$Summary</p>
	
			<h3>Register For Event</h3>
	
			<h3>Event Information</h3>
			$Event.Content
		<% end_control %>
	</div>
</div>