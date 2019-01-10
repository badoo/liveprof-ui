<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class LoggerTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetLogMsg()
    {
        $Logger = new \Badoo\LiveProfilerUI\Logger();
        $log_msg = $this->invokeMethod($Logger, 'getLogMsg', ['error', 'Error msg', ['param' => 1]]);
        self::assertEquals(date('Y-m-d H:i:s'). "\terror\tError msg\t{\"param\":1}\n", $log_msg);
    }

    public function testLog()
    {
        $tmp_log_file = tempnam('/tmp', 'live.profiling');
        $Logger = new \Badoo\LiveProfilerUI\Logger($tmp_log_file);
        $Logger->log('error', 'Error msg');

        $log_msg = file_get_contents($tmp_log_file);
        unset($tmp_log_file);

        self::assertEquals(date('Y-m-d H:i:s'). "\terror\tError msg\n", $log_msg);
    }

    public function testLogSetFile()
    {
        $tmp_log_file = tempnam('/tmp', 'live.profiling');
        $Logger = new \Badoo\LiveProfilerUI\Logger();
        $Logger->setLogFile($tmp_log_file);
        $Logger->log('error', 'Error msg');

        $log_msg = file_get_contents($tmp_log_file);
        unset($tmp_log_file);

        self::assertEquals(date('Y-m-d H:i:s'). "\terror\tError msg\n", $log_msg);
    }
}
