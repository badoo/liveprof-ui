<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

use Badoo\LiveProfilerUI\Entity\Snapshot;

interface SnapshotInterface
{
    /**
     * @param string $app
     * @return Snapshot[]
     */
    public function getList(string $app = '') : array;
    public function getLastSnapshots(string $app = '', string $from_date = '') : array;
    public function getOneById(int $snapshot_id) : Snapshot;
    public function getListByIds(array $snapshot_ids) : array;
    public function getOneByAppAndLabel(string $app, string$label) : Snapshot;
    public function getOneByAppAndLabelAndDate(string $app, string $label, string $date) : Snapshot;
    public function getSnapshotsByDates(array $dates, string $param = '') : array;
    public function getSnapshotIdsByDates(array $dates, string $app, string $label) : array;
    public function getOldSnapshots(int $keep_days = 200, int $limit = 2000) : array;
    public function getDatesByAppAndLabel(string $app, string $label) : array;
    public function getMaxCallsCntByAppAndLabel(string $app, string $label) : int;
    public function getAppList(string $label = '') : array;
    public function updateSnapshot(int $snapshot_id, array $snapshot_data) : bool;
    public function createSnapshot(array $snapshot_data) : int;
    public function deleteById(int $snapshot_id) : bool;
}
