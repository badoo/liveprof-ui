<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class LiveProfilerUITest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetContainer()
    {
        $LiveProfilerUI = new \Badoo\LiveProfilerUI\LiveProfilerUI();

        $Container1 = $this->invokeMethod($LiveProfilerUI, 'getContainer');
        $Container2 = $this->invokeMethod($LiveProfilerUI, 'getContainer');

        self::assertSame($Container1, $Container2);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetContainerEnv()
    {
        putenv('AGGREGATOR_CONFIG_PATH=' . __DIR__ . '/../../../../src/config/services.yaml');

        $LiveProfilerUI = new \Badoo\LiveProfilerUI\LiveProfilerUI();

        $Container1 = $this->invokeMethod($LiveProfilerUI, 'getContainer');
        $Container2 = $this->invokeMethod($LiveProfilerUI, 'getContainer');

        self::assertSame($Container1, $Container2);

        putenv('AGGREGATOR_CONFIG_PATH=');
    }
}
