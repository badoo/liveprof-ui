<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

use Badoo\LiveProfilerUI\DB\Validators\Operator;

class OperatorTest extends \unit\Badoo\BaseTestCase
{
    public function testValidate()
    {
        $operator = '>';
        $result = Operator::validate($operator);;

        self::assertTrue($result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid operator: !!
     */
    public function testValidateError()
    {
        $operator = '!!';
        Operator::validate($operator);
    }
}
