<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

use Badoo\LiveProfilerUI\Entity\Job;

class AjaxPagesTest extends \unit\Badoo\BaseTestCase
{
    public function providerRebuildSnapshot() : array
    {
        return [
            'exists_job' => [
                'exists_job' => new Job(['id' => 1]),
                'add_result' => true,
                'expected' => [
                    'status' => false,
                    'message' => 'Job for snapshot (app, label, date) is already exists'
                ],
            ],
            'successfully_added' => [
                'exists_job' => null,
                'add_result' => true,
                'expected' => [
                    'status' => true,
                    'message' => 'Added a job for aggregating a snapshot (app, label, date)'
                ],
            ],
            'add_error' => [
                'exists_job' => null,
                'add_result' => false,
                'expected' => [
                    'status' => false,
                    'message' => 'Error in the snapshot (app, label, date) aggregating'
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerRebuildSnapshot
     * @param Job|null $ExistsJob
     * @param bool $add_result
     * @param array $expected
     * @throws \ReflectionException
     */
    public function testRebuildSnapshot($ExistsJob, $add_result, $expected)
    {
        $JobMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Job::class)
            ->disableOriginalConstructor()
            ->setMethods(['getJob', 'add'])
            ->getMock();
        $JobMock->method('getJob')->willReturnCallback(function () use ($ExistsJob) {
            if ($ExistsJob) {
                return $ExistsJob;
            }
            throw new \InvalidArgumentException('Can\'t get job');
        });
        $JobMock->method('add')->willReturn($add_result);

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Job', $JobMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', true);

        $result = $PagesMock->rebuildSnapshot('app', 'label', 'date');

        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRebuildSnapshotWithoutJobsSuccess()
    {
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['process'])
            ->getMock();
        $AggregatorMock->method('process')->willReturn(true);

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Aggregator', $AggregatorMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', false);

        $result = $PagesMock->rebuildSnapshot('app', 'label', 'date');

        $expected = [
            'status' => true,
            'message' => 'Job for the snapshot (app, label, date) is finished'
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRebuildSnapshotWithoutJobsFailure()
    {
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['process', 'getLastError'])
            ->getMock();
        $AggregatorMock->method('process')->willReturn(false);
        $AggregatorMock->method('getLastError')->willReturn('Error msg');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Aggregator', $AggregatorMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', false);

        $result = $PagesMock->rebuildSnapshot('app', 'label', 'date');

        $expected = [
            'status' => false,
            'message' => 'Error in the snapshot (app, label, date) aggregating: Error msg'
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testRebuildSnapshotWithoutJobsException()
    {
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['process'])
            ->getMock();
        $AggregatorMock->method('process')->willReturnCallback(function () {
            throw new \Doctrine\DBAL\DBALException('DB error');
        });

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Aggregator', $AggregatorMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', false);

        $result = $PagesMock->rebuildSnapshot('app', 'label', 'date');

        $expected = [
            'status' => false,
            'message' => 'Error in the snapshot (app, label, date) aggregating: DB error'
        ];
        static::assertEquals($expected, $result);
    }

    public function providerCheckSnapshot() : array
    {
        return [
            'empty_job' => [
                'exists_job' => null,
                'expected' => [
                    'message' => 'Job for the snapshot (app, label, date) is finished',
                    'is_new' => false,
                    'is_processing' => false,
                    'is_error' => false,
                    'is_finished' => true
                ],
            ],
            'new_job' => [
                'exists_job' => new Job(['status' => 'new']),
                'expected' => [
                    'message' => 'Added a job for aggregating snapshot (app, label, date)',
                    'is_new' => true,
                    'is_processing' => false,
                    'is_error' => false,
                    'is_finished' => false
                ],
            ],
            'processing_job' => [
                'exists_job' => new Job(['status' => 'processing']),
                'expected' => [
                    'message' => 'Job for the snapshot (app, label, date) is processing now',
                    'is_new' => false,
                    'is_processing' => true,
                    'is_error' => false,
                    'is_finished' => false
                ],
            ],
            'error_job' => [
                'exists_job' => new Job(['status' => 'error']),
                'expected' => [
                    'message' => 'Job for the snapshot (app, label, date) is finished with error',
                    'is_new' => false,
                    'is_processing' => false,
                    'is_error' => true,
                    'is_finished' => false
                ],
            ],
            'finished_job' => [
                'exists_job' => new Job(['status' => 'finished']),
                'expected' => [
                    'message' => 'Job for the snapshot (app, label, date) is finished',
                    'is_new' => false,
                    'is_processing' => false,
                    'is_error' => false,
                    'is_finished' => true
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerCheckSnapshot
     * @param Job|null $ExistsJob
     * @param array $expected
     * @throws \ReflectionException
     */
    public function testCheckSnapshot($ExistsJob, $expected)
    {
        $JobMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Job::class)
            ->disableOriginalConstructor()
            ->setMethods(['getJob'])
            ->getMock();
        $JobMock->method('getJob')->willReturnCallback(function () use ($ExistsJob) {
            if ($ExistsJob) {
                return $ExistsJob;
            }
            throw new \InvalidArgumentException('Can\'t get job');
        });

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Job', $JobMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', true);

        $result = $PagesMock->checkSnapshot('app', 'label', 'date');

        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCheckSnapshotWithoutJobs()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\Entity\Snapshot(['id' => 1], []);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneByAppAndLabelAndDate'])
            ->getMock();
        $SnapshotMock->method('getOneByAppAndLabelAndDate')->willReturn($Snapshot);

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', false);

        $result = $PagesMock->checkSnapshot('app', 'label', 'date');

        $expected = [
            'message' => 'Job for the snapshot (app, label, date) is finished',
            'is_new' => false,
            'is_processing' => false,
            'is_error' => false,
            'is_finished' => true
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCheckSnapshotWithoutJobsProcessing()
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneByAppAndLabelAndDate'])
            ->getMock();
        $SnapshotMock->method('getOneByAppAndLabelAndDate')->willReturnCallback(function () {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        });

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PagesMock, 'use_jobs', false);

        $result = $PagesMock->checkSnapshot('app', 'label', 'date');

        $expected = [
            'message' => 'Job for the snapshot (app, label, date) is processing now',
            'is_new' => false,
            'is_processing' => true,
            'is_error' => false,
            'is_finished' => false
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSearchMethods()
    {
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByName'])
            ->getMock();
        $MethodMock->method('findByName')->willReturn(['result']);

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Method', $MethodMock);

        $result = $PagesMock->searchMethods('term');

        static::assertEquals(['result'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSearchMethodsError()
    {
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByName'])
            ->getMock();
        $MethodMock->method('findByName')->willReturnCallback(function () {
            throw new \Doctrine\DBAL\DBALException('DB error');
        });

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $PagesMock */
        $PagesMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\AjaxPages::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PagesMock, 'Method', $MethodMock);

        $result = $PagesMock->searchMethods('term');

        static::assertEquals([], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstruct()
    {
        /** @var \Badoo\LiveProfilerUI\DataProviders\Method $MethodMock */
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\DataProviders\Job $JobMock */
        $JobMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Job::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $use_jobs = true;

        $Page = new \Badoo\LiveProfilerUI\Pages\AjaxPages(
            $SnapshotMock,
            $MethodMock,
            $JobMock,
            $AggregatorMock,
            $use_jobs
        );

        $Snapshot = $this->getProtectedProperty($Page, 'Snapshot');
        $Method = $this->getProtectedProperty($Page, 'Method');
        $Job = $this->getProtectedProperty($Page, 'Job');
        $Aggregator = $this->getProtectedProperty($Page, 'Aggregator');
        $use_jobs_new = $this->getProtectedProperty($Page, 'use_jobs');

        self::assertSame($JobMock, $Job);
        self::assertSame($SnapshotMock, $Snapshot);
        self::assertSame($MethodMock, $Method);
        self::assertSame($AggregatorMock, $Aggregator);
        self::assertSame($use_jobs_new, $use_jobs);
    }
}
