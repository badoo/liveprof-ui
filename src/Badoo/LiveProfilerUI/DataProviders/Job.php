<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\JobInterface;

class Job extends Base implements JobInterface
{
    const TABLE_NAME = 'aggregator_jobs';

    /**
     * @param string $status
     * @param int $limit
     * @return \Badoo\LiveProfilerUI\Entity\Job[]
     */
    public function getJobs(string $status, int $limit = 100) : array
    {
        $records = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [['status', $status]],
                'limit' => $limit
            ]
        );

        $return = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $return[] = new \Badoo\LiveProfilerUI\Entity\Job($record);
            }
        }

        return $return;
    }

    public function getJob(
        string $app,
        string $label,
        string $date,
        array $statuses
    ) : \Badoo\LiveProfilerUI\Entity\Job {
        $record = $this->AggregatorStorage->getOne(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['app', $app],
                    ['label', $label],
                    ['date', $date],
                    ['status', $statuses]
                ],
                'order' => ['id' => 'desc']
            ]
        );

        if (empty($record)) {
            throw new \InvalidArgumentException('Can\'t get job');
        }

        return new \Badoo\LiveProfilerUI\Entity\Job($record);
    }

    public function add(string $app, string $label, string $date, string $type = 'auto') : int
    {
        $fields = [
            'app' => $app,
            'label' => $label,
            'date' => $date,
            'type' => $type
        ];

        return $this->AggregatorStorage->insert(self::TABLE_NAME, $fields);
    }

    public function changeStatus(int $job_id, string $status) : bool
    {
        return $this->AggregatorStorage->update(
            self::TABLE_NAME,
            ['status' => $status],
            ['id' => $job_id]
        );
    }
}
