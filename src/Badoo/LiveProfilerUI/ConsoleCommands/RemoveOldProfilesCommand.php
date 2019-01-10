<?php declare(strict_types=1);
/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\ConsoleCommands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOldProfilesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cron:remove-old-profiles')
            ->setDescription('Removes all profile data older N days.')
            ->setHelp('Removes all profile data older N days.')
            ->addArgument('keep_days', InputArgument::OPTIONAL, 'Number of days to keep profiles data.', 200);
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

        $Snapshot = $App->getSnapshotDataProvider();

        $keep_days = (int)$input->getArgument('keep_days');
        $output->writeln('Deletes profiles older ' . $keep_days . ' days');

        $old_snapshots = $Snapshot->getOldSnapshots($keep_days);

        foreach ($old_snapshots as $old_snapshot) {
            $result = $Snapshot->deleteById((int)$old_snapshot['id']);

            $output->writeln(
                $old_snapshot['app'] . ' ' .
                $old_snapshot['label'] . ' ' .
                $old_snapshot['date'] . ' ' .
                ($result ? 'DELETED' : 'ERROR')
            );
        }

        $output->writeln($this->getName() . ' finished');
    }
}
