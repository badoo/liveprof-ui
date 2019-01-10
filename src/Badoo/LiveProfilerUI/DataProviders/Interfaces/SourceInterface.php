<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

interface SourceInterface
{
    public function getSnapshotsDataByDates(string $datetime_from, string $datetime_to) : array;
    public function getPerfData(string $app, string $label, string $date) : array;
    public function getLabelList() : array;
    public function getAppList() : array;
    public function insert(string $app, string $label, string $datetime, array $data) : int;
}
