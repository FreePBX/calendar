<h1><?php echo sprintf(_("Viewing %s"),$data['name'])?></h1>
<script>
var readonly = <?php echo $data['type'] !== 'local' ? 'true' : 'false'?>;
var calendarid = "<?php echo $data['id']?>";
var timezone = "<?php echo FreePBX::View()->getTimezone();?>";
</script>
<div id="calendar"></div>
<div class="row">
	<div class="col-sm-12">
		<div class="fpbx-container">
			<div class = "display no-border">
				<div id="eventModal" class="modal fade">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
								<h4 id="modalTitle" class="modal-title"><?php echo _("Event")?></h4>
							</div>
							<div id="modalBody" class="modal-body">
								<form name="eventForm" id="eventForm" action="ajax.php?command=eventform&amp;module=calendar" method="POST">
									<input type="hidden" name="calendarid" id="calendarid" class="form-control" value="<?php echo $data['id']?>">
									<input type="hidden" name="eventid" id="eventid" class="form-control" value="">
									<!--Event Description-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="title"><?php echo _("Event Title") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="title"></i>
														</div>
														<div class="col-md-9">
															<input type="text" class="form-control" id="title" name="title" value="">
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="title-help" class="help-block fpbx-help-block"><?php echo _("Friendly name for the event")?></span>
											</div>
										</div>
									</div>
									<!--END Event Description-->
									<!--Event Description-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="description"><?php echo _("Event Description") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
														</div>
														<div class="col-md-9">
															<input type="text" class="form-control" id="description" name="description" value="">
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="description-help" class="help-block fpbx-help-block"><?php echo _("Description for the event")?></span>
											</div>
										</div>
									</div>
									<!--END Event Description-->
									<!--Start Date and Time-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="startdate"><?php echo _("Start Date") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="startdate"></i>
														</div>
														<div class="col-md-9">
															<div class="input-group">
																<input type="text" class="form-control" id="startdate" name="startdate" value="">
																<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="startdate-help" class="help-block fpbx-help-block"><?php echo _("The time to start the event")?></span>
											</div>
										</div>
									</div>
									<!--END Start Date and Time-->
									<!--End Event Date and Time-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="enddate"><?php echo _("End Date") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="enddate"></i>
														</div>
														<div class="col-md-9">
															<div class="input-group">
																<input type="text" class="form-control" id="enddate" name="enddate" value="">
																<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="enddate-help" class="help-block fpbx-help-block"><?php echo _("Time this event ends")?></span>
											</div>
										</div>
									</div>
									<!--END End Event-->
									<!--End Event Date and Time-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="allday"><?php echo _("All Day") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="allday"></i>
														</div>
														<div class="col-md-9 radioset">
															<input type="checkbox" class="form-control" id="allday" name="allday" value="yes">
															<label for="allday"><?php echo _("All Day")?></label>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="allday-help" class="help-block fpbx-help-block"><?php echo _("All Day Event")?></span>
											</div>
										</div>
									</div>
									<!--END End Event-->
									<!--Start Time-->
									<div class="element-container time">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="starttime"><?php echo _("Start Time") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="starttime"></i>
														</div>
														<div class="col-md-9">
															<input type="text" class="form-control" id="starttime" name="starttime" value="<?php echo isset($starttime)?$starttime:''?>">
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="starttime-help" class="help-block fpbx-help-block"><?php echo _("Time event starts")?></span>
											</div>
										</div>
									</div>
									<!--END Start Time-->
									<!--End Time-->
									<div class="element-container time">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="endtime"><?php echo _("End Time") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="endtime"></i>
														</div>
														<div class="col-md-9">
															<input type="text" class="form-control" id="endtime" name="endtime" value="<?php echo isset($endtime)?$endtime:''?>">
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="endtime-help" class="help-block fpbx-help-block"><?php echo _("Time the event ends")?></span>
											</div>
										</div>
									</div>
									<!--END End Time-->
									<!--End Event Date and Time-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="reoccurring"><?php echo _("Reoccurring") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="reoccurring"></i>
														</div>
														<div class="col-md-9 radioset">
															<input type="checkbox" class="form-control" id="reoccurring" name="reoccurring" value="yes">
															<label for="reoccurring"><?php echo _("Reoccurring")?></label>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="reoccurring-help" class="help-block fpbx-help-block"><?php echo _("Reoccurring Event")?></span>
											</div>
										</div>
									</div>
									<!--END End Event-->
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="repeats"><?php echo _("Repeats") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="repeats"></i>
														</div>
														<div class="col-md-9">
															<select class="form-control">
																<option value="0" title="Daily">Daily</option>
																<option value="1" title="Every weekday (Monday to Friday)">Every weekday (Monday to Friday)</option>
																<option value="2" title="Every Monday, Wednesday, and Friday">Every Monday, Wednesday, and Friday</option>
																<option value="3" title="Every Tuesday and Thursday">Every Tuesday and Thursday</option>
																<option value="4" title="Weekly">Weekly</option>
																<option value="5" title="Monthly">Monthly</option>
																<option value="6" title="Yearly">Yearly</option>
															</select>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="repeats-help" class="help-block fpbx-help-block"><?php echo _("Repeats")?></span>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="repeats"><?php echo _("Repeat Every") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="repeats"></i>
														</div>
														<div class="col-md-9">
															<select class="form-control">
																<option value="1" selected="selected">1</option>
																<option value="2">2</option>
																<option value="3">3</option>
																<option value="4">4</option>
																<option value="5">5</option>
																<option value="6">6</option>
																<option value="7">7</option>
																<option value="8">8</option>
																<option value="9">9</option>
																<option value="10">10</option>
																<option value="11">11</option>
																<option value="12">12</option>
																<option value="13">13</option>
																<option value="14">14</option>
																<option value="15">15</option>
																<option value="16">16</option>
																<option value="17">17</option>
																<option value="18">18</option>
																<option value="19">19</option>
																<option value="20">20</option>
																<option value="21">21</option>
																<option value="22">22</option>
																<option value="23">23</option>
																<option value="24">24</option>
																<option value="25">25</option>
																<option value="26">26</option>
																<option value="27">27</option>
																<option value="28">28</option>
																<option value="29">29</option>
																<option value="30">30</option>
															</select>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="repeats-help" class="help-block fpbx-help-block"><?php echo _("Repeats")?></span>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="repeats"><?php echo _("Repeat On") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="repeats"></i>
														</div>
														<div class="col-md-9 radioset">
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">S</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">M</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">T</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">W</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">T</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">F</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">S</label>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="repeats-help" class="help-block fpbx-help-block"><?php echo _("Repeats")?></span>
											</div>
										</div>
									</div>
									<div class="element-container">
										<div class="row">
											<div class="col-md-12">
												<div class="row">
													<div class="form-group">
														<div class="col-md-3">
															<label class="control-label" for="repeats"><?php echo _("Ends") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="repeats"></i>
														</div>
														<div class="col-md-9 radioset">
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">Never</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">After</label>
															<input id=":sj.dow0" name="SU" type="checkbox" aria-label="Repeat on Sunday" title="Sunday">
															<label for=":sj.dow0" title="Sunday">On</label>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12">
												<span id="repeats-help" class="help-block fpbx-help-block"><?php echo _("Repeats")?></span>
											</div>
										</div>
									</div>
								</form>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-danger pull-left" data-id='' data-dismiss="modal" id="modalDelete"><?php echo _("Delete Event")?></button>
								<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close")?></button>
								<button type="submit" class="btn btn-default" form='eventForm' id="modalSubmit"><?php echo _("Submit")?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
