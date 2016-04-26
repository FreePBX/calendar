<div id="eventModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
                <h4 id="modalTitle" class="modal-title"><?php echo _("Event")?></h4>
            </div>
            <div id="modalBody" class="modal-body">
              <input type="hidden" name="eventid" id="eventid" value="">
              <!--Event Type-->
              <div class="element-container">
                <div class="row">
                  <div class="col-md-12">
                    <div class="row">
                      <div class="form-group">
                        <div class="col-md-3">
                          <label class="control-label" for="type"><?php echo _("Event Type") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="type"></i>
                        </div>
                        <div class="col-md-9">
                          <select id="type" class="form-control">
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
                    <span id="type-help" class="help-block fpbx-help-block"><?php echo _("")?></span>
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
                          <label class="control-label" for="stime"><?php echo _("Start Date and Time") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="stime"></i>
                        </div>
                        <div class="col-md-9">
                          <div class="input-group">
                            <input type="text" class="form-control" id="stime" name="stime" value="" readonly>
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="stime-help" class="help-block fpbx-help-block"><?php echo _("The time to start the event")?></span>
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
                          <label class="control-label" for="etime"><?php echo _("End Event Date and Time") ?></label>
                          <i class="fa fa-question-circle fpbx-help-icon" data-for="etime"></i>
                        </div>
                        <div class="col-md-9">
                          <div class="input-group">
                            <input type="text" class="form-control" id="etime" name="etime" value="" readonly>
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <span id="etime-help" class="help-block fpbx-help-block"><?php echo _("Time this event ends")?></span>
                  </div>
                </div>
              </div>
              <!--END End Event Date and Time-->
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close")?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal" id="modalSubmit"><?php echo _("Submit")?></button>
            </div>
        </div>
    </div>
</div>
