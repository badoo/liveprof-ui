<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\Entity\Job;

class JobTest extends \unit\Badoo\BaseTestCase
{
    protected $last_sql = '';
    /** @var \Badoo\LiveProfilerUI\DB\Storage */
    protected $AggregatorStorage;
    protected $FieldList;

    protected function setUp(): void
    {
        parent::setUp();

        $this->AggregatorStorage = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->setConstructorArgs(['sqlite:///:memory:'])
            ->setMethods()
            ->getMock();

        $this->AggregatorStorage->query(
            'create table aggregator_jobs(id integer, app text, label text, date text, type text, status text)'
        );
        $this->AggregatorStorage->insert(
            'aggregator_jobs',
            ['id' => 1, 'app' => 'app1', 'label' => 'label1', 'date' => '2019-01-01', 'status' => 'new']
        );

        $this->FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
    }

    public function testGetSnapshotsDataByDates()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Job($this->AggregatorStorage, $this->FieldList);
        $result = $Source->getJobs('new', 1);

        $expected = [
            new Job([
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'date' => '2019-01-01',
            ])
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetNotExistsJob()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t get job');
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Job($this->AggregatorStorage, $this->FieldList);
        $result = $Source->getJob('app2', 'label2', '2019-01-02', ['new']);

        $expected = new Job([
            'id' => 1,
            'app' => 'app1',
            'label' => 'label1',
            'date' => '2019-01-01',
        ]);
        self::assertEquals($expected, $result);
    }

    public function testGetJob()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Job($this->AggregatorStorage, $this->FieldList);
        $result = $Source->getJob('app1', 'label1', '2019-01-01', ['new']);

        $expected = new Job([
            'id' => 1,
            'app' => 'app1',
            'label' => 'label1',
            'date' => '2019-01-01',
        ]);
        self::assertEquals($expected, $result);
    }

    public function testAdd()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Job($this->AggregatorStorage, $this->FieldList);
        $result = $Source->add('app2', 'label2', '2019-01-02', 'auto');

        self::assertEquals(2, $result);
    }

    public function testChangeStatus()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Job($this->AggregatorStorage, $this->FieldList);
        $result = $Source->changeStatus(1, 'finished');

        self::assertTrue($result);
    }
}
