<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

class MethodTree extends MethodData
{
    /** @var int */
    protected $parent_id = 0;

    public function __construct(array $data, array $fields)
    {
        parent::__construct($data, $fields);
        $this->parent_id = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
    }

    public function getParentId() : int
    {
        return $this->parent_id;
    }

    public function setParentId(int $parent_id) : self
    {
        $this->parent_id = $parent_id;
        return $this;
    }
}
