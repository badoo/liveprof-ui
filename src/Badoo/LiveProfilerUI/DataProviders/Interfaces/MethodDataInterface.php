<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

use Badoo\LiveProfilerUI\Entity\MethodData;

interface MethodDataInterface
{
    /**
     * @param int $snapshot_id
     * @return MethodData[]
     */
    public function getDataBySnapshotId(int $snapshot_id) : array;

    /**
     * @param int[] $snapshot_ids
     * @param int[] $method_ids
     * @param int $limit
     * @param int $start_snapshot_id
     * @return MethodData[]
     */
    public function getDataByMethodIdsAndSnapshotIds(array $snapshot_ids, array $method_ids, int $limit = 0, int $start_snapshot_id = 0) : array;
    public function getOneParamDataBySnapshotIds(array $snapshot_ids, string $param, int $threshold = 1000) : array;
    public function deleteBySnapshotId(int $snapshot_id) : bool;
    public function insertMany(array $inserts) : bool;
}
