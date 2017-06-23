$(document).ready(function() {
	$('#events').multiselect({
			enableFiltering: true,
			enableFullValueFiltering: true,
			enableClickableOptGroups: true,
			enableCollapsibleOptGroups: true,
			includeSelectAllOption: true,
			enableCaseInsensitiveFiltering: true,
			buttonWidth: '50%'
	});
	$('#calendars').multiselect({
			enableFiltering: true,
			enableFullValueFiltering: true,
			enableCaseInsensitiveFiltering: true,
			buttonWidth: '50%'
	});
	$('#categories').multiselect({
			enableFiltering: true,
			enableFullValueFiltering: true,
			enableClickableOptGroups: true,
			enableCollapsibleOptGroups: true,
			includeSelectAllOption: true,
			enableCaseInsensitiveFiltering: true,
			buttonWidth: '50%'
	});
	$('#calendars').change(function() {
		$.post( "ajax.php?module=calendar&command=groupeventshtml", {calendars: $(this).val(), categories: $("#categories").val()}, function( data ) {
			var cats = $("#categories").val();
			$("#categories").html(data.categorieshtml);
			$('#categories').multiselect('rebuild');
			$('#categories').multiselect('select', cats);
			$("#events").html(data.eventshtml);
			$('#events').multiselect('rebuild');
		});
	});
	$('#categories').change(function() {
		$.post( "ajax.php?module=calendar&command=groupeventshtml", {calendars: $("#calendars").val(), categories: $(this).val()}, function( data ) {
			$("#events").html(data.eventshtml);
			$('#events').multiselect('rebuild');
		});
	});

	if($('#calendars').length && $('#calendars').val() !== null) {
		$.post( "ajax.php?module=calendar&command=groupeventshtml", {calendars: $("#calendars").val(), categories: categories}, function( data ) {
			$("#categories").html(data.categorieshtml);
			$('#categories').multiselect('rebuild');
			$('#categories').multiselect('select', categories);
			$("#events").html(data.eventshtml);
			$('#events').multiselect('rebuild');
			$('#events').multiselect('select', events);
		});
	}
});
