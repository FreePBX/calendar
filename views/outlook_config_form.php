<?php
    if(isset($outlookdata['authurl'])){
        $button = '<a href="'.($outlookdata['authurl'] ?? "").'" id="oauthbutton" class = "btn btn-danger">'._("Authorize access").'</a><p>Please click on Authorize access button if you are not redirected to outlook authorization page after saving the details.</p>';
    }
?>
<div class = "display full-border">
    <div class="container-fluid">
		<h1>
			<span><?php echo _('Outlook Config') ?></span>
		</h1>
	</div>
    <div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display full-border">
                <form class="fpbx-submit settingsform" method="post" action="?display=calendar&action=saveoutlooksettings">
                    <input id="id" name="id" type="hidden" class="form-control" value="<?php echo $outlookdata['id'] ?? ''; ?>">
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
                                            <input id="name" name="name" type="text" class="form-control" value="<?php echo $outlookdata['name'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="name-help" class="help-block fpbx-help-block"><?php echo _("Name for the settings.")?></span>
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
                                            <input id="description" name="description" type="text" class="form-control" value="<?php echo $outlookdata['description'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="description-help" class="help-block fpbx-help-block"><?php echo _("Description for the settings.")?></span>
                            </div>
                        </div>
                    </div>
                    <div class="element-container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="form-group">
                                        <div class="col-md-3">
                                            <label class="control-label" for="pbxurl"><?php echo _("PBX URL") ?></label>
                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="pbxurl"></i>
                                        </div>
                                        <div class="col-md-9">
                                            <input id="pbxurl" name="pbxurl" type="text" class="form-control" value="<?php echo "https://".$_SERVER['HTTP_HOST']."/"; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="pbxurl-help" class="help-block fpbx-help-block"><?php echo _("The PBX URL to get on which you will get auth responses.")?></span>
                            </div>
                        </div>
                    </div>
                    <div class="element-container">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-3">
                                    <label class="control-label" for="tenantid"><?php echo _("Directory (tenant) ID") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="tenantid"></i>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="tenantid" name="tenantid" value="<?php echo $outlookdata['tenantid'] ?? ''?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="tenantid-help" class="help-block fpbx-help-block"><?php echo _("Azzure Active Directory Id or Tenant Id, which you will get on azure portal while registering your application")?></span>
                            </div>
                        </div>
                    </div>
                    <!--API Key-->
                    <div class="element-container">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-3">
                                    <label class="control-label" for="consumerkey"><?php echo _("Application (client) ID") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="consumerkey"></i>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="consumerkey" name="consumerkey" value="<?php echo $outlookdata['consumerkey'] ?? ''?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="consumerkey-help" class="help-block fpbx-help-block"><?php echo _("Consumer Key For Outlook")?></span>
                            </div>
                        </div>
                    </div>
                    <!--END API Key-->
                    <!--API Secret-->
                    <div class="element-container">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-3">
                                    <label class="control-label" for="consumersecret"><?php echo _("Client Secret Value") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="consumersecret"></i>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="consumersecret" name="consumersecret" value="<?php echo $outlookdata['consumersecret'] ?? ''?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="consumersecret-help" class="help-block fpbx-help-block"><?php echo _("helptext")?></span>
                            </div>
                        </div>
                    </div>
                    <!--END API Secret-->
                    <!--outlook url-->
                    <div class="element-container">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-3">
                                    <label class="control-label" for="outlookurl"><?php echo _("Outlook Auth URL") ?></label>
                                    <i class="fa fa-question-circle fpbx-help-icon" data-for="outlookurl"></i>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="outlookurl" name="outlookurl" required value="<?php echo $outlookdata['outlookurl'] ?? 'https://login.microsoftonline.com/'?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="outlookurl-help" class="help-block fpbx-help-block"><?php echo _("Login URL for Outlook")?></span>
                            </div>
                        </div>
                    </div>
                    <!--END outlook url-->
                    <!--outlook Token-->
                    <div class="element-container">
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-3">
                                    <label class="control-label" for="token"></label>
                                </div>
                                <div class="col-md-9">
                                    <a class = "btn btn-default" id="save"><?php echo _("Save")?></a>
                                    <?php echo $button ?? "" ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <span id="token-help" class="help-block fpbx-help-block"><?php echo _("Get OAUTH2 Token for the Outlook API")?></span>
                            </div>
                        </div>
                    </div>
                    <!--END outlook Token-->
                </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var generatedAuthUrl = '';
    $("#save").click(function() {
        let id = $("#id").val();
        let name = $("#name").val();
        let description = $("#description").val();
        let pbxurl = $("#pbxurl").val();
        let tenant = $("#tenantid").val();
        let client = $("#consumerkey").val();
        let secreat = $("#consumersecret").val();
        let authurl = $("#outlookurl").val();

        if(name && pbxurl && tenant && client && secreat && authurl) {
            $.post("ajax.php?module=calendar&command=saveoutlooksettings",{ id: id, name: name, description: description, pbxurl: pbxurl, tenantid: tenant, consumerkey: client, consumersecret: secreat, outlookurl: authurl}, function(data) {
                if(data.status) {
                    alert(_('You will be redirected to microsoft loging page for authorization, If you are not redirected please click on "Authorize access" button to give access to generate token.'));
                    window.location.href = data.authurl;
                } else {
                    fpbxToast(data.message,'','error');
                }
            }).fail(function() {
                alert(_("There was an error"));
            });
        } else {
            alert(_("All fields are mandatory"));
            return;
        }
	});
</script>