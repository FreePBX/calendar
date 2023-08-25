<?php
$cal = FreePBX::Calendar();
//Gives an array of names used for validation later
echo '<script> var calnames='.json_encode($cal->getCalendarNames(), JSON_THROW_ON_ERROR).'</script>';
echo $cal->showCalendarPage();
