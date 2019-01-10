<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class DateGeneratorTest extends \unit\Badoo\BaseTestCase
{
    public function providerGetDatesArray()
    {
        return [
            [
                1,
                1,
                ['2019-01-10']
            ],
            [
                2,
                3,
                ['2019-01-09', '2019-01-10']
            ],
            [
                0,
                0,
                []
            ],
        ];
    }

    /**
     * @dataProvider providerGetDatesArray
     * @param $interval_in_days
     * @param $count
     * @param $expected
     */
    public function testGetDatesArray($interval_in_days, $count, $expected)
    {
        $result = \Badoo\LiveProfilerUI\DateGenerator::getDatesArray('2019-01-10', $interval_in_days, $count);

        self::assertEquals($expected, $result);
    }
}
