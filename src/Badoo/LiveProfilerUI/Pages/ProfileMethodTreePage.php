<?php declare(strict_types=1);

/**
 * A page with inheritance of methods of the snapshot
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\Entity\Snapshot;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class ProfileMethodTreePage extends BasePage
{
    const STAT_INTERVAL_WEEK = 7;
    const STAT_INTERVAL_MONTH = 31;
    const STAT_INTERVAL_HALF_YEAR = 182;

    /** @var string */
    protected static $template_path = 'profile_method_tree';
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodTreeInterface */
    protected $MethodTree;
    /** @var MethodDataInterface */
    protected $MethodData;
    /** @var FieldList */
    protected $FieldList;
    /** @var string */
    protected $calls_count_field = '';
    /** @var array */
    protected static $graph_intervals = [
        '7 days' => self::STAT_INTERVAL_WEEK,
        '1 month' => self::STAT_INTERVAL_MONTH,
        '6 months' => self::STAT_INTERVAL_HALF_YEAR,
    ];

    public function __construct(
        ViewInterface $View,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodTreeInterface $MethodTree,
        MethodDataInterface $MethodData,
        FieldList $FieldList,
        string $calls_count_field
    ) {
        $this->View = $View;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodTree = $MethodTree;
        $this->MethodData = $MethodData;
        $this->FieldList = $FieldList;
        $this->calls_count_field = $calls_count_field;
    }

    protected function cleanData() : bool
    {
        $this->data['app'] = isset($this->data['app']) ? trim($this->data['app']) : '';
        $this->data['label'] = isset($this->data['label']) ? trim($this->data['label']) : '';
        $this->data['snapshot_id'] = isset($this->data['snapshot_id']) ? (int)$this->data['snapshot_id'] : 0;

        if (!$this->data['snapshot_id'] && (!$this->data['app'] || !$this->data['label'])) {
            throw new \InvalidArgumentException('Empty snapshot_id, app and label');
        }

        $this->initDates();
        $this->initMethodId();

        return true;
    }

    protected function initMethodId()
    {
        $this->data['method_id'] = isset($this->data['method_id']) ? (int)$this->data['method_id'] : 0;

        if (!empty($this->data['method_name']) && !$this->data['method_id']) {
            $methods = $this->Method->findByName($this->data['method_name'], true);
            if (!empty($methods)) {
                $this->data['method_id'] = array_keys($methods)[0];
            }
        }
    }

    public function initDates()
    {
        $this->data['date1'] = isset($this->data['date1']) ? trim($this->data['date1']) : '';
        $this->data['date2'] = isset($this->data['date2']) ? trim($this->data['date2']) : '';

        if ($this->data['date1'] && $this->data['date2']) {
            $this->data['stat_interval'] = 0;
        } else {
            $this->data['stat_interval'] = isset($this->data['stat_interval']) ? (int)$this->data['stat_interval'] : 0;
            if (!\in_array($this->data['stat_interval'], self::$graph_intervals, true)) {
                $this->data['stat_interval'] = self::STAT_INTERVAL_MONTH;
            }
        }
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getTemplateData() : array
    {
        $Snapshot = $this->getSnapshot();

        if (!$this->data['method_id']) {
            $this->data['method_id'] = $this->getMainMethodId();
        }

        $methods = $this->Method->getListByIds([$this->data['method_id']]);
        $method_name = '?';
        if (!empty($methods)) {
            $method_name = $methods[$this->data['method_id']];
        }

        if ($this->data['date1'] && $this->data['date2']) {
            $dates = \Badoo\LiveProfilerUI\DateGenerator::getDatesByRange(
                $this->data['date1'],
                $this->data['date2']
            );
        } else {
            $dates = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray(
                $Snapshot->getDate(),
                $this->data['stat_interval'],
                $this->data['stat_interval']
            );
            $this->data['date1'] = current($dates);
            $this->data['date2'] = end($dates);
        }

        $link_base = '/profiler/tree-view.phtml?'
            . 'app=' . urlencode($Snapshot->getApp())
            . '&label=' . urlencode($Snapshot->getLabel());

        $view_data = [
            'snapshot_id' => $Snapshot->getId(),
            'snapshot_app' => $Snapshot->getApp(),
            'snapshot_label' => $Snapshot->getLabel(),
            'snapshot_date' => $Snapshot->getDate(),
            'method_id' => $this->data['method_id'],
            'method_name' => $method_name,
            'method_dates' => $dates,
            'stat_intervals' => $this->getIntervalsFormData($link_base),
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
            'available_graphs' => $this->getGraphsData(),
        ];

        $common_block_data = [
            'link_base' => $link_base,
            'fields' => $this->FieldList->getAllFieldsWithVariations(),
            'field_descriptions' => $this->FieldList->getFieldDescriptions(),
            'stat_interval' => $this->data['stat_interval'],
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
        ];

        $date_to_snapshot_map = $this->Snapshot->getSnapshotIdsByDates(
            $dates,
            $Snapshot->getApp(),
            $Snapshot->getLabel()
        );

        $method_data = $this->getMethodDataWithHistory($date_to_snapshot_map, $this->data['method_id']);
        if (!empty($method_data)) {
            $view_data['method_data'] = $this->View->fetchFile(
                'profiler_result_view_part',
                $common_block_data + ['data' => $method_data, 'hide_lines_column' => true],
                false
            );
        }

        $parents = $this->getMethodParentsWithHistory($date_to_snapshot_map, $this->data['method_id']);
        if (!empty($parents)) {
            $this->sortList($parents);
            $view_data['parents'] = $this->View->fetchFile(
                'profiler_result_view_part',
                $common_block_data + ['data' => $parents],
                false
            );
        }

        $children = $this->getMethodChildrenWithHistory($date_to_snapshot_map, $this->data['method_id']);
        if (!empty($children)) {
            $this->sortList($children);
            $view_data['children'] = $this->View->fetchFile(
                'profiler_result_view_part',
                $common_block_data + ['data' => $children, 'hide_lines_column' => true],
                false
            );
        }

        $view_data['js_graph_data_all'] = array_merge($method_data, $children);
        $view_data['all_apps'] = $this->Snapshot->getAppList($Snapshot->getLabel());

        return $view_data;
    }

    protected function getSnapshot() : Snapshot
    {
        try {
            if ($this->data['snapshot_id']) {
                $Snapshot = $this->Snapshot->getOneById($this->data['snapshot_id']);
            } elseif ($this->data['app'] && $this->data['label']) {
                $Snapshot = $this->Snapshot->getOneByAppAndLabel($this->data['app'], $this->data['label']);
            } else {
                throw new \InvalidArgumentException('Can\'t get snapshot');
            }
        } catch (\InvalidArgumentException $Ex) {
            $Snapshot = new Snapshot(
                [
                    'app' => $this->data['app'],
                    'label' => $this->data['label'],
                    'id' => 0,
                ],
                []
            );
        }

        return $Snapshot;
    }

    protected function sortList(array &$records)
    {
        $sort_field = (string)current($this->FieldList->getFields());
        usort($records, function ($Element1, $Element2) use ($sort_field) : int {
            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Element1 */
            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Element2 */
            return $Element2->getValue($sort_field) > $Element1->getValue($sort_field) ? 1 : -1;
        });
    }

    protected function getMainMethodId() : int
    {
        $methods = $this->Method->findByName('main()', true);
        if (!empty($methods)) {
            return array_keys($methods)[0];
        }
        return 0;
    }

    protected function getGraphsData() : array
    {
        $fields = $this->FieldList->getAllFieldsWithVariations();
        $fields['calls_count'] = 'calls_count';

        $data = [];
        foreach ($fields as $field) {
            if ($field === 'calls_count') {
                $data[$field] = [
                    'type' => 'times',
                    'label' => 'profiles count',
                    'graph_label' => 'calls count'
                ];
                continue;
            }

            if (strpos($field, 'mem') !== false) {
                $type = 'memory';
            } elseif (strpos($field, $this->calls_count_field) !== false) {
                $type = 'times';
            } else {
                $type = 'time';
            }
            $data[$field] = [
                'type' => $type,
                'label' => $field,
                'graph_label' => $field . ' self + children calls graph'
            ];
        }

        return $data;
    }

    protected function getIntervalsFormData(string $link_base) : array
    {
        $data = [];
        foreach (self::$graph_intervals as $name => $value) {
            $data[] = [
                'name' => $name,
                'link' => $link_base . "&method_id={$this->data['method_id']}&stat_interval=$value",
                'selected' => $value === $this->data['stat_interval'],
            ];
        }
        return $data;
    }

    protected function getMethodDataWithHistory(array $dates_to_snapshots, int $method_id) : array
    {
        $snapshot_ids = array_column(array_filter(array_values($dates_to_snapshots)), 'id');
        if (empty($snapshot_ids)) {
            return [];
        }

        $method_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds($snapshot_ids, [$method_id]);
        $method_data = $this->Method->injectMethodNames($method_data);

        return $this->getProfilerRecordsWithHistory($method_data, $dates_to_snapshots);
    }

    protected function getMethodParentsWithHistory(array $dates_to_snapshots, int $method_id) : array
    {
        $snapshot_ids = array_column(array_filter(array_values($dates_to_snapshots)), 'id');
        if (empty($snapshot_ids)) {
            return [];
        }

        $MethodTree = $this->MethodTree->getDataByMethodIdsAndSnapshotIds($snapshot_ids, [$method_id]);

        foreach ($MethodTree as &$Item) {
            $Item->setMethodId($Item->getParentId());
        }
        unset($Item);

        $MethodTree = $this->Method->injectMethodNames($MethodTree);

        return $this->getProfilerRecordsWithHistory($MethodTree, $dates_to_snapshots);
    }

    protected function getMethodChildrenWithHistory(array $dates_to_snapshots, int $method_id) : array
    {
        $snapshot_ids = array_column(array_filter(array_values($dates_to_snapshots)), 'id');
        if (empty($snapshot_ids)) {
            return [];
        }

        $MethodTree = $this->MethodTree->getDataByParentIdsAndSnapshotIds($snapshot_ids, [$method_id]);
        $MethodTree = $this->Method->injectMethodNames($MethodTree);

        return $this->getProfilerRecordsWithHistory($MethodTree, $dates_to_snapshots);
    }

    /**
     * @param \Badoo\LiveProfilerUI\Entity\MethodData[] $result
     * @param array $dates_to_snapshots
     * @param int $method_id
     * @return array
     */
    protected function getProfilerRecordsWithHistory(array $result, array $dates_to_snapshots) : array
    {
        $all_fields = $this->FieldList->getAllFieldsWithVariations();

        $snapshot_ids = array_column(array_filter($dates_to_snapshots), 'id');
        $last_snapshot_id = end($snapshot_ids);

        $history = [];
        foreach ($result as $Row) {
            $history[$Row->getMethodId()][$Row->getSnapshotId()] = $Row;
        }

        $result = [];
        foreach ($history as $method_rows) {
            // the method was not called in the last snapshot, so it will not displayed
            if (!isset($method_rows[$last_snapshot_id])) {
                continue;
            }

            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Row */
            $Row = $method_rows[$last_snapshot_id];

            $data = [];
            foreach ($all_fields as $field) {
                $data[$field] = [];
            }

            // extract data from previous snapshots
            foreach ($dates_to_snapshots as $snapshot) {
                $values = [];
                $snapshot_id = $snapshot['id'];
                if ($snapshot_id && isset($method_rows[$snapshot_id])) {
                    /** @var \Badoo\LiveProfilerUI\Entity\MethodData $PreviousRow */
                    $PreviousRow = $method_rows[$snapshot_id];
                    $values = $PreviousRow->getValues();
                }

                foreach ($all_fields as $field) {
                    $data[$field][] = ['val' => $values[$field] ?? 0];
                }

                if ($data) {
                    $data['calls_count'][] = ['val' => $snapshot['calls_count']];
                }
            }

            $Row->setHistoryData($data);

            $result[] = $Row;
        }

        return $result;
    }
}
