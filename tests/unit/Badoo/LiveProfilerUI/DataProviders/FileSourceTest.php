<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DataProviders;

class FileSourceTest extends \unit\Badoo\BaseTestCase
{
    protected $path = '/tmp/FileSourceTest';
    /** @var \Badoo\LiveProfilerUI\DB\Storage */
    protected $SourceStorage;
    /** @var \Badoo\LiveProfilerUI\Interfaces\DataPackerInterface */
    protected $DataPacker;

    protected function setUp(): void
    {
        parent::setUp();

        !is_dir($this->path) && mkdir($this->path);

        $profiles = [
            [
                'id' => 1,
                'app' => 'app1',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 01:00:00', strtotime('-1 day')),
                'perfdata' => '1'
            ],
            [
                'app' => 'app2',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 02:00:00', strtotime('-1 day')),
                'perfdata' => '2'
            ],
            [
                'app' => 'app2',
                'label' => 'label2',
                'timestamp' => date('Y-m-d 03:00:00'),
                'perfdata' => '3'
            ],
            [
                'app' => 'app2',
                'label' => 'label1',
                'timestamp' => date('Y-m-d 04:00:00'),
                'perfdata' => '4'
            ]
        ];
        foreach ($profiles as $profile) {
            $app_dir = $this->path . '/' . $profile['app'];
            !is_dir($app_dir) && mkdir($app_dir);

            $label_dir = $app_dir . '/' . base64_encode($profile['label']);
            !is_dir($label_dir) && mkdir($label_dir);

            $filename = $label_dir . '/' . strtotime($profile['timestamp']) . '.json';
            file_put_contents($filename, $profile['perfdata']);
        }

        $this->DataPacker = new \Badoo\LiveProfilerUI\DataPacker();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDirectory($this->path);
    }

    protected function deleteDirectory(string $dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    public function testGetSnapshotsDataByDates()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\FileSource($this->path, $this->DataPacker);
        $result = $Source->getSnapshotsDataByDates(date('Y-m-d', strtotime('-1 day')), date('Y-m-d'));

        $date = date('Y-m-d', strtotime('-1 day'));
        $expected = [
            'app1|label1|' . $date => [
                'app' => 'app1',
                'label' => 'label1',
                'date' => $date
            ],
            'app2|label1|' . $date => [
                'app' => 'app2',
                'label' => 'label1',
                'date' => $date
            ],
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetPerfDataEmptyData()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\FileSource($this->path, $this->DataPacker);
        $result = $Source->getPerfData('new_app', 'new_label', date('Y-m-d', strtotime('-1 day')));

        $expected = [];
        self::assertEquals($expected, $result);
    }

    public function testGetPerfData()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\FileSource($this->path, $this->DataPacker);
        $result = $Source->getPerfData('app1', 'label1', date('Y-m-d', strtotime('-1 day')));

        $expected = ['1'];
        self::assertEquals($expected, $result);
    }

    public function testGetLabelList()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\FileSource($this->path, $this->DataPacker);
        $result = $Source->getLabelList();

        $expected = ['label1', 'label2'];
        self::assertEquals($expected, $result);
    }

    public function testGetAppList()
    {
        $Source = new \Badoo\LiveProfilerUI\DataProviders\FileSource($this->path, $this->DataPacker);
        $result = $Source->getAppList();

        $expected = ['app1', 'app2'];
        self::assertEquals($expected, $result);
    }
}
