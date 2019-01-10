<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Interfaces;

interface DataPackerInterface
{
    public function pack(array $data): string;
    public function unpack(string $data): array;
}
