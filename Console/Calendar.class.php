<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Tables
use Symfony\Component\Console\Helper\TableHelper;
//Process
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Command\HelpCommand;
class Calendar extends Command {
	protected function configure(){
		$this->setName('calendar')
		->setDescription(_('Calendar'))
		->setDefinition(array(
			new InputOption('sync', null, InputOption::VALUE_NONE, _('Syncronize all Calendars')),
			new InputOption('force', null, InputOption::VALUE_NONE, _('Force command')),
			new InputOption('export', null, InputOption::VALUE_REQUIRED, _('Export Calendar by ID')),
			new InputOption('match', null, InputOption::VALUE_REQUIRED, _('Check if match, value can be any timestamp')),
			new InputOption('type', null, InputOption::VALUE_REQUIRED, _('One of: calendar | event | group')),
			new InputOption('calid', null, InputOption::VALUE_REQUIRED, _('Calendar ID')),
			new InputOption('eventid', null, InputOption::VALUE_REQUIRED, _('Event ID')),
			new InputOption('groupid', null, InputOption::VALUE_REQUIRED, _('Group ID')),
		));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$calendar = \FreePBX::create()->Calendar;
		if($input->getOption('sync')) {
			return $this->sync($calendar, $input, $output);
		}

		if($input->getOption('export')) {
			return $this->export($calendar, $input, $output);
		}

		if($input->getOption('match') && $input->getOption('type')) {
			return $this->match($calendar, $input, $output);
		}


		$this->outputHelp($input,$output);
	}

	private function match($calendar, InputInterface $input, OutputInterface $output) {
		$match = $input->getOption('match');
		$match = strtolower($match) !== 'now' ? $match : time();
		if($match < time()) {
			throw new \Exception("Match time can not be in the past");
		}
		$calendar->setNow($match);

		$type = $input->getOption('type');
		switch($type) {
			case 'calendar':
				if($calendar->matchCalendar($input->getOption('calid'))) {
					$start = $calendar->getNow()->copy()->subMinute();
					$stop = $calendar->getNow()->copy()->addMinute();
					$events = $calendar->listEvents($input->getOption('calid'), $start, $stop);
					$matched = null;
					foreach($events as $event) {
						if($event['now']) {
							$matched = $event;
							break;
						}
					}
					$output->writeln('<info>Matched '.$matched['name'].'</info>');
				} else {
					$output->writeln('<error>No Match Found</error>');
				}
			break;
			case 'event':
				if($calendar->matchEvent($input->getOption('calid'),$input->getOption('eventid'))) {
					$output->writeln('<info>Matched</info>');
				} else {
					$output->writeln('<error>No Match Found</error>');
				}
			break;
			case 'group':
				if($calendar->matchGroup($input->getOption('groupid'))) {
					$output->writeln('<info>Matched</info>');
				} else {
					$output->writeln('<error>No Match Found</error>');
				}
			break;
			default:
				throw new \Exception("Invalid type");
			break;
		}
	}

	private function sync($calendar, InputInterface $input, OutputInterface $output) {
		$output->writeln("Starting Sync...");
		$calendar->sync($output,true);
		$output->writeln("Finished");
	}

	private function export($calendar, InputInterface $input, OutputInterface $output) {
		$output->writeln($calendar->getRawCalendar($input->getOption('export')));
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Symfony\Component\Console\Exception\ExceptionInterface
	 */
	protected function outputHelp(InputInterface $input, OutputInterface $output)	 {
		$help = new HelpCommand();
		$help->setCommand($this);
		return $help->run($input, $output);
	}
}
