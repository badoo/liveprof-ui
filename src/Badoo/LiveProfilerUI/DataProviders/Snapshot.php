<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;

class Snapshot extends Base implements SnapshotInterface
{
    const TABLE_NAME = 'aggregator_snapshots';

    /**
     * @param string $app
     * @return \Badoo\LiveProfilerUI\Entity\Snapshot[]
     */
    public function getList(string $app = '') : array
    {
        $last_snapshots = $this->getLastSnapshots($app);
        if (empty($last_snapshots)) {
            return [];
        }

        $last_snapshots_data = [];
        foreach ($last_snapshots as $last_snapshot) {
            $snapshot_filter = [];
            unset($last_snapshot['id']);
            foreach ($last_snapshot as $key => $value) {
                $snapshot_filter[] = [$key, $value];
            }
            $last_snapshots_data[] = $snapshot_filter;
        }

        $filter = [
            ['union', $last_snapshots_data],
            ['label', '', '!='],
        ];
        if ($app) {
            $filter[] = ['app', $app];
        }

        $snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => $filter,
                'order' => [
                    'date' => 'desc',
                    'label' => 'asc',
                    'app' => 'asc'
                ]
            ]
        );

        $return = [];
        if (!empty($snapshots)) {
            foreach ($snapshots as $snapshot) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\Snapshot(
                    $snapshot,
                    $this->FieldList->getAllFieldsWithVariations()
                );
            }
        }

        return $return;
    }

    public function getLastSnapshots(string $app = '', string $from_date = '') : array
    {
        $filter = [
            ['label', '', '!='],
        ];
        if ($app) {
            $filter[] = ['app', $app];
        }
        if ($from_date) {
            $filter[] = ['date', $from_date, '>='];
        }

        $last_snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            [['field' => 'id', 'function' => 'max'], 'app', 'label', ['field' => 'date', 'function' => 'max']],
            [
                'filter' => $filter,
                'group' => [
                    'app',
                    'label'
                ]
            ]
        );

        return $last_snapshots;
    }

    public function getOneById(int $snapshot_id) : \Badoo\LiveProfilerUI\Entity\Snapshot
    {
        $snapshot = $this->AggregatorStorage->getOne(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [['id', $snapshot_id]]
            ]
        );

        if (empty($snapshot)) {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        }

        return new \Badoo\LiveProfilerUI\Entity\Snapshot(
            $snapshot,
            $this->FieldList->getAllFieldsWithVariations()
        );
    }

    public function getOneByAppAndLabel(string $app, string $label) : \Badoo\LiveProfilerUI\Entity\Snapshot
    {
        $snapshot = $this->AggregatorStorage->getOne(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['app', $app],
                    ['label', $label]
                ],
                'order' => ['date' => 'desc']
            ]
        );

        if (empty($snapshot)) {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        }

        return new \Badoo\LiveProfilerUI\Entity\Snapshot(
            $snapshot,
            $this->FieldList->getAllFieldsWithVariations()
        );
    }

    public function getOneByAppAndLabelAndDate(
        string $app,
        string $label,
        string $date
    ) : \Badoo\LiveProfilerUI\Entity\Snapshot {
        $snapshot = $this->AggregatorStorage->getOne(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['app', $app],
                    ['label', $label],
                    ['date', $date]
                ],
                'order' => ['date' => 'desc']
            ]
        );

        if (empty($snapshot)) {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        }

        return new \Badoo\LiveProfilerUI\Entity\Snapshot(
            $snapshot,
            $this->FieldList->getAllFieldsWithVariations()
        );
    }

    public function getSnapshotsByDates(array $dates, string $param = '') : array
    {
        if (empty($dates)) {
            return [];
        }

        $fields = ['id', 'app', 'label', 'date', 'type'];
        if ($param) {
            $fields[] = $param;
        }

        $snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            $fields,
            [
                'filter' => [
                    ['date', $dates],
                ],
            ]
        );
        return $snapshots;
    }

    public function getSnapshotIdsByDates(array $dates, string $app, string $label) : array
    {
        if (empty($dates) || !$app || !$label) {
            return [];
        }

        $snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['id', 'calls_count', 'date'],
            [
                'filter' => [
                    ['date', $dates],
                    ['app', $app],
                    ['label', $label],
                ],
            ]
        );

        $result = [];
        if (!empty($snapshots)) {
            foreach ($snapshots as $row) {
                $result[$row['date']] = [
                    'id' => (int)$row['id'],
                    'calls_count' => (int)$row['calls_count'],
                ];
            }
        }
        foreach ($dates as $date) {
            if (!isset($result[$date])) {
                $result[$date] = null;
            }
        }
        ksort($result);

        return $result;
    }

    public function getOldSnapshots(int $keep_days = 200, int $limit = 2000) : array
    {
        $snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['id', 'app', 'label', 'date'],
            [
                'filter' => [
                    ['date', date('Y-m-d', strtotime("-$keep_days day")), '<'],
                ],
                'order' => ['id' => 'asc'],
                'limit' => $limit
            ]
        );

        return $snapshots;
    }

    public function getDatesByAppAndLabel(string $app, string $label) : array
    {
        $snapshots = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['date'],
            [
                'filter' => [
                    ['date', date('Y-m-d', strtotime('-1 year')), '>'],
                    ['app', $app],
                    ['label', $label],
                ],
                'order' => ['date' => 'desc']
            ]
        );

        return $snapshots ? array_column($snapshots, 'date') : [];
    }

    public function getMaxCallsCntByAppAndLabel(string $app, string $label) : int
    {
        $result = $this->AggregatorStorage->getOne(
            self::TABLE_NAME,
            [['field' => 'calls_count', 'function' => 'max']],
            [
                'filter' => [
                    ['app', $app],
                    ['label', $label],
                ],
                'group' => ['app', 'label'],
            ]
        );

        return $result ? (int)current($result) : 0;
    }

    public function getAppList(string $label = '') : array
    {
        $filter = [];
        if ($label) {
            $filter[] = ['label', $label];
        }
        $apps = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['app'],
            [
                'filter' => $filter,
                'group' => ['app'],
                'order' => ['app' => 'asc'],
            ]
        );

        return $apps ? array_column($apps, 'app') : [];
    }

    public function updateSnapshot(int $snapshot_id, array $snapshot_data) : bool
    {
        if (empty($snapshot_data)) {
            return false;
        }

        return $this->AggregatorStorage->update(
            self::TABLE_NAME,
            $snapshot_data,
            ['id' => $snapshot_id]
        );
    }

    public function createSnapshot(array $snapshot_data) : int
    {
        if (empty($snapshot_data)) {
            return 0;
        }

        return $this->AggregatorStorage->insert(self::TABLE_NAME, $snapshot_data);
    }

    /**
     * Delete snapshot by id,  aggregator_tree and aggregator_method_data will be deleted by cascade
     * @param int $snapshot_id
     * @return bool
     */
    public function deleteById(int $snapshot_id) : bool
    {
        if (!$snapshot_id) {
            return false;
        }

        return $this->AggregatorStorage->delete(
            self::TABLE_NAME,
            ['id' => $snapshot_id]
        );
    }
}
