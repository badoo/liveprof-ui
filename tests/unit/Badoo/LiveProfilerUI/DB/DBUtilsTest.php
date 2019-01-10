<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

class DBUtilsTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @throws \Exception
     */
    public function testPrepareCreateTablesSqlite()
    {
        $sql = 'create table t (%SNAPSHOT_CUSTOM_FIELDS% %DATA_CUSTOM_FIELDS% %TREE_CUSTOM_FIELDS%)';
        $result = \Badoo\LiveProfilerUI\DB\SqlTableBuilder::prepareCreateTables('pdo_sqlite', $sql, ['wt', 'ct', 'ct_min'], 'ct');

        $expected = <<<SQL
create table t (wt INTEGER  DEFAULT NULL,
 wt INTEGER  DEFAULT NULL,
ct REAL DEFAULT NULL,
ct_min INTEGER  DEFAULT NULL,
 wt INTEGER  DEFAULT NULL,
ct REAL DEFAULT NULL,
ct_min INTEGER  DEFAULT NULL,
)
SQL;
        self::assertEquals($expected, $result);
    }

    /**
     * @throws \Exception
     */
    public function testPrepareCreateTablesMysql()
    {
        $sql = 'create table t (%SNAPSHOT_CUSTOM_FIELDS% %DATA_CUSTOM_FIELDS% %TREE_CUSTOM_FIELDS%)';
        $result = \Badoo\LiveProfilerUI\DB\SqlTableBuilder::prepareCreateTables('pdo_mysql', $sql, ['wt', 'ct', 'ct_min'], 'ct');

        $expected = <<<SQL
create table t (`wt` int(11) unsigned DEFAULT NULL,
 `wt` int(11) unsigned DEFAULT NULL,
`ct` float DEFAULT NULL,
`ct_min` int(11) unsigned DEFAULT NULL,
 `wt` int(11) unsigned DEFAULT NULL,
`ct` float DEFAULT NULL,
`ct_min` int(11) unsigned DEFAULT NULL,
)
SQL;
        self::assertEquals($expected, $result);
    }

    /**
     * @throws \Exception
     */
    public function testPrepareCreateTablesPgsql()
    {
        $sql = 'create table t (%SNAPSHOT_CUSTOM_FIELDS% %DATA_CUSTOM_FIELDS% %TREE_CUSTOM_FIELDS%)';
        $result = \Badoo\LiveProfilerUI\DB\SqlTableBuilder::prepareCreateTables('pdo_pgsql', $sql, ['wt', 'ct', 'ct_min'], 'ct');

        $expected = <<<SQL
create table t (wt INT DEFAULT NULL wt INT DEFAULT NULL,
ct REAL DEFAULT NULL,
ct_min INT DEFAULT NULL wt INT DEFAULT NULL,
ct REAL DEFAULT NULL,
ct_min INT DEFAULT NULL)
SQL;
        self::assertEquals($expected, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Not supported db type: invalid
     */
    public function testPrepareCreateTablesInvalidType()
    {
        \Badoo\LiveProfilerUI\DB\SqlTableBuilder::prepareCreateTables('invalid', '', ['wt'], 'ct');
    }
}
