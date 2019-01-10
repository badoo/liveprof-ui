<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

abstract class BasePage
{
    /** @var string */
    protected static $template_path;
    /** @var array */
    protected $data = [];
    /** @var ViewInterface */
    protected $View;

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    abstract public function getTemplateData() : array;
    abstract protected function cleanData() : bool;

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data) : self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        try {
            $this->cleanData();
            return $this->View->fetchFile(static::$template_path, $this->getTemplateData());
        } catch (\Throwable $Ex) {
            return $this->View->fetchFile('error', ['error' => $Ex->getMessage()]);
        }
    }
}
