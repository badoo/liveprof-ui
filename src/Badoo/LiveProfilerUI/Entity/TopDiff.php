<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

class TopDiff
{
    /** @var string */
    protected $app = '';
    /** @var string */
    protected $label = '';
    /** @var int */
    protected $method_id = 0;
    /** @var string */
    protected $method_name = '';
    /** @var float */
    protected $value;
    /** @var string */
    protected $from_value;
    /** @var string */
    protected $to_value;
    /** @var string */
    protected $formatted_value;
    /** @var float */
    protected $percent;

    public function __construct(array $data)
    {
        $this->app = isset($data['app']) ? trim($data['app']) : '';
        $this->label = isset($data['label']) ? trim($data['label']) : '';
        $this->method_id = isset($data['method_id']) ? (int)$data['method_id'] : 0;
        $this->value = isset($data['value']) ? (float)$data['value'] : 0.0;
        $this->from_value = isset($data['from_value']) ? number_format((float)$data['from_value']) : '';
        $this->to_value = isset($data['to_value']) ? number_format((float)$data['to_value']) : '';
        $this->formatted_value = number_format($this->value);
        $this->percent = isset($data['percent']) ? (float)$data['percent'] : 0.0;
    }

    public function getApp() : string
    {
        return $this->app;
    }

    public function setApp(string $app) : self
    {
        $this->app = $app;
        return $this;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function setLabel(string $label) : self
    {
        $this->label = $label;
        return $this;
    }

    public function getMethodId() : int
    {
        return $this->method_id;
    }

    public function setMethodId(int $method_id) : self
    {
        $this->method_id = $method_id;
        return $this;
    }

    public function getValue() : float
    {
        return $this->value;
    }

    public function getFormattedValue() : string
    {
        return $this->formatted_value;
    }

    public function setValue(float $value) : self
    {
        $this->value = $value;
        $this->formatted_value = number_format($this->value);
        return $this;
    }

    public function getFromValue() : string
    {
        return $this->from_value;
    }

    public function setFromValue(float $value) : self
    {
        $this->from_value = number_format($value);
        return $this;
    }

    public function getToValue() : string
    {
        return $this->to_value;
    }

    public function setToValue(float $value) : self
    {
        $this->to_value = number_format($value);
        return $this;
    }
    
    public function getPercent() : float
    {
        return $this->percent;
    }

    public function setPercent(float $percent) : self
    {
        $this->percent = $percent;
        return $this;
    }

    public function getMethodName() : string
    {
        return $this->method_name;
    }

    public function setMethodName(string $method_name) : self
    {
        $this->method_name = $method_name;
        return $this;
    }
}
