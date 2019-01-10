<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB\Validators;

use Badoo\LiveProfilerUI\Exceptions\InvalidTableNameException;

class Table
{
    /** @var string[] */
    protected static $allowed_table_names = [
        'details',
        'aggregator_snapshots',
        'aggregator_tree',
        'aggregator_method_data',
        'aggregator_metods',
        'aggregator_jobs',
    ];

    public static function validate(string $table) : bool
    {
        if (!\in_array($table, self::$allowed_table_names, true)) {
            throw new InvalidTableNameException('Invalid table name: ' . $table);
        }

        return true;
    }
}
