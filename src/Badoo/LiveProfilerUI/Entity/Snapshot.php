<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

class Snapshot extends BaseEntity
{
    /** @var int */
    protected $id = 0;
    /** @var int */
    protected $calls_count = 0;
    /** @var string */
    protected $app = '';
    /** @var string */
    protected $label = '';
    /** @var string */
    protected $date = '';
    /** @var string */
    protected $type = '';
    /** @var bool */
    protected $is_auto_method = false;
    /** @var array */
    protected $values = [];
    /** @var array */
    protected $formatted_values = [];

    public function __construct(array $data, array $fields)
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->calls_count = isset($data['calls_count']) ? (int)$data['calls_count'] : 0;
        $this->app = isset($data['app']) ? trim($data['app']) : '';
        $this->label = isset($data['label']) ? trim($data['label']) : '';
        $this->date = isset($data['date']) ? trim($data['date']) : '';
        $this->type = isset($data['type']) ? trim($data['type']) : '';
        $this->is_auto_method = !empty($data['is_auto_method']);

        $this->setValues($this->prepareValues($data, $fields));
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getApp() : string
    {
        return $this->app;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getDate() : string
    {
        return $this->date;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setValues(array $values) : self
    {
        $this->values = $values;
        $this->formatted_values = $this->prepareFormattedValues($values);
        return $this;
    }

    public function getValues() : array
    {
        return $this->values;
    }

    public function getFormattedValues() : array
    {
        return $this->formatted_values;
    }

    public function getCallsCount() : int
    {
        return $this->calls_count;
    }
}
