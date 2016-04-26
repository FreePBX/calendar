
$(document).ready(function() {
  $('#calendar').fullCalendar({
    dayNames: daysOfWeek,
    dayNamesShort: daysOfWeekShort,
    displayEventEnd: true,
    customButtons: {
        addEvent: {
            text: 'Add Event',
            click: function() {
              $('#description').val('');
              $("#type").val('');
              resetDrawselects()
              $('.dest').addClass('hidden');
              $('#stime').val(moment(Date.now()).format("YYYY-MM-DD h:mm:ss"));
              $('#etime').val(moment(Date.now()).format("YYYY-MM-DD h:mm:ss"));
              $('#stime').datetimepicker('update');
              $('#etime').datetimepicker('update');
              $('#eventid').val('new');
              $('#eventModal').modal('show');
              if(event.canedit !== false){
                $("#modalSubmit").show();
              }else{
                $("#modalSubmit").hide();
              }
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
      $('#eventid').val(event.id);
      $("#type option[value='"+event.type+"']").prop('selected', true)
      $('#stime').val(moment(event.start).format("YYYY-MM-DD h:mm:ss"));
      $('#etime').val(moment(event.end).format("YYYY-MM-DD h:mm:ss"));
      $('#stime').datetimepicker('update');
      $('#etime').datetimepicker('update');
      $('#eventModal').modal('show');
      if(event.canedit !== false){
        $("#modalSubmit").show();
      }else{
        $("#modalSubmit").hide();
      }
      if(event.type == 'calflow'){
        $('.dest').removeClass('hidden');
      }else{
        $('.dest').addClass('hidden');
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
  $('#type').on('change',function(){
    if($('#type').val() == 'callflow'){
      $('.dest').removeClass('hidden');
    }else{
      $('.dest').addClass('hidden');
    }
  });
});
function resetDrawselects(){
  $(".destdropdown").each(function() {
    $(this).val($("this option:first").val())
		var v = $(this).val(),
				i = $(this).data("id");
		if(v !== "") {
			$(".destdropdown2").not("#" + v + i).addClass("hidden");
			$("#"+ v + i).removeClass("hidden");
		} else {
			$(".destdropdown2").addClass("hidden");
		}
	});
}
function setDrawselect(id,val){
  var vals = val.split(",");
  var dataid = $(id).data('id');
  $(id+" option[value='"+vals[0]+"']").prop('selected', true);
  $(vals[0]+dataid+" option[value='"+val+"']").prop('selected', true);
  console.log(vals[1]);
}

$.get( "ajax.php?module=calendar&command=destdetails&dest=ext-callrecording%2C1%2C1", function( data ) {
  console.log(data);
});
