<% require themedCSS(RegisterableEvent) %>
<div id="content" class="typography">
	<% control DateTime %>
		<h2>$EventTitle</h2>
		<div class="registration-links">
			<% if CanRegister %>
				<a href="$RegisterLink" class="register"><% _t('REGISTER','Register') %></a>
			<% else %>
				<em class="no-places"><% _t('NOPLACES','No places remaining') %></em>
			<% end_if %>
			<a href="$UnregisterLink" class="unregister"><% _t('UNREGISTER','Un-Register') %></a>
		</div>

		<p class="dates">
			$_Dates
			<span class="times">
				<% if AllDay %>
					(<% _t('ALLDAY','All Day') %>)
				<% else_if StartTime %>
					($_Times)
				<% end_if %>
			</span>
		</p>
		
		$Event.Content
	<% end_control %>
</div>