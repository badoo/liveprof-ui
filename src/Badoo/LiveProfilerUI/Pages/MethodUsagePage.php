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

    public function __construct(
        View $View,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodDataInterface $MethodData,
        FieldList $FieldList
    ) {
        $this->View = $View;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodData = $MethodData;
        $this->FieldList = $FieldList;
    }

    protected function cleanData() : bool
    {
        $this->data['method'] = isset($this->data['method']) ? trim($this->data['method']) : '';
        $this->data['period'] = isset($this->data['period']) ? (int)$this->data['period'] : 7;

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

        if ($this->data['period'] <= 0) {
            $error = 'Invalid period';
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
            $method_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds(
                [],
                array_keys($methods),
                100
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
                $results[] = $result;
            }
        }

        return [
            'methods' => $methods,
            'method' => $this->data['method'],
            'period' => $this->data['period'],
            'results' => $results,
            'field_descriptions' => $field_descriptions,
            'error' => $error
        ];
    }
}
