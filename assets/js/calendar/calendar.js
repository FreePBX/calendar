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
			fpbxToast('Something Failed Unable to submit');
		}
	});
});
$("#modalDelete").on('click',function(e){
	if(readonly) {
		return;
	}
	$.post( "ajax.php?module=calendar&command=delevent", {calendarid: calendarid, eventid: $("#eventid").val()}, function( data ) {
		$("#calendar").fullCalendar( 'refetchEvents' );
	});
});

function resetModalForm(){
	$(".time").show();
	$('input[type=checkbox]').prop("checked",false);
	$('select')[0].selectedIndex = 0;
	$('input[type=text]').val('');
	$("#timezone").val('');
	$("#timezone").multiselect('select', '');
	updateReoccurring();
	$("#repeat0").prop("checked",true);
	$("modalDelete").hide();
	updateAllDay();
}

function actionformatter(v,r) {
	return '<div class="actions"><a href="?display=calendar&amp;action=view&amp;type=calendar&amp;id='+r.id+'"><i class="fa fa-eye" aria-hidden="true"></i></a><a href="?display=calendar&amp;action=edit&amp;type=calendar&amp;id='+r.id+'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a><a href="?display=calendar&amp;action=delete&amp;type=calendar&amp;id='+r.id+'"><i class="fa fa-trash" aria-hidden="true"></i></a></div>';
}
var test = '';
var buttons = {};
if($('#calendar').length && !readonly) {
	buttons = {
			addEvent: {
					text: _('Add Event'),
					click: function() {
						resetModalForm();
						$('#description').val('');
						$("#eventtype").val('');
						$('.dest').addClass('hidden');
						$('#startdate').val(moment(Date.now()).format("YYYY-MM-DD"));
						$('#enddate').val(moment(Date.now()).format("YYYY-MM-DD"));
						$('#starttime').val(moment(Date.now()).format("kk:mm"));
						$('#endtime').val(moment(Date.now()).format("kk:mm"));
						$('#startdate').datepicker('update');
						$('#enddate').datepicker('update');
						$('#eventid').val('new');
						$('#eventModal').modal('show');
						$('#modalDelete').data('id', null);
						if(event.canedit !== false){
							$("#modalSubmit").show();
							$("modalDelete").hide();
						}else{
							$("#modalSubmit").hide();
							$("modalDelete").hide();
						}
						updateReoccurring();
						updateAllDay();
					}
			}
	};
}

$(document).ready(function() {
	$("#allday").click(function() {
		if($(this).is(":checked")) {
			$(".time").addClass("hidden");
		} else {
			$(".time").removeClass("hidden");
		}
	});
	var daysOfWeek = moment.weekdays(),
			daysOfWeekShort = moment.weekdaysShort();

	$('#starttime').clockpicker({
		donetext: _('Done'),
		twelvehour: true
	});
	$('#endtime').clockpicker({
		donetext: _('Done'),
		twelvehour: true
	});

	$("#afterdate").datepicker({
		format: {
			toDisplay: function (date, format, language) {
				return moment(date).tz('UTC').format("YYYY-MM-DD");
			},
			toValue: function (date, format, language) {
				return moment(date).toDate();
			}
		}
	});

	$("#startdate").datepicker({
		format: {
			toDisplay: function (date, format, language) {
				return moment(date).tz('UTC').format("YYYY-MM-DD");
			},
			toValue: function (date, format, language) {
				return moment(date).toDate();
			}
		}
	});

	$("#enddate").datepicker({
		format: {
			toDisplay: function (date, format, language) {
				return moment(date).tz('UTC').format("YYYY-MM-DD");
			},
			toValue: function (date, format, language) {
				return moment(date).toDate();
			}
		}
	});
	//Add bootstrap classes to full calendar
	$('.fc-button').addClass('btn btn-default');

	if($('#calendar').length) {
		$('#calendar').fullCalendar({
			dayNames: daysOfWeek,
			dayNamesShort: daysOfWeekShort,
			displayEventEnd: true,
			nextDayThreshold: '00:00:01',
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
			height: 650,
			timezone: timezone,
			eventSources: [{
				url: 'ajax.php',
				type: 'GET',
				data: {
					module:'calendar',
					command: 'events',
					calendarid: calendarid
				},
				error: function(){
					fpbxToast(_('There was an error fetching the events'),'','warning');
				},
			}],
			eventClick: function( event, jsEvent, view ) {
				var src = (typeof event.parent !== "undefined") ? event.parent : event,
						tz = (typeof src.timezone !== "undefined") ? src.timezone : timezone,
						ms = moment.unix(src.ustarttime).tz(tz),
						me = moment.unix(src.uendtime).tz(tz),
						allday = src.allDay;
				resetModalForm();
				$("#rstartdate").val(src.rstartdate);
				$("#renddate").val(src.renddate);
				$('#title').val(src.title);
				$('#description').val(src.description);
				$('#eventid').val(src.linkedid);
				$("#eventtype option[value='"+src.eventtype+"']").prop('selected', true);
				$('#startdate').val(ms.format("YYYY-MM-DD"));
				$('#enddate').val(me.format("YYYY-MM-DD"));
				$('#starttime').val(ms.format("kk:mm"));
				$('#endtime').val(me.format("kk:mm"));
				$('#startdate').datepicker('update');
				$('#enddate').datepicker('update');
				if(typeof src.timezone !== "undefined") {
					$("#timezone").val(src.timezone);
					$("#timezone").multiselect('select', src.timezone);
				}
				$('#modalDelete').data('id', src.uid);
				$('#eventModal').modal('show');
				if(src.recurring) {
					$("#reoccurring").prop("checked",true);
					if(src.rrules.interval !== "") {
						$("#repeat-count").val(src.rrules.interval);
					}
					if(src.rrules.count !== "") {
						$("#repeat1").prop("checked",true);
						$("#occurrences").val(src.rrules.count);
					}
					if(src.rrules.until !== "") {
						$("#repeat2").prop("checked",true);
						$('#afterdate').val(moment.unix(src.rrules.until).format("YYYY-MM-DD"));
						$('#afterdate').datepicker('update');
					}
					switch(src.rrules.frequency) {
						case "DAILY":
							$("#repeats").val(0);
						break;
						case "WEEKLY":
							if(src.rrules.interval === "") {
								switch(src.rrules.byday) {
									case "MO,TU,WE,TH,FR":
										$("#repeats").val(1);
									break;
									case "MO,WE,FR":
										$("#repeats").val(2);
									break;
									case "TU,TH":
										$("#repeats").val(3);
									break;
								}
							} else {
								$("#repeats").val(4);
							}
						break;
					}
					var days = {"MO": 0, "TU": 1, "WE": 2, "TH": 3, "FR": 4, "SA": 5, "SU": 6};
					$.each(src.rrules.days, function(i,v) {
						$("#weekday"+days[v]).prop("checked",true);
					});
				}
				if(!readonly){
					$("#eventForm input").prop("disabled",false);
					$("#eventForm select").prop("disabled",false);
					$("#modalSubmit").show();
					$("#modalDelete").show();
				}else{
					$("#eventForm input").prop("disabled",true);
					$("#eventForm select").prop("disabled",true);
					$("#modalSubmit").hide();
					$("#modalDelete").hide();
				}
				updateReoccurring();
				updateAllDay();
			},
			dayClick: function( event, jsEvent, view ) {
				if(readonly) {
					return;
				}
				resetModalForm();
				$('#startdate').val(event.format("YYYY-MM-DD"));
				$('#enddate').val(event.format("YYYY-MM-DD"));
				$('#startdate').datepicker('update');
				$('#enddate').datepicker('update');
				$('#starttime').val(moment(Date.now()).format("kk:mm"));
				$('#endtime').val(moment(Date.now()).add(1, 'h').format("kk:mm"));
				$('#eventid').val('new');
				$('#eventModal').modal('show');
				updateAllDay();
			}

		});
	}
});

$("#repeat0").click(function() {
	$("#occurrences-container").addClass("hidden");
	$("#after-container").addClass("hidden");
});

$("#repeat1").click(function() {
	$("#occurrences-container").removeClass("hidden");
	$("#after-container").addClass("hidden");
});

$("#repeat2").click(function() {
	$("#after-container").removeClass("hidden");
	$("#occurrences-container").addClass("hidden");
});

$("#reoccurring").click(function() {
	updateReoccurring();
});

$("#repeats").change(function() {
	updateReoccurring();
});

function updateReoccurring() {
	if($("#reoccurring").is(":checked")) {
		$(".reoccurring").removeClass("hidden");
		updateReoccurringOptions();
	} else {
		$(".reoccurring").addClass("hidden");
	}
}

function updateReoccurringOptions() {
	switch($("#repeats").val()) {
		case "4":
			$("#repeat-on-container").removeClass("hidden");
			$("#repeats-every-container").removeClass("hidden");
			$("#repeat-by-container").addClass("hidden");
			$("#countType").text(_("Weeks"));
		break;
		case "0":
			$("#countType").text(_("Days"));
			$("#repeat-on-container").addClass("hidden");
			$("#repeats-every-container").removeClass("hidden");
			$("#repeat-by-container").addClass("hidden");
		break;
		case "1":
		case "2":
		case "3":
			$("#repeat-on-container").addClass("hidden");
			$("#repeats-every-container").addClass("hidden");
			$("#repeat-by-container").addClass("hidden");
		break;
		case "5":
			$("#countType").text(_("Months"));
			$("#repeat-on-container").addClass("hidden");
			$("#repeats-every-container").removeClass("hidden");
			$("#repeat-by-container").removeClass("hidden");
		break;
		case "6":
			$("#countType").text(_("Years"));
			$("#repeat-on-container").addClass("hidden");
			$("#repeats-every-container").addClass("hidden");
			$("#repeat-by-container").addClass("hidden");
		break;
	}
	if($("#repeat1").is(":checked")) {
		$("#occurrences-container").removeClass("hidden");
	} else {
		$("#occurrences-container").addClass("hidden");
	}
	if($("#repeat2").is(":checked")) {
		$("#after-container").removeClass("hidden");
	} else {
		$("#after-container").addClass("hidden");
	}
}

function updateAllDay() {
	if($("#starttime").val() == $("#endtime").val()) {
		$("#allday").prop("checked",true);
		$(".time").addClass("hidden");
	} else {
		$("#allday").prop("checked",false);
		$(".time").removeClass("hidden");
	}
}
