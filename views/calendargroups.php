<form action="" method="post" class="fpbx-submit" id="cgform" name="cgform" data-fpbx-delete="config.php?display=calendargroups&action=delete&id=<?php echo $id?>">
<input type="hidden" name='action' value="<?php echo $id?'edit':'add' ?>">
<input type="hidden" name='type' value = 'group'>
<!--Description-->
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
						<input type="text" class="form-control" id="description" name="description" value="<?php echo isset($description )?$description :''?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="description-help" class="help-block fpbx-help-block"><?php echo _("Human readable description")?></span>
		</div>
	</div>
</div>
<!--END Description-->
<!--Events-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="events"><?php echo _("Events") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="events"></i>
					</div>
					<div class="col-md-9">
						<select id="events" name="events[]" class="form-control" multiple="multiple">
							<?php echo $eventopts?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="events-help" class="help-block fpbx-help-block"><?php echo _("Events to include in to this group")?></span>
		</div>
	</div>
</div>
<!--END Events-->

</form>
<script type="text/javascript">
		$(document).ready(function() {
				$('#events').multiselect({
						enableFiltering: true,
						enableFullValueFiltering: true
				});
		});
</script>
