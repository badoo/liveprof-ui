<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\Entity;

class TopDiffTest extends \unit\Badoo\BaseTestCase
{
    public function testApp()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_app = $TopDiff->getApp();
        self::assertEquals('', $empty_app);

        $TopDiff->setApp('new app');
        $new_app = $TopDiff->getApp();
        self::assertEquals('new app', $new_app);
    }

    public function testLabel()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_label = $TopDiff->getLabel();
        self::assertEquals('', $empty_label);

        $TopDiff->setLabel('new label');
        $new_label = $TopDiff->getLabel();
        self::assertEquals('new label', $new_label);
    }

    public function testMethodId()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_method_id = $TopDiff->getMethodId();
        self::assertEquals(0, $empty_method_id);

        $TopDiff->setMethodId(1);
        $new_method_id = $TopDiff->getMethodId();
        self::assertEquals(1, $new_method_id);
    }

    public function testMethodName()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_method_name = $TopDiff->getMethodName();
        self::assertEquals('', $empty_method_name);

        $TopDiff->setMethodName('new method name');
        $new_method_name = $TopDiff->getMethodName();
        self::assertEquals('new method name', $new_method_name);
    }

    public function testValue()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_value = $TopDiff->getValue();
        $empty_formatted_value = $TopDiff->getFormattedValue();
        self::assertEquals(0, $empty_value);
        self::assertEquals(0, $empty_formatted_value);

        $TopDiff->setValue(1.0);
        $new_value = $TopDiff->getValue();
        $new_formatted_value = $TopDiff->getFormattedValue();
        self::assertEquals(1.0, $new_value);
        self::assertEquals(1.0, $new_formatted_value);
    }

    public function testPercent()
    {
        $TopDiff = new \Badoo\LiveProfilerUI\Entity\TopDiff([]);

        $empty_percent = $TopDiff->getPercent();
        self::assertEquals(0, $empty_percent);

        $TopDiff->setPercent(1);
        $new_percent = $TopDiff->getPercent();
        self::assertEquals(1, $new_percent);
    }
}
