<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB;

class SqlTableBuilder
{
    public static function prepareCreateTables(
        string $db_type,
        string $sql,
        array $custom_fields,
        string $call_count_field
    ) : string {
        switch ($db_type) {
            case 'mysqli':
            case 'pdo_mysql':
                return self::mysql($sql, $custom_fields, $call_count_field);
            case 'pdo_pgsql':
                return self::pgsql($sql, $custom_fields, $call_count_field);
            case 'pdo_sqlite':
                return self::sqlite($sql, $custom_fields, $call_count_field);
            default:
                throw new \InvalidArgumentException('Not supported db type: ' . $db_type);
        }
    }

    public static function mysql(string $sql, array $custom_fields, string $call_count_field) : string
    {
        $snapshot_sql = '';
        $data_sql = '';
        $tree_sql = '';
        foreach ($custom_fields as $custom_field) {
            if ($custom_field === $call_count_field) {
                $data_sql .= "`{$custom_field}` float DEFAULT NULL,\n";
                $tree_sql .= "`{$custom_field}` float DEFAULT NULL,\n";
            } elseif (strpos($custom_field, $call_count_field) !== false) {
                $data_sql .= "`{$custom_field}` int(11) unsigned DEFAULT NULL,\n";
                $tree_sql .= "`{$custom_field}` int(11) unsigned DEFAULT NULL,\n";
            } else {
                $snapshot_sql .= "`{$custom_field}` int(11) unsigned DEFAULT NULL,\n";
                $data_sql .= "`{$custom_field}` int(11) unsigned DEFAULT NULL,\n";
                $tree_sql .= "`{$custom_field}` int(11) unsigned DEFAULT NULL,\n";
            }
        }
        $sql = str_replace(
            ['%SNAPSHOT_CUSTOM_FIELDS%', '%DATA_CUSTOM_FIELDS%', '%TREE_CUSTOM_FIELDS%'],
            [$snapshot_sql, $data_sql, $tree_sql],
            $sql
        );
        return $sql;
    }

    public static function pgsql(string $sql, array $custom_fields, string $call_count_field) : string
    {
        $snapshot_sql = [];
        $data_sql = [];
        $tree_sql = [];
        foreach ($custom_fields as $custom_field) {
            if ($custom_field === $call_count_field) {
                $data_sql[] = "{$custom_field} REAL DEFAULT NULL";
                $tree_sql[] = "{$custom_field} REAL DEFAULT NULL";
            } elseif (strpos($custom_field, $call_count_field) !== false) {
                $data_sql[] = "{$custom_field} INT DEFAULT NULL";
                $tree_sql[] = "{$custom_field} INT DEFAULT NULL";
            } else {
                $snapshot_sql[] = "{$custom_field} INT DEFAULT NULL";
                $data_sql[] = "{$custom_field} INT DEFAULT NULL";
                $tree_sql[] = "{$custom_field} INT DEFAULT NULL";
            }
        }
        $snapshot_sql = implode(",\n", $snapshot_sql);
        $data_sql = implode(",\n", $data_sql);
        $tree_sql = implode(",\n", $tree_sql);

        $sql = str_replace(
            ['%SNAPSHOT_CUSTOM_FIELDS%', '%DATA_CUSTOM_FIELDS%', '%TREE_CUSTOM_FIELDS%'],
            [$snapshot_sql, $data_sql, $tree_sql],
            $sql
        );
        return $sql;
    }

    public static function sqlite(string $sql, array $custom_fields, string $call_count_field) : string
    {
        $snapshot_sql = '';
        $data_sql = '';
        $tree_sql = '';
        foreach ($custom_fields as $custom_field) {
            if ($custom_field === $call_count_field) {
                $data_sql .= "{$custom_field} REAL DEFAULT NULL,\n";
                $tree_sql .= "{$custom_field} REAL DEFAULT NULL,\n";
            } elseif (strpos($custom_field, $call_count_field) !== false) {
                $data_sql .= "{$custom_field} INTEGER  DEFAULT NULL,\n";
                $tree_sql .= "{$custom_field} INTEGER  DEFAULT NULL,\n";
            } else {
                $snapshot_sql .= "{$custom_field} INTEGER  DEFAULT NULL,\n";
                $data_sql .= "{$custom_field} INTEGER  DEFAULT NULL,\n";
                $tree_sql .= "{$custom_field} INTEGER  DEFAULT NULL,\n";
            }
        }
        $sql = str_replace(
            ['%SNAPSHOT_CUSTOM_FIELDS%', '%DATA_CUSTOM_FIELDS%', '%TREE_CUSTOM_FIELDS%'],
            [$snapshot_sql, $data_sql, $tree_sql],
            $sql
        );
        return $sql;
    }
}
