<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateAllProfilesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cron:aggregate-all-profiles')
            ->setDescription('Aggregate profiles for last N days.')
            ->setHelp('Aggregate profiles for last N days.')
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
        ini_set('memory_limit', '1G');

        $output->writeln($this->getName() . ' started');

        $App = new \Badoo\LiveProfilerUI\LiveProfilerUI();

        $last_num_days = (int)$input->getArgument('last_num_days');
        $output->writeln('Aggregating profiles for ' . $last_num_days . ' days');

        $snapshots = $App->getAggregator()->getSnapshotsDataForProcessing($last_num_days);

        foreach ($snapshots as $snapshot) {
            $result = $App->getAggregator()
                ->setApp($snapshot['app'])
                ->setLabel($snapshot['label'])
                ->setDate($snapshot['date'])
                ->reset()
                ->process();

            $output->writeln(
                $snapshot['app'] . ' ' .
                $snapshot['label'] . ' ' .
                $snapshot['date'] . ' ' .
                ($result ? 'AGGREGATED' : 'ERROR')
            );
        }

        $output->writeln($this->getName() . ' finished');
    }
}
