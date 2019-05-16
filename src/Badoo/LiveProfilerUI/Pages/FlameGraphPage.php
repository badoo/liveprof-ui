<?php declare(strict_types=1);

/**
 * A page with a list of all methods of the snapshot
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\Entity\Snapshot;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\FlameGraph;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class FlameGraphPage extends BasePage
{
    const MAX_METHODS_IN_FLAME_GRAPH = 3000;
    const DEFAULT_THRESHOLD = 300;

    /** @var string */
    protected static $template_path = 'flame_graph';
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

    public function cleanData() : bool
    {
        $this->data['app'] = $this->data['app'] ?? '';
        $this->data['label'] = $this->data['label'] ?? '';
        $this->data['snapshot_id'] = (int)($this->data['snapshot_id'] ?? 0);
        $this->data['diff'] = (bool)($this->data['diff'] ?? false);
        $this->data['date'] = $this->data['date'] ?? '';
        $this->data['date1'] = $this->data['date1'] ?? '';
        $this->data['date2'] = $this->data['date2'] ?? '';

        if (empty($this->data['param'])) {
            $this->data['param'] = current($this->FieldList->getFields());
        }

        return true;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTemplateData() : array
    {
        $Snapshot = $this->getSnapshot();

        $dates =$this->initDates($Snapshot);
        $dates = $this->Snapshot->getSnapshotIdsByDates($dates, $Snapshot->getApp(), $Snapshot->getLabel());
        if (isset($dates[$this->data['date']]) && $Snapshot->getDate() !== $this->data['date']) {
            $Snapshot = new Snapshot([
                'id' => $dates[$this->data['date']]['id'],
                'app' => $Snapshot->getApp(),
                'label' => $Snapshot->getLabel(),
                'date' => $this->data['date'],
            ], []);
        }

        $snapshot_id1 = $Snapshot->getId();
        $snapshot_id2 = 0;
        if ($this->data['diff']) {
            list($snapshot_id1, $snapshot_id2) = $this->getSnapshotIdsByDates(
                $Snapshot->getApp(),
                $Snapshot->getLabel(),
                $this->data['date1'],
                $this->data['date2']
            );
        }

        $graph_data = $this->getDataForFlameGraph(
            $snapshot_id1,
            $snapshot_id2,
            $this->data['param'],
            $this->data['diff']
        );
        $graph = FlameGraph::getSVG($graph_data);

        krsort($dates);
        $view_data = [
            'snapshot_id' => $Snapshot->getId(),
            'snapshot_app' => $Snapshot->getApp(),
            'snapshot_label' => $Snapshot->getLabel(),
            'snapshot_date' => $Snapshot->getDate(),
            'param' => $this->data['param'],
            'params' => array_diff($this->FieldList->getFields(), [$this->calls_count_field]),
            'diff' => $this->data['diff'],
            'dates' => array_keys($dates),
            'date' => $this->data['date'],
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
            'svg' => $graph,
        ];

        if (!$graph) {
            $view_data['error'] = 'Not enough data to show graph';
        }

        return $view_data;
    }

    protected function getSnapshot() : Snapshot
    {
        if ($this->data['snapshot_id']) {
            $Snapshot = $this->Snapshot->getOneById($this->data['snapshot_id']);
        } elseif ($this->data['app'] && $this->data['label']) {
            $Snapshot = $this->Snapshot->getOneByAppAndLabel($this->data['app'], $this->data['label']);
        } else {
            throw new \InvalidArgumentException('Empty snapshot_id, app and label');
        }

        return $Snapshot;
    }

    /**
     * Get input data for flamegraph.pl
     * @param int $snapshot_id1
     * @param int $snapshot_id2
     * @param string $param
     * @param bool $diff
     * @return string
     */
    protected function getDataForFlameGraph(
        int $snapshot_id1,
        int $snapshot_id2,
        string $param,
        bool $diff
    ) : string {
        $tree1 = $this->MethodTree->getSnapshotMethodsTree($snapshot_id1);
        if (empty($tree1)) {
            return '';
        }

        if ($diff) {
            $tree2 = $this->MethodTree->getSnapshotMethodsTree($snapshot_id2);
            if (empty($tree2)) {
                return '';
            }

            foreach ($tree2 as $key => $item) {
                $old_value = 0;
                if (isset($tree1[$key])) {
                    $old_value = $tree1[$key]->getValue($param);
                }
                $new_value = $item->getValue($param);
                $item->setValue($param, $new_value - $old_value);
            }
            $tree1 = $tree2;
        }

        $root_method_data = $this->getRootMethodData($tree1, $param, $snapshot_id1, $snapshot_id2);
        if (!$root_method_data) {
            return '';
        }

        $threshold = self::calculateParamThreshold($tree1, $param);
        $tree1 = array_filter(
            $tree1,
            function (\Badoo\LiveProfilerUI\Entity\MethodTree $Elem) use ($param, $threshold) : bool {
                return $Elem->getValue($param) > $threshold;
            }
        );

        $tree1 = $this->Method->injectMethodNames($tree1);
        $parents_param = $this->getAllMethodParentsParam($tree1, $param);

        if (empty($parents_param)) {
            return '';
        }

        $root_method = [
            'method_id' => $root_method_data->getMethodId(),
            'name' => 'main()',
            $param => $root_method_data->getValue($param)
        ];
        $texts = FlameGraph::buildFlameGraphInput($tree1, $parents_param, $root_method, $param, $threshold);

        return $texts;
    }

    /**
     * Returns a list of parents with the required param value for every method
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $methods_tree
     * @param string $param
     * @return array
     */
    protected function getAllMethodParentsParam(array $methods_tree, string $param) : array
    {
        $all_parents = [];
        foreach ($methods_tree as $Element) {
            $all_parents[$Element->getMethodId()][$Element->getParentId()] = $Element->getValue($param);
        }
        return $all_parents;
    }

    /**
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $methods_tree
     * @return int
     */
    protected function getRootMethodId(array $methods_tree) : int
    {
        $methods = [];
        $parents = [];
        foreach ($methods_tree as $Item) {
            $methods[] = $Item->getMethodId();
            $parents[] = $Item->getParentId();
        }
        $root_method_ids = array_diff($parents, $methods);
        return $root_method_ids ? (int)min($root_method_ids) : 0;
    }

    /**
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $tree
     * @param string $param
     * @return float
     */
    protected static function calculateParamThreshold(array $tree, string $param) : float
    {
        if (\count($tree) <= self::MAX_METHODS_IN_FLAME_GRAPH) {
            return self::DEFAULT_THRESHOLD;
        }

        $values = [];
        foreach ($tree as $Elem) {
            $values[] = $Elem->getValue($param);
        }
        rsort($values);

        return max($values[self::MAX_METHODS_IN_FLAME_GRAPH], self::DEFAULT_THRESHOLD);
    }

    protected function getSnapshotIdsByDates($app, $label, $date1, $date2) : array
    {
        if (!$date1 || !$date2) {
            return [0, 0];
        }

        $snapshot_ids = $this->Snapshot->getSnapshotIdsByDates([$date1, $date2], $app, $label);
        $snapshot_id1 = (int)$snapshot_ids[$date1]['id'];
        $snapshot_id2 = (int)$snapshot_ids[$date2]['id'];

        return [$snapshot_id1, $snapshot_id2];
    }

    protected function getRootMethodData(array $tree, $param, $snapshot_id1, $snapshot_id2)
    {
        $root_method_id = $this->getRootMethodId($tree);

        $snapshot_ids = [];
        if ($snapshot_id1) {
            $snapshot_ids[] = $snapshot_id1;
        }
        if ($snapshot_id2) {
            $snapshot_ids[] = $snapshot_id2;
        }
        $methods_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds(
            $snapshot_ids,
            [$root_method_id]
        );

        if (empty($methods_data) || count($methods_data) !== count($snapshot_ids)) {
            return [];
        }

        if ($snapshot_id1 && $snapshot_id2) {
            $old_value = $methods_data[1]->getValue($param);
            $new_value = $methods_data[0]->getValue($param);

            $methods_data[0]->setValue($param, abs($new_value - $old_value));
        }

        return $methods_data[0];
    }

    /**
     * Calculates date params
     * @param Snapshot $Snapshot
     * @return array
     * @throws \Exception
     */
    public function initDates(Snapshot $Snapshot) : array
    {
        $dates = $this->Snapshot->getDatesByAppAndLabel($Snapshot->getApp(), $Snapshot->getLabel());

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

        return $dates;
    }
}
