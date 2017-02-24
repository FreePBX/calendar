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
									<span id="username-help" class="help-block fpbx-help-block"><?php echo _("The username of this calendar's user")?></span>
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
									<span id="password-help" class="help-block fpbx-help-block"><?php echo _("The remote url for this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="url"><?php echo _("EWS Server URL") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="url"></i>
											</div>
											<div class="col-md-9">
												<div class="input-group">
													<input type="text" class="form-control" id="url" name="url" value="<?php echo !empty($data['url']) ? $data['url'] : ''?>">
													<span class="input-group-btn">
														<button class="btn btn-default" id="ews-autodetect" type="button"><?php echo _("Autodetect")?></button>
													</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="url-help" class="help-block fpbx-help-block"><?php echo _("The remote url for this calendar")?></span>
								</div>
							</div>
						</div>
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="url"><?php echo _("EWS Version") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="url"></i>
											</div>
											<div class="col-md-9">
												<select id="version" class="form-control" name="version">
													<option value="VERSION_2007" data-name="Exchange2007">Exchange 2007</option>
													<option value="VERSION_2007_SP1" data-name="Exchange2007_SP1">Exchange2007 SP1</option>
													<option value="VERSION_2009" data-name="Exchange2009">Exchange 2009</option>
													<option value="VERSION_2010" data-name="Exchange2010">Exchange 2010</option>
													<option value="VERSION_2010_SP1" data-name="Exchange2010_SP1">Exchange 2010 SP1</option>
													<option value="VERSION_2010_SP2" data-name="Exchange2010_SP2">Exchange 2010 SP2</option>
													<option value="VERSION_2013" data-name="Exchange2013">Exchange 2013</option>
													<option value="VERSION_2013_SP1" data-name="Exchange2013_SP1">Exchange 2013 SP1</option>
													<option value="VERSION_2016" data-name="Exchange2016">Exchange 2016</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="url-help" class="help-block fpbx-help-block"><?php echo _("The remote url for this calendar")?></span>
								</div>
							</div>
						</div>
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
<?php
if (strtolower($action) == "add") {
	$selclass = "hidden";
	$unsetclass = "";
} else {
	$selclass = "";
	$unsetclass = "hidden";
}
?>
												<span id='setspan' class="<?php echo $selclass; ?>">
													<select id="calendars" name="calendars[]" class="form-control" multiple="multiple">
														<?php foreach($calendars as $calendar) { ?>
															<option value="<?php echo $calendar['id']?>" <?php echo $calendar['selected'] ? 'selected' : ''?>><?php echo $calendar['name']?></option>
														<?php } ?>
													</select>
												</span>
												<span id='unsetspan' class="<?php echo $unsetclass; ?>"><?php echo _("Please enter valid credentials above"); ?></span>
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
	var updating = false;
	function updateCalendars() {
		if($("#purl").val() !== "" && $("#username").val() !== "" && $("#password").val() !== "" && !updating) {
			updating = true;
			$("#unsetspan").text('<?php echo _("Attempting to load"); ?>...').show();
			$("#setspan").addClass("hidden");
			$(".diswhenloading").addClass("disabled").attr("disabled", true);
			$.post( "ajax.php?module=calendar&command=getewscals", {purl: $("#url").val(), username: $("#username").val(), password: $("#password").val(), version: $("#version").val()}, function( data ) {
				$("#calendars").html(data.calshtml);
				$('#calendars').multiselect('rebuild');
				$("#setspan").removeClass("hidden");
				$("#unsetspan").addClass("hidden");
				$(".diswhenloading").removeClass("disabled").attr("disabled", false);
			}).always(function() {
				updating = false;
			});
		}
	}
	$(document).ready(function() {
		$('#calendars').multiselect({
				enableFiltering: true,
				enableFullValueFiltering: true,
				enableCaseInsensitiveFiltering: true,
				buttonWidth: '50%'
		});
	});
</script>
