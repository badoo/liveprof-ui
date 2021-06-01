<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\Entity\MethodTree;

class MethodTreeTest extends \unit\Badoo\BaseTestCase
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
            'create table aggregator_tree (snapshot_id integer, parent_id integer, method_id integer, wt integer)'
        );
        $this->AggregatorStorage->insert(
            'aggregator_tree',
            ['snapshot_id' => 1, 'parent_id' => 2, 'method_id' => 2, 'wt' => 3]
        );
        $this->AggregatorStorage->insert(
            'aggregator_tree',
            ['snapshot_id' => 2, 'parent_id' => 2, 'method_id' => 3, 'wt' => 4]
        );

        $this->FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
    }

    public function testGetSnapshotMethodsTree()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getSnapshotMethodsTree(1);

        $expected = [
            '2|2' => new MethodTree(
                [
                    'snapshot_id' => 1,
                    'parent_id' => 2,
                    'method_id' => 2,
                    'wt' => 3
                ],
                ['wt' => 'wt']
            )
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetSnapshotMethodsTreeResult()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getSnapshotMethodsTree(1);

        self::assertArrayHasKey('2|2', $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodTree::class, $result['2|2']);
    }

    public function testGetDataByMethodIdsAndSnapshotIdsEmpty()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByMethodIdsAndSnapshotIds([], []);

        self::assertEquals([], $result);
    }

    public function testGetDataByMethodIdsAndSnapshotIds()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByMethodIdsAndSnapshotIds([1], [2]);

        $expected = [
            new MethodTree(
                [
                    'snapshot_id' => 1,
                    'parent_id' => 2,
                    'method_id' => 2,
                    'wt' => 3
                ],
                ['wt' => 'wt']
            )
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetDataByMethodIdsAndSnapshotIdsResult()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByMethodIdsAndSnapshotIds([1], [2]);

        self::assertArrayHasKey(0, $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodData::class, $result[0]);
    }

    public function testGetDataByParentIdsAndSnapshotIdsEmpty()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByParentIdsAndSnapshotIds([], []);

        self::assertEquals([], $result);
    }

    public function testGetDataByParentIdsAndSnapshotIds()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByParentIdsAndSnapshotIds([1], [2]);

        $expected = [
            new MethodTree(
                [
                    'snapshot_id' => 1,
                    'parent_id' => 2,
                    'method_id' => 2,
                    'wt' => 3
                ],
                ['wt' => 'wt']
            )
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetDataByParentIdsAndSnapshotIdsResult()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getDataByParentIdsAndSnapshotIds([1], [2]);

        self::assertArrayHasKey(0, $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodData::class, $result[0]);
    }

    public function testGetSnapshotParentsData()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getSnapshotParentsData([1]);

        $expected = [
            1 => [
                2 => [
                    'wt' => 3
                ]
            ]
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetSnapshotParentsDataResult()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getSnapshotParentsData([1]);

        self::assertEquals([1 => [2 => ['wt' => 3]]], $result);
    }

    public function testGetSnapshotParentsDataEmpty()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->getSnapshotParentsData([], []);

        self::assertEquals([], $result);
    }

    public function testInsertManyEmpty()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->insertMany([]);

        self::assertFalse($result);
    }

    public function testInsertMany()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->insertMany([
            ['parent_id' => 1, 'method_id' => 2, 'wt' => 3]
        ]);

        self::assertTrue($result);
    }

    public function testDeleteBySnapshotId()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\MethodTree($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->deleteBySnapshotId(1);

        self::assertTrue($result);
    }
}
