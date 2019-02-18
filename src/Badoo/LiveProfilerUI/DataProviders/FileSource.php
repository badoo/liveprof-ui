<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\SourceInterface;
use Badoo\LiveProfilerUI\Interfaces\DataPackerInterface;

class FileSource implements SourceInterface
{
    const SELECT_LIMIT = 1440;

    /** @var string  */
    protected $path;
    /** @var DataPackerInterface */
    protected $DataPacker;

    public function __construct(string $path, DataPackerInterface $DataPacker)
    {
        $this->path = $path;
        $this->DataPacker = $DataPacker;
    }

    public function getSnapshotsDataByDates(string $datetime_from, string $datetime_to): array
    {
        $snapshots = [];
        $apps = $this->getAppList();
        foreach ($apps as $app) {
            $label_dirs = scandir($this->path . '/' . $app, SCANDIR_SORT_NONE);
            foreach ($label_dirs as $label_dir) {
                if ($label_dir === '.' || $label_dir === '..') {
                    continue;
                }
                $files = scandir($this->path . '/' . $app . '/' . $label_dir, SCANDIR_SORT_NONE);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $timestamp = (int)str_replace('.json', '', $file);
                    if ($timestamp >= strtotime($datetime_from) && $timestamp <= strtotime($datetime_to)) {
                        $label = base64_decode($label_dir);
                        $date = date('Y-m-d', $timestamp);
                        $snapshots["$app|$label|$date"] = [
                            'app' => $app,
                            'label' => $label,
                            'date' => $date,
                        ];
                    }
                }
            }
        }

        return $snapshots;
    }

    public function getPerfData(string $app, string $label, string $date): array
    {
        $perf_data = [];
        $dir = $this->path . '/' . $app . '/' . base64_encode($label);
        if (is_dir($dir)) {
            $files = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $timestamp = (int)str_replace('.json', '', $file);
                if ($timestamp >= strtotime($date . ' 00:00:00') && $timestamp <= strtotime($date . ' 23:59:59')) {
                    $perf_data[] = file_get_contents($dir . '/' . $file);
                    if (count($perf_data) >= self::SELECT_LIMIT + 1) {
                        break;
                    }
                }
            }
        }

        return $perf_data;
    }

    public function getLabelList(): array
    {
        $labels = [];
        $apps = $this->getAppList();
        foreach ($apps as $app) {
            $label_dirs = scandir($this->path . '/' . $app, SCANDIR_SORT_NONE);
            foreach ($label_dirs as $label_dir) {
                if ($label_dir !== '.' && $label_dir !== '..' && !isset($labels[$label_dir])) {
                    $label = base64_decode($label_dir);
                    $labels[$label_dir] = $label;
                }
            }
        }
        sort($labels);

        return $labels;
    }

    public function getAppList(): array
    {
        $apps = [];
        $dirs = scandir($this->path, SCANDIR_SORT_ASCENDING);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..') {
                $apps[] = $dir;
            }
        }

        return $apps;
    }
}
