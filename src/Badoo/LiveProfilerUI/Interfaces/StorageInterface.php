<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Interfaces;

interface StorageInterface
{
    public function getAll(string $table, array $fields, array $params) : array;
    public function getOne(string $table, array $fields, array $params) : array;
    public function update(string $table, array $fields, array $params) : bool;
    public function delete(string $table, array $params) : bool;
    public function insert(string $table, array $fields) : int;
    public function insertMany(string $table, array $fields) : bool;
    public function getType() : string;
    public function multiQuery(string $sql) : bool;
}
