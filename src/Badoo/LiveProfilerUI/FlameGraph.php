<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

class FlameGraph
{
    /**
     * Get svg data for flame graph
     * @param string $graph_input
     * @return string
     */
    public static function getSVG(string $graph_input) : string
    {
        if (!$graph_input) {
            return '';
        }

        $tmp_file = tempnam(__DIR__, 'flamefile');
        file_put_contents($tmp_file, $graph_input);
        exec('perl ' . __DIR__ . '/../../../scripts/flamegraph.pl ' . $tmp_file, $output);
        unlink($tmp_file);

        return implode("\n", $output);
    }

    /**
     * @param \Badoo\LiveProfilerUI\Entity\MethodTree[] $elements
     * @param array $parents_param
     * @param array $parent
     * @param string $param
     * @param float $threshold
     * @param int $level
     * @return string
     */
    public static function buildFlameGraphInput(
        array $elements,
        array $parents_param,
        array $parent,
        string $param,
        float $threshold,
        int $level = 0
    ) : string {
        if ($level > 50) {
            // limit nesting level
            return '';
        }

        $texts = '';
        foreach ($elements as $Element) {
            if ($Element->getParentId() === $parent['method_id']) {
                $element_value = $Element->getValue($param);
                $value = $parent[$param] - $element_value;

                if (($value <= 0) && !empty($parents_param[$Element->getParentId()])) {
                    $p = $parents_param[$Element->getParentId()];
                    $sum_p = array_sum($p);
                    $element_value = 0;
                    if ($sum_p !== 0) {
                        $element_value = ($parent[$param] / $sum_p) * $Element->getValue($param);
                    }
                    $value = $parent[$param] - $element_value;
                }

                if ($element_value < $threshold) {
                    continue;
                }

                $new_parent = [
                    'method_id' => $Element->getMethodId(),
                    'name' => $parent['name'] . ';' . $Element->getMethodNameAlt(),
                    $param => $element_value
                ];
                $texts .= self::buildFlameGraphInput(
                    $elements,
                    $parents_param,
                    $new_parent,
                    $param,
                    $threshold,
                    $level + 1
                );
                $parent[$param] = $value;
            }
        }

        $texts .= $parent['name'] . ' ' . $parent[$param] . "\n";

        return $texts;
    }
}
