<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

use Badoo\LiveProfilerUI\Interfaces\FieldHandlerInterface;

class FieldHandler implements FieldHandlerInterface
{
    /** @var float */
    protected $percentile = 0.95;

    /**
     * @param string $function
     * @param array $data
     * @return null|float
     */
    public function handle(string $function, array $data)
    {
        if (!$data) {
            return null;
        }

        if (method_exists($this, $function)) {
            return $this->$function($data);
        }

        return null;
    }

    protected function min(array $data) : float
    {
        return (float)min($data);
    }

    protected function max(array $data) : float
    {
        return (float)max($data);
    }

    protected function avg(array $data) : float
    {
        return array_sum($data)/\count($data);
    }

    /**
     * @param array $data
     * @return null|float
     */
    protected function percent(array $data)
    {
        $count = \count($data);
        if ($count < 50) {
            return null;
        }

        sort($data);
        $index = $this->percentile * $count;
        $index_rounded = floor($index);
        if ($index_rounded === $index) {
            $index = (int)$index;
            $percent = ($data[$index - 1] + $data[$index]) / 2;
        } else {
            $percent = $data[(int)$index_rounded];
        }
        return $percent;
    }
}
