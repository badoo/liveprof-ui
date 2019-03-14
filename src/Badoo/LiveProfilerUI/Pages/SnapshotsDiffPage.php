<?php declare(strict_types=1);

/**
 * A page with snapshots difference
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class SnapshotsDiffPage extends BasePage
{
    const MIN_CTS_DIFF = 0.2;
    const MAX_NAME_LENGTH = 50;

    /** @var string */
    protected static $template_path = 'snapshots_diff';
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodTreeInterface */
    protected $MethodTree;
    /** @var FieldList */
    protected $FieldList;
    /** @var string */
    protected $calls_count_field = '';

    public function __construct(
        ViewInterface $View,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodTreeInterface $MethodTree,
        FieldList $FieldList,
        string $calls_count_field
    ) {
        $this->View = $View;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodTree = $MethodTree;
        $this->FieldList = $FieldList;
        $this->calls_count_field = $calls_count_field;
    }

    protected function cleanData() : bool
    {
        $this->data['app'] = isset($this->data['app']) ? trim($this->data['app']) : '';
        $this->data['label'] = isset($this->data['label']) ? trim($this->data['label']) : '';
        $this->data['date1'] = isset($this->data['date1']) ? trim($this->data['date1']) : '';
        $this->data['date2'] = isset($this->data['date2']) ? trim($this->data['date2']) : '';
        $this->data['param'] = isset($this->data['param']) ? trim($this->data['param']) : '';

        return true;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTemplateData() : array
    {
        $fields = $this->FieldList->getFields();
        $field_descriptions = $this->FieldList->getFieldDescriptions();

        if (!$this->calls_count_field || !\in_array($this->calls_count_field, $fields, true)) {
            throw new \InvalidArgumentException('Diff functionality is not available');
        }

        $fields = array_diff($fields, [$this->calls_count_field]);

        if (!$this->data['param']) {
            $this->data['param'] = current($fields);
        }

        $this->initDates();

        $Snapshot1 = false;
        if ($this->data['date1']) {
            try {
                $Snapshot1 = $this->Snapshot->getOneByAppAndLabelAndDate(
                    $this->data['app'],
                    $this->data['label'],
                    $this->data['date1']
                );
            } catch (\InvalidArgumentException $Ex) {
                // just skip error
            }
        }

        $Snapshot2 = false;
        if ($this->data['date2']) {
            try {
                $Snapshot2 = $this->Snapshot->getOneByAppAndLabelAndDate(
                    $this->data['app'],
                    $this->data['label'],
                    $this->data['date2']
                );
            } catch (\InvalidArgumentException $Ex) {
                // just skip error
            }
        }

        $diff = false;
        if ($Snapshot1 && $Snapshot2) {
            $diff = $this->getSnapshotsDiff(
                $Snapshot1->getId(),
                $Snapshot2->getId(),
                $this->data['param']
            );
        }

        return [
            'app' => $this->data['app'],
            'label' => $this->data['label'],
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
            'snapshot1' => $Snapshot1,
            'snapshot2' => $Snapshot2,
            'diff' => $diff,
            'params' => $fields,
            'param' => $this->data['param'],
            'field_descriptions' => $field_descriptions,
            'link_base' => "/profiler/tree-view.phtml?app={$this->data['app']}&label={$this->data['label']}"
        ];
    }

    /**
     * Calculates date params
     * @return bool
     * @throws \Exception
     */
    public function initDates() : bool
    {
        $dates = $this->Snapshot->getDatesByAppAndLabel($this->data['app'], $this->data['label']);

        $last_date = '';
        $month_old_date = '';
        if (!empty($dates) && \count($dates) >= 2) {
            $last_date = $dates[0];
            $last_datetime = new \DateTime($last_date);
            for ($i = 1; $i < 30 && $i < \count($dates); $i++) {
                $month_old_date = $dates[$i];
                $month_old_datetime = new \DateTime($month_old_date);
                $Interval = $last_datetime->diff($month_old_datetime);
                if ($Interval->days > 30) {
                    break;
                }
            }
        }

        if (!$this->data['date1']) {
            $this->data['date1'] = $month_old_date;
        }

        if (!$this->data['date2']) {
            $this->data['date2'] = $last_date;
        }

        return true;
    }

    protected function getSnapshotsDiff(int $snapshot1_id, int $snapshot2_id, string $param) : array
    {
        $tree = [];
        $method_ids = [];
        $snapshot1_method_cts = $this->extractTreeData($snapshot1_id, 0, $tree, $method_ids);
        $snapshot2_method_cts = $this->extractTreeData($snapshot2_id, 1, $tree, $method_ids);

        $method_names = $this->Method->getListByIds($method_ids);

        $diff = [];
        foreach ($tree as $parent_id => $data) {
            if (empty($data[0]) || empty($data[1])
                || empty($snapshot1_method_cts[$parent_id]) || empty($snapshot2_method_cts[$parent_id])) {
                continue;
            }

            $res = $this->getMethodsDiff(
                $parent_id,
                $data[0],
                $data[1],
                $snapshot1_method_cts[$parent_id],
                $snapshot2_method_cts[$parent_id],
                $method_names,
                $param
            );
            if ($res) {
                $diff[$parent_id] = $res;
            }
        }
        usort(
            $diff,
            function ($element1, $element2) : int {
                return $element2['delta'] > $element1['delta'] ? 1 : -1;
            }
        );
        return $diff;
    }

    protected function extractTreeData(int $snapshot_id, int $position, array &$tree, array &$method_ids) : array
    {
        $method_cts = [];
        $result = $this->MethodTree->getSnapshotMethodsTree($snapshot_id);
        foreach ($result as $Row) {
            $parent_id = $Row->getParentId();
            $method_id = $Row->getMethodId();
            if (!isset($tree[$parent_id])) {
                $tree[$parent_id] = [];
            }
            if (!isset($tree[$parent_id][$position])) {
                $tree[$parent_id][$position] = [];
            }
            $tree[$parent_id][$position][$method_id] = $Row;

            if (!isset($method_cts[$method_id])) {
                $method_cts[$method_id] = 0;
            }
            $method_cts[$method_id] += $Row->getValue($this->calls_count_field);
            $method_ids[$method_id] = $method_id;
        }

        return $method_cts;
    }

    /**
     * @param int $parent_id
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $data1
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $data2
     * @param float $method_calls_count1
     * @param float $method_calls_count2
     * @param array $method_names
     * @param string $param
     * @return array|bool
     */
    protected function getMethodsDiff(
        int $parent_id,
        array $data1,
        array $data2,
        float $method_calls_count1,
        float $method_calls_count2,
        array $method_names = [],
        string $param = ''
    ) {
        $all_fields = $this->FieldList->getFields();

        $res = [];
        $delta = 0;
        $method_ids = array_unique(array_merge(array_keys($data1), array_keys($data2)));
        foreach ($method_ids as $method_id) {
            $fields = [];
            foreach ($all_fields as $field) {
                $fields[$field][1] = !empty($data1[$method_id]) ? $data1[$method_id]->getValue($field) : 0;
                $fields[$field][2] = !empty($data2[$method_id]) ? $data2[$method_id]->getValue($field) : 0;
            }

            if (!$fields[$this->calls_count_field][1] && !$fields[$this->calls_count_field][2]) {
                return false;
            }

            $calls_per_parent1 = $fields[$this->calls_count_field][1] / $method_calls_count1;
            $calls_per_parent2 = $fields[$this->calls_count_field][2] / $method_calls_count2;

            if ($calls_per_parent1) {
                $relative_calls_diff = abs($calls_per_parent1 - $calls_per_parent2) / $calls_per_parent1;
                if ($relative_calls_diff < self::MIN_CTS_DIFF) {
                    // Skip methods with similar calls count
                    continue;
                }
            }

            $local_delta = $fields[$param][2] - $fields[$param][1];
            if (!$local_delta) {
                // Skip not changed methods
                continue;
            }

            $delta += $local_delta;

            $res[$method_id] = [
                'fields' => $fields,
                'name' => substr(
                    $method_names[$method_id] ?? '?',
                    -self::MAX_NAME_LENGTH,
                    self::MAX_NAME_LENGTH
                ),
                'name_alt' => $method_names[$method_id] ?? '?',
                'method_id' => $method_id,
            ];
        }

        if (!$delta) {
            return false;
        }

        uasort(
            $res,
            function ($el1, $el2) use ($param) : int {
                $diff1 = $el1['fields'][$param][2] - $el1['fields'][$param][1];
                $diff2 = $el2['fields'][$param][2] - $el2['fields'][$param][1];
                return $diff2 > $diff1 ? 1 : -1;
            }
        );

        return [
            'delta' => $delta,
            'name' => substr(
                $method_names[$parent_id] ?? '?',
                -self::MAX_NAME_LENGTH,
                self::MAX_NAME_LENGTH
            ),
            'name_alt' => $method_names[$parent_id] ?? '?',
            'method_id' => $parent_id,
            'ct1' => $method_calls_count1,
            'ct2' => $method_calls_count2,
            'info' => $res
        ];
    }
}
