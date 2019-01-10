<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Interfaces;

interface ViewInterface
{
    /**
     * @param string $template_name
     * @param array $data
     * @param bool $use_layout_locally
     * @return string
     */
    public function fetchFile(string $template_name, array $data, bool $use_layout_locally = true);
}
