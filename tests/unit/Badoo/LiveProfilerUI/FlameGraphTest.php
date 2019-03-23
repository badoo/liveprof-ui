<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class FlameGraphTest extends \unit\Badoo\BaseTestCase
{
    public function testGetSVGEmptyInput()
    {
        $result = \Badoo\LiveProfilerUI\FlameGraph::getSVG('');
        self::assertEquals('', $result);
    }

    public function testBuildFlameGraphInputNestedLevel()
    {
        $elements = [];
        $parents_param = [];
        $root = [];
        $param = 'wt';
        $threshold = 0;

        $result = \Badoo\LiveProfilerUI\FlameGraph::buildFlameGraphInput(
            $elements,
            $parents_param,
            $root,
            $param,
            $threshold,
            51
        );
        self::assertEquals('', $result);
    }

    public function providerBuildFlameGraphInput() : array
    {
        return [
            [
                'elements' => [
                    ['method_id' => 1, 'parent_id' => 0, 'method_name' => 'main()', 'wt' => 7],
                ],
                'expected' => "main() 7\n",
            ],
            [
                'elements' => [
                    ['method_id' => 2, 'parent_id' => 1, 'method_name' => 'f', 'wt' => 1],
                    ['method_id' => 1, 'parent_id' => 0, 'method_name' => 'main()', 'wt' => 2],
                ],
                'expected' => "main();f 1\nmain() 6\n",
            ],
            [
                'elements' => [
                    ['method_id' => 5, 'parent_id' => 4, 'method_name' => 'c2', 'wt' => 2],
                    ['method_id' => 4, 'parent_id' => 3, 'method_name' => 'c1', 'wt' => 2],
                    ['method_id' => 4, 'parent_id' => 2, 'method_name' => 'c1', 'wt' => 2],
                    ['method_id' => 3, 'parent_id' => 1, 'method_name' => 'p1', 'wt' => 3],
                    ['method_id' => 2, 'parent_id' => 1, 'method_name' => 'p2', 'wt' => 3],
                ],
                'expected' => "main();p1;c1;c2 1\nmain();p1;c1 1\nmain();p1 1\nmain();p2;c1;c2 1\nmain();p2;c1 1\nmain();p2 1\nmain() 1\n",
            ],
        ];
    }

    /**
     * @dataProvider providerBuildFlameGraphInput
     * @param array $elements
     * @param string $expected
     * @throws \ReflectionException
     */
    public function testBuildFlameGraphInput($elements, $expected)
    {
        foreach ($elements as &$element) {
            $element = new \Badoo\LiveProfilerUI\Entity\MethodTree($element, ['wt' => 'wt']);
        }
        unset($element);

        /** @var \Badoo\LiveProfilerUI\Pages\FlameGraphPage $PageMock */
        $PageMock = $this->getMockBuilder(\Badoo\LiveProfilerUI\Pages\FlameGraphPage::class)
            ->disableOriginalConstructor()
            ->setMethods(['__construct'])
            ->getMock();

        $root_method_data = ['method_id' => 1, 'name' => 'main()', 'wt' => 7];
        $param = 'wt';
        $threshold = 0;
        $parents_param = $this->invokeMethod($PageMock, 'getAllMethodParentsParam', [$elements, $param]);
        $result = \Badoo\LiveProfilerUI\FlameGraph::buildFlameGraphInput(
            $elements,
            $parents_param,
            $root_method_data,
            $param,
            $threshold
        );

        self::assertEquals($expected, $result);
    }
}
