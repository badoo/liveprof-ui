<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\JobInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessAggregatingJobsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cron:process-aggregating-jobs')
            ->setDescription('Processes aggregating jobs.')
            ->setHelp('Processes aggregating jobs.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock_filename = '/tmp/aggregator_processor.lock';
        !file_exists($lock_filename) && touch($lock_filename);
        $lock_fp = fopen($lock_filename, 'rb+');
        if (!flock($lock_fp, LOCK_EX | LOCK_NB)) {
            $output->writeln('script is already running');
            return;
        }

        ini_set('memory_limit', '1G');

        $output->writeln($this->getName() . ' started');

        $App = new \Badoo\LiveProfilerUI\LiveProfilerUI();

        $JobStorage = $App->getJobDataProvider();

        $started_ts = time();
        while (time() - $started_ts < 300) {
            $jobs = $JobStorage->getJobs(JobInterface::STATUS_NEW, 100);
            foreach ($jobs as $Job) {
                try {
                    $JobStorage->changeStatus($Job->getId(), JobInterface::STATUS_PROCESSING);

                    $Aggregator = $App->getAggregator()
                        ->setApp($Job->getApp())
                        ->setLabel($Job->getLabel())
                        ->setDate($Job->getDate())
                        ->setIsManual($Job->getType() && $Job->getType() === 'manual');

                    $result = $Aggregator->process();
                    if (!empty($result)) {
                        $JobStorage->changeStatus($Job->getId(), JobInterface::STATUS_FINISHED);
                    } else {
                        $JobStorage->changeStatus($Job->getId(), JobInterface::STATUS_ERROR);
                    }
                } catch (\Exception $Ex) {
                    $JobStorage->changeStatus($Job->getId(), JobInterface::STATUS_ERROR);
                }
            }
            sleep(2);
        }

        $output->writeln($this->getName() . ' finished');
    }
}
