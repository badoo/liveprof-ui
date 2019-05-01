<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class AggregatorTest extends \unit\Badoo\BaseTestCase
{
    public function providerProcessInvalidParams()
    {
        return [
            ['', 'label', 'date'],
            ['app', '', 'date'],
            ['app', 'label', '']
        ];
    }

    /**
     * @dataProvider providerProcessInvalidParams
     * @param $app
     * @param $label
     * @param $date
     * @throws \Exception
     */
    public function testProcessInvalidParams($app, $label, $date)
    {
        $LoggerMock = $this->getMockBuilder(\get_class(self::$Container->get('logger')))
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $logger_messages = [];
        $LoggerMock->method('info')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp($app)
            ->setLabel($label)
            ->setDate($date);
        $this->setProtectedProperty($AggregatorMock, 'Logger', $LoggerMock);

        $result = $AggregatorMock->process();

        static::assertFalse($result);
        static::assertEquals(['Invalid params'], $logger_messages);
    }

    /**
     * @throws \Exception
     */
    public function testProcessExistsSnapshot()
    {
        $LoggerMock = $this->getMockBuilder(\get_class(self::$Container->get('logger')))
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $logger_messages = [];
        $LoggerMock->method('info')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );

        $SnapshotEntityMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotEntityMock->method('getId')->willReturn(1);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOneByAppAndLabelAndDate'])
            ->getMock();
        $SnapshotMock->method('getOneByAppAndLabelAndDate')->willReturn($SnapshotEntityMock);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'Logger', $LoggerMock);
        $this->setProtectedProperty($AggregatorMock, 'Snapshot', $SnapshotMock);
        $result = $AggregatorMock->process();

        static::assertTrue($result);
        static::assertEquals(['Started aggregation (app, label, date)', 'Snapshot already exists'], $logger_messages);
    }

    /**
     * @throws \Exception
     */
    public function testProcessEmptyProfilerData()
    {
        $LoggerMock = $this->getMockBuilder(\get_class(self::$Container->get('logger')))
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $logger_messages = [];
        $LoggerMock->method('info')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );

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

        $SourceMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Source::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPerfData'])
            ->getMock();
        $SourceMock->method('getPerfData')->willReturn([]);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date')
            ->setIsManual(true);
        $this->setProtectedProperty($AggregatorMock, 'Logger', $LoggerMock);
        $this->setProtectedProperty($AggregatorMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($AggregatorMock, 'Source', $SourceMock);
        $result = $AggregatorMock->process();

        static::assertFalse($result);
        static::assertEquals(
            ['Started aggregation (app, label, date)', 'Failed to get snapshot data from DB'],
            $logger_messages
        );
    }

    public function providerProcess()
    {
        return [
            [
                'save_result' => true,
                'error_msg' => [
                    'Started aggregation (app, label, date)',
                    'Processing rows: 1442',
                    'Too many profiles for app:label:date',
                    'Aggregating',
                    'Saving result'
                ],
                'expected' => true,
            ],
            [
                'save_result' => false,
                'error_msg' => [
                    'Started aggregation (app, label, date)',
                    'Processing rows: 1442',
                    'Too many profiles for app:label:date',
                    'Aggregating',
                    'Saving result',
                    'Can\'t save aggregated data'
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider providerProcess
     * @param $save_result
     * @param $error_msg
     * @param $expected
     * @throws \Exception
     */
    public function testProcess($save_result, $error_msg, $expected)
    {
        $LoggerMock = $this->getMockBuilder(\get_class(self::$Container->get('logger')))
            ->disableOriginalConstructor()
            ->setMethods(['info', 'error'])
            ->getMock();
        $logger_messages = [];
        $LoggerMock->method('info')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );
        $LoggerMock->method('error')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );

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

        $SourceMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Source::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPerfData'])
            ->getMock();
        $SourceMock->method('getPerfData')->willReturn(array_fill_keys(range(0, 1441), '{}'));

        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['processPerfdata', 'aggregate', 'saveResult'])
            ->getMock();
        $AggregatorMock->method('processPerfdata')->willReturn(true);
        $AggregatorMock->method('aggregate')->willReturn(true);
        $AggregatorMock->method('saveResult')->willReturn($save_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'Logger', $LoggerMock);
        $this->setProtectedProperty($AggregatorMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($AggregatorMock, 'Source', $SourceMock);
        $this->setProtectedProperty($AggregatorMock, 'DataPacker', new \Badoo\LiveProfilerUI\DataPacker());

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $result = $AggregatorMock->process();

        static::assertEquals($expected, $result);
        static::assertEquals($error_msg, $logger_messages);
    }

    public function providerProcessPerfdata()
    {
        return [
            [
                'data' => [],
                'methods' => [],
                'call_map' => [],
                'method_data' => [],
                'expected' => false,
            ],
            [
                'data' => ['main()' => ['wt' => 1]],
                'methods' => ['main()' => 1],
                'call_map' => [],
                'method_data' => [
                    'main()' => [
                        'wts' => '1,'
                    ]
                ],
                'expected' => false,
            ],
            [
                'data' => ['main()==>f' => ['wt' => 1]],
                'methods' => [
                    'main()' => 1,
                    'f' => 1
                ],
                'call_map' => [
                    'main()' => [
                        'f' => [
                            'wts' => '1,'
                        ]
                    ]
                ],
                'method_data' => [
                    'f' => [
                        'wts' => '1,'
                    ]
                ],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerProcessPerfdata
     * @param array $data
     * @param array $methods
     * @param array $call_map
     * @param array $method_data
     * @param bool $expected
     * @throws \ReflectionException
     */
    public function testProcessPerfdata($data, $methods, $call_map, $method_data, $expected)
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], [], []);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'fields', $FieldList->getFields());
        $result = $this->invokeMethod($AggregatorMock, 'processPerfdata', [$data]);

        self::assertAttributeEquals($methods, 'methods', $AggregatorMock);
        self::assertAttributeEquals($call_map, 'call_map', $AggregatorMock);
        self::assertAttributeEquals($method_data, 'method_data', $AggregatorMock);

        static::assertEquals($expected, $result);
    }

    public function providerAggregate()
    {
        return [
            [
                'call_map' => [],
                'method_data' => [],
                'expected_call_map' => [],
                'expected_method_data' => [],
            ],
            [
                'call_map' => [
                    'main()' => [
                        'f' => [
                            'wt' => 1,
                            'wts' => '1,'
                        ]
                    ]
                ],
                'method_data' => [
                    'f' => [
                        'wt' => 1,
                        'wts' => '1,'
                    ]
                ],
                'expected_call_map' => [
                    'main()' => [
                        'f' => [
                            'wt' => 1,
                            'min_wt' => '1',
                            'max_wt' => '1',
                            'percent_wt' => null
                        ]
                    ]
                ],
                'expected_method_data' => [
                    'f' => [
                        'wt' => 1,
                        'min_wt' => '1',
                        'max_wt' => '1',
                        'percent_wt' => null
                    ]
                ],
            ],
            [
                'call_map' => [
                    'main()' => [
                        'f' => [
                            'wt' => 0,
                            'wts' => '',
                        ]
                    ]
                ],
                'method_data' => [
                    'f' => [
                        'wt' => 0,
                        'wts' => '',
                    ]
                ],
                'expected_call_map' => [
                    'main()' => [
                        'f' => [
                            'wt' => 0,
                            'min_wt' => null,
                            'max_wt' => null,
                            'percent_wt' => null
                        ]
                    ]
                ],
                'expected_method_data' => [
                    'f' => [
                        'wt' => 0,
                        'min_wt' => null,
                        'max_wt' => null,
                        'percent_wt' => null
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerAggregate
     * @param array $call_map
     * @param array $method_data
     * @param array $expected_call_map
     * @param array $expected_method_data
     * @throws \ReflectionException
     */
    public function testAggregate($call_map, $method_data, $expected_call_map, $expected_method_data)
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt'], ['min', 'max', 'percent'], []);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');

        $this->setProtectedProperty($AggregatorMock, 'perf_count', 1);
        $this->setProtectedProperty($AggregatorMock, 'method_data', $method_data);
        $this->setProtectedProperty($AggregatorMock, 'call_map', $call_map);
        $this->setProtectedProperty($AggregatorMock, 'fields', $FieldList->getFields());
        $this->setProtectedProperty($AggregatorMock, 'fields', $FieldList->getFields());
        $this->setProtectedProperty($AggregatorMock, 'field_variations', $FieldList->getFieldVariations());
        $this->setProtectedProperty($AggregatorMock, 'FieldHandler', new \Badoo\LiveProfilerUI\FieldHandler());

        $this->invokeMethod($AggregatorMock, 'aggregate', []);

        self::assertAttributeEquals($expected_call_map, 'call_map', $AggregatorMock);
        self::assertAttributeEquals($expected_method_data, 'method_data', $AggregatorMock);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetMethodNamesMap()
    {
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListByNames'])
            ->getMock();
        $MethodMock->method('getListByNames')->willReturn(
            [
                ['name' => ' Method ']
            ]
        );

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'Method', $MethodMock);

        $method_names = ['method'];
        $result = $this->invokeMethod($AggregatorMock, 'getMethodNamesMap', [$method_names]);

        $expected = [
            'method' => [
                'name' => ' Method '
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function providerSave()
    {
        return [
            [
                'insert_result' => true,
                'expected' => true
            ],
            [
                'insert_result' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider providerSave
     * @param $insert_result
     * @param $expected
     * @throws \ReflectionException
     */
    public function testPushToMethodNamesMap($insert_result, $expected)
    {
        $MethodMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Method::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertMany'])
            ->getMock();
        $MethodMock->expects(self::once())->method('insertMany')->willReturn($insert_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'Method', $MethodMock);

        $method_names = ['method'];
        $result = $this->invokeMethod($AggregatorMock, 'pushToMethodNamesMap', [$method_names]);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider providerSave
     * @param $insert_result
     * @param $expected
     * @throws \ReflectionException
     */
    public function testSaveMethodData($insert_result, $expected)
    {
        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertMany'])
            ->getMock();
        $MethodDataMock->method('insertMany')->willReturn($insert_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'fields', ['wt']);
        $this->setProtectedProperty($AggregatorMock, 'field_variations', ['min']);
        $this->setProtectedProperty($AggregatorMock, 'MethodData', $MethodDataMock);

        $method_data = [];
        $method_names = [];
        for ($i = 0; $i < \Badoo\LiveProfilerUI\Aggregator::SAVE_PORTION_COUNT + 1; $i++) {
            $method_data['f' . $i] = [
                'wt' => 0,
                'min_wt' => 0,
            ];
            $method_names['f' . $i] = ['id' => $i];
        }
        $this->setProtectedProperty($AggregatorMock, 'method_data', $method_data);

        $result = $this->invokeMethod($AggregatorMock, 'saveMethodData', [1, $method_names]);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider providerSave
     * @param $insert_result
     * @param $expected
     * @throws \ReflectionException
     */
    public function testSaveTree($insert_result, $expected)
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertMany'])
            ->getMock();
        $MethodTreeMock->method('insertMany')->willReturn($insert_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'fields', ['wt']);
        $this->setProtectedProperty($AggregatorMock, 'field_variations', ['min']);
        $this->setProtectedProperty($AggregatorMock, 'MethodTree', $MethodTreeMock);

        $call_map = [];
        $method_names = ['main()' => ['id' => 1]];
        for ($i = 0; $i < \Badoo\LiveProfilerUI\Aggregator::SAVE_PORTION_COUNT + 1; $i++) {
            $call_map['main()']['f' . $i] = [
                'wt' => 1,
                'min_wt' => 1,
                'wts' => '1,',
            ];
            $method_names['f' . $i] = ['id' => $i];
        }
        $this->setProtectedProperty($AggregatorMock, 'call_map', $call_map);

        $result = $this->invokeMethod($AggregatorMock, 'saveTree', [1, $method_names]);

        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAndPopulateMethodNamesMap()
    {
        $method_names = ['method' => 1];
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethodNamesMap', 'pushToMethodNamesMap'])
            ->getMock();
        $AggregatorMock->method('getMethodNamesMap')->willReturn($method_names);
        $AggregatorMock->method('pushToMethodNamesMap')->willReturn(true);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');

        $method_names = ['method'];
        $result = $this->invokeMethod($AggregatorMock, 'getAndPopulateMethodNamesMap', [$method_names]);

        $expected = ['method' => 1];
        self::assertEquals($expected, $result);
    }

    public function providerDeleteOldData()
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotMock->method('getId')->willReturn(1);

        return [
            [
                'exists_snapshot' => null,
                'delete_tree_result' => true,
                'delete_data_result' => true,
                'expected' => true
            ],
            [
                'exists_snapshot' => $SnapshotMock,
                'delete_tree_result' => true,
                'delete_data_result' => true,
                'expected' => true
            ],
            [
                'exists_snapshot' => $SnapshotMock,
                'delete_tree_result' => false,
                'delete_data_result' => true,
                'expected' => false
            ],
            [
                'exists_snapshot' => $SnapshotMock,
                'delete_tree_result' => true,
                'delete_data_result' => false,
                'expected' => false
            ],
            [
                'exists_snapshot' => $SnapshotMock,
                'delete_tree_result' => false,
                'delete_data_result' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider providerDeleteOldData
     * @param $exists_snapshot
     * @param $delete_tree_result
     * @param $delete_data_result
     * @param $expected
     * @throws \ReflectionException
     */
    public function testDeleteOldData($exists_snapshot, $delete_tree_result, $delete_data_result, $expected)
    {
        $MethodTreeMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodTree::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteBySnapshotId'])
            ->getMock();
        $MethodTreeMock->method('deleteBySnapshotId')->willReturn($delete_tree_result);

        $MethodDataMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\MethodData::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteBySnapshotId'])
            ->getMock();
        $MethodDataMock->method('deleteBySnapshotId')->willReturn($delete_data_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'MethodTree', $MethodTreeMock);
        $this->setProtectedProperty($AggregatorMock, 'MethodData', $MethodDataMock);
        $this->setProtectedProperty($AggregatorMock, 'exists_snapshot', $exists_snapshot);

        $result = $this->invokeMethod($AggregatorMock, 'deleteOldData', []);

        self::assertEquals($expected, $result);
    }

    public function providerCreateOrUpdateSnapshot()
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Entity\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $SnapshotMock->method('getId')->willReturn(2);

        return [
            [
                'exists_snapshot' => null,
                'update_result' => 1,
                'create_result' => 1,
                'expected' => 1
            ],
            [
                'exists_snapshot' => null,
                'update_result' => 1,
                'create_result' => 0,
                'expected' => false
            ],
            [
                'exists_snapshot' => $SnapshotMock,
                'update_result' => 1,
                'create_result' => 0,
                'expected' => 2
            ]
        ];
    }

    /**
     * @dataProvider providerCreateOrUpdateSnapshot
     * @param $exists_snapshot
     * @param $update_result
     * @param $create_result
     * @param $expected
     * @throws \ReflectionException
     */
    public function testCreateOrUpdateSnapshot($exists_snapshot, $update_result, $create_result, $expected)
    {
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateSnapshot', 'createSnapshot'])
            ->getMock();
        $SnapshotMock->method('updateSnapshot')->willReturn($update_result);
        $SnapshotMock->method('createSnapshot')->willReturn($create_result);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');

        $method_data = [
            'main()' => [
                'wt' => 1,
                'min_wt' => 1,
            ]
        ];

        $this->setProtectedProperty($AggregatorMock, 'fields', ['ct', 'wt']);
        $this->setProtectedProperty($AggregatorMock, 'field_variations', ['min']);
        $this->setProtectedProperty($AggregatorMock, 'calls_count_field', 'ct');
        $this->setProtectedProperty($AggregatorMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($AggregatorMock, 'exists_snapshot', $exists_snapshot);
        $this->setProtectedProperty($AggregatorMock, 'method_data', $method_data);

        $result = $this->invokeMethod($AggregatorMock, 'createOrUpdateSnapshot', []);

        self::assertEquals($expected, $result);
    }

    public function providerSaveResult() : array
    {
        return [
            [
                'method_data' => 'method_data',
                'delete_result' => true,
                'create_result' => true,
                'save_tree' => true,
                'save_data' => true,
                'error_msg' => [],
                'expected' => true
            ],
            [
                'method_data' => 'method_data',
                'delete_result' => false,
                'create_result' => true,
                'save_tree' => true,
                'save_data' => true,
                'error_msg' => ['Can\'t delete old data'],
                'expected' => false
            ],
            [
                'method_data' => 'method_data',
                'delete_result' => true,
                'create_result' => false,
                'save_tree' => true,
                'save_data' => true,
                'error_msg' => ['Can\'t create or update snapshot'],
                'expected' => false
            ],
            [
                'method_data' => 'method_data',
                'delete_result' => true,
                'create_result' => true,
                'save_tree' => false,
                'save_data' => true,
                'error_msg' => ['Can\'t save tree data'],
                'expected' => false
            ],
            [
                'method_data' => 'method_data',
                'delete_result' => true,
                'create_result' => true,
                'save_tree' => true,
                'save_data' => false,
                'error_msg' => ['Can\'t save method data'],
                'expected' => false
            ],
            [
                'method_data' => null,
                'delete_result' => true,
                'create_result' => true,
                'save_tree' => true,
                'save_data' => true,
                'error_msg' => ['Empty method data'],
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider providerSaveResult
     * @param $method_data
     * @param $delete_result
     * @param $create_result
     * @param $save_tree
     * @param $save_data
     * @param $error_msg
     * @param $expected
     * @throws \ReflectionException
     */
    public function testSaveResult(
        $method_data,
        $delete_result,
        $create_result,
        $save_tree,
        $save_data,
        $error_msg,
        $expected
    ) {
        $LoggerMock = $this->getMockBuilder(\get_class(self::$Container->get('logger')))
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMock();
        $logger_messages = [];
        $LoggerMock->method('error')->willReturnCallback(
            function ($msg) use (&$logger_messages) {
                $logger_messages[] = $msg;
                return true;
            }
        );

        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'deleteOldData',
                    'createOrUpdateSnapshot',
                    'getAndPopulateMethodNamesMap',
                    'saveTree',
                    'saveMethodData'
                ]
            )
            ->getMock();
        $AggregatorMock->method('deleteOldData')->willReturn($delete_result);
        $AggregatorMock->method('createOrUpdateSnapshot')->willReturn($create_result);
        $AggregatorMock->method('getAndPopulateMethodNamesMap')->willReturn([]);
        $AggregatorMock->method('saveTree')->willReturn($save_tree);
        $AggregatorMock->method('saveMethodData')->willReturn($save_data);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');
        $this->setProtectedProperty($AggregatorMock, 'Logger', $LoggerMock);
        $this->setProtectedProperty($AggregatorMock, 'method_data', $method_data);

        $result = $this->invokeMethod($AggregatorMock, 'saveResult', []);

        self::assertEquals($expected, $result);
        self::assertEquals($error_msg, $logger_messages);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetLastError()
    {
        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $AggregatorMock->setApp('app')
            ->setLabel('label')
            ->setDate('date');

        $this->setProtectedProperty($AggregatorMock, 'last_error', 'last error');

        $result = $this->invokeMethod($AggregatorMock, 'getLastError', []);

        self::assertEquals('last error', $result);
    }

    public function providerGetSnapshotsDataForProcessing()
    {
        return [
            ['processed' => [], 'new' => [], 'expected' => []],
            [
                'processed' => [],
                'new' => [['date' => '2018-01-01', 'app' => 'app', 'label' => 'label', 'type' => 'auto']],
                'expected' => [['app' => 'app', 'label' => 'label', 'date' => '2018-01-01', 'type' => 'auto']]
            ],
            [
                'processed' => [['app' => 'app', 'label' => 'label', 'date' => '2018-01-01', 'type' => 'auto']],
                'new' => [['date' => '2018-01-01', 'app' => 'app', 'label' => 'label', 'type' => 'auto']],
                'expected' => []
            ],
        ];
    }

    /**
     * @dataProvider providerGetSnapshotsDataForProcessing
     * @param $processed
     * @param $new
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetSnapshotsDataForProcessing($processed, $new, $expected)
    {
        $SourceMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Source::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotsDataByDates'])
            ->getMock();
        $SourceMock->expects($this->once())->method('getSnapshotsDataByDates')->willReturn($new);

        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshotsByDates'])
            ->getMock();
        $SnapshotMock->expects($this->once())->method('getSnapshotsByDates')->willReturn($processed);

        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($AggregatorMock, 'Snapshot', $SnapshotMock);
        $this->setProtectedProperty($AggregatorMock, 'Source', $SourceMock);

        $result = $AggregatorMock->getSnapshotsDataForProcessing(3);

        static::assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Num of days must be > 0
     */
    public function testGetSnapshotsDataForProcessingError()
    {
        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $AggregatorMock->getSnapshotsDataForProcessing(0);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReset()
    {
        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($AggregatorMock, 'method_data', [1]);
        $this->setProtectedProperty($AggregatorMock, 'methods', [2]);
        $this->setProtectedProperty($AggregatorMock, 'call_map', [3]);

        $AggregatorMock->reset();

        $method_data = $this->getProtectedProperty($AggregatorMock, 'method_data');
        $methods = $this->getProtectedProperty($AggregatorMock, 'methods');
        $call_map = $this->getProtectedProperty($AggregatorMock, 'call_map');

        self::assertEquals([], $method_data);
        self::assertEquals([], $methods);
        self::assertEquals([], $call_map);
    }

    public function providerIsIncludePHPFile() : array
    {
        return [
            ['main()', false],
            ['parent==>child', false],
            ['main_autoload==>eval::file.php12345', true],
            ['main_autoload==>load::file.php12345', true],
            ['main_autoload==>run_init::file.php12345', true],
            ['eval::file.php12345==>child', true],
            ['load::file.php12345==>child', true],
            ['run_init::file.php12345==>child', true],
        ];
    }

    /**
     * @dataProvider providerIsIncludePHPFile
     * @param string $key
     * @param bool $expected_result
     * @throws \ReflectionException
     */
    public function testIsIncludePHPFile(string $key, bool $expected_result)
    {
        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $result = $this->invokeMethod($AggregatorMock, 'isIncludePHPFile', [$key]);
        self::assertEquals($expected_result, $result);
    }

    public function providerSplitMethods() : array
    {
        return [
            ['main()', [0, 'main()']],
            ['parent==>child', ['parent', 'child']],
        ];
    }

    /**
     * @dataProvider providerSplitMethods
     * @param string $key
     * @param array $expected_result
     * @throws \ReflectionException
     */
    public function testSplitMethods(string $key, array $expected_result)
    {
        /** @var \Badoo\LiveProfilerUI\Aggregator $AggregatorMock */
        $AggregatorMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Aggregator::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $result = $this->invokeMethod($AggregatorMock, 'splitMethods', [$key]);
        self::assertEquals($expected_result, $result);
    }
}
