<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

class FieldList
{
    /** @var string[] */
    private $fields = [];
    /** @var string[] */
    private $variations;
    /** @var string[] */
    private $descriptions;

    public function __construct(array $fields, array $variations, array $descriptions)
    {
        foreach ($fields as $field) {
            if (\is_array($field)) {
                foreach ($field as $profiler_field => $aggregator_field) {
                    $this->fields[$profiler_field] = $aggregator_field;
                }
            } else {
                $this->fields[$field] = $field;
            }
        }
        $this->variations = $variations;
        $this->descriptions = $descriptions;
    }

    /**
     * @return string[]
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getFieldVariations() : array
    {
        return $this->variations;
    }

    /**
     * @return string[]
     */
    public function getFieldDescriptions() : array
    {
        return $this->descriptions;
    }

    /**
     * @return string[]
     */
    public function getAllFieldsWithVariations() : array
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $field;
            foreach ($this->variations as $variation) {
                $result[$variation . '_' . $field] = $variation . '_' . $field;
            }
        }

        return $result;
    }
}
