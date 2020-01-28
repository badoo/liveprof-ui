<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders\Interfaces;

interface MethodInterface
{
    public function findByName(string $method_name, bool $strict = false) : array;
    public function all() : array;
    public function getListByNames(array $names) : array;
    public function getListByIds(array $ids) : array;
    public function insertMany(array $inserts) : bool;
    public function injectMethodNames(array $records) : array;
    public function setLastUsedDate(array $ids, string $date) : bool;
}
