<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\Entity;

class SnapshotTest extends \unit\Badoo\BaseTestCase
{
    public function testValues()
    {
        $Snapshot = new \Badoo\LiveProfilerUI\Entity\Snapshot([], []);

        $empty_values = $Snapshot->getValues();
        $empty_formatted_values = $Snapshot->getFormattedValues();
        self::assertEquals([], $empty_values);
        self::assertEquals([], $empty_formatted_values);

        $Snapshot->setValues(['wt' => 1, 'ct' => null]);

        $new_values = $Snapshot->getValues();
        $new_formatted_values = $Snapshot->getFormattedValues();
        self::assertEquals(['wt' => 1, 'ct' => null], $new_values);
        self::assertEquals(['wt' => '1', 'ct' => '-'], $new_formatted_values);
    }

    public function testGetters()
    {
        $data = [
            'id' => '1',
            'calls_count' => '2',
            'app' => ' app ',
            'label' => ' label ',
            'date' => ' date ',
            'type' => ' type '
        ];
        $Snapshot = new \Badoo\LiveProfilerUI\Entity\Snapshot($data, []);

        self::assertEquals(1, $Snapshot->getId());
        self::assertEquals('app', $Snapshot->getApp());
        self::assertEquals('label', $Snapshot->getLabel());
        self::assertEquals('date', $Snapshot->getDate());
        self::assertEquals('type', $Snapshot->getType());
        self::assertEquals(2, $Snapshot->getCallsCount());
    }
}
