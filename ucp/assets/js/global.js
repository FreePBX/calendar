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
						$("#eventModal").modal('show');
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
