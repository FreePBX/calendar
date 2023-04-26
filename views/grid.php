<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
//
if(!empty($message)) { ?>
<div class="alert alert-<?php echo $message['type']?>">
	<?php echo $message['message']?>
</div>
<?php } ?>
<div id="toolbar-all">
	<div class="dropdown" style="display: inline-block !important;">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			<i class="fa fa-plus">&nbsp;</i><?php echo _("Add Calendar")?> <span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
			<?php foreach($dropdown as $name => $driver) {?>
				<li><a href="?display=calendar&amp;action=add&amp;type=<?php echo $name?>"><i class="fa fa-plus"></i> <strong><?php echo sprintf(_('Add %s'),$driver)?></strong></a></li>
			<?php } ?>
		</ul>
	</div>
	<div style="display: inline-block !important;">
		<a class="btn btn-default" href = "?display=calendar&amp;action=oauthsettings">
			<?php echo _("Outlook Oauth2 Config")?>
		</a>
	</div>
</div>
<table data-toolbar="#toolbar-all" data-escape="true" data-toggle="table" data-url="ajax.php?module=calendar&amp;command=grid" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" id="table-all">
	<thead>
		<tr>
			<th data-sortable="true" data-field="name"><?php echo _("Name")?></th>
			<th data-sortable="true" data-field="description"><?php echo _("Description")?></th>
			<th data-sortable="true" data-field="type" data-formatter="typeformatter"><?php echo _("Type")?></th>
			<th data-formatter="actionformatter"><?php echo _("Actions")?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
<script>
	var drivers = <?php echo json_encode($dropdown)?>;
	$(document).ready(function() {
		var pageurl = window.location.href;
		if(pageurl.includes("code")) {
			var origUrl = pageurl.split('&')[0];
			var params = pageurl.split('&')[1];
			var params2 = pageurl.split('&')[2];
			var acode = params.split('=')[1];
			var state = params2.split('=')[1];
			$.post("ajax.php?module=calendar&command=saveOauth",{id: state, auth_code: acode}, function(data) {
				if(data.status) {
					$.post("ajax.php?module=calendar&command=getToken",{id: state}, function(data) {
						if(data.status) {
							fpbxToast(_('Auth token generated successfully.'));
							setTimeout(function(){
								window.location.href = origUrl;
							}, 1000);
						} else {
							fpbxToast(_('Invalid client credentials.'),'','error');
							setTimeout(function(){
								window.location.href = origUrl;
							}, 1000);
						}
					}).fail(function() {
						alert(_("There was an error while getting auth token"));
					});
				}
			}).fail(function() {
				alert(_("There was an error"));
			});
		}
	});
</script>
