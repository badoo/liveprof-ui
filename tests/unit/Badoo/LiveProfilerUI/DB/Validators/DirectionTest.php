<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

use Badoo\LiveProfilerUI\DB\Validators\Direction;

class DirectionTest extends \unit\Badoo\BaseTestCase
{
    public function testValidate()
    {
        $direction = 'desc';
        $result = Direction::validate($direction);

        self::assertTrue($result);
    }

    public function testValidateError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order direction: Invalid');
        $direction = 'Invalid';
        Direction::validate($direction);
    }
}
