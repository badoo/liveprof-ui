<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

class MethodData extends BaseEntity implements \JsonSerializable
{
    const MAX_NAME_LENGTH = 50;

    /** @var int */
    protected $snapshot_id = 0;
    /** @var int */
    protected $method_id = 0;
    /** @var string */
    protected $method_name = '';
    /** @var string */
    protected $method_name_alt = '';
    /** @var array */
    protected $values = [];
    /** @var array */
    protected $formatted_values = [];
    /** @var array */
    protected $history_data = [];

    public function __construct(array $data, array $fields)
    {
        $this->snapshot_id = isset($data['snapshot_id']) ? (int)$data['snapshot_id'] : 0;
        $this->method_id = isset($data['method_id']) ? (int)$data['method_id'] : 0;
        $this->setMethodName(isset($data['method_name']) ? trim($data['method_name']) : '');
        $this->setValues($this->prepareValues($data, $fields));
    }

    public function getSnapshotId() : int
    {
        return $this->snapshot_id;
    }

    public function getMethodId() : int
    {
        return $this->method_id;
    }

    public function getValues() : array
    {
        return $this->values;
    }

    public function getValue(string $param) : float
    {
        return $this->values[$param] ?? 0;
    }

    public function getFormattedValue(string $param) : string
    {
        return $this->formatted_values[$param] ?? '-';
    }

    public function getFormattedValues() : array
    {
        return $this->formatted_values;
    }

    public function getMethodName() : string
    {
        return $this->method_name;
    }

    public function getMethodNameAlt() : string
    {
        return $this->method_name_alt;
    }

    public function getHistoryData() : array
    {
        return $this->history_data;
    }

    public function setMethodId(int $method_id) : self
    {
        $this->method_id = $method_id;
        return $this;
    }

    public function setValue(string $param, float $value) : self
    {
        $this->values[$param] = $value;
        $this->formatted_values = $this->prepareFormattedValues($this->values);
        return $this;
    }

    public function setValues(array $values) : self
    {
        $this->values = $values;
        $this->formatted_values = $this->prepareFormattedValues($values);
        return $this;
    }

    public function setMethodName(string $method_name) : self
    {
        $this->method_name_alt = $method_name;
        if (\strlen($method_name) > self::MAX_NAME_LENGTH) {
            $method_name = substr(
                $method_name,
                -self::MAX_NAME_LENGTH,
                self::MAX_NAME_LENGTH
            );
        }
        $this->method_name = $method_name;

        return $this;
    }

    public function setHistoryData(array $history_data) : self
    {
        $this->history_data = $history_data;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() : array
    {
        return get_object_vars($this);
    }
}
