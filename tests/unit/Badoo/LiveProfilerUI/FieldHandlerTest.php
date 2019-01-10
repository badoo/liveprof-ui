<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class FieldHandlerTest extends \unit\Badoo\BaseTestCase
{
    public function providerPercent() : array
    {
        return [
            [
                'array' => [],
                'expected' => null
            ],
            [
                'array' => range(0, 100),
                'expected' => 95
            ],
            [
                'array' => range(0, 99),
                'expected' => 94.5
            ]
        ];
    }

    /**
     * @dataProvider providerPercent
     * @param $array
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetPercentil($array, $expected)
    {
        $FieldHandler = new \Badoo\LiveProfilerUI\FieldHandler();

        $result = $this->invokeMethod($FieldHandler, 'percent', [$array]);

        self::assertEquals($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testAvg()
    {
        $array = [1, 2, 3];
        $FieldHandler = new \Badoo\LiveProfilerUI\FieldHandler();

        $result = $this->invokeMethod($FieldHandler, 'avg', [$array]);

        $expected = 2.0;
        self::assertEquals($expected, $result);
    }

    public function testHandleEmpty()
    {
        $FieldHandler = new \Badoo\LiveProfilerUI\FieldHandler();

        $result = $FieldHandler->handle('min', []);

        self::assertEquals(null, $result);
    }

    public function testHandleNotExistsFunction()
    {
        $array = [1, 2, 3];
        $FieldHandler = new \Badoo\LiveProfilerUI\FieldHandler();

        $result = $FieldHandler->handle('invalid_function', $array);

        self::assertEquals(null, $result);
    }
}
