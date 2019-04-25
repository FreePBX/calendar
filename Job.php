<?php

namespace FreePBX\modules\Calendar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
class Job implements \FreePBX\Job\TaskInterface {
	public static function run(InputInterface $input, OutputInterface $output) {
		$output->writeln("Starting Calendar Sync...");
		\FreePBX::Calendar()->sync($output,false);
		$output->writeln("Finished");
		return true;
	}
}