<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateManualCommand extends Command
{
    protected function configure()
    {
        $App = new \Badoo\LiveProfilerUI\LiveProfilerUI();

        $this
            ->setName('aggregator:aggregate-manual')
            ->setDescription('Aggregate profiles for last N days.')
            ->setHelp('Aggregate profiles for last N days.')
            ->addArgument('label', InputArgument::REQUIRED, 'Label of profile.')
            ->addArgument('app', InputArgument::OPTIONAL, 'App of profile.', $App->getDefaultApp())
            ->addArgument('date', InputArgument::OPTIONAL, 'Date of profile.', date('Y-m-d'));
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

        $app = $input->getArgument('app');
        $label = $input->getArgument('label');
        $date = $input->getArgument('date');

        $result = $App->getAggregator()
            ->setApp($app)
            ->setLabel($label)
            ->setDate($date)
            ->setIsManual(true)
            ->reset()
            ->process();

        $output->writeln("$app $label $date " . ($result ? 'AGGREGATED' : 'ERROR'));

        $output->writeln($this->getName() . ' finished');
    }
}
