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
use Badoo\LiveProfilerUI\DateGenerator;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;
use Badoo\LiveProfilerUI\Entity\TopDiff;

class TopDiffPage extends BasePage
{
    const THRESHOLD = 5000; // Do not get methods data with < 5ms to reduce load

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
        $this->data['date1'] = isset($this->data['date1']) ? trim($this->data['date1']) : date('Y-m-d', strtotime('-3 months'));
        $this->data['date2'] = isset($this->data['date2']) ? trim($this->data['date2']) : date('Y-m-d', strtotime('-1 day'));
        $this->data['param'] = isset($this->data['param']) ? trim($this->data['param']) : '';
        $this->data['mode'] = isset($this->data['mode']) ? trim($this->data['mode']) : 'snapshots';

        return true;
    }

    public function getTemplateData() : array
    {
        $fields = array_diff(
            $this->FieldList->getAllFieldsWithVariations(),
            [$this->calls_count_field]
        );// exclude calls count param (ct)

        if (!$this->data['param']) {
            $this->data['param'] = current($fields);
        }

        $snapshots = $this->getSnapshotsByTwoDates($this->data['date1'], $this->data['date2'], $this->data['param']);

        if ($this->data['mode'] === 'snapshots') {
            $diff = [];
            foreach ($snapshots as $snapshot) {
                $diff[] = new TopDiff(
                    [
                        'app' => $snapshot['app'],
                        'label' => $snapshot['label'],
                        'from_value' => $snapshot['from_value'],
                        'to_value' => $snapshot['to_value'],
                        'value' => $snapshot['diff'],
                        'percent' => $snapshot['percent'],
                    ]
                );
            }
        } else {
            $second_part = array_slice($snapshots, 0, 50);

            $snapshots1 = [];
            $snapshots2 = [];
            foreach ($second_part as $key => $snapshots) {
                list($app, $label) = explode('|', $key);

                $snapshots1[$snapshots['snapshot_id1']] = compact('app', 'label');
                $snapshots2[$snapshots['snapshot_id2']] = compact('app', 'label');
            }

            $diff = $this->getDiff($snapshots1, $snapshots2, $this->data['param'], $this->data['mode']);
        }

        return [
            'date1' => $this->data['date1'],
            'date2' => $this->data['date2'],
            'param' => $this->data['param'],
            'mode' => $this->data['mode'],
            'data' => $diff,
            'params' => $fields
        ];
    }

    protected function getSnapshotsByTwoDates(string $date1, string $date2, string $param) : array
    {
        $dates = DateGenerator::getDatesByRange($date1, $date2);
        $snapshots_data = $this->Snapshot->getSnapshotsByDates($dates, $param);

        $dates_num = count($snapshots_data) > 100 ? count($dates) / 8 : 0;
        $calls_count_limit = count($snapshots_data) > 100 ? 20 : 0;

        $first = (int)current($snapshots_data)['id'];
        $last = (int)end($snapshots_data)['id'];
        $middle = $first + intdiv($last - $first, 2);

        $first_part = $second_part = [];
        foreach ($snapshots_data as $item) {
            // skip rare snapshots
            if (isset($item['calls_count']) && $item['calls_count'] < $calls_count_limit) {
                continue;
            }

            // skip empty values
            if (!$item[$param]) {
                continue;
            }

            $key = $item['app'] . '|' . $item['label'];

            if ($item['id'] <= $middle) {
                $first_part[$key]['snapshots'][$item['id']] = $item[$param];
            } else {
                $second_part[$key]['snapshots'][$item['id']] = $item[$param];
            }
        }

        foreach ($first_part as $key => $snapshots) {
            if (count($snapshots['snapshots']) < $dates_num) {
                unset($first_part[$key]);
                continue;
            }
            $first_part[$key]['avg'] = array_sum($snapshots['snapshots']) / count($snapshots['snapshots']);

            asort($snapshots['snapshots']);
            $keys = array_keys($snapshots['snapshots']);
            $idx = count($snapshots['snapshots']) * 0.5;
            $first_part[$key]['snapshot_id'] = $keys[(int)$idx];
            $first_part[$key]['value'] = $snapshots['snapshots'][$first_part[$key]['snapshot_id']];
        }

        foreach ($second_part as $key => $snapshots) {
            if (count($snapshots['snapshots']) < $dates_num) {
                unset($second_part[$key]);
                continue;
            }

            $second_part[$key]['avg'] = array_sum($snapshots['snapshots']) / count($snapshots['snapshots']);

            if (!isset($first_part[$key]) || $second_part[$key]['avg'] <= $first_part[$key]['avg']) {
                unset($second_part[$key]);
                continue;
            }

            asort($snapshots['snapshots']);
            $keys = array_keys($snapshots['snapshots']);
            $idx = count($snapshots['snapshots']) * 0.5;
            $snapshots['snapshot_id'] = $keys[(int)$idx];
            $snapshots['value'] = $snapshots['snapshots'][$snapshots['snapshot_id']];

            unset($second_part[$key]['snapshots']);

            $second_part[$key]['diff'] = $snapshots['value'] - $first_part[$key]['value'];

            if ($second_part[$key]['diff'] < 100) {
                unset($second_part[$key]);
                continue;
            }

            $second_part[$key]['percent'] = intdiv(
                (int)$second_part[$key]['diff'] * 100,
                (int)$first_part[$key]['value']
            );

            list($app, $label) = explode('|', $key);
            $second_part[$key]['app'] = $app;
            $second_part[$key]['label'] = $label;
            $second_part[$key]['snapshot_id1'] = $first_part[$key]['snapshot_id'];
            $second_part[$key]['snapshot_id2'] = $snapshots['snapshot_id'];

            $second_part[$key]['from_value'] = $first_part[$key]['value'];
            $second_part[$key]['to_value'] = $snapshots['value'];
        }
        unset($first_part);

        uasort(
            $second_part,
            function ($el1, $el2) : int {
                return $el2['diff'] > $el1['diff'] ? 1 : -1;
            }
        );

        return $second_part;
    }

    protected function getDiff(array $snapshotById1, array $snapshotById2, string $param, string $mode) : array
    {
        $snapshot_ids = array_merge(array_keys($snapshotById1), array_keys($snapshotById2));

        $children_data = [];
        if ($mode === 'methods_exclude') {
            $children_data = $this->MethodTree->getSnapshotParentsData($snapshot_ids, [$param], self::THRESHOLD);
        }

        $method_data = $this->MethodData->getOneParamDataBySnapshotIds($snapshot_ids, $param, self::THRESHOLD);

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

                // Skip if both values below threshold
                if ($diff_data[$key]['from_value'] < self::THRESHOLD && $diff_data[$key]['to_value'] < self::THRESHOLD) {
                    continue;
                }

                $diff_value = $diff_data[$key]['to_value'] - $diff_data[$key]['from_value'];
                $diff_percent = $diff_data[$key]['from_value']
                    ? intdiv($diff_value * 100, $diff_data[$key]['from_value'])
                    : 0;

                $diff[] = new TopDiff(
                    [
                        'app' => $snapshot['app'],
                        'label' => $snapshot['label'],
                        'method_id' => $item['method_id'],
                        'from_value' => $diff_data[$key]['from_value'],
                        'to_value' => $diff_data[$key]['to_value'],
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

        // return only 2000 most diff methods
        $diff = \array_slice($diff, 0, 2000);

        return $this->Method->injectMethodNames($diff);
    }
}
