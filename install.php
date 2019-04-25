<?php
$crons = \FreePBX::Cron()->getAll();
foreach($crons as $c) {
	if(preg_match('/fwconsole calendar sync/',$c,$matches)) {
		\FreePBX::Cron()->remove($c);
	}
	if(preg_match('/fwconsole calendar --sync/',$c,$matches)) {
		\FreePBX::Cron()->remove($c);
	}
}

\FreePBX::Job()->addClass('calendar', 'sync', 'FreePBX\modules\Calendar\Job', '* * * * *');
