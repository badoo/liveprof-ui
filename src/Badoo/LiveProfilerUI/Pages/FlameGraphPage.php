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
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class FlameGraphPage extends BasePage
{
    const MAX_METHODS_IN_FLAME_GRAPH = 3000;
    const DEFAULT_THRESHOLD = 100;

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
        $this->data['app'] = isset($this->data['app']) ? trim($this->data['app']) : '';
        $this->data['label'] = isset($this->data['label']) ? trim($this->data['label']) : '';
        $this->data['snapshot_id'] = isset($this->data['snapshot_id']) ? (int)$this->data['snapshot_id'] : 0;

        if (!$this->data['snapshot_id'] && (!$this->data['app'] || !$this->data['label'])) {
            throw new \InvalidArgumentException('Empty snapshot_id, app and label');
        }

        $this->data['param'] = isset($this->data['param']) ? trim($this->data['param']) : '';

        return true;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getTemplateData() : array
    {
        $Snapshot = false;
        if ($this->data['snapshot_id']) {
            $Snapshot = $this->Snapshot->getOneById($this->data['snapshot_id']);
        } elseif ($this->data['app'] && $this->data['label']) {
            $Snapshot = $this->Snapshot->getOneByAppAndLabel($this->data['app'], $this->data['label']);
        }

        if (empty($Snapshot)) {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        }

        $fields = $this->FieldList->getFields();
        $fields = array_diff($fields, [$this->calls_count_field]);

        if (!$this->data['param']) {
            $this->data['param'] = current($fields);
        }

        $graph = $this->getSVG($Snapshot->getId(), $this->data['param']);
        $view_data = [
            'snapshot' => $Snapshot,
            'params' => [],
        ];
        if ($graph) {
            $view_data['svg'] = $graph;
        } else {
            $view_data['error'] = 'Not enough data to show graph';
        }

        foreach ($fields as $field) {
            $view_data['params'][] = [
                'value' => $field,
                'label' => $field,
                'selected' => $field === $this->data['param']
            ];
        }

        return $view_data;
    }

    /**
     * Get svg data for flame graph
     * @param int $snapshot_id
     * @param string $param
     * @return string
     */
    protected function getSVG(int $snapshot_id, string $param) : string
    {
        if (!$snapshot_id) {
            return '';
        }

        $graph_data = $this->getDataForFlameGraph($snapshot_id, $param);
        if (!$graph_data) {
            return '';
        }

        $tmp_file = tempnam(__DIR__, 'flamefile');
        file_put_contents($tmp_file, $graph_data);
        exec('perl ' . __DIR__ . '/../../../../scripts/flamegraph.pl ' . $tmp_file, $output);
        unlink($tmp_file);

        return implode("\n", $output);
    }

    /**
     * Get input data for flamegraph.pl
     * @param int $snapshot_id
     * @param string $param
     * @return string
     */
    protected function getDataForFlameGraph(int $snapshot_id, string $param) : string
    {
        $tree = $this->MethodTree->getSnapshotMethodsTree($snapshot_id);
        if (!$tree) {
            return '';
        }

        $root_method_id = $this->getRootMethodId($tree);
        $root_method_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds([$snapshot_id], [$root_method_id]);

        if (!$root_method_data) {
            return '';
        }

        $threshold = self::calculateParamThreshold($tree, $param);
        $tree = array_filter(
            $tree,
            function (\Badoo\LiveProfilerUI\Entity\MethodTree $Elem) use ($param, $threshold) : bool {
                return $Elem->getValue($param) > $threshold;
            }
        );

        $tree = $this->Method->injectMethodNames($tree);

        $parents_param = $this->getAllMethodParentsParam($tree, $param);
        $root_method = [
            'method_id' => $root_method_data[0]->getMethodId(),
            'name' => 'main()',
            $param => $root_method_data[0]->getValue($param)
        ];
        $texts = $this->buildFlameGraphInput($tree, $parents_param, $root_method, $param, $threshold);

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
        return $root_method_ids ? (int)current($root_method_ids) : 0;
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

        return $values[self::MAX_METHODS_IN_FLAME_GRAPH];
    }

    /**
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $elements
     * @param array $parents_param
     * @param array $parent
     * @param string $param
     * @param float $threshold
     * @return string
     */
    protected function buildFlameGraphInput(
        array $elements,
        array $parents_param,
        array $parent,
        string $param,
        float $threshold
    ) : string {
        if (!$elements || !$parent) {
            return '';
        }

        $texts = '';
        foreach ($elements as $Element) {
            if ($Element->getParentId() === $parent['method_id']) {
                $element_value = $Element->getValue($param);
                $value = $parent[$param] - $element_value;

                if ($value <= 0) {
                    if (!empty($parents_param[$Element->getParentId()])) {
                        $p = $parents_param[$Element->getParentId()];
                        $element_value = ($parent[$param] / array_sum($p)) * $Element->getValue($param);
                        $value = $parent[$param] - $element_value;
                    } else {
                        $value = 0;
                    }
                }

                if ($value <= 0 || $element_value < $threshold) {
                    continue;
                }

                $new_parent = [
                    'method_id' => $Element->getMethodId(),
                    'name' => $parent['name'] . ';' . $Element->getMethodNameAlt(),
                    $param => $element_value
                ];
                $texts .= $this->buildFlameGraphInput($elements, $parents_param, $new_parent, $param, $threshold);
                $parent[$param] = $value;
            }
        }

        $texts .= $parent['name'] . ' ' . $parent[$param] . "\n";

        return $texts;
    }
}
