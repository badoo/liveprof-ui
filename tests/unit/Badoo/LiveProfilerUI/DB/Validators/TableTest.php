<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

use Badoo\LiveProfilerUI\DB\Validators\Table;

class TableTest extends \unit\Badoo\BaseTestCase
{
    public function testValidate()
    {
        $table = 'aggregator_snapshots';
        $result = Table::validate($table);

        self::assertTrue($result);
    }

    public function testValidateError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name: table');
        $table = 'table';
        Table::validate($table);
    }
}
