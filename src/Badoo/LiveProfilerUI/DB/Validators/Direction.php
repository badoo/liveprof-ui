<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB\Validators;

use Badoo\LiveProfilerUI\Exceptions\InvalidOrderDirectionException;

class Direction
{
    public static function validate(string $direction) : bool
    {
        if (!\in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidOrderDirectionException('Invalid order direction: ' . $direction);
        }

        return true;
    }
}
