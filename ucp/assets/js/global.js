var CalendarC = UCPMC.extend({
	init: function(){
    var self = this;
	},
  display: function(event) {
    $('#calendar').fullCalendar({
  		dayNames: daysOfWeek,
  		dayNamesShort: daysOfWeekShort,
  		displayEventEnd: true,
  		nextDayThreshold: '00:00:01'
    });
  },
	settingsDisplay: function() {
	},
	settingsHide: function() {
	},
	poll: function(data){
	}

});
