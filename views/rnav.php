<div id="toolbar-all">
	<a href="?display=calendar" class="btn btn-default"><i class="fa fa-list"></i> <?php echo _("List Calendars")?></a>
  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
		<i class="fa fa-plus">&nbsp;</i><?php echo _("Add Calendar")?> <span class="caret"></span>
	</button>
	<ul class="dropdown-menu" role="menu">
			<li><a href="?display=calendar&amp;action=add&amp;type=local"><i class="fa fa-plus"></i> <strong><?php echo _('Add New Local Calendar')?></strong></a></li>
			<li><a href="?display=calendar&amp;action=add&amp;type=ical"><i class="fa fa-plus"></i> <strong><?php echo _('Add Remote iCal Calendar')?></strong></a></li>
			<li><a href="?display=calendar&amp;action=add&amp;type=caldav"><i class="fa fa-plus"></i> <strong><?php echo _('Add Remote CalDAV Calendar')?></strong></a></li>
			<li><a href="?display=calendar&amp;action=add&amp;type=outlook"><i class="fa fa-plus"></i> <strong><?php echo _('Add Remote Outlook Calendar')?></strong></a></li>
			<li><a href="?display=calendar&amp;action=add&amp;type=google"><i class="fa fa-plus"></i> <strong><?php echo _('Add Remote Google Calendar')?></strong></a></li>
	</ul>
</div>
 <table id="announcegrid-side" data-url="ajax.php?module=calendar&amp;command=grid" data-maintain-selected="true" data-show-columns="true" data-show-toggle="true" data-toggle="table" data-pagination="true" data-search="true"  id="table-all">
 	<thead>
 		<tr>
 			<th data-sortable="true" data-field="name"><?php echo _("Name")?></th>
 			<th data-sortable="true" data-field="type"><?php echo _("Type")?></th>
 		</tr>
 	</thead>
 	<tbody>
 	</tbody>
 </table>
</table>
