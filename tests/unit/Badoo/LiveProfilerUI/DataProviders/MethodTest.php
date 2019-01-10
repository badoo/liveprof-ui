<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

class MethodTest extends \unit\Badoo\BaseTestCase
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
            'create table aggregator_metods (id integer, name text)'
        );
        $this->AggregatorStorage->insert('aggregator_metods', ['id' => 2, 'name' => 'method']);
        $this->FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
    }

    public function testFindByNameEmpty()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->findByName('');

        self::assertEquals([], $result);
    }

    public function testFindByName()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->findByName('method');

        $expected = [2 => 'method'];
        self::assertEquals($expected, $result);
    }

    public function testFindByNameResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 'method id', 'name' => 'method name']]);

        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($StorageMock, $this->FieldList);
        $result = $Method->findByName('method');

        self::assertEquals(['method id' => 'method name'], $result);
    }

    public function testGetListByNames()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->getListByNames(['method name']);

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetListByNamesEmpty()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->getListByNames([]);

        self::assertEquals([], $result);
    }

    public function testGetListByIds()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->getListByIds([1]);

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetListByIdsEmpty()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->getListByIds([]);

        self::assertEquals([], $result);
    }

    public function testGetListByIdsResult()
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAll'])
            ->getMock();
        $StorageMock->method('getAll')->willReturn([['id' => 'method id', 'name' => 'method name']]);

        $Source = new \Badoo\LiveProfilerUI\DataProviders\Method($StorageMock, $this->FieldList);
        $result = $Source->getListByIds([1]);

        self::assertEquals(['method id' => 'method name'], $result);
    }

    public function testInsertManyEmpty()
    {
        $Method = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $Method->insertMany([]);

        self::assertFalse($result);
    }

    public function testInsertMany()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\DataProviders\Method($this->AggregatorStorage, $this->FieldList);
        $result = $MethodTree->insertMany([
            ['name' => 'method2']
        ]);

        self::assertTrue($result);
    }

    public function testInjectMethodNames()
    {
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodId'])
            ->getMock();
        $MethodDataMock->method('getMethodId')->willReturn(1);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListByIds'])
            ->getMock();
        $MethodMock->expects($this->once())->method('getListByIds')->willReturn([1 => 'method_name']);

        /** @var \Badoo\LiveProfilerUI\Entity\MethodData $MethodDataMock */
        self::assertEquals('', $MethodDataMock->getMethodName());

        /** @var \Badoo\LiveProfilerUI\DataProviders\Method $MethodMock */
        /** @var \Badoo\LiveProfilerUI\Entity\MethodData[] $result */
        $result = $MethodMock->injectMethodNames([$MethodDataMock]);

        self::assertArrayHasKey(0, $result);
        self::assertInstanceOf(\Badoo\LiveProfilerUI\Entity\MethodData::class, $result[0]);
        self::assertEquals('method_name', $result[0]->getMethodName());
    }
}
