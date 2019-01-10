<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

class BaseEntity
{
    protected function prepareValues(array $data, array $fields) : array
    {
        $values = [];
        foreach ($data as $field => $value) {
            if (!empty($fields[$field])) {
                $values[$field] = \is_string($value) ? (float)$value : $value;
            }
        }
        return $values;
    }

    protected function prepareFormattedValues(array $values) : array
    {
        $formatted_values = [];
        foreach ($values as $key => $value) {
            $formatted_values[$key] = is_numeric($value)
                ? str_replace('.000', '', number_format((float)$value, 3))
                : '-';
        }
        return $formatted_values;
    }
}
