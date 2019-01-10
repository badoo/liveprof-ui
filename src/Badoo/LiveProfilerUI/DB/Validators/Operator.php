<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB\Validators;

use Badoo\LiveProfilerUI\Exceptions\InvalidOperatorException;

class Operator
{
    /** @var string[] */
    private static $allowed_operators = [
        '=',
        'like',
        '!=',
        '<',
        '>',
        '>=',
        '<=',
    ];

    public static function validate(string $operator) : bool
    {
        if (!\in_array($operator, self::$allowed_operators, true)) {
            throw new InvalidOperatorException('Invalid operator: ' . $operator);
        }

        return true;
    }
}
