<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAggregatingJobsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cron:create-aggregating-jobs')
            ->setDescription('Creates aggregating jobs for last N days.')
            ->setHelp('Creates aggregating jobs for last N days.')
            ->addArgument('last_num_days', InputArgument::OPTIONAL, 'Number of last days for aggregating.', 3);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getName() . ' started');

        $App = new \Badoo\LiveProfilerUI\LiveProfilerUI();
        $last_num_days = (int)$input->getArgument('last_num_days');
        $output->writeln('Creating jobs for ' . $last_num_days . ' days');

        $snapshots = $App->getAggregator()->getSnapshotsDataForProcessing($last_num_days);
        $JobStorage = $App->getJobDataProvider();

        foreach ($snapshots as $snapshot) {
            $result = $JobStorage->add($snapshot['app'], $snapshot['label'], $snapshot['date']);

            $output->writeln(
                $snapshot['app'] . ' ' .
                $snapshot['label'] . ' ' .
                $snapshot['date'] . ' ' .
                ($result ? 'JOB CREATED' : 'ERROR')
            );
        }

        $output->writeln($this->getName() . ' finished');
    }
}
