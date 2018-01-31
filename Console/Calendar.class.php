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
class Calendar extends Command {
	protected function configure(){
		$this->setName('calendar')
		->setDescription(_('Calendar'))
		->setDefinition(array(
			new InputOption('sync', null, InputOption::VALUE_NONE, _('Syncronize all Calendars')),
			new InputOption('force', null, InputOption::VALUE_NONE, _('Force command')),
			new InputOption('export', null, InputOption::VALUE_REQUIRED, _('Export Calendar by ID')),
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

			$output->writeln("<error>The command provided is not valid.</error>");
			$output->writeln("Avalible commands are:");
			$output->writeln("<info>sync</info> - Syncronize Calendar Information");
		}

		private function sync($calendar, InputInterface $input, OutputInterface $output) {
			$output->writeln("Starting Sync...");
			$calendar->sync($output,true);
			$output->writeln("Finished");
		}

		private function export($calendar, InputInterface $input, OutputInterface $output) {
			$output->writeln($calendar->getRawCalendar($input->getOption('export')));
		}
	}
