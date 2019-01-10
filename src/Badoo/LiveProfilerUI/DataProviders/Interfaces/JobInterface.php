<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

use Badoo\LiveProfilerUI\Entity\Job;

interface JobInterface
{
    const TYPE_AUTO = 'auto';
    const TYPE_MANUAL = 'manual';
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FINISHED = 'finished';
    const STATUS_ERROR = 'error';

    /**
     * @param string $status
     * @param int $limit
     * @return Job[]
     */
    public function getJobs(string $status, int $limit) : array;
    public function add(string $app, string $label, string $date, string $type = 'auto') : int;
    public function getJob(string $app, string $label, string $date, array $statuses) : Job;
    public function changeStatus(int $job_id, string $status) : bool;
}
