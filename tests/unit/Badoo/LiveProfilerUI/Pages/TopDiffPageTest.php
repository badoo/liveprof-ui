<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class TopDiffPageTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetTemplateData()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotsByDates'])
            ->getMock();
        $SnapshotMock->method('getSnapshotsByDates')->willReturn(
            [
                ['id' => 1, 'app' => 'app', 'label' => 'label', 'date' => 'date 1', 'wt' => 10000],
                ['id' => 2, 'app' => 'app', 'label' => 'label', 'date' => 'date 2', 'wt' => 20000]
            ]
        );

        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotParentsData'])
            ->getMock();
        $MethodTreeMock->method('getSnapshotParentsData')->willReturn([2 => [2 => ['wt' => 10000]]]);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneParamDataBySnapshotIds'])
            ->getMock();
        $MethodDataMock->method('getOneParamDataBySnapshotIds')->willReturn(
            [
                ['snapshot_id' => 1, 'method_id' => 1, 'wt' => 30000],
                ['snapshot_id' => 1, 'method_id' => 2, 'wt' => 30000],
                ['snapshot_id' => 2, 'method_id' => 1, 'wt' => 40000],
                ['snapshot_id' => 2, 'method_id' => 2, 'wt' => 40000],
            ]
        );

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['injectMethodNames'])
            ->getMock();
        $MethodMock->method('injectMethodNames')->willReturnArgument(0);

        $data = [
            'date1' => 'date 1',
            'date2' => 'date 2',
            'param' => 'wt',
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\TopDiffPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\TopDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PageMock, 'calls_count_field', 'ct');
        $PageMock->setData($data);

        $result = $this->invokeMethod($PageMock, 'getTemplateData');

        $expected = [
            'date1' => 'date 1',
            'date2' => 'date 2',
            'param' => 'wt',
            'data' => [
                new \Badoo\LiveProfilerUI\Entity\TopDiff([
                    'app' => 'app',
                    'label' => 'label',
                    'method_id' => 1,
                    'from_value' => 30000,
                    'to_value' => 40000,
                    'value' => 10000,
                    'percent' => 33
                ]),
                new \Badoo\LiveProfilerUI\Entity\TopDiff([
                    'app' => 'app',
                    'label' => 'label',
                    'method_id' => 2,
                    'from_value' => 30000,
                    'to_value' => 30000,
                    'value' => 0,
                    'percent' => 0
                ]),
            ],
            'params' => ['wt' => 'wt'],
            'exclude' => true
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\TopDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\TopDiffPage $PageMock */
        $PageMock->setData(['date1' => ' date1 ', 'date2' => ' date2 ', 'param' => ' param ']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = [
            'date1' => 'date1',
            'date2' => 'date2',
            'param' => 'param'
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

        $Page = new \Badoo\LiveProfilerUI\Pages\TopDiffPage(
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
