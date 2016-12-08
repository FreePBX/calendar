<form id="calform<?php echo $submittedid?>">
<input type = 'hidden' id="id" value = '<?php echo $id?>'>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="title"><?php echo _("Event Title") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="title" name="title" value="<?php echo $title?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="sdate"><?php echo _("Start Date") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="sdate" name="sdate" value="<?php echo $sdate?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="edate"><?php echo _("End Date") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="edate" name="edate" value="<?php echo $edate?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="allday"><?php echo _("All Day") ?></label>
			</div>
			<div class="col-md-9">
				<input type="checkbox" class="form-control" id="allday" name="allday" value="1" <?php echo ($allday === 1)?'checked':''?>>
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="stime"><?php echo _("Start Time") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="stime" name="stime" value="<?php echo $stime?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="etime"><?php echo _("End Time") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="etime" name="etime" value="<?php echo $etime?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="tz"><?php echo _("Timezone") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="tz" name="tz" value="<?php echo $tz?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="reoccuring"><?php echo _("Reoccuring") ?></label>
			</div>
			<div class="col-md-9">
				<input type="checkbox" class="form-control" id="reoccuring" name="reoccuring" value="1" <?php echo ($reoccuring === 1)?'checked':''?>>
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="repeats"><?php echo _("Repeats") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="repeats" name="repeats" value="<?php echo $repeats?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="rint"><?php echo _("Repeat Every") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="rint" name="rint" value="<?php echo $rint?>">
			</div>
		</div>
	</div>
</div><div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="ends"><?php echo _("Ends") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="ends" name="ends" value="<?php echo $ends?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="occur"><?php echo _("Occurances") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="occur" name="occur" value="<?php echo $occur?>">
			</div>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-3">
				<label class="control-label" for="after"><?php echo _("After Date") ?></label>
			</div>
			<div class="col-md-9">
				<input type="text" class="form-control" id="after" name="after" value="<?php echo $after?>">
			</div>
		</div>
	</div>
</div>
</form>
