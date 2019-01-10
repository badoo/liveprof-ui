<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\Entity;

class JobTest extends \unit\Badoo\BaseTestCase
{
    public function testGetters()
    {
        $data = [
            'id' => '1',
            'app' => ' app ',
            'label' => ' label ',
            'date' => ' date ',
            'type' => ' type ',
            'status' => ' status ',
        ];
        $Job = new \Badoo\LiveProfilerUI\Entity\Job($data);

        self::assertEquals(1, $Job->getId());
        self::assertEquals('app', $Job->getApp());
        self::assertEquals('label', $Job->getLabel());
        self::assertEquals('date', $Job->getDate());
        self::assertEquals('type', $Job->getType());
        self::assertEquals('status', $Job->getStatus());

        $Job->setId(2);
        $Job->setApp('new app');
        $Job->setLabel('new label');
        $Job->setDate('new date');
        $Job->setType('new type');
        $Job->setStatus('new status');

        self::assertEquals(2, $Job->getId());
        self::assertEquals('new app', $Job->getApp());
        self::assertEquals('new label', $Job->getLabel());
        self::assertEquals('new date', $Job->getDate());
        self::assertEquals('new type', $Job->getType());
        self::assertEquals('new status', $Job->getStatus());
    }
}
