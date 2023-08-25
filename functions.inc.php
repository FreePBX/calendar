<?php
/*This file is only used for legacy functionality and things that aren't in BMO.
*These functions should generally make a BMO Call if possible.
*/

function calendar_getdest($exten) {
	return ["calendargroups,$exten,1"];
}

function calendar_destinations(){
	$extens = [];
	foreach(FreePBX::Calendar()->listGroups() as $id => $group){
		$extens[] = ['destination' => 'calendargroups,'.$id.',1', 'description' => sprintf(_("Calendar Group: %s"),$group['name']), 'category' => _('Calendar Groups')];
	}
	return $extens;
}


function calendar_getdestinfo($dest) {
	if (str_starts_with(trim((string) $dest), 'calendargroups')) {
		$parts = explode(',', (string) $dest);
		$group = FreePBX::Calendar()->getGroup($parts[1]);
		return ['description' => sprintf(_("Calendar Group: %s"),$group['name']), 'edit_url' => '?display=calendargroups&action=edit&id='.$parts[1]];
	} else {
		return false;
	}
}
