<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;

class MethodData extends Base implements MethodDataInterface
{
    const MAX_METHODS_DATA = 50;
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
        int $limit = self::MAX_METHODS_DATA
    ) : array {
        if (empty($snapshot_ids) || empty($method_ids)) {
            return [];
        }

        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['snapshot_id', $snapshot_ids],
                    ['method_id', $method_ids],
                ],
                'order' => ['snapshot_id' => 'desc'],
                'limit' => $limit
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
