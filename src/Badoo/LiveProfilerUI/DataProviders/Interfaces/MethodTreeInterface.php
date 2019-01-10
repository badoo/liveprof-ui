<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

use Badoo\LiveProfilerUI\Entity\MethodTree;

interface MethodTreeInterface
{
    /**
     * @param int $snapshot_id
     * @return MethodTree[]
     */
    public function getSnapshotMethodsTree(int $snapshot_id) : array;

    /**
     * @param int[] $snapshot_ids
     * @param int[] $method_ids
     * @return MethodTree[]
     */
    public function getDataByMethodIdsAndSnapshotIds(array $snapshot_ids, array $method_ids) : array;

    /**
     * @param int[] $snapshot_ids
     * @param int[] $parent_ids
     * @return MethodTree[]
     */
    public function getDataByParentIdsAndSnapshotIds(array $snapshot_ids, array $parent_ids) : array;
    public function getSnapshotParentsData(array $snapshot_ids, array $fields = [], int $threshold = 0) : array;
    public function deleteBySnapshotId(int $snapshot_id) : bool;
    public function insertMany(array $inserts) : bool;
}
