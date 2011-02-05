<% require css(eventmanagement/css/EventRegistrationTicketsTableField.css) %>

<table id="$ID" class="$CSSClasses event-tickets field">
	<thead>
		<tr>
			<th>Ticket Title</th>
			<th>Available</th>
			<th>On Sale Until</th>
			<th>Price</th>
			<th>Quantity</th>
		</tr>
	</thead>
	<tbody>
		<% if Tickets %>
			<% control Tickets %>
				<tr class="$EvenOdd $FirstLast <% if Available %><% else %>event-tickets-unavailable<% end_if %>">
					<td class="title">$Title</td>
					<% if Available %>
						<td class="available">$Available</td>
						<td class="on-sale-until">$End.Nice</td>
						<td class="price">$Price</td>
						<td class="quantity">$Quantity</td>
					<% else %>
						<td colspan="4">$Reason<% if AvailableAt %> Available at $AvailableAt.Nice.<% end_if %></td>
					<% end_if %>
				</tr>
			<% end_control %>
		<% else %>
			<tr class="event-tickets-no-tickets">
				<td colspan="5">There are no tickets available.</td>
			</tr>
		<% end_if %>
	</tbody>
</table>