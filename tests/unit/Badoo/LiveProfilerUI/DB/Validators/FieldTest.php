<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

use Badoo\LiveProfilerUI\DB\Validators\Field;

class FieldTest extends \unit\Badoo\BaseTestCase
{
    public function testValidate()
    {
        $field = '_abc_';
        $result = Field::validate($field);

        self::assertTrue($result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid field: _123_
     */
    public function testValidateError()
    {
        $field = '_123_';
        Field::validate($field);
    }
}
