<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AWeekDegradationExampleCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('example:a-week-degradation')
            ->setDescription('Runs test code profiling which gets worse during 7 days.')
            ->setHelp('Runs test code profiling which gets worse during 7 days.');
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

        $SourceStorage = $App->getSourceStorage();

        $install_data_path = __DIR__ . '/../../../../bin/install_data/';
        $source_type = $SourceStorage->getType();
        $source_sql = file_get_contents($install_data_path . $source_type . '/example.sql');

        $insert_result = $SourceStorage->multiQuery($source_sql);
        $output->writeln('Example data inserted: ' . ($insert_result ? 'success' : 'error'));

        $Aggregator = $App->getAggregator();

        $dates = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray(
            date('Y-m-d'),
            8,
            8
        );
        foreach ($dates as $date) {
            // Run aggregating process manually to see results
            $Aggregator->setApp('App')
                ->setLabel('A week degradation')
                ->setDate($date)
                ->setIsManual(true)
                ->reset()
                ->process();
        }

        $output->writeln($this->getName() . ' finished');
    }
}
