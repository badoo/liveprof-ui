<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\Entity;

class MethodTreeTest extends \unit\Badoo\BaseTestCase
{
    public function testGetters()
    {
        $MethodTree = new \Badoo\LiveProfilerUI\Entity\MethodTree([], []);

        $empty_parent_id = $MethodTree->getParentId();
        self::assertEquals(0, $empty_parent_id);

        $MethodTree->setParentId(1);
        $new_parent_id = $MethodTree->getParentId();
        self::assertEquals(1, $new_parent_id);
    }
}
