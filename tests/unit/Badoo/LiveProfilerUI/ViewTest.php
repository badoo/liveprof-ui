<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class ViewTest extends \unit\Badoo\BaseTestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Template file not found
     */
    public function testFetchFileWrongTemplate()
    {
        $View = new \Badoo\LiveProfilerUI\View();
        $View->fetchFile('wrong_template', []);
    }

    public function providerFetchFile()
    {
        return [
            [
                'use_layout' => false,
                'expected' => "<div class='alert alert-danger' role='alert'>test error</div>\n"
            ],
            [
                'use_layout' => true,
                'expected' => '<!doctype html>'
            ]
        ];
    }

    /**
     * @dataProvider providerFetchFile
     * @param $use_layout
     * @param $expected
     * @throws \Exception
     */
    public function testFetchFile($use_layout, $expected)
    {
        $View = new \Badoo\LiveProfilerUI\View($use_layout);
        $result = $View->fetchFile('error', ['error' => 'test error']);

        self::assertContains($expected, $result);
    }
}
