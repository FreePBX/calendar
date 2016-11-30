var CalendarC = UCPMC.extend({
	init: function(){
    var self = this;
	},
	settingsDisplay: function() {
	},
	settingsHide: function() {
	},
	poll: function(data){
	},
	displayWidget: function(widget_id,dashboard_id) {
		var buttons = {
			addEvent: {
					text: _('Add Event'),
					click: function() {
						$.getJSON('index.php?quietmode=1&module=calendar&command=eventform&calendar_id='+widget_id, function(data){
							if(data.status === true){
								$('#globalModalBody').html(data.message);
								$(':checkbox').bootstrapToggle();
							}else{
								$('#globalModalBody').html('<h2>'+_("Error getting form")+'</h2>');
							}
						});
						$('#globalModalLabel').html('<h3>'+_("Add Event")+'</h3>');
						$('#globalModalFooter').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">'+_("Close")+'</button><button id="save" type="button" class="btn btn-primary">'+ _("Save changes")+'</button>');
						$("#globalModal").modal('show');
						$('#save').on('click',function(){
							$.ajax({
								type: 'POST',
								url: 'index.php?quietmode=1&module=calendar&command=saveform',
								data: $('#calform'+widget_id).serialize(),
								success: function (data) {
									console.log(data);
								}
							});
						});
					}
			}
		};

		calid = widget_id.replace(/calendar-/,'');
		$('#'+widget_id).fullCalendar({
			displayEventEnd: true,
			nextDayThreshold: '00:00:01',
			fixedWeekCount: false,
			contentHeight: "auto",
			eventSources: [{
				url: 'index.php?quietmode=1',
				type: 'GET',
				data: {
					module:'calendar',
					command: 'events',
					calendarid: calid
				}
			}],
			customButtons: buttons,
			buttonIcons: {
				prev: 'left-single-arrow',
				next: 'right-single-arrow',
				prevYear: 'left-double-arrow',
				nextYear: 'right-double-arrow',
				addEvent: 'fa fa-calendar-plus-o'
			},
			header: {
				left:	 'prev,addEvent,next',
				center: 'title',
				right:	'month,basicWeek,agendaDay'
			},
			eventClick: function(event, jsEvent, view){
			 console.log(event, jsEvent, view); 
		 	}
		});
	},
	displayWidgetSettings: function(widget_id,dashboard_id) {
	console.log(["normal:settings:show",widget_id,dashboard_id]);
	},
	displaySmallWidget: function(widget_id) {
		console.log(["small:show",widget_id]);
	},
	displaySmallWidgetSettings: function(widget_id) {
		$('#'+widget_id).fullCalendar({
			displayEventEnd: true,
			nextDayThreshold: '00:00:01',
			defaultView: 'listWeek',
			fixedWeekCount: false
		});
	},
	deleteWidget: function(widget_id,dashboard_id) {
		console.log(["normal:delete",widget_id,dashboard_id]);
	},
	deleteSmallWidget: function(widget_id) {
		console.log(["small:delete",widget_id]);
	},
	showDashboard: function(dashboard_id) {
		console.log(["dashboard",dashboard_id]);
	}
});
