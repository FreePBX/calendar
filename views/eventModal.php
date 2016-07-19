
<div id="eventModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
                <h4 id="modalTitle" class="modal-title"><?php echo _("Event")?></h4>
            </div>
            <div id="modalBody" class="modal-body">
              <form name="eventForm" id="eventForm" action="ajax.php?command=eventform&module=calendar" method="POST">
              <input type="hidden" name="eventid" id="eventid" class="form-control" value="">
              <!--Event Type-->
              <div class="element-container">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row">
                      <div class="form-group">
                        <div class="col-md-3">
                          <label class="control-label" for="eventtype"><?php echo _("Event Type") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="type"></i>
                        </div>
                        <div class="col-md-9">
                          <select id="eventtype" name="eventtype" class="form-control">
                            <option value=''><?php echo _("Choose One")?></option>
                            <?php foreach ($cal->getEventTypes() as $key => $value) {
                              echo '<option value="'.$key.'">'.$value['desc'].'</option>';
                            }?>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="eventtype-help" class="help-block fpbx-help-block"><?php echo _("")?></span>
                  </div>
                </div>
              </div>
              <!--END Event Type-->
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
                    <span id="description-help" class="help-block fpbx-help-block"><?php echo _("Friendly name for the event")?></span>
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
              <!--Start Time-->
              <div class="element-container">
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
              <div class="element-container">
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
                    <span id="endtime-help" class="help-block fpbx-help-block"><?php echo _("Time the event ends.")?></span>
                  </div>
                </div>
              </div>
              <!--END End Time-->
              <!--Days of the Week-->
              <div class="element-container">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row">
                      <div class="form-group">
                        <div class="col-md-3">
                          <label class="control-label" for="weekdays"><?php echo _("Days of the Week") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="weekdays"></i>
                        </div>
                        <div class="col-md-9">
                          <select class="form-control" id="weekdays" name="weekdays[]" multiple="multiple">
                            <option value="1"><?php echo _("Monday") ?> </option>
                            <option value="2"><?php echo _("Tuesday") ?> </option>
                            <option value="3"><?php echo _("Wednesday") ?> </option>
                            <option value="4"><?php echo _("Thursday") ?> </option>
                            <option value="5"><?php echo _("Friday") ?> </option>
                            <option value="6"><?php echo _("Saturday") ?> </option>
                            <option value="7"><?php echo _("Sunday") ?> </option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="weekdays-help" class="help-block fpbx-help-block"><?php echo _("Days of week that this matches event matches true")?></span>
                  </div>
                </div>
              </div>
              <!--END Days of the Week-->
              <!--Match Destination-->
              <div class="element-container dest hidden">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row">
                      <div class="form-group">
                        <div class="col-md-3">
                          <label class="control-label" for="goto0"><?php echo _("Match Destination") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="goto0"></i>
                        </div>
                        <div class="col-md-9">
                          <?php echo drawselects('', 0, false,true,'', false, false, false)?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="goto0-help" class="help-block fpbx-help-block"><?php echo _("Destination if time matches")?></span>
                  </div>
                </div>
              </div>
              <!--END Match Destination-->
              <!--Non Match Destination-->
              <div class="element-container dest hidden">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row">
                      <div class="form-group">
                        <div class="col-md-3">
                          <label class="control-label" for="goto1"><?php echo _("Non Match Destination") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="goto1"></i>
                        </div>
                        <div class="col-md-9">
                          <?php echo drawselects('', 1, false,true,'', false, false, false)?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="goto1-help" class="help-block fpbx-help-block"><?php echo _("Destination if time does NOT match")?></span>
                  </div>
                </div>
              </div>
              <!--END Non Match Destination-->
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
