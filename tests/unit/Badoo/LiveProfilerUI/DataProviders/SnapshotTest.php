<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\Entity\Snapshot;

class SnapshotTest extends \unit\Badoo\BaseTestCase
{
    protected $last_sql = '';
    /** @var \Badoo\LiveProfilerUI\DB\Storage */
    protected $AggregatorStorage;
    protected $FieldList;

    protected function setUp()
    {
        parent::setUp();

        $this->AggregatorStorage = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->setConstructorArgs(['sqlite:///:memory:'])
            ->setMethods()
            ->getMock();

        $this->AggregatorStorage->query(
            'create table aggregator_snapshots(
                      id integer,
                      calls_count integer,
                      app text,
                      label text,
                      date text,
                      type text,
                      wt integer
                  )'
        );
        $this->AggregatorStorage->insert(
            'aggregator_snapshots',
            [
                'id' => 1,
                'calls_count' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'date' => '2019-01-01',
                'type' => 'auto',
                'wt' => 2
            ]
        );
        $this->AggregatorStorage->insert(
            'aggregator_snapshots',
            [
                'id' => 2,
                'calls_count' => 1,
                'app' => 'app2',
                'label' => 'label2',
                'date' => '2019-01-02',
                'type' => 'auto',
                'wt' => 3
            ]
        );

        $this->FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
    }

    public function testGetListEmpty()
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->setConstructorArgs([$this->AggregatorStorage, $this->FieldList])
            ->setMethods(['getLastSnapshots'])
            ->getMock();
        $SnapshotMock->expects($this->once())->method('getLastSnapshots')->willReturn([]);

        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $result = $SnapshotMock->getList('app');

        self::assertEquals([], $result);
    }

    public function testGetListResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 1]]);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->setConstructorArgs([$StorageMock, $this->FieldList])
            ->setMethods(['getLastSnapshots'])
            ->getMock();
        $SnapshotMock->expects($this->once())->method('getLastSnapshots')->willReturn([['id' => 'id', 'app' => 'app']]);

        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $result = $SnapshotMock->getList('app1');

        self::assertNotEmpty($result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\Snapshot::class, $result[0]);
    }

    public function testGetList()
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->setConstructorArgs([$this->AggregatorStorage, $this->FieldList])
            ->setMethods(['getLastSnapshots'])
            ->getMock();
        $SnapshotMock->expects($this->once())->method('getLastSnapshots')->willReturn([['id' => 'id', 'app' => 'app']]);

        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $result = $SnapshotMock->getList('app1');

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetLastSnapshots()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getLastSnapshots('app1', '2019-01-01');

        $expected = [
            [
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'date' => '2019-01-01'
            ]
        ];
        self::assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't get snapshot
     */
    public function testGetOneById()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getOneById(5);

        $expected = new Snapshot(
            [
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'type' => 'auto',
                'date' => '2019-01-01',
                'wt' => '2',
            ],
            ['wt' => 'wt']
        );
        self::assertEquals($expected, $result);
    }

    public function testGetOneByIdResult()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getOneById(1);

        self::assertNotEmpty($result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\Snapshot::class, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't get snapshot
     */
    public function testGetOneByAppAndLabel()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $Snapshot->getOneByAppAndLabel('app', 'label');
    }

    public function testGetOneByAppAndLabelResult()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getOneByAppAndLabel('app1', 'label1');

        self::assertNotEmpty($result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\Snapshot::class, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't get snapshot
     */
    public function testGetOneByAppAndLabelAndDate()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getOneByAppAndLabelAndDate('app', 'label', 'date');

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetOneByAppAndLabelAndDateResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOne'])
            ->getMock();
        $StorageMock->method('getOne')->willReturn(['id' => 1]);

        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($StorageMock, $this->FieldList);
        $result = $Snapshot->getOneByAppAndLabelAndDate('app', 'label', 'date');

        self::assertNotEmpty($result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\Snapshot::class, $result);
    }

    public function testGetSnapshotsByDates()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getSnapshotsByDates(['2019-01-01', '2019-01-01'], 'wt');

        $expected = [
            [
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'type' => 'auto',
                'date' => '2019-01-01',
                'wt' => '2',
            ]
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetSnapshotsByDatesEmpty()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getSnapshotsByDates([], '', '');

        self::assertEquals([], $result);
    }

    public function testGetSnapshotIdsByDatesResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 1, 'date' => 'date', 'calls_count' => 1]]);

        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($StorageMock, $this->FieldList);
        $result = $Snapshot->getSnapshotIdsByDates(['date'], 'app', 'label');

        self::assertEquals(['date' => ['id' => 1, 'calls_count' => 1]], $result);
    }

    public function testGetSnapshotIdsByDates()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getSnapshotIdsByDates(['2019-01-01'], 'app1', 'label1');

        $expected = ['2019-01-01' => ['id' => 1, 'calls_count' => 1]];
        self::assertEquals($expected, $result);
    }

    public function testGetSnapshotIdsByDatesEmpty()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getSnapshotIdsByDates([], '', '');

        self::assertEquals([], $result);
    }

    public function testGetOldSnapshots()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getOldSnapshots(100);

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetDatesByAppAndLabel()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getDatesByAppAndLabel('app1', 'label1');

        $expected = ['2019-01-01'];
        self::assertEquals($expected, $result);
    }

    public function providerGetAppList() : array
    {
        return [
            ['', ['app1', 'app2']],
            ['label1', ['app1']],
        ];
    }

    /**
     * @dataProvider providerGetAppList
     * @param string $label
     * @param array $expected
     */
    public function testGetAppList(string $label, array $expected)
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->getAppList($label);

        self::assertEquals($expected, $result);
    }

    public function testCreateSnapshotEmpty()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->createSnapshot([]);

        self::assertEquals(0, $result);
    }

    public function testCreateSnapshot()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->createSnapshot(['label' => 'label3']);

        self::assertEquals(3, $result);
    }

    public function testUpdateSnapshotEmpty()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->updateSnapshot(0, []);

        self::assertFalse($result);
    }

    public function testUpdateSnapshot()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->updateSnapshot(1, ['label' => 'label3']);

        self::assertTrue($result);
    }

    public function testDeleteByIdEmpty()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->deleteById(0);

        self::assertFalse($result);
    }

    public function testDeleteById()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\DataProviders\Snapshot($this->AggregatorStorage, $this->FieldList);
        $result = $Snapshot->deleteById(1);

        self::assertTrue($result);
    }
}
