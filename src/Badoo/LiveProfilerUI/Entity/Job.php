<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Entity;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\JobInterface;

class Job
{
    /** @var int */
    protected $id = 0;
    /** @var string */
    protected $app = '';
    /** @var string */
    protected $label = '';
    /** @var string */
    protected $date = '';
    /** @var string */
    protected $type = '';
    /** @var string */
    protected $status = '';

    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->app = isset($data['app']) ? trim($data['app']) : '';
        $this->label = isset($data['label']) ? trim($data['label']) : '';
        $this->date = isset($data['date']) ? trim($data['date']) : '';
        $this->type = isset($data['type']) ? trim($data['type']) : JobInterface::TYPE_AUTO;
        $this->status = isset($data['status']) ? trim($data['status']) : JobInterface::STATUS_NEW;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function setApp(string $app) : self
    {
        $this->app = $app;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label) : self
    {
        $this->label = $label;
        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date) : self
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type) : self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status) : self
    {
        $this->status = $status;
        return $this;
    }
}
