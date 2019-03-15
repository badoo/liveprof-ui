#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Script to create tables in profile source and aggregate databases
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

$vendor_path = __DIR__ . '/../vendor/autoload.php';
$use_as_library_vendor_path = __DIR__ . '/../../../../vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
} elseif (file_exists($use_as_library_vendor_path)) {
    require_once $use_as_library_vendor_path;
}

use Symfony\Component\Console\Application;
use Badoo\LiveProfilerUI\ConsoleCommands\RemoveOldProfilesCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\AggregateAllProfilesCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\CreateAggregatingJobsCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\ProcessAggregatingJobsCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\AggregateManualCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\InstallCommand;
use Badoo\LiveProfilerUI\ConsoleCommands\AWeekDegradationExampleCommand;

$application = new Application();
$application->add(new RemoveOldProfilesCommand());
$application->add(new AggregateAllProfilesCommand());
$application->add(new CreateAggregatingJobsCommand());
$application->add(new ProcessAggregatingJobsCommand());
$application->add(new AggregateManualCommand());
$application->add(new InstallCommand());
$application->add(new AWeekDegradationExampleCommand());
$application->run();
