<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DB;

use Badoo\LiveProfilerUI\DB\Validators\Direction;
use Badoo\LiveProfilerUI\DB\Validators\Field;
use Badoo\LiveProfilerUI\DB\Validators\Functions;
use Badoo\LiveProfilerUI\DB\Validators\Operator;
use Badoo\LiveProfilerUI\DB\Validators\Table;
use Badoo\LiveProfilerUI\Exceptions\DatabaseException;
use Badoo\LiveProfilerUI\Exceptions\InvalidFieldValueException;
use Badoo\LiveProfilerUI\Interfaces\StorageInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;

class Storage implements StorageInterface
{
    /** @var \Doctrine\DBAL\Connection */
    protected $Conn;

    /** @var int */
    protected $lastInsertId = 0;

    /**
     * Storage constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = ['url' => $url];

        try {
            $this->Conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        } catch (\Throwable $Ex) {
            throw new DatabaseException('Can\'t connect to db server: ' . $Ex->getMessage());
        }

        if ($this->Conn->errorCode() && $this->Conn->errorCode() !== '00000') {
            throw new DatabaseException('Can\'t connect to db server: ' . print_r($this->Conn->errorInfo(), true));
        }
    }

    public function getType() : string
    {
        return $this->Conn->getDriver()->getName();
    }

    public function getAll(string $table, array $fields, array $params) : array
    {
        $Result = $this->getSelectQueryBuilder($table, $fields, $params)->execute();
        if (!\is_object($Result)) {
            throw new DatabaseException(
                'Can\'t get data from ' . $table . ': ' . print_r($this->Conn->errorInfo(), true)
            );
        }

        return $Result->fetchAll() ?: [];
    }

    public function getOne(string $table, array $fields, array $params) : array
    {
        $params['limit'] = 1;
        $Result = $this->getSelectQueryBuilder($table, $fields, $params)->execute();
        if (!\is_object($Result)) {
            throw new DatabaseException(
                'Can\'t get data from ' . $table . ': ' . print_r($this->Conn->errorInfo(), true)
            );
        }

        return $Result->fetch() ?: [];
    }

    protected function getSelectQueryBuilder(string $table, array $fields, array $params) : QueryBuilder
    {
        Table::validate($table);

        $QueryBuilder = $this->Conn->createQueryBuilder();
        $QueryBuilder->select($this->prepareFieldList($fields))->from($table);

        if (!empty($params['filter'])) {
            $this->fillQueryBuilderFilter($QueryBuilder, $params['filter']);
        }

        if (!empty($params['group'])) {
            $this->fillQueryBuilderGroup($QueryBuilder, $params['group']);
        }

        if (!empty($params['having'])) {
            $this->fillQueryBuilderHaving($QueryBuilder, $params['having']);
        }

        if (!empty($params['order'])) {
            $this->fillQueryBuilderOrder($QueryBuilder, $params['order']);
        }

        if (!empty($params['limit'])) {
            $this->fillQueryBuilderLimit($QueryBuilder, (int)$params['limit']);
        }

        return $QueryBuilder;
    }

    protected function fillQueryBuilderFilter(QueryBuilder $QueryBuilder, array $filter)
    {
        $is_first = true;
        foreach ($filter as $param) {
            if (empty($param[2])) {
                $param[2] = '=';
            } else {
                Operator::validate($param[2]);
            }
            list($field_name, $value, $operator) = $param;

            if ($field_name === 'union') {
                $parts = [];
                foreach ($value as $item) {
                    $sub_parts = [];
                    foreach ($item as list($union_field_name, $union_value)) {
                        $named_param = $QueryBuilder->createNamedParameter($union_value);
                        $sub_parts[] = $QueryBuilder->expr()->eq($union_field_name, $named_param);
                        $QueryBuilder->setParameter($named_param, $union_value);
                    }
                    $parts[] = $QueryBuilder->expr()->andX(...$sub_parts);
                }
                $where_expr = $QueryBuilder->expr()->orX(...$parts);
            } elseif (\is_array($value)) {
                $named_params = [];
                foreach ($value as $item) {
                    $named_param = $QueryBuilder->createNamedParameter($item);
                    $named_params[] = $named_param;
                    $QueryBuilder->setParameter($named_param, $item);
                }
                $where_expr = $field_name . ' IN (' . implode(',', $named_params) . ')';
            } else {
                if ($operator === 'like') {
                    $value = $this->prepareLikeValue($value);
                }

                $named_param = $QueryBuilder->createNamedParameter($value);
                $QueryBuilder->setParameter($named_param, $value);
                $where_expr = $QueryBuilder->expr()->comparison($field_name, $operator, $named_param);
            }

            if ($is_first) {
                $QueryBuilder->where($where_expr);
                $is_first = false;
            } else {
                $QueryBuilder->andWhere($where_expr);
            }
        }
    }

    protected function prepareLikeValue(string $value) : string
    {
        if ($this->Conn->getDriver()->getName() !== 'pdo_sqlite') {
            $value = addcslashes($value, '%_\\');
        }
        $value = '%' . $value . '%';

        return $value;
    }

    protected function fillQueryBuilderGroup(QueryBuilder $QueryBuilder, array $group)
    {
        $is_first = true;
        foreach ($group as $field) {
            Field::validate($field);
            if ($is_first) {
                $QueryBuilder->groupBy($field);
                $is_first = false;
            } else {
                $QueryBuilder->addGroupBy($field);
            }
        }
    }

    protected function fillQueryBuilderHaving(QueryBuilder $QueryBuilder, array $havings)
    {
        $is_first = true;
        foreach ($havings as $having) {
            if (empty($having[2])) {
                $having[2] = '=';
            } else {
                Operator::validate($having[2]);
            }
            list($field_name, $value, $operator) = $having;
            $named_param = $QueryBuilder->createNamedParameter($value);
            $having_expr = $QueryBuilder->expr()->comparison($field_name, $operator, $named_param);
            if ($is_first) {
                $QueryBuilder->having($having_expr);
                $is_first = false;
            } else {
                $QueryBuilder->andHaving($having_expr);
            }
            $QueryBuilder->setParameter($named_param, $value);
        }
    }

    protected function fillQueryBuilderOrder(QueryBuilder $QueryBuilder, array $order)
    {
        $is_first = true;
        foreach ($order as $field => $direction) {
            Field::validate($field);
            Direction::validate($direction);
            if ($is_first) {
                $QueryBuilder->orderBy($field, $direction);
                $is_first = false;
            } else {
                $QueryBuilder->addOrderBy($field, $direction);
            }
        }
    }

    protected function fillQueryBuilderLimit(QueryBuilder $QueryBuilder, int $limit)
    {
        $QueryBuilder->setMaxResults($limit);
    }

    /**
     * @param string $sql
     * @return \Doctrine\DBAL\Driver\Statement|false
     */
    public function query(string $sql)
    {
        try {
            return $this->Conn->query($sql);
        } catch (DBALException $Ex) {
            return false;
        }
    }

    /**
     * @param string $sql
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function multiQuery(string $sql) : bool
    {
        $queries = array_filter(array_map('trim', explode(";\n\n", $sql)));

        foreach ($queries as $query) {
            $this->Conn->exec($query);
        }

        return true;
    }

    protected function prepareFieldList(array $fields) : string
    {
        if (\in_array('all', $fields, true)) {
            return '*';
        }

        foreach ($fields as $key => $field) {
            if (\is_array($field)) {
                if (!empty($field['function'])) {
                    Functions::validate($field['function']);
                    $fields[$key] = $field['function'] . '(' . $field['field'] . ')';
                } else {
                    $fields[$key] = $field['field'];
                }
                $alias = $field['alias'] ?? $field['field'];
                Field::validate($field['field']);
                Field::validate($alias);
                $fields[$key] .= ' ' . $alias;
            } else {
                Field::validate($field);
            }
        }

        return implode(',', $fields);
    }

    public function insert(string $table, array $fields) : int
    {
        $this->insertMany($table, [$fields]);

        return $this->getLastInsertId();
    }

    protected function getLastInsertId() : int
    {
        return $this->lastInsertId;
    }

    protected function setLastInsertId(int $lastInsertId)
    {
        $this->lastInsertId = $lastInsertId;
    }

    public function insertMany(string $table, array $fields) : bool
    {
        Table::validate($table);

        if (empty($fields)) {
            throw new InvalidFieldValueException('Can\'t insert empty data');
        }

        try {
            $this->Conn->beginTransaction();
            foreach ($fields as $field) {
                if (!$field) {
                    throw new InvalidFieldValueException('Can\'t insert empty data');
                }

                $QueryBuilder = $this->Conn->createQueryBuilder();
                $QueryBuilder->insert($table);
                foreach ($field as $field_name => $value) {
                    $named_param = $QueryBuilder->createNamedParameter($value);
                    $QueryBuilder->setValue($field_name, $named_param);
                    $QueryBuilder->setParameter($named_param, $value);
                }
                $QueryBuilder->execute();
            }

            $this->setLastInsertId((int)$this->Conn->lastInsertId());
            $this->Conn->commit();
        } catch (DBALException $Ex) {
            throw new DatabaseException('Can\'t insert into ' . $table);
        }

        return true;
    }

    public function delete(string $table, array $params) : bool
    {
        Table::validate($table);

        if (empty($params)) {
            throw new \InvalidArgumentException('Can\'t delete without any conditions');
        }

        $QueryBuilder = $this->Conn->createQueryBuilder();
        $QueryBuilder->delete($table);

        $is_first = true;
        foreach ($params as $field => $value) {
            $named_param = $QueryBuilder->createNamedParameter($value);
            if ($is_first) {
                $QueryBuilder->where($field . ' = ' . $named_param);
                $is_first = false;
            } else {
                $QueryBuilder->andWhere($field . ' = ' . $named_param);
            }
            $QueryBuilder->setParameter($named_param, $value);
        }

        $affected_rows = $QueryBuilder->execute();

        return $affected_rows >= 0;
    }

    public function update(string $table, array $fields, array $params) : bool
    {
        Table::validate($table);

        if (empty($fields)) {
            throw new \InvalidArgumentException('Can\'t update without any fields');
        }

        if (empty($params)) {
            throw new \InvalidArgumentException('Can\'t update without any conditions');
        }

        $QueryBuilder = $this->Conn->createQueryBuilder();
        $QueryBuilder->update($table);

        foreach ($fields as $field => $value) {
            $named_param = $QueryBuilder->createNamedParameter($value);
            $QueryBuilder->set($field, $named_param);
            $QueryBuilder->setParameter($named_param, $value);
        }

        $is_first = true;
        foreach ($params as $field => $value) {
            $named_param = $QueryBuilder->createNamedParameter($value);
            if ($is_first) {
                $QueryBuilder->where($field . ' = ' . $named_param);
                $is_first = false;
            } else {
                $QueryBuilder->andWhere($field . ' = ' . $named_param);
            }
            $QueryBuilder->setParameter($named_param, $value);
        }

        $affected_rows = $QueryBuilder->execute();

        return $affected_rows >= 0;
    }
}
