<div class="innerBox filterBox">
	
	<h2>Filter <% if $CurrentFilterString %><a href="$Link"><strong>X</strong> Clear filter</a><% end_if %></h2>
	
	<% if $DateFilters %>	
		<% include DateFilters %>
	<% end_if %>
	
	<% loop $Filters %>
		<h3>$Title</h3>
		<div class="filter">
			<ul class="clearfix">
				<% loop $Options %>
					<li><a class="$LinkingMode greyGradientButton" href="$Link"><span></span>$Title</a></li>
				<% end_loop %>
			</ul>				
		</div>
	<% end_loop %>
						
</div>	