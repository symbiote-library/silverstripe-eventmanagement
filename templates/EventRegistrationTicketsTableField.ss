<% require css(eventmanagement/css/EventRegistrationTicketsTableField.css) %>
<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(eventmanagement/javascript/EventRegistrationTicketsTableField.js) %>

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
	<% if ShowTotalRow %>
		<tfoot>
			<tr>
				<td colspan="3"></td>
				<td>Total</td>
				<td><% if Total %>$Total.Nice<% else %>Free<% end_if %></td>
			</tr>
		</tfoot>
	<% else_if DateTime.Capacity %>
		<tfoot>
			<tr>
				<td colspan="3"></td>
				<td>Remaining Places</td>
				<td>$RemainingCapacity</td>
			</tr>
		</tfoot>
	<% end_if %>
	<tbody>
		<% if Tickets %>
			<% control Tickets %>
				<tr class="$EvenOdd $FirstLast <% if Last %>last <% end_if %> <% if Available %><% else %>event-tickets-unavailable<% end_if %>">
					<td class="title">
						$Title
						<% if Description %>
							<a href="#" class="event-tickets-toggle-description">Description</a>
						<% end_if %>
					</td>
					<% if Available %>
						<td class="available">$Available</td>
						<td class="on-sale-until">$End.Nice</td>
						<td class="price">$Price</td>
						<td class="quantity">$Quantity</td>
					<% else %>
						<td colspan="4">$Reason<% if AvailableAt %> Available at $AvailableAt.Nice.<% end_if %></td>
					<% end_if %>
				</tr>
				<% if Description %>
					<tr class="event-tickets-description">
						<td colspan="5">$Description</td>
					</tr>
				<% end_if %>
			<% end_control %>
		<% else %>
			<tr class="event-tickets-no-tickets">
				<td colspan="5">There are no tickets available.</td>
			</tr>
		<% end_if %>
	</tbody>
</table>