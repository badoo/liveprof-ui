<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB\Validators;

use Badoo\LiveProfilerUI\Exceptions\InvalidFieldNameException;

class Field
{
    public static function validate(string $field) : bool
    {
        if (!preg_match('/^[_a-z]+$/', $field)) {
            throw new InvalidFieldNameException('Invalid field: ' . $field);
        }

        return true;
    }
}
