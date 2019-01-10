<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class ProfileListPageTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testGetTemplateData()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList(['wt', 'ct'], [], []);

        $snapshots = ['snapshot'];
        $apps = ['app'];
        $SnapshotMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Snapshot::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getAppList'])
            ->getMock();
        $SnapshotMock->expects($this->once())->method('getList')->willReturn($snapshots);
        $SnapshotMock->expects($this->once())->method('getAppList')->willReturn($apps);

        $source_apps = ['app'];
        $source_labels = ['label'];
        $SourceMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Source::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLabelList', 'getAppList'])
            ->getMock();
        $SourceMock->expects($this->once())->method('getLabelList')->willReturn($source_labels);
        $SourceMock->expects($this->once())->method('getAppList')->willReturn($source_apps);

        $data = [
            'app' => 'app',
            'label' => 'label',
            'date' => 'date',
        ];

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileListPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();
        $this->setProtectedProperty($PageMock, 'FieldList', $FieldList);
        $this->setProtectedProperty($PageMock, 'Source', $SourceMock);
        $this->setProtectedProperty($PageMock, 'Snapshot', $SnapshotMock);
        $PageMock->setData($data);

        $result = $this->invokeMethod($PageMock, 'getTemplateData');

        $expected = [
            'app' => 'app',
            'label' => 'label',
            'date' => 'date',
            'apps' => $apps,
            'source_apps' => $source_apps,
            'source_labels' => $source_labels,
            'results' => $snapshots,
            'fields' => ['ct' => 'ct', 'wt' => 'wt'],
            'field_descriptions' => []
        ];
        static::assertEquals($expected, $result);
    }

    /**
     * @throws \Exception
     */
    public function testRender()
    {
        $ViewMock = $this->getMockBuilder(\get_class(self::$Container->get('view')))
            ->disableOriginalConstructor()
            ->setMethods(['fetchFile'])
            ->getMock();
        $ViewMock->method('fetchFile')->will($this->returnArgument(1));

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemplateData'])
            ->getMock();
        $PageMock->expects($this->once())->method('getTemplateData')->willReturn(['a' => 'b']);
        $this->setProtectedProperty($PageMock, 'View', $ViewMock);

        /** @var \Badoo\LiveProfilerUI\Pages\BasePage $PageMock */
        $result = $PageMock->render();
        static::assertEquals(['a' => 'b'], $result);
    }

    /**
     * @throws \Exception
     */
    public function testRenderError()
    {
        $ViewMock = $this->getMockBuilder(\get_class(self::$Container->get('view')))
            ->disableOriginalConstructor()
            ->setMethods(['fetchFile'])
            ->getMock();
        $ViewMock->method('fetchFile')->will($this->returnArgument(1));

        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemplateData'])
            ->getMock();
        $PageMock->expects($this->once())->method('getTemplateData')->willReturnCallback(function () {
            throw new \RuntimeException('Some error');
        });
        $this->setProtectedProperty($PageMock, 'View', $ViewMock);

        /** @var \Badoo\LiveProfilerUI\Pages\BasePage $PageMock */
        $result = $PageMock->render();
        static::assertEquals(['error' => 'Some error'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCleanData()
    {
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\ProfileListPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        /** @var \Badoo\LiveProfilerUI\Pages\ProfileListPage $PageMock */
        $PageMock->setData(['app' => ' app ', 'label' => ' label ', 'date' => ' date ']);
        $this->invokeMethod($PageMock, 'cleanData');

        $data = $this->getProtectedProperty($PageMock, 'data');

        $expected = ['app' => 'app', 'label' => 'label', 'date' => 'date'];
        self::assertEquals($expected, $data);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstruct()
    {
        $FieldList = new \Badoo\LiveProfilerUI\FieldList([], [], []);

        /** @var \Badoo\LiveProfilerUI\DataProviders\Source $SourceMock */
        $SourceMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\DataProviders\Source::class)
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

        $Page = new \Badoo\LiveProfilerUI\Pages\ProfileListPage($ViewMock, $SourceMock, $SnapshotMock, $FieldList);

        $View = $this->getProtectedProperty($Page, 'View');
        $Source = $this->getProtectedProperty($Page, 'Source');
        $Snapshot = $this->getProtectedProperty($Page, 'Snapshot');
        $FieldListNew = $this->getProtectedProperty($Page, 'FieldList');

        self::assertSame($ViewMock, $View);
        self::assertSame($SourceMock, $Source);
        self::assertSame($SnapshotMock, $Snapshot);
        self::assertSame($FieldList, $FieldListNew);
    }
}
