<% require css(event_calendar/css/calendar.css) %>
<% require themedCSS(RegisterableEvent) %>
<% require javascript(event_calendar/javascript/calendar_core.js) %>

<div id="content" class="typography">
	<div id="calendar-sidebar">
		<h3><% _t('BROWSECALENDAR','Browse the Calendar') %></h3>
		<div id="monthNav">
			<p><% _t('USECALENDAR','Use the calendar below to navigate dates') %></p>
			$CalendarWidget
			$MonthNavigator
			$CalendarFilterForm
		</div>
	</div>

	<div id="calendar-main">
		<div id="topHeading" class="clearfix">
			<span class="feed"><a href="$RSSLink"><% _t('SUBSCRIBE','Subscribe to the Calendar') %></a></span>
			<h2>$Title</h2>
			$Content
		</div>

		<div id="dateHeader">
			<% if DateHeader %>
				<h3>$DateHeader</h3>
			<% end_if %>
		</div>

		<% if Events %>
			<div id="events">
				<% control Events %>
					<div class="vevent clearfix">
						<div class="dates">
							$_Dates
							<% if RegisterLink %>
								<div class="registration-links">
									<% if CanRegister %>
										<a href="$RegisterLink" class="register"><% _t('REGISTER','Register') %></a>
									<% else %>
										<em class="no-places"><% _t('NOPLACES','No places remaining') %></em>
									<% end_if %>
									<a href="$UnregisterLink" class="unregister"><% _t('UNREGISTER','Un-Register') %></a>
								</div>
							<% end_if %>
						</div>
						<div class="details">
							<h3 class="summary"><% if Announcement %>$EventTitle<% else %><a href="$Link">$EventTitle</a><% end_if %></h3>
							<dl>
							<% if AllDay %>
								<dt><% _t('ALLDAY','All Day') %></dt>
							<% else %>
								<% if StartTime %>
								<dt><% _t('TIME','Time') %>:&nbsp;</dt>
										<dd>$_Times</dd>
								<% end_if %>
							<% end_if %>
							</dl>
							<div class="description">
									<% if Announcement %>
										$Content
									<% else %>
										<% control Event %>$Content.LimitWordCount(60)<% end_control %> <a href="<% if DetailsLink %>$DetailsLink<% else %>$Link<% end_if %>"><% _t('MORE','more...') %></a>
									<% end_if %>
									<% if OtherDates %>
									<h4><% _t('SEEALSO','See also') %>:</h4>
									<ul>
									<% control OtherDates %>
										<li><a href="<% if DetailsLink %>$DetailsLink<% else %>$Link<% end_if %>" title="$Event.Title">$_Dates</a>
											<% if StartTime %>
												<ul>
													<li>$_Times</li>
												</ul>
											<% end_if %>
										</li>
									<% end_control %>
									</ul>
									<% end_if %>
							</div>
						</div>
						<ul class="utility">
							<li><a class="btn add" href="$ICSLink"><% _t('ADD','Add to Calendar') %></a></li>
						</ul>
					</div>
				<% end_control %>
			</div>
		<% else %>
			<% _t('NOEVENTS','There are no events') %>.
		<% end_if %>
	</div>
</div>