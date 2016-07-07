<?php
/*This file is only used for legacy functionality and things that aren't in BMO.
 *These functions should generally make a BMO Call if possible.
 */

 $cal = FreePBX::Calendar();

 function calendar_getdest($exten) {
 	return array("calendargroups,$exten,1");
 }

 function calendar_destinations(){
  global $cal;
 	$extens = array();
  foreach($cal->listGroups() as $id => $group){
   	$extens[] = array(
   		'destination' => 'calendargroups,'.$id.',1',
   		'description' => sprintf(_("Calendar Group: %s"),$group['description']),
   		'category' => _('Calendar Groups'),
   	);
  }
 	return $extens;
 }


 function calendar_getdestinfo($dest) {
  global $cal;
 	if (substr(trim($dest),0,14) == 'calendargroups') {
    $parts = explode(',', $dest);
    $group = $cal->getGroup($parts[1]);
 		return array(
 			'description' => sprintf(_("Calendar Group: %s"),$group['description']),
 			'edit_url' => '?display=calendargroups&view=form&id='.$parts[1]
 		);
 	} else {
 		return false;
 	}
 }
