<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\Entity\MethodData;

class MethodDataTest extends \unit\Badoo\BaseTestCase
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
            'create table aggregator_method_data (snapshot_id integer, method_id integer, wt integer)'
        );
        $this->AggregatorStorage->insert(
            'aggregator_method_data',
            ['snapshot_id' => 1, 'method_id' => 1, 'wt' => 1001]
        );
        $this->AggregatorStorage->insert(
            'aggregator_method_data',
            ['snapshot_id' => 2, 'method_id' => 3, 'wt' => 1002]
        );

        $this->FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
    }

    public function testGetDataBySnapshotId()
    {
        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodData->getDataBySnapshotId(0);

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetDataBySnapshotIdResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 1]]);

        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($StorageMock, $this->FieldList);
        $result = $MethodData->getDataBySnapshotId(0);

        self::assertArrayHasKey(0, $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodData::class, $result[0]);
    }

    public function testGetDataByMethodIdsAndSnapshotIdsEmpty()
    {
        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodData->getDataByMethodIdsAndSnapshotIds([], []);

        self::assertEquals([], $result);
    }

    public function testGetDataByMethodIdsAndSnapshotIds()
    {
        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodData->getDataByMethodIdsAndSnapshotIds([1], [1]);

        $expected = [
            new MethodData(
                [
                    'snapshot_id' => 1,
                    'method_id' => 1,
                    'wt' => 1001
                ],
                ['wt' => 'wt']
            )
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetDataByMethodIdsAndSnapshotIdsResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 1]]);

        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($StorageMock, $this->FieldList);
        $result = $MethodData->getDataByMethodIdsAndSnapshotIds([1], [2]);

        self::assertArrayHasKey(0, $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodData::class, $result[0]);
    }

    public function testGetOneParamDataBySnapshotIdsEmpty()
    {
        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodData->getOneParamDataBySnapshotIds([], 'wt');

        self::assertEquals([], $result);
    }

    public function testGetOneParamDataBySnapshotIds()
    {
        $MethodData = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodData->getOneParamDataBySnapshotIds([1], 'wt');

        $expected = [
            [
                'snapshot_id' => 1,
                'method_id' => 1,
                'wt' => 1001
            ]
        ];
        self::assertEquals($expected, $result);
    }

    public function testInsertManyEmpty()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->insertMany([]);

        self::assertFalse($result);
    }

    public function testInsertMany()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->insertMany([
            ['method_id' => 2, 'wt' => 3]
        ]);

        self::assertTrue($result);
    }

    public function testDeleteBySnapshotId()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodData($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->deleteBySnapshotId(1);

        self::assertTrue($result);
    }
}
