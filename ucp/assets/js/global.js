var CalendarC = UCPMC.extend({
	init: function(){
    var self = this;
	},
  display: function(event) {
    $('#calendar').fullCalendar({
  		displayEventEnd: true,
  		nextDayThreshold: '00:00:01',
			width: 450
    });
  },
	settingsDisplay: function() {
	},
	settingsHide: function() {
	},
	poll: function(data){
	}

});
