<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class ProfileMethodListPageTest extends \unit\Badoo\BaseTestCase
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

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodListPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodListPage::class)
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
        $ViewMock = $this->getMockBuilder(\get_class(self::$Container->get('view')))
            ->disableOriginalConstructor()
            ->setMethods(['fetchFile'])
            ->getMock();
        $ViewMock->method('fetchFile')->will($this->returnArgument(1));

        $SnapshotEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotEntityMock->method('getId')->willReturn(1);

        $MethodDataEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodId'])
            ->getMock();
        $MethodDataEntityMock->method('getMethodId')->willReturn(1);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataBySnapshotId'])
            ->getMock();
        $MethodDataMock->method('getDataBySnapshotId')->willReturn([$MethodDataEntityMock]);

        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotParentsData'])
            ->getMock();
        $MethodTreeMock->method('getSnapshotParentsData')->willReturn([]);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneById', 'getOneByAppAndLabel'])
            ->getMock();
        $SnapshotMock->method('getOneById')->willReturn($SnapshotEntityMock);
        $SnapshotMock->method('getOneByAppAndLabel')->willReturn($SnapshotEntityMock);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);

        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);

        $data = [
            'app' => 'app',
            'label' => 'label',
            'snapshot_id' => 0,
            'all' => false,
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodListPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodListPage::class)
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

        $expected = [
            'wall' => [
                'data' => [$MethodDataEntityMock],
                'fields' => [
                    'wt' => 'wt',
                    'ct' => 'ct',
                    'wt_excl' => 'wt_excl'
                ],
                'field_descriptions' => [],
                'link_base' => '/profiler/tree-view.phtml?app=app&label=label',
            ],
            'snapshot' => $SnapshotEntityMock,
            'all' => false
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodListPage $PageMock */
        $PageMock->setData(['app' => ' app ', 'label' => ' label ']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'snapshot_id' => 0,
            'all' => false
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
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileMethodListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileMethodListPage $PageMock */
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

        $Page = new \Badoo\LiveProfilerUI\Pages\ProfileMethodListPage(
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
