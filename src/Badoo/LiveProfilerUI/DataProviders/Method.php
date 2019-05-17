<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;

class Method extends Base implements MethodInterface
{
    const MAX_METHODS_BY_NAME = 20;
    const TABLE_NAME = 'aggregator_metods';

    public function findByName(string $method_name, bool $strict = false) : array
    {
        if (!$method_name) {
            return [];
        }

        $name_filter = ['name', $method_name];
        if (!$strict) {
            $name_filter[] = 'like';
        }

        $result = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [$name_filter],
                'limit' => self::MAX_METHODS_BY_NAME
            ]
        );
        $methods = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $methods[$row['id']] = $row;
            }
        }

        return $methods;
    }

    public function getListByNames(array $names) : array
    {
        if (empty($names)) {
            return [];
        }

        $result = $this->AggregatorStorage->getAll(
            self::TABLE_NAME,
            ['all'],
            [
                'filter' => [
                    ['name', $names]
                ]
            ]
        );

        return $result;
    }

    public function getListByIds(array $ids) : array
    {
        if (empty($ids)) {
            return [];
        }

        $methods = [];
        while (!empty($ids)) {
            $ids_to_het = \array_slice($ids, 0, 500);
            $ids = \array_slice($ids, 500);

            $result = $this->AggregatorStorage->getAll(
                self::TABLE_NAME,
                ['all'],
                [
                    'filter' => [
                        ['id', $ids_to_het]
                    ]
                ]
            );

            if (!empty($result)) {
                foreach ($result as $method) {
                    $methods[$method['id']] = $method['name'];
                }
            }
        }

        return $methods;
    }

    public function insertMany(array $inserts) : bool
    {
        if (empty($inserts)) {
            return false;
        }

        return $this->AggregatorStorage->insertMany(self::TABLE_NAME, $inserts);
    }

    public function injectMethodNames(array $data) : array
    {
        $method_ids = [];
        foreach ($data as $Item) {
            $method_ids[$Item->getMethodId()] = $Item->getMethodId();
        }

        $methods = $this->getListByIds($method_ids);

        if (!empty($methods)) {
            foreach ($data as $key => $Item) {
                $Item->setMethodName(
                    isset($methods[$Item->getMethodId()])
                        ? trim($methods[$Item->getMethodId()])
                        : '?'
                );
                $data[$key] = $Item;
            }
        }

        return $data;
    }

    public function setLastUsedDate(array $ids, string $date) : bool
    {
        if (empty($ids)) {
            return false;
        }

        return $this->AggregatorStorage->update(
            self::TABLE_NAME,
            ['date' => $date],
            ['id' => $ids]
        );
    }
}
