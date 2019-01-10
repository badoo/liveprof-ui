<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class SnapshotsDiffPageTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testGetTemplateData()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);

        $SnapshotEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotEntityMock->method('getId')->willReturn(1);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDatesByAppAndLabel', 'getOneByAppAndLabelAndDate'])
            ->getMock();
        $SnapshotMock->method('getDatesByAppAndLabel')->willReturn(['2019-01-01', '2019-02-02', '2019-03-03']);
        $SnapshotMock->method('getOneByAppAndLabelAndDate')->willReturn($SnapshotEntityMock);

        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->method('getSnapshotMethodsTree')->willReturn([]);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListByIds'])
            ->getMock();
        $MethodMock->method('getListByIds')->willReturn([]);

        $data = [
            'app' => 'app',
            'label' => 'label',
            'date1' => '',
            'date2' => '',
            'param' => 'wt',
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($PageMock, 'calls_count_field', 'ct');
        $PageMock->setData($data);

        $result = $this->invokeMethod($PageMock, 'getTemplateData');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'date1' => '2019-02-02',
            'date2' => '2019-01-01',
            'snapshot1' => $SnapshotEntityMock,
            'snapshot2' => $SnapshotEntityMock,
            'diff' => [],
            'params' => ['wt' => 'wt'],
            'param' => 'wt',
            'field_descriptions' => [],
            'link_base' => '/profiler/tree-view.phtml?app=app&label=label',
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \Exception
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Diff functionality is not available
     */
    public function testGetSnapshotsDiffNotSupported()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);

        $data = [
            'app' => 'app',
            'label' => 'label'
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'calls_count_field', false);
        $PageMock->setData($data);

        $this->invokeMethod($PageMock, 'getTemplateData');
    }

    public function providerGetMethodsDiff()
    {
        return [
            [
                'parent_id' => 0,
                'data1' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree([], [])
                ],
                'data2' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree([], [])
                ],
                'method_calls_count1' => 1,
                'method_calls_count2' => 1,
                'expected' => false
            ],
            [
                'parent_id' => 0,
                'data1' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'data2' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'method_calls_count1' => 1,
                'method_calls_count2' => 1,
                'expected' => false
            ],
            [
                'parent_id' => 0,
                'data1' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'data2' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'method_calls_count1' => 1,
                'method_calls_count2' => 2,
                'expected' => false
            ],
            [
                'parent_id' => 0,
                'data1' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct']),
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 200, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'data2' => [
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 100, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct']),
                    new \Badoo\LiveProfilerUI\Entity\MethodTree(['wt' => 100, 'ct' => 1], ['wt' => 'wt', 'ct' => 'ct'])
                ],
                'method_calls_count1' => 1,
                'method_calls_count2' => 2,
                'expected' => [
                    'delta' => -200.0,
                    'name' => '?',
                    'name_alt' => '?',
                    'method_id' => 0,
                    'ct1' => 1.0,
                    'ct2' => 2.0,
                    'info' => [
                        [
                            'fields' => [
                                'ct' => [1 => 1, 2 => 1],
                                'wt' => [1 => 200, 2 => 100],
                            ],
                            'name' => '?',
                            'name_alt' => '?',
                            'method_id' => 0,
                        ],
                        [
                            'fields' => [
                                'ct' => [1 => 1, 2 => 1],
                                'wt' => [1 => 200, 2 => 100],
                            ],
                            'name' => '?',
                            'name_alt' => '?',
                            'method_id' => 1,
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider providerGetMethodsDiff
     * @param $parent_id
     * @param $data1
     * @param $data2
     * @param $method_calls_count1
     * @param $method_calls_count2
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetMethodsDiff(
        $parent_id,
        $data1,
        $data2,
        $method_calls_count1,
        $method_calls_count2,
        $expected
    )
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt' => 'wt', 'ct' => 'ct'], [], []);

        /** @var \Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'calls_count_field', 'ct');

        $method_names = [];
        $param = 'wt';
        $params = [
            $parent_id,
            $data1,
            $data2,
            $method_calls_count1,
            $method_calls_count2,
            $method_names,
            $param
        ];
        $result = $this->invokeMethod($PageMock, 'getMethodsDiff', $params);

        self::assertEquals($expected, $result);
    }

    public function providerGetSnapshotsDiff()
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $MethodTreeMock->method('getValue')->willReturn(200);

        return [
            [
                'method_tree' => [],
                'expected' => [],
            ],
            [
                'method_tree' => [$MethodTreeMock],
                'expected' => [],
            ],
        ];
    }

    /**
     * @dataProvider providerGetSnapshotsDiff
     * @param $method_tree
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetSnapshotsDiff($method_tree, $expected)
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotMethodsTree'])
            ->getMock();
        $MethodTreeMock->method('getSnapshotMethodsTree')->willReturn($method_tree);

        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListByIds'])
            ->getMock();
        $MethodMock->method('getListByIds')->willReturn([]);

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodsDiff'])
            ->getMock();
        $PageMock->method('getMethodsDiff')->willReturn([]);
        $this->setProtectedProperty($PageMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($PageMock, 'Method', $MethodMock);

        $result = $this->invokeMethod($PageMock, 'getSnapshotsDiff', [1, 2, 'wt']);

        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage $PageMock */
        $PageMock->setData(['app' => ' app ', 'label' => ' label ']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'date1' => '',
            'date2' => '',
            'param' => ''
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

        $Page = new \Badoo\LiveProfilerUI\Pages\SnapshotsDiffPage(
            $ViewMock,
            $SnapshotMock,
            $MethodMock,
            $MethodTreeMock,
            $FieldList,
            $calls_count_field
        );

        $View = $this->getProtectedProperty($Page, 'View');
        $Snapshot = $this->getProtectedProperty($Page, 'Snapshot');
        $Method = $this->getProtectedProperty($Page, 'Method');
        $MethodTree = $this->getProtectedProperty($Page, 'MethodTree');
        $FieldListNew = $this->getProtectedProperty($Page, 'FieldList');
        $calls_count_new = $this->getProtectedProperty($Page, 'calls_count_field');

        self::assertSame($ViewMock, $View);
        self::assertSame($SnapshotMock, $Snapshot);
        self::assertSame($MethodMock, $Method);
        self::assertSame($MethodTreeMock, $MethodTree);
        self::assertSame($FieldList, $FieldListNew);
        self::assertSame($calls_count_field, $calls_count_new);
    }
}
