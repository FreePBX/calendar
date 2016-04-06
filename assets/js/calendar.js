
$(document).ready(function() {
  $('#calendar').fullCalendar({
    header: {
      left:   'prev',
      center: 'title',
      right:  'next'
    },
    height: 650,
    timezone: timezone,
    eventSources: [{
      url: 'ajax.php',
      type: 'GET',
      data: {
        module:'calendar',
        command: 'events'
      },
      error: function(){fpbxToast(_('There was an error fetching the events'),'','warning');},
    }],
    eventClick: function( event, jsEvent, view ) { console.log(event); }
  });
});
