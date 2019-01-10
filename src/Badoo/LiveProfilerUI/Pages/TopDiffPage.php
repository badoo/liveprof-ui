<?php declare(strict_types=1);

/**
 * A page with a list of the most changed methods in two days.
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;
use Badoo\LiveProfilerUI\Entity\TopDiff;

class TopDiffPage extends BasePage
{
    const DIFF_THRESHOLD = 10000; // Do not get methods data with < 10ms to reduce load

    /** @var string */
    protected static $template_path = 'top_diff';
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

    protected function cleanData() : bool
    {
        $this->data['date1'] = isset($this->data['date1']) ? trim($this->data['date1']) : '';
        $this->data['date2'] = isset($this->data['date2']) ? trim($this->data['date2']) : '';
        $this->data['param'] = isset($this->data['param']) ? trim($this->data['param']) : '';

        return true;
    }

    public function getTemplateData() : array
    {
        $fields = array_diff($this->FieldList->getFields(), [$this->calls_count_field]);// exclude calls count param (ct)

        if (!$this->data['param']) {
            $this->data['param'] = current($fields);
        }

        if (!isset($this->data['exclude'])) {
            $this->data['exclude'] = true;
        }

        list($snapshots1, $snapshots2) = $this->getSnapshotsByTwoDates(
            $this->data['date1'],
            $this->data['date2'],
            $this->data['param']
        );

        $diff = $this->getDiff($snapshots1, $snapshots2, $this->data['param'], $this->data['exclude']);

        return [
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
            'param' => $this->data['param'],
            'exclude' => $this->data['exclude'],
            'data' => $diff,
            'params' => $fields
        ];
    }

    protected function getSnapshotsByTwoDates(string $date1, string $date2, string $param) : array
    {
        $snapshots_data = $this->Snapshot->getSnapshotsByDates([$date1, $date2], $param);
        $snapshots = [];
        foreach ($snapshots_data as $item) {
            $key = $item['app'] . '|' . $item['label'];

            if ($item['date'] === $date1) {
                $index = 'id1';
            } else {
                $index = 'id2';
            }

            if (!isset($snapshots[$key])) {
                $snapshots[$key] = [
                    $index => $item['id'],
                    'app' => $item['app'],
                    'label' => $item['label'],
                    $param => $item[$param],
                    'diff' => PHP_INT_MIN,
                ];
            } else {
                $snapshots[$key][$index] = $item['id'];
                $snapshots[$key]['diff'] = abs($item[$param] - $snapshots[$key][$param]);
            }
        }

        usort($snapshots, function ($snapshot1, $snapshot2) : int {
            return $snapshot2['diff'] > $snapshot1['diff'] ? 1 : -1;
        });

        // keep only 50 most diff snapshots on each date
        $snapshots = \array_slice($snapshots, 0, 50);

        // get snapshot data for first and second date
        $snapshotById1 = [];
        $snapshotById2 = [];
        foreach ($snapshots as $snapshot) {
            if (isset($snapshot['id1'])) {
                $snapshotById1[$snapshot['id1']] = [
                    'app' => $snapshot['app'],
                    'label' => $snapshot['label'],
                ];
            }

            if (isset($snapshot['id2'])) {
                $snapshotById2[$snapshot['id2']] = [
                    'app' => $snapshot['app'],
                    'label' => $snapshot['label'],
                ];
            }
        }

        return [$snapshotById1, $snapshotById2];
    }

    protected function getDiff(array $snapshotById1, array $snapshotById2, string $param, bool $exclude) : array
    {
        $snapshot_ids = array_merge(array_keys($snapshotById1), array_keys($snapshotById2));

        $children_data = [];
        if ($exclude) {
            $children_data = $this->MethodTree->getSnapshotParentsData($snapshot_ids, [$param], self::DIFF_THRESHOLD);
        }

        $method_data = $this->MethodData->getOneParamDataBySnapshotIds($snapshot_ids, $param, self::DIFF_THRESHOLD);

        $diff_data = [];
        $diff = [];
        foreach ($method_data as $item) {
            if (!empty($snapshotById1[$item['snapshot_id']])) {
                $snapshot = $snapshotById1[$item['snapshot_id']];
                $index = 'from_value';
            } else {
                $snapshot = $snapshotById2[$item['snapshot_id']];
                $index = 'to_value';
            }

            $children_value = 0;
            if (isset($children_data[$item['snapshot_id']][$item['method_id']])) {
                $children_value = (int)$children_data[$item['snapshot_id']][$item['method_id']][$param];
            }

            // Skip methods with self usage < sum of children usage
            if ((int)$item[$param] < $children_value) {
                continue;
            }

            $key = "{$snapshot['app']}|{$snapshot['label']}|{$item['method_id']}";

            if (!isset($diff_data[$key]) || isset($diff_data[$key][$index])) {
                $diff_data[$key] = [
                    'app' => $snapshot['app'],
                    'label' => $snapshot['label'],
                    'method_id' => $item['method_id'],
                    $index => (int)$item[$param] - $children_value,
                ];
            } else {
                $diff_data[$key][$index] = (int)$item[$param] - $children_value;

                $diff_value = $diff_data[$key]['to_value'] - $diff_data[$key]['from_value'];
                $diff_percent = $diff_data[$key]['from_value']
                    ? intdiv($diff_value * 100, $diff_data[$key]['from_value'])
                    : 0;

                $diff[] = new TopDiff(
                    [
                        'app' => $snapshot['app'],
                        'label' => $snapshot['label'],
                        'method_id' => $item['method_id'],
                        'value' => $diff_value,
                        'percent' => $diff_percent
                    ]
                );
            }
        }

        usort(
            $diff,
            function ($Element1, $Element2) : int {
                /** @var \Badoo\LiveProfilerUI\Entity\TopDiff $Element1 */
                /** @var \Badoo\LiveProfilerUI\Entity\TopDiff $Element2 */
                return $Element2->getValue() > $Element1->getValue() ? 1 : -1;
            }
        );

        // keep only 1000 most diff methods
        $diff = \array_slice($diff, 0, 5000);

        return $this->Method->injectMethodNames($diff);
    }
}
