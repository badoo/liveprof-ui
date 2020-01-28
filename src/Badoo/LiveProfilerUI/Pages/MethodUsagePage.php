<?php declare(strict_types=1);

/**
 * A page with method usage
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\View;

class MethodUsagePage extends BasePage
{
    /** @var string */
    protected static $template_path = 'method_usage';
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodDataInterface */
    protected $MethodData;
    /** @var FieldList */
    protected $FieldList;
    /** @var bool */
    protected $use_method_usage_optimisation = false;

    public function __construct(
        View $View,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodDataInterface $MethodData,
        FieldList $FieldList,
        bool $use_method_usage_optimisation = false
    ) {
        $this->View = $View;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodData = $MethodData;
        $this->FieldList = $FieldList;
        $this->use_method_usage_optimisation = $use_method_usage_optimisation;
    }

    protected function cleanData() : bool
    {
        $this->data['method'] = isset($this->data['method']) ? trim($this->data['method']) : '';

        return true;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getTemplateData() : array
    {
        $error = '';
        if (!$this->data['method']) {
            $error = 'Enter method name';
        }

        $methods = [];
        if (!$error) {
            $this->data['method'] = ltrim($this->data['method'], '\\');
            $methods = $this->Method->findByName($this->data['method'], true);

            if (empty($methods)) {
                $error = 'Method "' . $this->data['method'] . '" not found';
            }
        }

        $fields = $this->FieldList->getFields();
        $field_descriptions = $this->FieldList->getFieldDescriptions();

        $results = [];
        if (!empty($methods)) {
            $start_snapshot_id = 0;
            if ($this->use_method_usage_optimisation) {
                $last_two_days = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray(date('Y-m-d'), 2, 2);
                $start_snapshot_id = in_array(current($methods)['date'], $last_two_days, true)
                    ? $this->Snapshot->getMinSnapshotIdByDates($last_two_days)
                    : 0;
            }

            $method_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds(
                [],
                array_keys($methods),
                200,
                $start_snapshot_id
            );

            $snapshot_ids = [];
            foreach ($method_data as $Row) {
                $snapshot_id = $Row->getSnapshotId();
                $snapshot_ids[$snapshot_id] = $snapshot_id;
            }
            $snapshots = $this->Snapshot->getListByIds($snapshot_ids);

            foreach ($method_data as $Row) {
                $result = [];
                $result['date'] = $snapshots[$Row->getSnapshotId()]['date'];
                $result['method_id'] = $Row->getMethodId();
                $result['method_name'] = $methods[$Row->getMethodId()]['name'];
                $result['app'] = $snapshots[$Row->getSnapshotId()]['app'];
                $result['label'] = $snapshots[$Row->getSnapshotId()]['label'];
                $values = $Row->getFormattedValues();
                foreach ($fields as $field) {
                    $result['fields'][$field] = $values[$field];
                }
                $result['fields']['calls_count'] = $snapshots[$Row->getSnapshotId()]['calls_count'];
                $results[] = $result;
            }
        }

        return [
            'methods' => $methods,
            'method' => $this->data['method'],
            'results' => $results,
            'field_descriptions' => $field_descriptions,
            'error' => $error
        ];
    }
}
