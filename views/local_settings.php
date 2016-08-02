<div class = "display full-border">
  <div class="container-fluid">
    <h1>
      <span><?php echo sprintf(_('%s %s Calendar'),_('Add'),$type)?></span>
    </h1>
  </div>
  <div class="row">
    <div class="col-sm-12">
      <div class="fpbx-container">
        <div class="display full-border">
          <form class="fpbx-submit" name="editAnnouncement" action="" method="post" onsubmit="return checkAnnouncement(editAnnouncement);" data-fpbx-delete="config.php?display=announcement&amp;extdisplay=<?php echo $extdisplay ?>&amp;action=delete">
            <input type="hidden" name="extdisplay" value="<?php echo $extdisplay; ?>">
            <input type="hidden" name="announcement_id" value="<?php echo $extdisplay; ?>">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <input type="hidden" name="display" value="announcement">
            <input type="hidden" name="view" value="form">
            <!--Name-->
            <div class="element-container">
            	<div class="row">
            		<div class="col-md-12">
            			<div class="row">
            				<div class="form-group">
            					<div class="col-md-3">
            						<label class="control-label" for="description"><?php echo _("Name") ?></label>
            						<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
            					</div>
            					<div class="col-md-9">
            						<input type="text" class="form-control" id="description" name="description" value="<?php  echo $description; ?>">
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>
            	<div class="row">
            		<div class="col-md-12">
            			<span id="description-help" class="help-block fpbx-help-block"><?php echo _("The name of this announcement.")?></span>
            		</div>
            	</div>
            </div>
            <div class="element-container">
              <div class="row">
                <div class="col-md-12">
                  <div class="row">
                    <div class="form-group">
                      <div class="col-md-3">
                        <label class="control-label" for="description"><?php echo _("Description") ?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
                      </div>
                      <div class="col-md-9">
                        <input type="text" class="form-control" id="description" name="description" value="<?php  echo $description; ?>">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <span id="description-help" class="help-block fpbx-help-block"><?php echo _("The name of this announcement.")?></span>
                </div>
              </div>
            </div>
            <div class="element-container">
              <div class="row">
                <div class="col-md-12">
                  <div class="row">
                    <div class="form-group">
                      <div class="col-md-3">
                        <label class="control-label" for="fileupload"><?php echo _("Upload Recording")?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="fileupload"></i>
                      </div>
                      <div class="col-md-9">
                        <span class="btn btn-default btn-file">
                          <?php echo _("Browse")?><input id="fileupload" type="file" class="form-control" name="files[]" data-url="ajax.php?module=soundlang&amp;command=upload" multiple="">
                        </span>
                        <span class="filename"></span>
                        <strong><?php echo _("Files to upload")?>:</strong> <span id="filecount">0</span>
                        <div id="upload-progress" class="progress">
                          <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                        </div>
                        <div id="dropzone">
                          <div class="message"><?php echo _("Drop Multiple Files or Archives Here")?></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <span id="fileupload-help" class="help-block fpbx-help-block"><?php echo sprintf(_("Upload files from your local system. Supported upload formats are: %s. This includes archives (that include multiple files, such as %s) and multiple files"),"<i><strong>"."files here"."</strong></i>","<i><strong>tar,gz,zip</strong></i>")?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
