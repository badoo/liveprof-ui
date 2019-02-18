<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\SourceInterface;
use Badoo\LiveProfilerUI\Interfaces\DataPackerInterface;
use Badoo\LiveProfilerUI\Interfaces\StorageInterface;

class Source implements SourceInterface
{
    const MAX_SELECT_LIMIT = 10000;
    const SELECT_LIMIT = 1440;
    const TABLE_NAME = 'details';

    /** @var StorageInterface  */
    protected $SourceStorage;
    /** @var DataPackerInterface */
    protected $DataPacker;

    public function __construct(StorageInterface $SourceStorage, DataPackerInterface $DataPacker)
    {
        $this->SourceStorage = $SourceStorage;
        $this->DataPacker = $DataPacker;
    }

    public function getSnapshotsDataByDates(string $datetime_from, string $datetime_to) : array
    {
        $snapshots = $this->SourceStorage->getAll(
            self::TABLE_NAME,
            ['app', 'label', ['field' => 'timestamp', 'function' => 'date', 'alias' => 'date']],
            [
                'filter' => [
                    ['label', '', '!='],
                    ['timestamp', $datetime_from, '>='],
                    ['timestamp', $datetime_to, '<='],
                ],
                'group' => ['app', 'label', 'date'],
            ]
        );

        return $snapshots ?? [];
    }

    public function getPerfData(string $app, string $label, string $date) : array
    {
        $result = $this->SourceStorage->getAll(
            self::TABLE_NAME,
            ['id'],
            [
                'filter' => [
                    ['timestamp', $date . ' 00:00:00', '>='],
                    ['timestamp', $date . ' 23:59:59', '<='],
                    ['app', $app],
                    ['label', $label],
                ],
                'limit' => self::MAX_SELECT_LIMIT,
            ]
        );

        if (!$result) {
            return [];
        }

        // get maximum SELECT_LIMIT random records
        shuffle($result);
        $result = array_slice($result, 0, self::SELECT_LIMIT + 1);

        $ids = $result ? array_column($result, 'id') : [];

        $result = $this->SourceStorage->getAll(
            self::TABLE_NAME,
            ['perfdata'],
            [
                'filter' => [
                    ['id', $ids],
                ],
                'limit' => self::SELECT_LIMIT + 1,
            ]
        );

        return $result ? array_column($result, 'perfdata') : [];
    }

    public function getLabelList() : array
    {
        $labels = $this->SourceStorage->getAll(
            self::TABLE_NAME,
            ['label'],
            [
                'filter' => [
                    ['timestamp', date('Y-m-d 00:00:00', strtotime('-1 day')), '>'],
                    ['label', '', '!=']
                ],
                'group' => ['label'],
                'order' => ['label' => 'asc']
            ]
        );

        return $labels ? array_column($labels, 'label') : [];
    }

    public function getAppList() : array
    {
        $labels = $this->SourceStorage->getAll(
            self::TABLE_NAME,
            ['app'],
            [
                'filter' => [
                    ['timestamp', date('Y-m-d 00:00:00', strtotime('-1 day')), '>'],
                ],
                'group' => ['app'],
                'order' => ['app' => 'asc']
            ]
        );

        return $labels ? array_column($labels, 'app') : [];
    }
}
