<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//la mesa
use Symfony\Component\Console\Helper\Table;
//Process
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Command\HelpCommand;

use Carbon\Carbon;

#[\AllowDynamicProperties]
class Calendar extends Command
{
	protected function configure()
	{
		$this->setName('calendar')
			->setDescription(_('Calendar'))
			->setDefinition([new InputOption('sync', null, InputOption::VALUE_NONE, _('Syncronize all Calendars')), new InputOption('force', null, InputOption::VALUE_NONE, _('Force command')), new InputOption('list', null, InputOption::VALUE_NONE, _('List Events')), new InputOption('export', null, InputOption::VALUE_REQUIRED, _('Export Calendar by ID')), new InputOption('import', null, InputOption::VALUE_REQUIRED, _('Import Calendar by ID')), new InputOption('reset', null, InputOption::VALUE_REQUIRED, _('Reset Calendar by ID')), new InputOption('file', null, InputOption::VALUE_REQUIRED, _('File location of the ics to import')), new InputOption('match', null, InputOption::VALUE_REQUIRED, _('Check if match, value can be any timestamp')), new InputOption('type', null, InputOption::VALUE_REQUIRED, _('One of: calendar | event | group')), new InputOption('id', null, InputOption::VALUE_REQUIRED, _('One of: calendar id | event id | group id'))]);
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$calendar = \FreePBX::create()->Calendar;
		if ($input->getOption('sync')) {
			return $this->sync($calendar, $input, $output);
		}

		if ($input->getOption('export')) {
			return $this->export($calendar, $input, $output);
		}

		if ($input->getOption('import')) {
			return $this->import($calendar, $input, $output);
		}

		if ($input->getOption('reset')) {
			return $this->reset($calendar, $input, $output);
		}

		if ($input->getOption('match') && $input->getOption('type')) {
			return $this->match($calendar, $input, $output);
		}

		if ($input->getOption('list')) {
			if ($input->getOption('id')) {
				return $this->listCalendarEvents($calendar, $input, $output);
			}

			return $this->listAllCalendar($calendar, $input, $output);
		}

		/*
		if($input->getOption('list') && $input->getOption('id')) {
			return $this->listGroupEvents($calendar, $input, $output);
		}
		*/

		$this->outputHelp($input, $output);
	}

	private function listAllCalendar($calendar, InputInterface $input, OutputInterface $output)
	{
		$calendars = $calendar->listCalendars();
		$table = new Table($output);
		$table->setHeaders([_('ID'), _('Name'), _('Description'), _('Type'), _("Timezone")]);
		$rows = $ids = [];
		foreach ($calendars as $id => $content) {
			$ids[] = $id;
			$rows[] = [$id, $content["name"], $content["description"], $content["type"], $content["timezone"]];
		}
		$table->setRows($rows);
		$table->render();
		$output->writeln('');
		$output->writeln("<info>" . _("Get more details about calendars, please run the commands below:") . "</info>");
		$table->setHeaders([_('Commands')]);
		$rows = [];
		foreach ($ids as $id) {
			$rows[] = [sprintf('fwconsole calendar --list --id=%s', $id)];
		}
		$table->setRows($rows);
		$table->render();
	}

	private function listCalendarEvents($calendar, InputInterface $input, OutputInterface $output)
	{
		$cal = $calendar->getDriverById($input->getOption('id'));
		$start = $cal->getNow()->copy()->subMonth();
		$stop = $cal->getNow()->copy()->addMonth();
		$events = $cal->getEventsBetween($start, $stop);
		if ($output->isVerbose()) {
			print_r($events);
		} else {
			$table = new Table($output);
			$table->setHeaders([_('Name'), _('Description'), _('Timezone'), _('Start'), _("End"), _("UID"), _("Recurring"), _("All Day"), _("Now Match")]);
			$rows = [];
			foreach ($events as $event) {
				$rows[] = [$event['name'], $event['description'], $event['timezone'], $event['start'], $event['end'], $event['linkedid'], $event['recurring'] ? _('Yes') : _('No'), $event['allDay'] ? _('Yes') : _('No'), $event['now'] ? _('Yes') : _('No')];
			}
			$table->setRows($rows);
			$table->render();
		}
	}

	/*
	private function listGroupEvents($calendarClass, InputInterface $input, OutputInterface $output) {
		$now = Carbon::now();
		$start = $now->copy()->subWeek();
		$stop = $now->copy()->addWeek();
		$matchingEvents = $calendarClass->matchGroupVerbose($input->getOption('id'),$start,$stop);

		if ($output->isVerbose()) {
			print_r($matchingEvents);
		} else {
			$table = new Table($output);
			$table->setHeaders(array(_('Name'),_('Description'),_('Timezone'),_('Start'),_("End"), _("UID"),_("Recurring"),_("All Day"),_("Now Match")));
			$rows = array();
			foreach($matchingEvents as $event) {
				$rows[] = array(
					$event['name'],
					$event['description'],
					$event['timezone'],
					$event['start'],
					$event['end'],
					$event['linkedid'],
					$event['recurring'] ? _('Yes') : _('No'),
					$event['allDay'] ? _('Yes') : _('No'),
					$event['now'] ? _('Yes') : _('No')
				);
			}
			$table->setRows($rows);
			$table->render();
		}
	}
	*/

	private function match($calendar, InputInterface $input, OutputInterface $output)
	{
		$match = $input->getOption('match');
		$match = strtolower((string) $match) !== 'now' ? $match : time();
		if ($match < time()) {
			throw new \Exception("Match time can not be in the past");
		}

		$type = $input->getOption('type');
		switch ($type) {
			case 'calendar':
				$calendarID = $input->getOption('id');
				$cal = $calendar->getDriverById($calendarID);
				$cal->setNow($match);
				$events = $cal->fastHandler();

				if ($events) {
					$output->writeln("<info>" . _("Match Found") . "</info>");
					$output->writeln(print_r($events, true));
				} else
					$output->writeln("<error>" . _("No Match Found") . "</error>");

				break;
			case 'event':
				$eventID = $input->getOption('id');
				$matched = [];
				foreach ($calendar->listCalendars() as $id => $c) {
					$cal = $calendar->getDriverById($id);
					$cal->setNow($match);
					$events = $cal->fastHandler();

					//continue if there are no matching events in this calendar
					if (!$events)
						continue;

					foreach ($events as $event) {
						if ($event['UID'] === $eventID) {
							$matched[] = $event;
							break 2; //beacuse UID is unique (or should be) we can continue right away
						}
					}
				}
				if ($matched) {
					$output->writeln("<info>" . _("Matched") . "</info>");
					$output->writeln(print_r($matched, true));
				} else
					$output->writeln("<error>" . _("No Match Found") . "</error>");

				break;
			case 'group':
				$groupID = $input->getOption('id');
				$matched = $calendar->matchGroupVerbose($groupID, $match);

				if (!empty($matched)) {
					$output->writeln("<info>" . _("Matched") . "</info>");
					$output->writeln(print_r($matched, true));
				} else
					$output->writeln("<error>" . _("No Match Found") . "</error>");

				break;
			default:
				throw new \Exception(_("Invalid type"));
				break;
		}
	}

	private function sync($calendar, InputInterface $input, OutputInterface $output)
	{
		$output->writeln(_("Starting Sync..."));
		$calendar->sync($output, $input->getOption('force'));
		$output->writeln(_("Finished"));
	}

	private function export($calendar, InputInterface $input, OutputInterface $output)
	{
		$driver = $calendar->getDriverById($input->getOption('export'));
		$output->writeln($driver->getIcal());
	}

	private function reset($calendar, InputInterface $input, OutputInterface $output)
	{
		$info = $calendar->getCalendarById($input->getOption('reset'));
		if ($info['type'] !== 'local') {
			$output->writeln("<error>" . _("Reset is only supported on local calendars!") . "</error>");
			exit(-1);
		}
		$output->writeln($info['calendar']->deleteiCal());
		$output->writeln(_("Successfully reset the calendar"));
	}

	private function import($calendar, InputInterface $input, OutputInterface $output)
	{
		$info = $calendar->getCalendarById($input->getOption('import'));
		if ($info['type'] !== 'local') {
			$output->writeln("<error>" . _("Reset is only supported on local calendars!") . "</error>");
			exit(-1);
		}
		$file = $input->getOption('file');
		if (!file_exists($file)) {
			$output->writeln("<error>" . _("File does not exist") . "</error>");
			exit(-1);
		}
		$info['calendar']->saveiCal(file_get_contents($file));
		$output->writeln(_("Successfully imported calendar"));
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Symfony\Component\Console\Exception\ExceptionInterface
	 */
	protected function outputHelp(InputInterface $input, OutputInterface $output)
	{
		$help = new HelpCommand();
		$help->setCommand($this);
		return $help->run($input, $output);
	}
}

