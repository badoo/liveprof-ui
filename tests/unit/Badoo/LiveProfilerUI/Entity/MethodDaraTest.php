<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\Entity;

class MethodDaraTest extends \unit\Badoo\BaseTestCase
{
    public function testGetters()
    {
        $data = [
            'snapshot_id' => '1',
            'method_id' => '2',
            'method_name' => ' this is a very long method name with length more than 60 characters ',
            'wt' => 123,
            'ct' => null,
        ];
        $MethodData = new \Badoo\LiveProfilerUI\Entity\MethodData($data, ['ct' => 'ct', 'wt' => 'wt']);

        self::assertEquals(1, $MethodData->getSnapshotId());
        self::assertEquals(2, $MethodData->getMethodId());
        self::assertEquals('ng method name with length more than 60 characters', $MethodData->getMethodName());
        self::assertEquals(
            'this is a very long method name with length more than 60 characters',
            $MethodData->getMethodNameAlt()
        );
        self::assertEquals(['wt' => 123, 'ct' => null], $MethodData->getValues());
        self::assertEquals(['wt' => '123', 'ct' => '-'], $MethodData->getFormattedValues());
    }

    public function testValue()
    {
        $MethodData = new \Badoo\LiveProfilerUI\Entity\MethodData([], []);

        $empty_value = $MethodData->getValue('wt');
        $empty_formatted_value = $MethodData->getFormattedValue('wt');
        self::assertEquals(0, $empty_value);
        self::assertEquals('-', $empty_formatted_value);

        $MethodData->setValue('wt', 123);

        $new_value = $MethodData->getValue('wt');
        $new_formatted_value = $MethodData->getValue('wt');
        self::assertEquals(123, $new_value);
        self::assertEquals('123', $new_formatted_value);
    }

    public function testHistoryData()
    {
        $MethodData = new \Badoo\LiveProfilerUI\Entity\MethodData([], []);

        $empty_value = $MethodData->getHistoryData();
        self::assertEquals([], $empty_value);

        $MethodData->setHistoryData(['data']);

        $new_value = $MethodData->getHistoryData();
        self::assertEquals(['data'], $new_value);
    }

    public function testJsonEncode()
    {
        $MethodData = new \Badoo\LiveProfilerUI\Entity\MethodData([], []);

        $json = json_encode($MethodData);

        self::assertNotEmpty($json);
    }
}
