<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Interfaces;

interface FieldHandlerInterface
{
    /**
     * @param string $function Name of aggregating function
     * @param array $data array of values
     * @return float|null
     */
    public function handle(string $function, array $data);
}
