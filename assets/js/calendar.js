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
							resetModalForm();
							$('#description').val('');
							$("#eventtype").val('');
							$('.dest').addClass('hidden');
							$('#startdate').val(moment(Date.now()).format("YYYY-MM-DD"));
							$('#enddate').val(moment(Date.now()).format("YYYY-MM-DD"));
							$('#startdate').datepicker('update');
							$('#enddate').datepicker('update');
							$('#eventid').val('new');
							$('#eventModal').modal('show');
							if(event.canedit !== false){
								$("#modalSubmit").show();
								$("modalDelete").hide();
							}else{
								$("#modalSubmit").hide();
								$("modalDelete").hide();
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
			var src = event;
			if(typeof event.parent !== "undefined"){
				src = event.parent;
			}
			console.log(src);
			resetModalForm();
			$('#description').val(src.title);
			$('#eventid').val(src.uid);
			$("#eventtype option[value='"+src.eventtype+"']").prop('selected', true);
			$('#startdate').val(moment(src.startdate).format("YYYY-MM-DD"));
			$('#enddate').val(moment(src.enddate).format("YYYY-MM-DD"));
			$('#startdate').datepicker('update');
			$('#enddate').datepicker('update');
			$('#modalDelete').attr('data-id',src.uid);
			if(typeof src.starttime !== "undefined"){
				$('#starttime').val(src.starttime);
			}
			if(typeof src.endtime !== "undefined"){
				$('#endtime').val(src.endtime);
			}
			$('#eventModal').modal('show');
			if(typeof src.truedest !== "undefined"){
				setDrawselect('goto0', src.truedest);
			}
			if(typeof src.weekdays !== "undefined"){
				for (var key in src.weekdays) {
					$('#weekdays').multiselect('select', key);
				}
			}
			if(typeof src.falsedest !== "undefined"){
				setDrawselect('goto1', src.falsedest);
			}
			if(src.canedit !== false){
				$("#modalSubmit").show();
				$("#modalDelete").show();
			}else{
				$("#modalSubmit").hide();
				$("#modalDelete").hide();
			}
			if(src.eventtype == 'callflow'){
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
	$('#starttime').clockpicker();
	$('#endtime').clockpicker();
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
	$('#'+id+' > option[value="'+item.category+'"]').prop("selected","selected");
	$("#"+id).trigger("change");
	$('#'+item.category+idx+' > option[value="'+item.destination+'"]').prop("selected","selected");
	$('#'+item.category+idx).trigger("change");
}

//Handle submition from modal.

$("#eventForm").submit(function(e) {
	e.preventDefault();
	var frm = $(this);
	var frmurl = frm.attr('action');
	var frmdata = frm.serializeArray();
	$.ajax({
		url : frmurl,
		type: "POST",
		data : frmdata,
		success:function(data, status){
			$('#eventModal').modal('hide');
			fpbxToast(data.message);
			$("#calendar").fullCalendar( 'refetchEvents' );
		},
		error: function(jqXHR, status, error){
			fpbxToast('Something Failed Unable to submit')
		}
	});
});
$("#modalDelete").on('click',function(e){
	console.log($(this));
});

function resetModalForm(){
	resetDrawselects();
	$('select').val('');
	$('input').val('');
	$('#weekdays').multiselect('rebuild');
}
