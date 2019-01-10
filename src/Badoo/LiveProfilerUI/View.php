<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

use Badoo\LiveProfilerUI\Exceptions\FileNotFoundException;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class View implements ViewInterface
{
    /** @var bool */
    protected $use_layout;

    /**
     * @param bool $use_layout flag to include layout in template
     */
    public function __construct(bool $use_layout = true)
    {
        $this->use_layout = $use_layout;
    }

    /**
     * @param string $template_name
     * @param array $data template data
     * @param bool $use_layout_locally if true - render template without layout for local usage
     * @return string
     * @throws FileNotFoundException
     */
    public function fetchFile(string $template_name, array $data, bool $use_layout_locally = true)
    {
        $template_filename = __DIR__ . '/../../templates/' . $template_name . '.php';
        if (!file_exists($template_filename)) {
            throw new FileNotFoundException('Template file not found: ' . $template_filename);
        }

        ob_start();
        include $template_filename;
        $content = ob_get_clean();

        if (!$this->use_layout || !$use_layout_locally) {
            return $content;
        }

        ob_start();
        $layout_filename = __DIR__ . '/../../templates/layout.php';
        include $layout_filename;

        return ob_get_clean();
    }
}
