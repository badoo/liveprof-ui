<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;

class MethodTree extends Base implements MethodTreeInterface
{
    const TABLE_NAME = 'aggregator_tree';

    public function getSnapshotMethodsTree(int $snapshot_id) : array
    {
        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['snapshot_id', $snapshot_id],
                ]
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $MethodTree = new \Badoo\LiveProfilerUI\Entity\MethodTree(
                    $record,
                    $this->FieldList->getAllFieldsWithVariations()
                );
                $return[$MethodTree->getParentId() . '|' . $MethodTree->getMethodId()] = $MethodTree;
            }
        }

        return $return;
    }

    public function getDataByMethodIdsAndSnapshotIds(array $snapshot_ids, array $method_ids) : array
    {
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
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\MethodTree(
                    $record,
                    $this->FieldList->getAllFieldsWithVariations()
                );
            }
        }

        return $return;
    }

    public function getDataByParentIdsAndSnapshotIds(array $snapshot_ids, array $parent_ids) : array
    {
        if (empty($snapshot_ids) || empty($parent_ids)) {
            return [];
        }

        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['snapshot_id', $snapshot_ids],
                    ['parent_id', $parent_ids],
                ],
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\MethodTree(
                    $record,
                    $this->FieldList->getAllFieldsWithVariations()
                );
            }
        }

        return $return;
    }

    public function getSnapshotParentsData(array $snapshot_ids, array $fields = [], int $threshold = 0) : array
    {
        if (empty($snapshot_ids)) {
            return [];
        }

        if (empty($fields)) {
            $fields = $this->FieldList->getFields();
        }

        $fields_cond = ['snapshot_id', 'parent_id'];
        $having_cond = [];
        foreach ($fields as $field) {
            $fields_cond[] = ['field' => $field, 'function' => 'sum'];
            if ($threshold) {
                $having_cond[] = ['sum(' . $field . ')', $threshold, '>='];
            }
        }

        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            $fields_cond,
            [
                'filter' => [
                    ['snapshot_id', $snapshot_ids]
                ],
                'group' => ['snapshot_id', 'parent_id'],
                'having' => $having_cond,
            ]
        );
        $parent_stats = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                foreach ($fields as $field) {
                    $parent_stats[$record['snapshot_id']][$record['parent_id']][$field] = (int)$record[$field];
                }
            }
        }

        return $parent_stats;
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
