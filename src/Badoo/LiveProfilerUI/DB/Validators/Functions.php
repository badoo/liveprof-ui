<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB\Validators;

use Badoo\LiveProfilerUI\Exceptions\InvalidFunctionNameException;

class Functions
{
    /** @var string[] */
    protected static $allowed_functions = [
        'sum',
        'date',
        'max',
        'min',
    ];

    public static function validate(string $function) : bool
    {
        if (!\in_array($function, self::$allowed_functions, true)) {
            throw new InvalidFunctionNameException('Invalid function name: ' . $function);
        }

        return true;
    }
}
