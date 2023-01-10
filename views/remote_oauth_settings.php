<?php if(!empty($message)) { ?>
	<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
<?php } ?>
<div class = "display full-border">
	<div class="container-fluid">
		<h1>
			<span><?php echo sprintf(_('%s Outlook Calendar'),ucfirst($action)) ?></span>
		</h1>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display full-border">
					<form class="fpbx-submit calform" method="post" action="?display=calendar" id="ewsform" name="ewsform" data-fpbx-delete="config.php?display=calendar&amp;id=<?php echo !empty($data['id']) ? $data['id'] : ''?>&amp;action=delete">
						<input type="hidden" name="action" value="<?php echo $action?>">
						<input type="hidden" name="type" value="oauth">
						<input type="hidden" name="id" value="<?php echo !empty($data['id']) ? $data['id'] : ''?>">
						<input type="hidden" id="auth_code" name="auth_code" value="">
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
									<span id="description-help" class="help-block fpbx-help-block"><?php echo _("The description of this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row"> 
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="email"><?php echo _("Email") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="email"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control" id="email" name="email" value="<?php echo !empty($data['email']) ? $data['email'] : ''?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="email-help" class="help-block fpbx-help-block"><?php echo _("The email address of this calendar's user [Optional]")?></span>
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
									<span id="username-help" class="help-block fpbx-help-block"><?php echo _("The username of this calendar's user. Please make sure username must be the micrsoft outlook's username")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="next"><?php echo _("Auto Refresh") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="next"></i>
											</div>
											<div class="col-md-9">
												<select id="next" class="form-control" name="next">
													<option value="60" <?php echo $data['next'] == 60 ? 'selected' : '' ?>><?php echo _("1 Minute")?></option>
													<option value="300" <?php echo $data['next'] == 300 ? 'selected' : '' ?>><?php echo _("5 Minutes")?></option>
													<option value="600" <?php echo $data['next'] == 600 ? 'selected' : '' ?>><?php echo _("10 Minutes")?></option>
													<option value="1800" <?php echo $data['next'] == 1800 ? 'selected' : '' ?>><?php echo _("30 Minutes")?></option>
													<option value="3600" <?php echo $data['next'] == 3600 ? 'selected' : '' ?>><?php echo _("1 Hour")?></option>
													<option value="7200" <?php echo $data['next'] == 7200 ? 'selected' : '' ?>><?php echo _("2 Hours")?></option>
													<option value="21600" <?php echo $data['next'] == 216000 ? 'selected' : '' ?>><?php echo _("6 Hours")?></option>
													<option value="43200" <?php echo $data['next'] == 43200 ? 'selected' : '' ?>><?php echo _("12 Hours")?></option>
													<option value="86400" <?php echo $data['next'] == 86400 ? 'selected' : '' ?>><?php echo _("1 Day")?></option>
													<option value="604800" <?php echo $data['next'] == 604800 ? 'selected' : '' ?>><?php echo _("1 Week")?></option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="next-help" class="help-block fpbx-help-block"><?php echo _("How often this calendar will synchronize with the remote server")?></span>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$(".fpbx-submit").submit(function() {
		if($("#username").val() == "") {
			return warnInvalid($("#username"),_("Please define a valid username"));
		}
		if($("#password").val() == "") {
			return warnInvalid($("#password"),_("Please define a valid password"));
		}
		if($("#url").val() == "") {
			return warnInvalid($("#url"),_("Please define a valid url"));
		}
	});

	$("#generate-token").click(function() {
		if($("#client_id").val() === "") {
			return warnInvalid($("#client_id"),_("Client Id can not be blank!"));
		}
		if($("#client_secrete").val() === "") {
			return warnInvalid($("#client_secrete"),_("Client secrete can not be blank!"));
		}
		var cliId = $("#client_id").val();
		var secrete = $("#client_secrete").val();

		$.post("ajax.php?module=calendar&command=generateOauth",{client_id: cliId, client_secrete: secrete, page: window.location.href}, function(data) {
			if(data.status) {
				location.replace(data.url);
			}
		}).fail(function() {
			alert(_("There was an error"));
		});

	});
</script>
