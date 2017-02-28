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
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		}
		protected function execute(InputInterface $input, OutputInterface $output){
			$args = $input->getArgument('args');
			$command = isset($args[0])?$args[0]:'';
			$calendar = \FreePBX::create()->Calendar;
			switch ($command) {
				case "sync":
					$output->writeln("Starting Sync...");
					$calendar->sync($output);
					$output->writeln("Finished");
				break;
				default:
					$output->writeln("<error>The command provided is not valid.</error>");
					$output->writeln("Avalible commands are:");
					$output->writeln("<info>sync</info> - Syncronize Calendar Information");
				exit(4);
				break;
			}
		}
	}
