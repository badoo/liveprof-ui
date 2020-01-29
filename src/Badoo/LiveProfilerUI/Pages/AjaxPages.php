<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\Aggregator;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SourceInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\JobInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;

class AjaxPages
{
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodDataInterface */
    protected $MethodData;
    /** @var JobInterface */
    protected $Job;
    /** @var Aggregator */
    protected $Aggregator;
    /** @var SourceInterface */
    protected $Source;
    /** @var FieldList */
    protected $FieldList;
    /** @var bool */
    protected $use_jobs;

    public function __construct(
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodDataInterface $MethodData,
        JobInterface $Job,
        Aggregator $Aggregator,
        SourceInterface $Source,
        FieldList $FieldList,
        bool $use_jobs = false
    ) {
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodData = $MethodData;
        $this->Job = $Job;
        $this->Aggregator = $Aggregator;
        $this->Source = $Source;
        $this->FieldList = $FieldList;
        $this->use_jobs = $use_jobs;
    }

    public function rebuildSnapshot(string $app, string $label, string $date) : array
    {
        $status = false;
        if ($this->use_jobs) {
            try {
                $this->Job->getJob(
                    $app,
                    $label,
                    $date,
                    [JobInterface::STATUS_NEW, JobInterface::STATUS_PROCESSING]
                );
                $message = "Job for snapshot ($app, $label, $date) is already exists";
            } catch (\InvalidArgumentException $Ex) {
                if ($this->Job->add($app, $label, $date, 'manual')) {
                    $message = "Added a job for aggregating a snapshot ($app, $label, $date)";
                    $status = true;
                } else {
                    $message = "Error in the snapshot ($app, $label, $date) aggregating";
                }
            }
        } else {
            try {
                $result = $this->Aggregator->setApp($app)
                    ->setLabel($label)
                    ->setDate($date)
                    ->setIsManual(true)
                    ->process();
                if (!empty($result)) {
                    $status = true;
                    $message = "Job for the snapshot ($app, $label, $date) is finished";
                } else {
                    $last_error = $this->Aggregator->getLastError();
                    $message = "Error in the snapshot ($app, $label, $date) aggregating: " . $last_error;
                }
            } catch (\Throwable $Ex) {
                $message = "Error in the snapshot ($app, $label, $date) aggregating: " . $Ex->getMessage();
            }
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    public function checkSnapshot(string $app, string $label, string $date) : array
    {
        if (!$this->use_jobs) {
            try {
                $this->Snapshot->getOneByAppAndLabelAndDate($app, $label, $date);
                $is_processing = false;
                $message = "Job for the snapshot ($app, $label, $date) is finished";
            } catch (\InvalidArgumentException $Ex) {
                $is_processing = true;
                $message = "Job for the snapshot ($app, $label, $date) is processing now";
            }

            return [
                'is_new' => false,
                'is_processing' => $is_processing,
                'is_error' => false,
                'is_finished' => !$is_processing,
                'message' => $message
            ];
        }

        $is_new = $is_processing = $is_error = $is_finished = false;

        try {
            $ExistsJob = $this->Job->getJob(
                $app,
                $label,
                $date,
                [
                    JobInterface::STATUS_NEW,
                    JobInterface::STATUS_PROCESSING,
                    JobInterface::STATUS_FINISHED,
                    JobInterface::STATUS_ERROR
                ]
            );
            if ($ExistsJob->getStatus() === JobInterface::STATUS_NEW) {
                $is_new = true;
                $message = "Added a job for aggregating snapshot ($app, $label, $date)";
            } elseif ($ExistsJob->getStatus() === JobInterface::STATUS_PROCESSING) {
                $is_processing = true;
                $message = "Job for the snapshot ($app, $label, $date) is processing now";
            } elseif ($ExistsJob->getStatus() === JobInterface::STATUS_ERROR) {
                $is_error = true;
                $message = "Job for the snapshot ($app, $label, $date) is finished with error";
            } else {
                $is_finished = true;
                $message = "Job for the snapshot ($app, $label, $date) is finished";
            }
        } catch (\InvalidArgumentException $Ex) {
            $is_finished = true;
            $message = "Job for the snapshot ($app, $label, $date) is finished";
        }

        return [
            'is_new' => $is_new,
            'is_processing' => $is_processing,
            'is_error' => $is_error,
            'is_finished' => $is_finished,
            'message' => $message
        ];
    }

    public function searchMethods(string $term) : array
    {
        $term = ltrim($term, '\\');
        try {
            return $this->Method->findByName($term);
        } catch (\Throwable $Ex) {
            return [];
        }
    }

    public function getMethodUsedApps(string $method_name) : array
    {
        $method_name = ltrim($method_name, '\\');
        try {
            $methods = $this->Method->findByName($method_name, true);
            if (empty($methods)) {
                return [];
            }

            $method = current($methods);

            $last_two_days = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray(date('Y-m-d'), 2, 2);
            $start_snapshot_id = in_array(current($methods)['date'], $last_two_days, true)
                ? $this->Snapshot->getMinSnapshotIdByDates($last_two_days)
                : 0;

            $method_data = $this->MethodData->getDataByMethodIdsAndSnapshotIds(
                [],
                [$method['id']],
                100,
                $start_snapshot_id
            );

            $snapshot_ids = [];
            foreach ($method_data as $Row) {
                $snapshot_id = $Row->getSnapshotId();
                $snapshot_ids[$snapshot_id] = $snapshot_id;
            }
            $snapshots = $this->Snapshot->getListByIds($snapshot_ids);

            $fields = $this->FieldList->getFields();

            $results = [];
            foreach ($method_data as $Row) {
                $result = [];
                $result['app'] = $snapshots[$Row->getSnapshotId()]['app'];
                $result['label'] = $snapshots[$Row->getSnapshotId()]['label'];
                $result['date'] = $snapshots[$Row->getSnapshotId()]['date'];

                $uniq_key = $result['app'] . '_' . $result['label'];
                if (!empty($results[$uniq_key])) {
                    continue;
                }

                $values = $Row->getValues();
                foreach ($fields as $field) {
                    $result['fields'][$field] = $values[$field];
                }
                $result['fields']['calls_count'] = $snapshots[$Row->getSnapshotId()]['calls_count'];

                $results[$uniq_key] = $result;
            }
            return array_values($results);
        } catch (\Throwable $Ex) {
            return [];
        }
    }

    public function allMethods() : array
    {
        try {
            $methods = $this->Method->all();

            $result = [];
            foreach ($methods as $method) {
                $result[$method['name']] = $method['date'];
            }

            return $result;
        } catch (\Throwable $Ex) {
            return [];
        }
    }

    public function getSourceAppList() : array
    {
        try {
            return $this->Source->getAppList();
        } catch (\Exception $Ex) {
            return [];
        }
    }

    public function getSourceLabelList() : array
    {
        try {
            return $this->Source->getLabelList();
        } catch (\Exception $Ex) {
            return [];
        }
    }
}
