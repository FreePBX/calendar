$(document).ready(function() {
	$('#weekdays').multiselect({
		includeSelectAllOption: true,
		allSelectedText: _('Every Day')
	});
	$('#calendar').fullCalendar({
		dayNames: daysOfWeek,
		dayNamesShort: daysOfWeekShort,
		displayEventEnd: true,
    nextDayThreshold: '00:00:01',
		customButtons: {
				addEvent: {
						text: 'Add Event',
						click: function() {
							$('#description').val('');
							$("#eventtype").val('');
							resetDrawselects()
							$('.dest').addClass('hidden');
							$('#startdate').val(moment(Date.now()).format("YYYY-MM-DD"));
							$('#enddate').val(moment(Date.now()).format("YYYY-MM-DD"));
							$('#startdate').datepicker('update');
							$('#enddate').datepicker('update');
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
			left:	 'prev,addEvent,next',
			center: 'title',
			right:	'month,basicWeek,agendaDay'
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
			console.log(event);
			$('#description').val(event.title);
			$('#eventid').val(event.uid);
			$("#eventtype option[value='"+event.eventtype+"']").prop('selected', true);
			$('#startdate').val(moment(event.startdate).format("YYYY-MM-DD"));
			$('#enddate').val(moment(event.enddate).format("YYYY-MM-DD"));
			$('#startdate').datepicker('update');
			$('#enddate').datepicker('update');
			$('#eventModal').modal('show');
			if(typeof event.truedest !== "undefined"){
				setDrawselect('goto0', event.truedest);
			}
			if(typeof event.falsedest !== "undefined"){
				setDrawselect('goto1', event.falsedest);
			}
			if(event.canedit !== false){
				$("#modalSubmit").show();
			}else{
				$("#modalSubmit").hide();
			}
			if(event.eventtype == 'callflow'){
				$('.dest').removeClass('hidden');
			}else{
				$('.dest').addClass('hidden');
			}
		},
		dayClick: function( event, jsEvent, view ) { console.log(event); }

	});

	$("#startdate").datepicker({
		format: {
			toDisplay: function (date, format, language) {
					return moment(date).format("YYYY-MM-DD");
			},
			toValue: function (date, format, language) {
				return moment(date).format("YYYY-MM-DD");
			}
		},
	});

	$("#enddate").datepicker({
		format: {
			toDisplay: function (date, format, language) {
					return moment(date).format("YYYY-MM-DD");
			},
			toValue: function (date, format, language) {
				return moment(date).format("YYYY-MM-DD");
			}
		},
	});
	//Add bootstrap classes to full calendar
  $('.fc-button').addClass('btn btn-default');

  //Handle show hide for event type hidden fields
  $('#eventtype').on('change',function(){
		if($('#eventtype').val() == 'callflow'){
			$('.dest').removeClass('hidden');
		}else{
			$('.dest').addClass('hidden');
		}
	});
});

//Resets Drawselects
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
/*
 * setDrawselect - Sets draw select location.
 * @id = element id such as goto0
 * @val = destination such as Announcements,s,1
 */
function setDrawselect(id,val){
	var item = destinations[val];
	if(typeof item === "undefined"){
		resetDrawselects();
		return;
	}
	var idx = $('#'+id).data('id');
	$('#'+id+' > option[value="'+item.name+'"]').prop("selected","selected");
	$("#"+id).trigger("change");
	$('#'+item.name+idx+' > option[value="'+item.destination+'"]').prop("selected","selected");
	$('#'+item.name+idx).trigger("change");
}

//Handle submition from modal. This submits a name value pair for id and any visible element
//TODO: Need to return the message from the call rather than generic "Event Added" and error handling
$('#modalSubmit').on('click',function(e){
	e.preventDefault();
	var fields = $("#eventModal .form-control:visible").serializeArray();
	var submitdata = {};
	submitdata['module'] = 'calendar';
	submitdata['command'] = 'eventform';
	submitdata['id'] = $('#eventid').val();
	for ( var i=0, l=fields.length; i<l; i++	) {
		submitdata[fields[i].name] = fields[i].value;
	}
	$.ajax({
		type: "POST",
		url: 'ajax.php',
		data: submitdata,
		success: function(){
      fpbxToast('Event Added');
      $("#calendar").fullCalendar( 'refetchEvents' );
      }
	});
});
