
$(document).ready(function() {
  $('#calendar').fullCalendar({
    dayNames: daysOfWeek,
    dayNamesShort: daysOfWeekShort,
    displayEventEnd: true,
    customButtons: {
        addEvent: {
            text: 'Add Event',
            click: function() {
                alert('clicked the custom button!');
            }
        }
    },
    buttonIcons: {
      prev: 'left-single-arrow',
      next: 'right-single-arrow',
      prevYear: 'left-double-arrow',
      nextYear: 'right-double-arrow',
      addEvent: 'fa fa-calendar-plus-o'
    },
    header: {
      left:   'prev,addEvent,next',
      center: 'title',
      right:  'month,basicWeek,agendaDay'
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
    eventClick: function( event, jsEvent, view ) {
      $('#description').val(event.title);
      $("#type option[value='"+event.type+"']").prop('selected', true)
      $('#stime').val(moment(event.start).format("YYYY-MM-DD h:mm:ss"));
      $('#etime').val(moment(event.end).format("YYYY-MM-DD h:mm:ss"));
      $('#stime').datetimepicker('update');
      $('#etime').datetimepicker('update');
      $('#eventModal').modal('show');
      console.log(event.canedit);
      if(event.canedit !== false){
        $("#modalSubmit").show();
      }else{
        $("#modalSubmit").hide();
      }
    },
    dayClick: function( event, jsEvent, view ) { console.log(event); }

  });
  $("#stime").datetimepicker({
      format: "dd MM yyyy - hh:ii"
  });
  $("#etime").datetimepicker({
      format: "dd MM yyyy - hh:ii"
  });
  $('.fc-button').addClass('btn btn-default');
});
