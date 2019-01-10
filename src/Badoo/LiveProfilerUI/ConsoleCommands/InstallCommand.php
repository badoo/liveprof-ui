<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('aggregator:install')
            ->setDescription('Prepares tables for keep profiles and aggregating data.')
            ->setHelp('Prepares tables for keep profiles and aggregating data.');
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
        $AggregatorStorage = $App->getAggregatorStorage();

        $source_type = $SourceStorage->getType();
        $aggregator_type = $AggregatorStorage->getType();

        $install_data_path = __DIR__ . '/../../../../bin/install_data/';

        $source_sql = file_get_contents($install_data_path . $source_type . '/source.sql');
        $aggregator_sql = file_get_contents($install_data_path. $aggregator_type . '/aggregator.sql');

        $source_result = $SourceStorage->multiQuery($source_sql);
        $output->writeln('Source storage creating: ' . ($source_result ? 'success' : 'error'));

        $FieldList = $App->getFieldService();
        $fields = $FieldList->getAllFieldsWithVariations();
        $calls_count_field = $App->getCallsCountField();

        $aggregator_sql = \Badoo\LiveProfilerUI\DB\SqlTableBuilder::prepareCreateTables(
            $aggregator_type,
            $aggregator_sql,
            $fields,
            $calls_count_field
        );

        $use_jobs_in_aggregation = $App->isUseJobsInAggregation();
        if ($use_jobs_in_aggregation) {
            $jobs_sql = file_get_contents($install_data_path . $aggregator_type . '/jobs.sql');
            $aggregator_sql .= "\n" . $jobs_sql;
        }

        $aggregator_result = $AggregatorStorage->multiQuery($aggregator_sql);
        $output->writeln('Aggregator storage creating: ' . ($aggregator_result ? 'success' : 'error'));
        $output->writeln($this->getName() . ' finished');
    }
}
