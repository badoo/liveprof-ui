<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;

class MethodData extends Base implements MethodDataInterface
{
    const TABLE_NAME = 'aggregator_method_data';

    public function getDataBySnapshotId(int $snapshot_id) : array
    {
        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['snapshot_id', $snapshot_id]
                ],
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\MethodData(
                    $record,
                    $this->FieldList->getAllFieldsWithVariations()
                );
            }
        }

        return $return;
    }

    public function getDataByMethodIdsAndSnapshotIds(
        array $snapshot_ids,
        array $method_ids,
        int $limit = 0,
        int $start_snapshot_id = 0
    ) : array {
        if (empty($snapshot_ids) && empty($method_ids)) {
            return [];
        }

        $filters = [];
        if (!empty($snapshot_ids)) {
            $filters[] = ['snapshot_id', $snapshot_ids];
        }
        if (!empty($method_ids)) {
            $filters[] = ['method_id', $method_ids];
        }
        if ($start_snapshot_id) {
            $filters[] = ['snapshot_id', $start_snapshot_id, '>='];
        }

        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => $filters,
                'order' => ['snapshot_id' => 'desc'],
                'limit' => $limit,
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\MethodData(
                    $record,
                    $this->FieldList->getAllFieldsWithVariations()
                );
            }
        }

        return $return;
    }

    public function getOneParamDataBySnapshotIds(array $snapshot_ids, string $param, int $threshold = 1000) : array
    {
        if (empty($snapshot_ids)) {
            return [];
        }

        $result = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['snapshot_id', 'method_id', $param],
            [
                'filter' => [
                    ['snapshot_id', $snapshot_ids],
                    [$param, $threshold, '>='],
                ]
            ]
        );
        return $result;
    }

    public function deleteBySnapshotId(int $snapshot_id) : bool
    {
        return $this->AggregatorStorage->delete(
            self::TABLE_NAME,
            ['snapshot_id' => $snapshot_id]
        );
    }

    public function insertMany(array $inserts) : bool
    {
        if (empty($inserts)) {
            return false;
        }

        return $this->AggregatorStorage->insertMany(self::TABLE_NAME, $inserts);
    }
}
