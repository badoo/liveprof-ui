<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class ProfileMethodTreePageTest extends \unit\Badoo\BaseTestCase
{
    public function providerInvalidData()
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
     * @expectedExceptionMessage Can't get snapshot
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

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PageMock, 'calls_count_field', 'ct');
        $PageMock->setData($data);

        $this->invokeMethod($PageMock, 'getTemplateData');
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetTemplateData()
    {
        $SnapshotEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotEntityMock->method('getId')->willReturn(1);

        $MethodDataEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodId', 'getSnapshotId', 'getHistoryData', 'getValues'])
            ->getMock();
        $MethodDataEntityMock->method('getMethodId')->willReturn(1);
        $MethodDataEntityMock->method('getSnapshotId')->willReturn(1);
        $MethodDataEntityMock->method('getHistoryData')->willReturn(['wt' => 1, 'ct' => 1, 'mem' => 1]);
        $MethodDataEntityMock->method('getValues')->willReturn(['wt' => 1, 'ct' => 1, 'mem' => 1]);

        $MethodTreeEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodId', 'getParentId', 'getSnapshotId', 'getValues'])
            ->getMock();
        $MethodTreeEntityMock->method('getMethodId')->willReturn(1);
        $MethodTreeEntityMock->method('getParentId')->willReturn(1);
        $MethodTreeEntityMock->method('getSnapshotId')->willReturn(1);
        $MethodTreeEntityMock->method('getValues')->willReturn(['wt' => 1, 'ct' => 1, 'mem' => 1]);

        $ViewMock = $this->getMockBuilder(\get_class(self::$Container->get('view')))
            ->disableOriginalConstructor()
            ->setMethods(['fetchFile'])
            ->getMock();
        $ViewMock->method('fetchFile')->will($this->returnArgument(1));
        self::$Container->set('view', $ViewMock);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneById', 'getOneByAppAndLabel', 'getSnapshotIdsByDates'])
            ->getMock();
        $SnapshotMock->method('getOneById')->willReturn($SnapshotEntityMock);
        $SnapshotMock->method('getOneByAppAndLabel')->willReturn($SnapshotEntityMock);
        $SnapshotMock->method('getSnapshotIdsByDates')->willReturn(['date' => 1]);
        self::$Container->set('snapshot', $SnapshotMock);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames', 'findByName', 'getListByIds'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);
        $MethodMock->method('findByName')->willReturn([[1 => 'test']]);
        $MethodMock->method('getListByIds')->willReturn([0 => 'test']);
        self::$Container->set('method', $MethodMock);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->method('getDataByMethodIdsAndSnapshotIds')->willReturn([$MethodDataEntityMock]);
        self::$Container->set('method_data', $MethodDataMock);

        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds', 'getDataByParentIdsAndSnapshotIds'])
            ->getMock();
        $MethodTreeMock->method('getDataByMethodIdsAndSnapshotIds')->willReturn([$MethodTreeEntityMock]);
        $MethodTreeMock->method('getDataByParentIdsAndSnapshotIds')->willReturn([$MethodTreeEntityMock]);
        self::$Container->set('method_tree', $MethodTreeMock);

        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);
        self::$Container->set('fields', $FieldList);

        $data = [
            'app' => 'app',
            'label' => 'label',
            'snapshot_id' => 0,
            'method_id' => 0,
            'stat_interval' => 7,
            'date1' => '',
            'date2' => '',
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'View', $ViewMock);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PageMock, 'calls_count_field', 'ct');
        $PageMock->setData($data);

        $result = $this->invokeMethod($PageMock, 'getTemplateData');

        $method_dates = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray(date('Y-m-d'), 7, 7);
        $date1 = current($method_dates);
        $date2 = end($method_dates);
        $expected = [
            'snapshot' => $SnapshotEntityMock,
            'js_graph_data_all' => [
                $MethodDataEntityMock,
                $MethodTreeEntityMock
            ],
            'method_dates' => $method_dates,
            'stat_intervals' => [
                [
                    'name' => '7 days',
                    'link' => '/profiler/tree-view.phtml?app=app&label=label&method_id=0&stat_interval=7',
                    'selected' => true
                ],
                [
                    'name' => '1 month',
                    'link' => '/profiler/tree-view.phtml?app=app&label=label&method_id=0&stat_interval=31',
                    'selected' => false
                ],
                [
                    'name' => '6 months',
                    'link' => '/profiler/tree-view.phtml?app=app&label=label&method_id=0&stat_interval=182',
                    'selected' => false
                ]
            ],
            'method_name' => 'test',
            'method_data' => [
                'link_base' => '/profiler/tree-view.phtml?app=app&label=label',
                'fields' => ['wt' => 'wt', 'ct' => 'ct'],
                'field_descriptions' => [],
                'data' => [$MethodDataEntityMock],
                'hide_lines_column' => true,
                'stat_interval' => 7,
                'date1' => $date1,
                'date2' => $date2,
            ],
            'parents' => [
                'link_base' => '/profiler/tree-view.phtml?app=app&label=label',
                'fields' => ['wt' => 'wt', 'ct' => 'ct'],
                'field_descriptions' => [],
                'data' => [$MethodTreeEntityMock],
                'stat_interval' => 7,
                'date1' => $date1,
                'date2' => $date2,
            ],
            'children' => [
                'link_base' => '/profiler/tree-view.phtml?app=app&label=label',
                'fields' => ['wt' => 'wt', 'ct' => 'ct'],
                'field_descriptions' => [],
                'data' =>  [$MethodTreeEntityMock],
                'hide_lines_column' => true,
                'stat_interval' => 7,
                'date1' => $date1,
                'date2' => $date2,
            ],
            'available_graphs' => [
                'wt' => [
                    'type' => 'time',
                    'label' => 'wt',
                    'graph_label' => 'wt self + children calls graph'
                ],
                'ct' => [
                    'type' => 'times',
                    'label' => 'ct',
                    'graph_label' => 'ct self + children calls graph'
                ],
                'mem' => [
                    'type' => 'memory',
                    'label' => 'mem',
                    'graph_label' => 'mem self + children calls graph'
                ]
            ],
            'method_id' => 0,
            'date1' => $date1,
            'date2' => $date2,
        ];
        static::assertEquals($expected, $result);
    }

    public function providerGetMethodDataWithHistory()
    {
        return [
            [
                'dates' => [],
                'expected' => []
            ],
            [
                'dates' => ['date'],
                'expected' => ['records with history']
            ]
        ];
    }

    /**
     * @dataProvider providerGetMethodDataWithHistory
     * @param $dates
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetMethodDataWithHistory($dates, $expected)
    {
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodDataMock->method('getDataByMethodIdsAndSnapshotIds')->willReturn([]);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfilerRecordsWithHistory'])
            ->getMock();
        $PageMock->method('getProfilerRecordsWithHistory')->willReturn(['records with history']);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $result = $this->invokeMethod($PageMock, 'getMethodDataWithHistory', [$dates, 1]);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider providerGetMethodDataWithHistory
     * @param $dates
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetMethodParentsWithHistory($dates, $expected)
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByMethodIdsAndSnapshotIds'])
            ->getMock();
        $MethodTreeMock->method('getDataByMethodIdsAndSnapshotIds')->willReturn([]);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfilerRecordsWithHistory'])
            ->getMock();
        $PageMock->method('getProfilerRecordsWithHistory')->willReturn(['records with history']);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $result = $this->invokeMethod($PageMock, 'getMethodParentsWithHistory', [$dates, 1]);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider providerGetMethodDataWithHistory
     * @param $dates
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetMethodChildrenWithHistory($dates, $expected)
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataByParentIdsAndSnapshotIds'])
            ->getMock();
        $MethodTreeMock->method('getDataByParentIdsAndSnapshotIds')->willReturn([]);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfilerRecordsWithHistory'])
            ->getMock();
        $PageMock->method('getProfilerRecordsWithHistory')->willReturn(['records with history']);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $result = $this->invokeMethod($PageMock, 'getMethodChildrenWithHistory', [$dates, 1]);

        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $PageMock->setData(['app' => ' app ', 'label' => ' label ']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'snapshot_id' => 0,
            'stat_interval' => 31,
            'method_id' => 0,
            'date1' => '',
            'date2' => '',
        ];
        self::assertEquals($expected, $data);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Empty snapshot_id, app and label
     * @throws \ReflectionException
     */
    public function testCleanDataInvalidData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage $PageMock */
        $PageMock->setData(['app' => ' app ']);
        $this->invokeMethod($PageMock, 'cleanData');
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

        $Page = new \Badoo\LiveProfilerUI\Pages\ProfileMethodTreePage(
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
}
