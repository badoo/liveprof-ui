<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

class SourceTest extends \unit\Badoo\BaseTestCase
{
    protected $last_sql = '';
    /** @var \Badoo\LiveProfilerUI\DB\Storage */
    protected $SourceStorage;
    protected $DataPacker;

    protected function setUp()
    {
        parent::setUp();

        $this->SourceStorage = $this->getMockBuilder(\Badoo\LiveProfilerUI\DB\Storage::class)
            ->setConstructorArgs(['sqlite:///:memory:'])
            ->setMethods()
            ->getMock();

        $this->SourceStorage->query(
            'create table details (id integer, app text, label text, timestamp text, perfdata text)'
        );
        $this->SourceStorage->insert(
            'details',
            [
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 01:00:00', strtotime('-1 day')),
                'perfdata' => '1'
            ]
        );
        $this->SourceStorage->insert(
            'details',
            [
                'app' => 'app2',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 02:00:00', strtotime('-1 day')),
                'perfdata' => '2'
            ]
        );
        $this->SourceStorage->insert(
            'details',
            [
                'app' => 'app2',
                'label' => 'label2',
                'timestamp' => date('Y-m-d 03:00:00'),
                'perfdata' => '3'
            ]
        );
        $this->SourceStorage->insert(
            'details',
            [
                'app' => 'app2',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 04:00:00'),
                'perfdata' => '4'
            ]
        );

        $this->DataPacker = new \Badoo\LiveProfilerUI\DataPacker();
    }

    public function testGetSnapshotsDataByDates()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Source($this->SourceStorage, $this->DataPacker);
        $result = $Source->getSnapshotsDataByDates(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));

        $expected = [
            ['app' => 'app1', 'label' => 'label1', 'date' => date('Y-m-d', strtotime('-1 day'))],
            ['app' => 'app2', 'label' => 'label1', 'date' => date('Y-m-d', strtotime('-1 day'))],
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetPerfData()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Source($this->SourceStorage, $this->DataPacker);
        $result = $Source->getPerfData('app1', 'label1', date('Y-m-d', strtotime('-1 day')));

        $expected = ['1'];
        self::assertEquals($expected, $result);
    }

    public function testGetLabelList()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Source($this->SourceStorage, $this->DataPacker);
        $result = $Source->getLabelList();

        $expected = ['label1', 'label2'];
        self::assertEquals($expected, $result);
    }

    public function testGetAppList()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\Source($this->SourceStorage, $this->DataPacker);
        $result = $Source->getAppList();

        $expected = ['app1', 'app2'];
        self::assertEquals($expected, $result);
    }
}
