<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class DataPackerTest extends \unit\Badoo\BaseTestCase
{
    public function testPack()
    {
        $data = ['a' => 1];
        $Packer = new \Badoo\LiveProfilerUI\DataPacker();

        $result = $Packer->pack($data);

        static::assertEquals(json_encode($data), $result);
    }

    /**
     * @depends testPack
     */
    public function testUnPack()
    {
        $data = ['a' => 1];
        $Packer = new \Badoo\LiveProfilerUI\DataPacker();
        $packed_data = $Packer->pack($data);

        $result = $Packer->unpack($packed_data);

        static::assertEquals(json_decode($packed_data, true), $result);
    }
}
