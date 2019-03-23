<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class FlameGraphPageTest extends \unit\Badoo\BaseTestCase
{
    public function providerInvalidData() : array
    {
        return [
            [
                'app' => '',
                'label' => '',
                'snapshot_id' => 0,
            ],
            [
                'app' => '',
                'label' => '',
                'snapshot_id' => 1,
            ],
            [
                'app' => 'app',
                'label' => 'label',
                'snapshot_id' => 0,
            ],
        ];
    }

    /**
     * @dataProvider providerInvalidData
     * @param $app
     * @param $label
     * @param $snapshot_id
     * @throws \ReflectionException
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidData($app, $label, $snapshot_id)
    {
        $StorageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOne'])
            ->getMock();
        $StorageMock->method('getOne')->willReturn([]);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($SnapshotMock, 'AggregatorStorage', $StorageMock);

        $data = [
            'app' => $app,
            'label' => $label,
            'snapshot_id' => $snapshot_id,
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $PageMock->setData($data);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->invokeMethod($PageMock, 'getTemplateData');
    }

    public function providerGetTemplateData() : array
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotMock->method('getId')->willReturn(1);

        return [
            [
                'app' => '',
                'label' => '',
                'snapshot_id' => 1,
                'snapshot' => $SnapshotMock,
                'svg' => '',
                'expected' => [
                    'error' => 'Not enough data to show graph',
                    'param' => '',
                    'params' => ['wt' => 'wt'],
                    'diff' => false,
                    'date1' => '2018-01-01',
                    'date2' => '2019-01-01',
                    'snapshot_id' => 1,
                    'snapshot_app' => '',
                    'snapshot_label' => '',
                    'snapshot_date' => '',
                    'dates' => [
                        '2019-01-01',
                        '2018-01-01',
                    ],
                    'date' => '',
                    'svg' => '',
                ],
            ],
            [
                'app' => 'app',
                'label' => 'label',
                'snapshot_id' => 0,
                'snapshot' => $SnapshotMock,
                'svg' => '',
                'expected' => [
                    'error' => 'Not enough data to show graph',
                    'param' => '',
                    'params' => ['wt' => 'wt'],
                    'diff' => false,
                    'date1' => '2018-01-01',
                    'date2' => '2019-01-01',
                    'snapshot_id' => 1,
                    'snapshot_app' => '',
                    'snapshot_label' => '',
                    'snapshot_date' => '',
                    'dates' => [
                        '2019-01-01',
                        '2018-01-01',
                    ],
                    'date' => '',
                    'svg' => '',
                ],
            ],
            [
                'app' => '',
                'label' => '',
                'snapshot_id' => 1,
                'snapshot' => $SnapshotMock,
                'svg' => '',
                'expected' => [
                    'param' => '',
                    'params' => ['wt' => 'wt'],
                    'diff' => false,
                    'date1' => '2018-01-01',
                    'date2' => '2019-01-01',
                    'svg' => '',
                    'snapshot_id' => 1,
                    'snapshot_app' => '',
                    'snapshot_label' => '',
                    'snapshot_date' => '',
                    'dates' => [
                        '2019-01-01',
                        '2018-01-01',
                    ],
                    'date' => '',
                    'error' => 'Not enough data to show graph'
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerGetTemplateData
     * @param $app
     * @param $label
     * @param $snapshot_id
     * @param $snapshot
     * @param $svg
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetTemplateData($app, $label, $snapshot_id, $snapshot, $svg, $expected)
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneById', 'getOneByAppAndLabel', 'getDatesByAppAndLabel', 'getSnapshotIdsByDates'])
            ->getMock();
        $SnapshotMock->method('getOneById')->willReturn($snapshot);
        $SnapshotMock->method('getOneByAppAndLabel')->willReturn($snapshot);
        $SnapshotMock->method('getSnapshotIdsByDates')->willReturn([
            '2019-01-01' => 1,
            '2018-01-01' => 1
        ]);
        $SnapshotMock->method('getDatesByAppAndLabel')->willReturn([
            '2019-01-01',
            '2019-01-02',
            '2018-01-01'
        ]);

        $data = [
            'app' => $app,
            'label' => $label,
            'snapshot_id' => $snapshot_id,
            'param' => '',
            'diff' => false,
            'date' => '',
            'date1' => '',
            'date2' => '',
        ];

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataForFlameGraph'])
            ->getMock();
        $PageMock->method('getDataForFlameGraph')->willReturn($svg);

        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt' => 'wt'], [], []);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock->setData($data);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);

        $result = $this->invokeMethod($PageMock, 'getTemplateData');

        static::assertEquals($expected, $result);
    }

    public function providerCalculateParamThreshold() : array
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $MethodTreeMock->method('getValue')->willReturn(300);
        $data = [];
        for ($i = 0; $i < 3001; $i++) {
            $data[$i] = $MethodTreeMock;
        }
        return [
            ['data' => [], 'expected' => 300],
            ['data' => $data, 'expected' => 300],
        ];
    }

    /**
     * @dataProvider providerCalculateParamThreshold
     * @param $data
     * @param $expected
     * @throws \ReflectionException
     */
    public function testCalculateParamThreshold($data, $expected)
    {
        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $threshold = $this->invokeMethod($PageMock, 'calculateParamThreshold', [$data, 'wt']);
        self::assertEquals($expected, $threshold);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAllMethodParentsParam()
    {
        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $data = [
            new \Badoo\LiveProfilerUI\Entity\MethodTree(
                ['method_id' => 1, 'parent_id' => 2, 'wt' => 3],
                ['wt'=> 'wt']
            )
        ];
        $param = 'wt';
        $result = $this->invokeMethod($PageMock, 'getAllMethodParentsParam', [$data, $param]);

        $expected = [1 => [2 => 3]];
        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetRootMethodId()
    {
        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', new \Badoo\LiveProfilerUI\FieldList([], [], []));

        $data = [
            new \Badoo\LiveProfilerUI\Entity\MethodTree(['method_id' => 1, 'parent_id' => 2], []),
            new \Badoo\LiveProfilerUI\Entity\MethodTree(['method_id' => 3, 'parent_id' => 1], [])
        ];
        $result = $this->invokeMethod($PageMock, 'getRootMethodId', [$data]);

        $expected = 2;
        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraphEmptyTree()
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->once())->method('getSnapshotMethodsTree')->willReturn([]);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [1, 0, 'wt', false]);
        self::assertEquals('', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraphByDiffEmptyTree()
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->once())->method('getSnapshotMethodsTree')->willReturn([]);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [2, 3, 'wt', true]);
        self::assertEquals('', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraphEmptyRootMethodData()
    {
        $tree = [1];
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->once())->method('getSnapshotMethodsTree')->willReturn($tree);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->expects($this->once())
            ->method('getDataByMethodIdsAndSnapshotIds')
            ->willReturn([]);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootMethodId'])
            ->getMock();
        $PageMock->method('getRootMethodId')->willReturn(1);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [1, 0, 'wt', false]);
        self::assertEquals('', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraphByDiffEmptyRootMethodData()
    {
        $tree = [
            new \Badoo\LiveProfilerUI\Entity\MethodTree(
                [
                    'snapshot_id' => 1,
                    'parent_id' => 2,
                    'method_id' => 2,
                    'wt' => 3
                ],
                ['wt' => 'wt']
            )
        ];
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->exactly(2))->method('getSnapshotMethodsTree')->willReturn($tree);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->expects($this->once())
            ->method('getDataByMethodIdsAndSnapshotIds')
            ->willReturn([]);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootMethodId'])
            ->getMock();
        $PageMock->method('getRootMethodId')->willReturn(1);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [2, 3, 'wt', true]);
        self::assertEquals('', $result);
    }

    /**
     * @depends testCalculateParamThreshold
     * @depends testGetAllMethodParentsParam
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraph()
    {
        $tree = [
            new \Badoo\LiveProfilerUI\Entity\MethodTree(
                ['method_id' => 2, 'parent_id' => 1, 'wt' => 2],
                ['wt' => 'wt']
            ),
        ];
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->once())->method('getSnapshotMethodsTree')->willReturn($tree);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->expects($this->once())->method('injectMethodNames')->willReturn($tree);

        $root_method_data = [
            new \Badoo\LiveProfilerUI\Entity\MethodData(['method_id' => 1, 'wt' => 2], ['wt' => 'wt'])
        ];
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->expects($this->once())
            ->method('getDataByMethodIdsAndSnapshotIds')
            ->willReturn($root_method_data);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootMethodId'])
            ->getMock();
        $PageMock->method('getRootMethodId')->willReturn(1);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [1, 0, 'wt', false]);
        self::assertEquals("main() 2\n", $result);
    }

    /**
     * @depends testCalculateParamThreshold
     * @depends testGetAllMethodParentsParam
     * @throws \ReflectionException
     */
    public function testGetDataForFlameGraphByDiff()
    {
        $tree = [
            new \Badoo\LiveProfilerUI\Entity\MethodTree(
                ['method_id' => 2, 'parent_id' => 1, 'wt' => 2],
                ['wt' => 'wt']
            ),
        ];
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->expects($this->exactly(2))->method('getSnapshotMethodsTree')->willReturn($tree);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->expects($this->any())->method('injectMethodNames')->willReturn($tree);

        $root_method_data = [
            new \Badoo\LiveProfilerUI\Entity\MethodData(['method_id' => 1, 'wt' => 2], ['wt' => 'wt']),
            new \Badoo\LiveProfilerUI\Entity\MethodData(['method_id' => 2, 'wt' => 3], ['wt' => 'wt'])
        ];
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->expects($this->once())
            ->method('getDataByMethodIdsAndSnapshotIds')
            ->willReturn($root_method_data);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootMethodId'])
            ->getMock();
        $PageMock->method('getRootMethodId')->willReturn(1);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        $result = $this->invokeMethod($PageMock, 'getDataForFlameGraph', [2, 3, 'wt', true]);
        self::assertEquals("main() 1\n", $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock->setData(['app' => 'app', 'label' => 'label']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'snapshot_id' => 0,
            'param' => 'wt',
            'diff' => false,
            'date' => '',
            'date1' => '',
            'date2' => ''
        ];
        self::assertEquals($expected, $data);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstruct()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList([], [], []);

        /** @var \Badoo\LiveProfilerUI\DataProviders\Method $MethodMock */
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\DataProviders\MethodTree $MethodTreeMock */
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\DataProviders\MethodData $MethodDataMock */
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\View $ViewMock */
        $ViewMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\View::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $calls_count_field = 'ct';

        $Page = new \Badoo\LiveProfilerUI\Pages\FlameGraphPage(
            $ViewMock,
            $SnapshotMock,
            $MethodMock,
            $MethodTreeMock,
            $MethodDataMock,
            $FieldList,
            $calls_count_field
        );

        $View = $this->getProtectedProperty($Page, 'View');
        $Snapshot = $this->getProtectedProperty($Page, 'Snapshot');
        $Method = $this->getProtectedProperty($Page, 'Method');
        $MethodTree = $this->getProtectedProperty($Page, 'MethodTree');
        $MethodData = $this->getProtectedProperty($Page, 'MethodData');
        $FieldListNew = $this->getProtectedProperty($Page, 'FieldList');
        $calls_count_new = $this->getProtectedProperty($Page, 'calls_count_field');

        self::assertSame($ViewMock, $View);
        self::assertSame($SnapshotMock, $Snapshot);
        self::assertSame($MethodMock, $Method);
        self::assertSame($MethodTreeMock, $MethodTree);
        self::assertSame($MethodDataMock, $MethodData);
        self::assertSame($FieldList, $FieldListNew);
        self::assertSame($calls_count_field, $calls_count_new);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetSnapshotIdsByDatesEmptyDate()
    {
        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $result = $this->invokeMethod($PageMock, 'getSnapshotIdsByDates', ['app', 'label', '', '']);
        self::assertEquals([0, 0], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetSnapshotIdsByDates()
    {
        /** @var \Badoo\LiveProfilerUI\DataProviders\Snapshot $SnapshotMock */
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotIdsByDates'])
            ->getMock();
        $SnapshotMock->method('getSnapshotIdsByDates')->willReturn([
            'date1' => ['id' => 1, 'calls_count' => 1],
            'date2' => ['id' => 2, 'calls_count' => 1]
        ]);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);

        $result = $this->invokeMethod($PageMock, 'getSnapshotIdsByDates', ['app', 'label', 'date1', 'date2']);
        self::assertEquals([1, 2], $result);
    }
}
