<div class = "display full-border">
	<div class="container-fluid">
		<h1>
			<span><?php echo sprintf(_('%s %s Calendar'),$action,$type)?></span>
		</h1>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display full-border">
					<form class="fpbx-submit" method="post" action="?display=calendar" data-fpbx-delete="config.php?display=calendar&amp;id=<?php echo $id ?>&amp;action=delete">
						<input type="hidden" name="action" value="<?php echo $action?>">
						<input type="hidden" name="type" value="<?php echo $type?>">
						<input type="hidden" name="id" value="<?php echo !empty($data['id']) ? $data['id'] : ''?>">
						<!--Name-->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="name"><?php echo _("Name") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="name"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="name" name="name" value="<?php echo !empty($data['name']) ? $data['name'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="name-help" class="help-block fpbx-help-block"><?php echo _("The name of this calendar")?></span>
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
												<input type="text" class="form-control" id="description" name="description" value="<?php echo !empty($data['description']) ? $data['description'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="description-help" class="help-block fpbx-help-block"><?php echo _("The name of this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="purl"><?php echo _("Principal URL") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="purl"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="purl" name="purl" value="<?php echo !empty($data['purl']) ? $data['purl'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="purl-help" class="help-block fpbx-help-block"><?php echo _("The principal url for this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="username"><?php echo _("Username") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="username"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="username" name="username" value="<?php echo !empty($data['username']) ? $data['username'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="username-help" class="help-block fpbx-help-block"><?php echo _("The Userman for this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="password"><?php echo _("Password") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="password"></i>
											</div>
											<div class="col-md-9">
												<input type="password" class="form-control" id="password" name="password" value="<?php echo !empty($data['password']) ? $data['password'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="password-help" class="help-block fpbx-help-block"><?php echo _("The password for this calendar")?></span>
								</div>
							</div>
						</div>
						<!--Events-->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="calendars"><?php echo _("Calendars") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="calendars"></i>
											</div>
											<div class="col-md-9">
												<select id="calendars" name="calendars[]" class="form-control" multiple="multiple">
													<?php foreach($calendars as $calendar) { ?>
														<option value="<?php echo $calendar['id']?>" <?php echo $calendar['selected'] ? 'selected' : ''?>><?php echo $calendar['name']?></option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="calendars-help" class="help-block fpbx-help-block"><?php echo _("Select specific calendars")?></span>
								</div>
							</div>
						</div>
						<!--END Events-->
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	var calendars = <?php echo !empty($data['calendars']) ? json_encode($data['calendars']) : '[]'?>;
	$("#purl").blur(function() {
		updateCalendars();
	});
	$("#username").blur(function() {
		updateCalendars();
	});
	$("#password").blur(function() {
		updateCalendars();
	});
	function updateCalendars() {
		if($("#purl").val() !== "" && $("#username").val() !== "" && $("#password").val() !== "") {
			$.post( "ajax.php?module=calendar&command=getcaldavcals", {purl: $("#purl").val(), username: $("#username").val(), password: $("#password").val()}, function( data ) {
				$("#calendars").html(data.calshtml);
				$('#calendars').multiselect('rebuild');
			});
		}
	}
	$(document).ready(function() {
		$('#calendars').multiselect({
				enableFiltering: true,
				enableFullValueFiltering: true,
				enableCaseInsensitiveFiltering: true
		});
	});
</script>
