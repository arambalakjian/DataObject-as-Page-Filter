<% if $CurrentFilterMessage %>
	<% with $CurrentFilterMessage %>
		<div class="message filterMessage $Class">			
			<p>$Message</p>
		</div>
	<% end_with %>
<% end_if %>