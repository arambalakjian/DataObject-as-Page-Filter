<div class="typography">
	<% if $Children %>
		<% include SideBar %>
		<div id="Content">
	<% end_if %>
	
	  	<% include BreadCrumbs %>
       
        <% include FilterBox %>		
       
        <% include FilterMessage %>		
        
		<% loop $Items %>
			<h2>$Title</h2>
			<a href="$Link">View</a>
		<% end_loop %>
		
	<% if $Children %>
		</div>
	<% end_if %>
</div>
