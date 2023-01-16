<?php
if(!empty($message)) { ?>
<div class="alert alert-<?php echo $message['type']?>">
	<?php echo $message['message']?>
</div>
<?php } ?>
<div id="toolbar-all">
<div style="display: inline-block !important;">
		<a class="btn btn-default" href = "?display=calendar&amp;action=outlooksettings">
			<?php echo _("Add Config")?>
		</a>
	</div>
</div>
<table data-toolbar="#toolbar-all" data-escape="true" data-toggle="table" data-url="ajax.php?module=calendar&amp;command=oauthsettings" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true" id="table-all">
	<thead>
		<tr>
			<th data-sortable="true" data-field="name"><?php echo _("Name")?></th>
			<th data-sortable="true" data-field="description"><?php echo _("Description")?></th>
			<th data-sortable="true" data-field="tenantid"><?php echo _("Tenant Id")?></th>
			<th data-formatter="settingsactionformatter"><?php echo _("Actions")?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>