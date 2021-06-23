<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SourceInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\Interfaces\DataPackerInterface;
use Badoo\LiveProfilerUI\Interfaces\FieldHandlerInterface;
use Psr\Log\LoggerInterface;

class Aggregator
{
    const SAVE_PORTION_COUNT = 150;

    /** @var SourceInterface */
    protected $Source;
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodTreeInterface */
    protected $MethodTree;
    /** @var MethodDataInterface */
    protected $MethodData;
    /** @var LoggerInterface */
    protected $Logger;
    /** @var DataPackerInterface */
    protected $DataPacker;
    /** @var FieldList */
    protected $FieldList;
    /** @var FieldHandlerInterface */
    protected $FieldHandler;
    /** @var string */
    protected $calls_count_field = 'ct';
    /** @var int */
    protected $minimum_profiles_cnt = 0;
    /** @var string */
    protected $app = '';
    /** @var string */
    protected $label = '';
    /** @var string */
    protected $date = '';
    /** @var bool */
    protected $is_manual = false;
    /** @var string */
    protected $last_error = '';
    /** @var \Badoo\LiveProfilerUI\Entity\Snapshot|null */
    protected $exists_snapshot;
    /** @var int */
    protected $perf_count = 0;
    /** @var array */
    protected $call_map = [];
    /** @var array */
    protected $method_data = [];
    /** @var array */
    protected $methods = [];
    /** @var string[] */
    protected $fields = [];
    /** @var string[] */
    protected $field_variations = [];

    public function __construct(
        SourceInterface $Source,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodTreeInterface $MethodTree,
        MethodDataInterface $MethodData,
        LoggerInterface $Logger,
        DataPackerInterface $DataPacker,
        FieldList $FieldList,
        FieldHandlerInterface $FieldHandler,
        string $calls_count_field,
        int $minimum_profiles_cnt = 0
    ) {
        $this->Source = $Source;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodTree = $MethodTree;
        $this->MethodData = $MethodData;
        $this->Logger = $Logger;
        $this->DataPacker = $DataPacker;
        $this->FieldList = $FieldList;
        $this->FieldHandler = $FieldHandler;
        $this->calls_count_field = $calls_count_field;
        $this->minimum_profiles_cnt = $minimum_profiles_cnt;

        $this->fields = $this->FieldList->getFields();
        $this->field_variations = $this->FieldList->getFieldVariations();
    }

    public function setApp(string $app) : self
    {
        $this->app = $app;
        return $this;
    }

    public function setLabel(string $label) : self
    {
        $this->label = $label;
        return $this;
    }

    public function setDate(string $date) : self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @param bool $is_manual
     * @return $this
     */
    public function setIsManual(bool $is_manual) : self
    {
        $this->is_manual = $is_manual;
        return $this;
    }

    public function reset() : self
    {
        $this->method_data = [];
        $this->methods = [];
        $this->call_map = [];

        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function process() : bool
    {
        if (!$this->app || !$this->label || !$this->date) {
            $this->Logger->info('Invalid params');
            return false;
        }

        $this->Logger->info("Started aggregation ({$this->app}, {$this->label}, {$this->date})");

        try {
            $this->exists_snapshot = $this->Snapshot->getOneByAppAndLabelAndDate($this->app, $this->label, $this->date);
        } catch (\InvalidArgumentException $Ex) {
            $this->exists_snapshot = null;
        }

        if ($this->exists_snapshot && !$this->is_manual && $this->exists_snapshot->getType() !== 'manual') {
            $this->Logger->info('Snapshot already exists');
            return true;
        }

        $perf_data = $this->Source->getPerfData($this->app, $this->label, $this->date);
        if (empty($perf_data)) {
            $this->last_error = 'Failed to get snapshot data from DB';
            $this->Logger->info($this->last_error);
            return false;
        }

        $this->perf_count = \count($perf_data);
        $this->Logger->info('Processing rows: ' . $this->perf_count);

        if ($this->perf_count > DataProviders\Source::SELECT_LIMIT) {
            $this->Logger->info("Too many profiles for $this->app:$this->label:$this->date");
        }

        if ($this->perf_count <= $this->minimum_profiles_cnt
            && $this->Snapshot->getMaxCallsCntByAppAndLabel($this->app, $this->label) <= $this->minimum_profiles_cnt) {
            $this->Logger->info("Too few profiles for $this->app:$this->label:$this->date");
            return false;
        }

        foreach ($perf_data as $record) {
            $data = $this->DataPacker->unpack($record);
            if (!$this->processPerfdata($data)) {
                $this->Logger->warning('Empty perf data');
            }
        }
        unset($perf_data);

        $this->Logger->info('Aggregating');

        $this->aggregate();

        $this->Logger->info('Saving result');

        $save_result = $this->saveResult();
        if (!$save_result) {
            $this->Logger->error('Can\'t save aggregated data');
        }

        return $save_result;
    }

    /**
     * Convert profiler data to call_map, method_map and methods list
     * @param array $data
     * @return bool
     */
    protected function processPerfdata(array $data) : bool
    {
        static $default_stat = [];
        if (empty($default_stat)) {
            foreach ($this->fields as $field) {
                $default_stat[$field . 's'] = '';
            }
        }

        foreach ($data as $key => $stats) {
            list($caller, $callee) = $this->splitMethods($key);

            if ($this->isIncludeFile((string)$caller) || $this->isIncludeFile((string)$callee)) {
                continue;
            }

            if (!isset($this->call_map[$caller][$callee])) {
                if (!isset($this->method_data[$callee])) {
                    $this->method_data[$callee] = $default_stat;
                }

                $this->call_map[$caller][$callee] = $default_stat;
                $this->methods[$caller] = 1;
                $this->methods[$callee] = 1;
            }

            foreach ($this->fields as $profile_param => $aggregator_param) {
                $value = $stats[$profile_param] > 0 ? $stats[$profile_param] : 0;
                $this->call_map[$caller][$callee][$aggregator_param . 's'] .= $value . ',';
                $this->method_data[$callee][$aggregator_param . 's'] .= $value . ',';
            }
        }
        unset($this->call_map[0], $this->methods[0]);

        return !empty($this->call_map) && !empty($this->method_data);
    }

    /**
     * Calculate aggregating values(min, max, percentile)
     * @return bool
     */
    protected function aggregate() : bool
    {
        foreach ($this->method_data as &$map) {
            $map = $this->aggregateRow($map);
        }
        unset($map);

        foreach ($this->call_map as &$map) {
            foreach ($map as &$stat) {
                $stat = $this->aggregateRow($stat);
            }
            unset($stat);
        }
        unset($map);

        return true;
    }

    protected function aggregateRow(array $map) : array
    {
        foreach ($this->fields as $param) {
            $map[$param . 's'] = $map[$param . 's'] ?? '';
            $map[$param . 's'] = explode(',', rtrim($map[$param . 's'], ','));
            $map[$param] = array_sum($map[$param . 's']);
            foreach ($this->field_variations as $field_variation) {
                $map[$field_variation . '_' . $param] = $this->FieldHandler->handle(
                    $field_variation,
                    $map[$param . 's']
                );
            }
            $map[$param] /= $this->perf_count;
            if ($param !== $this->calls_count_field) {
                $map[$param] = (int)$map[$param];
            }
            unset($map[$param . 's']);
        }

        return $map;
    }

    /**
     * Save all data in database
     * @return bool
     * @throws \Exception
     */
    protected function saveResult() : bool
    {
        if (empty($this->method_data)) {
            $this->Logger->error('Empty method data');
            return false;
        }

        $delete_result = $this->deleteOldData();
        if (!$delete_result) {
            $this->Logger->error('Can\'t delete old data');
            return false;
        }

        $snapshot_id = $this->createOrUpdateSnapshot();
        if (!$snapshot_id) {
            $this->Logger->error('Can\'t create or update snapshot');
            return false;
        }

        $map = $this->getAndPopulateMethodNamesMap(array_keys($this->methods));

        $save_tree_result = $this->saveTree($snapshot_id, $map);
        if (!$save_tree_result) {
            $this->Logger->error('Can\'t save tree data');
        }

        $save_data_result = $this->saveMethodData($snapshot_id, $map);
        if (!$save_data_result) {
            $this->Logger->error('Can\'t save method data');
        }

        return $save_tree_result && $save_data_result;
    }

    /**
     * Delete method data and method tree for exists snapshot
     * @return bool
     */
    protected function deleteOldData() : bool
    {
        if (!$this->exists_snapshot) {
            return true;
        }

        $result = $this->MethodTree->deleteBySnapshotId($this->exists_snapshot->getId());
        $result = $result && $this->MethodData->deleteBySnapshotId($this->exists_snapshot->getId());

        return $result;
    }

    protected function createOrUpdateSnapshot() : int
    {
        $main = $this->method_data['main()'];
        $snapshot_data = [
            'calls_count' => $this->perf_count,
            'label' => $this->label,
            'app' => $this->app,
            'date' => $this->date,
            'type' => $this->is_manual ? 'manual' : 'auto'
        ];
        foreach ($this->fields as $field) {
            if ($field === $this->calls_count_field) {
                continue;
            }
            $snapshot_data[$field] = (float)$main[$field];
            foreach ($this->field_variations as $variation) {
                $snapshot_data[$variation . '_' . $field] = (float)$main[$variation . '_' . $field];
            }
        }

        if ($this->exists_snapshot) {
            $update_result = $this->Snapshot->updateSnapshot($this->exists_snapshot->getId(), $snapshot_data);

            return $update_result ? $this->exists_snapshot->getId() : 0;
        }

        return $this->Snapshot->createSnapshot($snapshot_data);
    }

    /**
     * Get exists methods map and create new methods
     * @param array $names
     * @return array
     */
    protected function getAndPopulateMethodNamesMap(array $names) : array
    {
        $existing_names = $this->getMethodNamesMap($names);
        $missing_names = [];
        foreach ($names as $name) {
            if (!isset($existing_names[strtolower($name)])) {
                $missing_names[] = $name;
            }
        }

        $this->setMethodsLastUsedDate($existing_names);
        $this->pushToMethodNamesMap($missing_names);

        return array_merge($existing_names, $this->getMethodNamesMap($missing_names));
    }

    /**
     * Save method tree in database
     * @param int $snapshot_id
     * @param array $map
     * @return bool
     */
    protected function saveTree(int $snapshot_id, array $map) : bool
    {
        $inserts = [];
        $result = true;
        foreach ($this->call_map as $parent_name => $children) {
            foreach ($children as $child_name => $data) {
                $insert_data = [
                    'snapshot_id' => $snapshot_id,
                    'parent_id' => (int)$map[strtolower($parent_name)]['id'],
                    'method_id' => (int)$map[strtolower($child_name)]['id'],
                ];
                foreach ($this->fields as $field) {
                    $insert_data[$field] = (float)$data[$field];
                    foreach ($this->field_variations as $variation) {
                        $insert_data[$variation . '_' . $field] = (float)$data[$variation . '_' . $field];
                    }
                }
                $inserts[] = $insert_data;
                if (\count($inserts) >= self::SAVE_PORTION_COUNT) {
                    $result = $result && $this->MethodTree->insertMany($inserts);
                    $inserts = [];
                }
            }
        }
        if (!empty($inserts)) {
            $result = $result && $this->MethodTree->insertMany($inserts);
        }

        return $result;
    }

    /**
     * Save method data in database
     * @param int $snapshot_id
     * @param array $map
     * @return bool
     */
    protected function saveMethodData(int $snapshot_id, array $map) : bool
    {
        $inserts = [];
        $result = true;
        foreach ($this->method_data as $method_name => $data) {
            $insert_data = [
                'snapshot_id' => $snapshot_id,
                'method_id' => $map[trim(strtolower($method_name))]['id'],
            ];
            foreach ($this->fields as $field) {
                $insert_data[$field] = (float)$data[$field];
                foreach ($this->field_variations as $variation) {
                    $insert_data[$variation . '_' . $field] = (float)$data[$variation . '_' . $field];
                }
            }
            $inserts[] = $insert_data;
            if (\count($inserts) >= self::SAVE_PORTION_COUNT) {
                $result = $result && $this->MethodData->insertMany($inserts);
                $inserts = [];
            }
        }
        if (!empty($inserts)) {
            $result = $result && $this->MethodData->insertMany($inserts);
        }

        return $result;
    }

    /**
     * Returns exists methods map
     * @param array $names
     * @return array
     */
    protected function getMethodNamesMap(array $names) : array
    {
        $result = [];
        while (!empty($names)) {
            $names_to_get = \array_slice($names, 0, self::SAVE_PORTION_COUNT);
            $names = \array_slice($names, self::SAVE_PORTION_COUNT);
            $methods = $this->Method->getListByNames($names_to_get);
            foreach ($methods as $row) {
                $result[strtolower(trim($row['name']))] = $row;
            }
        }
        return $result;
    }

    protected function setMethodsLastUsedDate(array $methods) : bool
    {
        $methods_to_update = array_filter(
            $methods,
            function ($elem) {
                return $elem['date'] !== $this->date;
            }
        );

        $method_ids_to_update = array_column($methods_to_update, 'id');

        $result = true;
        while (!empty($method_ids_to_update)) {
            $to_update = \array_slice($method_ids_to_update, 0, self::SAVE_PORTION_COUNT);
            $method_ids_to_update = \array_slice($method_ids_to_update, self::SAVE_PORTION_COUNT);
            $result = $result && $this->Method->setLastUsedDate($to_update, $this->date);
        }
        return $result;
    }

    /**
     * Save methods
     * @param array $names
     * @return bool
     */
    protected function pushToMethodNamesMap(array $names) : bool
    {
        // create methods
        $result = true;
        while (!empty($names)) {
            $names_to_save = [];
            foreach (\array_slice($names, 0, self::SAVE_PORTION_COUNT) as $name) {
                $names_to_save[] = [
                    'name' => $name,
                    'date' => $this->date
                ];
            }
            $names = \array_slice($names, self::SAVE_PORTION_COUNT);
            $result = $result && $this->Method->insertMany($names_to_save);
        }

        return $result;
    }

    public function getLastError() : string
    {
        return $this->last_error;
    }

    /**
     * Returns a list of snapshots to aggregate
     * @param int $last_num_days
     * @return array
     */
    public function getSnapshotsDataForProcessing(int $last_num_days) : array
    {
        if ($last_num_days < 1) {
            throw new \InvalidArgumentException('Num of days must be > 0');
        }

        // Get already aggregated snapshots
        $dates = DateGenerator::getDatesArray(
            date('Y-m-d', strtotime('-1 day')),
            $last_num_days,
            $last_num_days
        );
        $processed_snapshots = $this->Snapshot->getSnapshotsByDates($dates);
        $processed = [];
        foreach ($processed_snapshots as $snapshot) {
            if ($snapshot['type'] !== 'manual') {
                $key = "{$snapshot['app']}|{$snapshot['label']}|{$snapshot['date']}";
                $processed[$key] = true;
            }
        }

        // Get all snapshots for last 3 days
        $snapshots = $this->Source->getSnapshotsDataByDates(
            date('Y-m-d 00:00:00', strtotime('-' . $last_num_days . ' days')),
            date('Y-m-d 23:59:59', strtotime('-1 day'))
        );

        // Exclude already aggregated snapshots
        foreach ($snapshots as $snapshot_key => $snapshot) {
            $key = "{$snapshot['app']}|{$snapshot['label']}|{$snapshots[$snapshot_key]['date']}";
            if (!empty($processed[$key])) {
                unset($snapshots[$snapshot_key]);
            }
        }

        return $snapshots;
    }

    /**
     * Checks that method is an included php file
     * @param string $key
     * @return bool
     */
    protected function isIncludeFile(string $key) : bool
    {
        return (bool)preg_match('/^(eval|run_init|load)::[\w\W]+\./', $key);
    }

    /**
     * Splits a string into parent and child method names
     * @param string $key
     * @return array
     */
    protected function splitMethods(string $key) : array
    {
        if (false === strpos($key, '==>')) {
            return [0, $key];
        } 
          
        return explode('==>', $key);
    }
}
